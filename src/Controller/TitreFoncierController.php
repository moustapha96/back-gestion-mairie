<?php

namespace App\Controller;

use App\Entity\TitreFoncier;
use App\Repository\LocaliteRepository;
use App\services\TitreFoncierService;
use Doctrine\ORM\EntityManagerInterface;
use Dom\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile; // <— ajouté
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[Route('/api/titres')]
class TitreFoncierController extends AbstractController
{
    public function __construct(
        private TitreFoncierService $service,
        private string $fileBaseUrl,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('', name: 'titres_list', methods: ['GET'])]
    public function list(Request $req): Response
    {
        $page = max(1, (int) $req->query->get('page', 1));
        $pageSize = min(200, max(1, (int) $req->query->get('pageSize', 10)));
        $sortField = $req->query->get('sortField', 'id');
        $sortOrder = $req->query->get('sortOrder', 'DESC');

        $filters = [
            'numero' => $req->query->get('numero'),
            'quartierId' => $req->query->get('quartierId'),
            'superficieMin' => $req->query->get('superficieMin'),
            'superficieMax' => $req->query->get('superficieMax'),
            'type' => $req->query->get('type'),
        ];

        $res = $this->service->searchPaginated($filters, $page, $pageSize, $sortField, $sortOrder);

        return $this->json([
            'success' => true,
            'items' => array_map(fn($t) => $this->serializeItem($t), $res['items']),
            'total' => $res['total'],
            'page' => $page,
            'pageSize' => $pageSize,
        ]);
    }

    #[Route('', name: 'titres_create', methods: ['POST'])]
    public function createTitre(Request $req, LocaliteRepository $quartierRepository): Response
    {

        $data = $req->request->all();

        // Validation des données obligatoires
        if (empty($data['type'])) {
            return $this->json(['message' => 'Le type de titre est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        // Gestion du fichier (upload ou URL)
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $req->files->get('fichier');
        if ($uploadedFile) {
            $data['fichier'] = $this->storeUploadedFileToTfs($uploadedFile);
        } elseif (!empty($data['fichierUrl'])) {
            $data['fichier'] = $this->normalizeToCdnUrl((string) $data['fichierUrl']);
        } elseif (!empty($data['fichier'])) {
            $data['fichier'] = $this->normalizeToCdnUrl((string) $data['fichier']);
        }

        // Si quartierId est fourni, on charge le quartier
        if (!empty($data['quartierId'])) {
            $quartier = $quartierRepository->find($data['quartierId']);
            if (!$quartier) {
                return $this->json(['message' => 'Quartier non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $data['quartier'] = $quartier;
        }

        // Création du titre
        $titre = new TitreFoncier();
        $titre
            ->setType($data['type'])
            ->setNumero($data['numero'] ?? null)
            ->setSuperficie($data['superficie'] ?? null)
            ->setEtatDroitReel($data['etatDroitReel'] ?? null)
            ->setFichier($data['fichier'] ?? null)
            ->setQuartier($data['quartier'] ?? null);

        $this->em->persist($titre);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'item' => $this->serializeItem($titre),
            'message' => 'Titre créé avec succès'
        ], Response::HTTP_CREATED);
    }




    #[Route('/{id}', name: 'titres_get', methods: ['GET'])]
    public function getOne(TitreFoncier $titre): Response
    {
        return $this->json(['success' => true, 'item' => $this->serializeItem($titre)]);
    }


    #[Route('/{id}', name: 'titres_update', methods: ['PUT', 'PATCH'])]
    public function updateTitre(Request $req, TitreFoncier $titre, LocaliteRepository $quartierRepository): Response
    {
        $isJson = 0 === strpos((string) $req->headers->get('Content-Type'), 'application/json');
        $data = $isJson ? (json_decode($req->getContent(), true) ?? []) : $req->request->all();

        // Gestion du fichier (upload ou URL)
        /** @var UploadedFile|null $uploadedFile */
        $uploadedFile = $req->files->get('fichier');
        if ($uploadedFile) {
            $data['fichier'] = $this->storeUploadedFileToTfs($uploadedFile);
        } elseif (isset($data['fichier']) && is_string($data['fichier'])) {
            $data['fichier'] = $this->normalizeToCdnUrl($data['fichier']);
        }

        // Si quartierId est fourni, on charge le quartier
        if (!empty($data['quartierId'])) {
            $quartier = $quartierRepository->find($data['quartierId']);
            if (!$quartier) {
                return $this->json(['message' => 'Quartier non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $titre->setQuartier($quartier);
        }

        // Mise à jour des champs
        if (array_key_exists('type', $data)) {
            $titre->setType($data['type']);
        }
        if (array_key_exists('numero', $data)) {
            $titre->setNumero($data['numero']);
        }
        if (array_key_exists('superficie', $data)) {
            $titre->setSuperficie($data['superficie']);
        }
        if (array_key_exists('etatDroitReel', $data)) {
            $titre->setEtatDroitReel($data['etatDroitReel']);
        }
       
        if (array_key_exists('fichier', $data)) {
            $titre->setFichier($data['fichier']);
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'item' => $this->serializeItem($titre),
            'message' => 'Titre mis à jour avec succès'
        ]);
    }


    #[Route('/{id}', name: 'titres_delete', methods: ['DELETE'])]
    public function delete(TitreFoncier $titre): Response
    {
        $this->em->remove($titre);
        $this->em->flush();
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }


    /**
     * Sauvegarde le fichier dans /public/tfs/titres et renvoie l'URL ABSOLUE CDN.
     * Exemple: {ASSET_BASE_URL}/tfs/titres/xxx.ext
     */
    private function storeUploadedFileToTfs(UploadedFile $uploadedFile): string
    {
        $rootDir = $this->getParameter('kernel.project_dir');
        $tfsDir = $rootDir . '/public/tfs/titres'; // Dossier dédié aux titres
        if (!is_dir($tfsDir)) {
            @mkdir($tfsDir, 0775, true);
        }
        $original = pathinfo($uploadedFile->getClientOriginalName(), PATHINFO_FILENAME);
        $ext = strtolower($uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin');
        $slug = (new AsciiSlugger())->slug($original)->lower();
        $filename = sprintf('%s-%s.%s', $slug, bin2hex(random_bytes(6)), $ext);
        $uploadedFile->move($tfsDir, $filename);
        return rtrim($this->fileBaseUrl, '/') . '/tfs/titres/' . $filename;
    }


    /**
     * Normalise une entrée en URL CDN pour les titres fonciers:
     * - URL déjà absolue sur le CDN -> inchangée
     * - URL http(s) externe: si path /tfs/titres/... -> bascule vers ton CDN ; sinon on laisse tel quel
     * - Chemin relatif -> préfixe /tfs/titres + base CDN
     */
    private function normalizeToCdnUrl(string $input): string
    {
        $u = trim($input);
        if ($u === '')
            return $u;
        if (str_starts_with($u, rtrim($this->fileBaseUrl, '/') . '/')) {
            return $u;
        }
        if (preg_match('#^https?://#i', $u)) {
            $parts = parse_url($u);
            $path = $parts['path'] ?? '';
            if ($path && str_starts_with($path, '/tfs/titres/')) {
                return rtrim($this->fileBaseUrl, '/') . $path;
            }
            return $u;
        }
        if ($u[0] !== '/')
            $u = '/' . ltrim($u, '/');
        if (!str_starts_with($u, '/tfs/titres/'))
            $u = '/tfs/titres' . $u;
        return rtrim($this->fileBaseUrl, '/') . $u;
    }

    /**
     * Sérialise un TitreFoncier en tableau, avec gestion de l'URL du fichier.
     */
    private function serializeItem(TitreFoncier $t): array
    {
        $file = $t->getFichier();
        if ($file && !preg_match('#^https?://#i', $file)) {
            if ($file[0] !== '/')
                $file = '/' . ltrim($file, '/');
            if (!str_starts_with($file, '/tfs/titres/'))
                $file = '/tfs/titres' . $file;
            $file = rtrim($this->fileBaseUrl, '/') . $file;
        }

        return [
            'id' => $t->getId(),
            'numero' => $t->getNumero(),
            'superficie' => $t->getSuperficie(),
            'titreFigure' => $t->getTitreFigure(),
            'etatDroitReel' => $t->getEtatDroitReel(),
            'type' => $t->getType(),
            'fichier' => $file,
            'quartier' => $t->getQuartier() ? [
                'id' => $t->getQuartier()->getId(),
                'nom' => method_exists($t->getQuartier(), 'getNom') ? $t->getQuartier()->getNom() : null,
            ] : null,
        ];
    }

}
