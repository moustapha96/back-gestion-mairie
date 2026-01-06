<?php
declare(strict_types=1);

// src/Controller/DemandeImportController.php
namespace App\Controller;

use App\Entity\User;
use App\Entity\Request as DemandeTerrain;
use App\Repository\UserRepository;
use App\Repository\LocaliteRepository;
use App\services\FonctionsService;
use App\services\Spreadsheet\ImportChunkReadFilter;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DemandeImportController extends AbstractController
{
    #[Route('/api/demande/import', name: 'api_demande_import', methods: ['POST'])]
    public function importerDemandes(
        Request $request,
        FonctionsService $fonctionsService,
        LocaliteRepository $localiteRepository,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'Fichier non trouvé'], Response::HTTP_BAD_REQUEST);
        }
        if (\strtolower((string)$file->getClientOriginalExtension()) !== 'xlsx') {
            return $this->json(['error' => 'Format de fichier incorrect. Seuls les fichiers XLSX sont acceptés.'], Response::HTTP_BAD_REQUEST);
        }

        // Entêtes attendus (exactement ceux du template)
        $expectedHeaders = [
            'CNI',
            'Email',
            'Nom',
            'Prenom',
            'Telephone',
            'Adresse',
            'Lieu de Naissance',
            'Date de Naissance',
            'Profession',
            'Situation Matrimoniale',
            'Nombre Enfant',
            'Type de demande',
            'Localite',
            'Superficie',
            'Usage prevu',
            'Possède autre terrain',
            'Type de titre',
            'Terrain à Kaolack',
            'Terrain ailleurs',
            'Date Demande',
        ];

        // 20 colonnes de A à T
        $columns   = \range('A', 'T');
        $chunkSize = 500; // lignes par paquet
        $batchSize = 50;  // flush Doctrine tous les X enregistrements

        $rowCount         = 0;
        $importedUsers    = 0;
        $importedDemandes = 0;
        $errors           = [];

        // Helpers
        $normalize = static function (string $s): string {
            $s = \trim($s);
            return \preg_replace('/\s+/', ' ', $s);
        };
        $toBool = static function (mixed $v): bool {
            $v = \strtolower((string) $v);
            return $v === 'oui' || $v === '1' || $v === 'true';
        };
        $parseExcelDate = static function (mixed $value): ?\DateTimeInterface {
            // Accepte DateTime direct, string parsable, ou date Excel (numérique)
            if ($value instanceof \DateTimeInterface) {
                return $value;
            }
            if ($value === null || $value === '') {
                return null;
            }
            if (\is_numeric($value)) {
                // Excel base 1900
                $unix = (int) \round(((float) $value - 25569) * 86400);
                return (new \DateTimeImmutable('@' . $unix))
                    ->setTimezone(new \DateTimeZone(\date_default_timezone_get()));
            }
            try {
                return new \DateTimeImmutable((string) $value);
            } catch (\Throwable) {
                return null;
            }
        };

        try {
            /** @var Xlsx $reader */
            $reader = IOFactory::createReader('Xlsx');
            $reader->setReadDataOnly(true);
            if (\method_exists($reader, 'setReadEmptyCells')) {
                $reader->setReadEmptyCells(false);
            }

            // 1) Lecture des entêtes uniquement
            $headerFilter = new ImportChunkReadFilter(1, 1, $columns);
            $reader->setReadFilter($headerFilter);

            $spreadsheet = $reader->load($file->getPathname());
            $sheet       = $spreadsheet->getActiveSheet();

            $headerRow = [];
            foreach ($columns as $col) {
                $val = $sheet->getCell($col.'1')->getValue();
                $headerRow[] = \is_null($val) ? '' : \trim((string) $val);
            }

            $normalizedHeader   = \array_map($normalize, $headerRow);
            $normalizedExpected = \array_map($normalize, $expectedHeaders);

            if ($normalizedHeader !== $normalizedExpected) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
                return $this->json([
                    'error'   => 'Format de fichier incorrect. Les colonnes ne correspondent pas.',
                    'attendu' => $expectedHeaders,
                    'recu'    => $headerRow,
                ], Response::HTTP_BAD_REQUEST);
            }

            // Libère mémoire du premier chargement
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            // 2) Lecture par chunks à partir de la ligne 2
            $startRow = 2;
            $reader->setReadFilter(new ImportChunkReadFilter($startRow, $chunkSize, $columns));

            while (true) {
                /** @var ImportChunkReadFilter $filter */
                $filter = $reader->getReadFilter();
                if (!$filter instanceof ImportChunkReadFilter) {
                    $filter = new ImportChunkReadFilter($startRow, $chunkSize, $columns);
                    $reader->setReadFilter($filter);
                } else {
                    $filter->setRows($startRow, $chunkSize);
                }

                $spreadsheet = $reader->load($file->getPathname());
                $sheet       = $spreadsheet->getActiveSheet();

                $emptyChunk = true;

                for ($row = $startRow; $row < $startRow + $chunkSize; $row++) {
                    $rowValues = [];
                    $allEmpty  = true;

                    foreach ($columns as $col) {
                        $cellVal = $sheet->getCell($col.$row)->getValue();
                        if (!\is_null($cellVal) && $cellVal !== '') $allEmpty = false;
                        $rowValues[] = \is_null($cellVal) ? '' : (\is_string($cellVal) ? \trim($cellVal) : $cellVal);
                    }

                    if ($allEmpty) {
                        continue; // ignore ligne vide
                    }
                    $emptyChunk = false;

                    if (\count($rowValues) < \count($expectedHeaders)) {
                        $errors[] = "Ligne $row : Ligne incomplète, ignorée.";
                        continue;
                    }

                    [
                        $cni,
                        $email,
                        $nom,
                        $prenom,
                        $telephone,
                        $adresse,
                        $lieuNaissance,
                        $dateNaissanceRaw,
                        $profession,
                        $situationMatrimoniale,
                        $nombreEnfant,
                        $typeDemande,
                        $localiteNom,
                        $superficie,
                        $usagePrevu,
                        $possedeAutreTerrainStr,
                        $typeTitre,
                        $terrainAKaolackStr,
                        $terrainAilleursStr,
                        $dateDemandeRaw
                    ] = $rowValues;

                    // ===== VALIDATIONS =====
                    if (empty($cni) || empty($nom) || empty($localiteNom) || empty($dateDemandeRaw)) {
                        $errors[] = "Ligne $row : Données obligatoires manquantes (CNI, Nom, Localité, Date Demande).";
                        continue;
                    }

                    if (!empty($email) && !\filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Ligne $row : Email invalide : $email.";
                        continue;
                    }

                    $dateNaissance = $parseExcelDate($dateNaissanceRaw);
                    $dateDemande   = $parseExcelDate($dateDemandeRaw);
                    if (!$dateDemande) {
                        $errors[] = "Ligne $row : Date Demande invalide.";
                        continue;
                    }

                    $possedeAutreTerrain = $toBool($possedeAutreTerrainStr);
                    $terrainAKaolack     = $toBool($terrainAKaolackStr);
                    $terrainAilleurs     = $toBool($terrainAilleursStr);

                    // ===== Requêtes =====
                    $localite = $localiteRepository->findOneBy(['nom' => (string)$localiteNom]);
                    if (!$localite) {
                        $errors[] = "Ligne $row : Localité non trouvée : $localiteNom.";
                        continue;
                    }

                    // Identifiant "maître" : CNI/numeroElecteur (plus stable que l'email)
                    $utilisateur = $userRepository->findOneBy(['numeroElecteur' => (string)$cni])
                        ?? (!empty($email) ? $userRepository->findOneBy(['email' => (string)$email]) : null);

                    if (!$utilisateur) {
                        $utilisateur = new User();
                        $utilisateur
                            ->setNom((string) $nom)
                            ->setPrenom((string) $prenom)
                            ->setEmail((string) $email)
                            ->setAdresse((string) $adresse)
                            ->setTelephone((string) $telephone)
                            ->setProfession((string) $profession)
                            ->setNumeroElecteur((string) $cni)
                            ->setLieuNaissance((string) $lieuNaissance)
                            ->setDateNaissance($dateNaissance ?: null)
                            ->setSituationMatrimoniale((string) $situationMatrimoniale)
                            ->setNombreEnfant(!empty($nombreEnfant) ? (int) $nombreEnfant : null)
                            ->setEnabled(true)
                            ->setActiveted(false) // si c’est bien ton champ
                            ->setRoles(User::ROLE_DEMANDEUR) // adapte si besoin
                            ->setUsername((string) ($email ?: $cni));

                        // Génère & hash mot de passe (si ta classe User a bien ces setters/méthodes)
                        $passwordGenere = method_exists($utilisateur, 'generatePassword')
                            ? $utilisateur->generatePassword(8)
                            : bin2hex(random_bytes(4));

                        if (method_exists($utilisateur, 'setPasswordClaire')) {
                            $utilisateur->setPasswordClaire($passwordGenere);
                        }
                        $utilisateur->setPassword(password_hash($passwordGenere, PASSWORD_BCRYPT));

                        if (method_exists($utilisateur, 'setTokenActiveted')) {
                            $utilisateur->setTokenActiveted(bin2hex(random_bytes(32)));
                        }

                        // Vérifie si c’est un habitant
                        try {
                            $resultat = $fonctionsService->checkNumeroElecteurExist((string) $cni);
                            $utilisateur->setHabitant($resultat ?? false);
                        } catch (\Throwable) {
                            // ne bloque pas l’import si le service faillit
                        }

                        $em->persist($utilisateur);
                        $importedUsers++;
                    } else {
                        // Mise à jour légère
                        $utilisateur
                            ->setNom((string) $nom)
                            ->setPrenom((string) $prenom)
                            ->setAdresse((string) $adresse)
                            ->setTelephone((string) $telephone)
                            ->setProfession((string) $profession)
                            ->setLieuNaissance((string) $lieuNaissance)
                            ->setDateNaissance($dateNaissance ?: null)
                            ->setSituationMatrimoniale((string) $situationMatrimoniale);

                        if (!empty($nombreEnfant)) {
                            $utilisateur->setNombreEnfant((int) $nombreEnfant);
                        }
                    }

                    // ===== Demande terrain =====
                    $demande = new DemandeTerrain();
                    $demande
                        ->setLocalite($localite ?? null)
                        ->setSuperficie((float) $superficie)
                        ->setTypeDemande((string) $typeDemande)
                        ->setUsagePrevu((string) $usagePrevu)
                        ->setDateCreation($dateDemande)
                        ->setDateModification($dateDemande)
                        ->setStatut(DemandeTerrain::STATUT_EN_ATTENTE)
                        ->setPossedeAutreTerrain($possedeAutreTerrain)
                        ->setTypeDocument('CNI')
                        ->setTypeTitre((string) $typeTitre)
                        ->setTerrainAKaolack($terrainAKaolack)
                        ->setTerrainAilleurs($terrainAilleurs)
                        ->setUtilisateur($utilisateur);

                    $em->persist($demande);
                    $importedDemandes++;
                    $rowCount++;

                    if (($rowCount % $batchSize) === 0) {
                        $em->flush();
                        // Attention: si tu fais clear(), il faut re-récupérer Localite & User aux lignes suivantes.
                        $em->clear();
                    }
                }

                // Libération mémoire du chunk
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                if ($emptyChunk) {
                    break; // fin de fichier
                }

                $startRow += $chunkSize;
            }

            // Flush final
            $em->flush();
            $em->clear();

            return $this->json([
                'message'               => 'Importation terminée avec succès.',
                'total_lignes'          => $rowCount,
                'utilisateurs_importes' => $importedUsers,
                'demandes_importees'    => $importedDemandes,
                'erreurs'               => $errors,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur critique lors de l\'importation : '.$e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
