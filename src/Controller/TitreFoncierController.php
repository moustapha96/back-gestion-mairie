<?php

namespace App\Controller;

use App\Entity\TitreFoncier;
use App\Repository\LocaliteRepository;
use App\services\TitreFoncierService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;

use Symfony\Component\HttpFoundation\Request as HttpRequest;
#[Route('/api/titres')]
class TitreFoncierController extends AbstractController
{
    public function __construct(
        private TitreFoncierService $service,
        private string $fileBaseUrl,                 // ex: https://cdn.mairie.sn
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

        if (empty($data['type'])) {
            return $this->json(['message' => 'Le type de titre est obligatoire'], Response::HTTP_BAD_REQUEST);
        }

        $titre = new TitreFoncier();
        $titre
            ->setType($data['type'])
            ->setNumero($data['numero'] ?? null)
            ->setSuperficie(isset($data['superficie']) ? (float) $data['superficie'] : null)
            ->setEtatDroitReel($data['etatDroitReel'] ?? null);

        if (!empty($data['quartierId'])) {
            $quartier = $quartierRepository->find($data['quartierId']);
            if (!$quartier) {
                return $this->json(['message' => 'Quartier non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $titre->setQuartier($quartier);
        }

        // Gestion du fichier
        $uploaded = $req->files->get('fichier');
        if ($uploaded instanceof UploadedFile) {
            // Dossier de stockage
            $dirFs = $this->getParameter('kernel.project_dir') . "/public/uploads/titres/{$titre->getId()}";
            $dirWeb = "/uploads/titres/{$titre->getId()}";

            // Création du dossier
            if (!is_dir($dirFs)) {
                @mkdir($dirFs, 0775, true);
            }

            // Nom du fichier
            $ext = strtolower($uploaded->guessExtension() ?: $uploaded->getClientOriginalExtension() ?: 'pdf');
            $fileBase = sprintf(
                '%s-%s-%s',
                ($data['type'] ?? 'titre'),
                date('YmdHis'),
                bin2hex(random_bytes(4))
            );

            // Déplacement du fichier
            $uploaded->move($dirFs, "{$fileBase}.{$ext}");
            $titre->setFichier("{$dirWeb}/{$fileBase}.{$ext}");
        } elseif (!empty($data['fichierUrl'])) {
            $titre->setFichier($this->toWebPath($data['fichierUrl']));
        } elseif (!empty($data['fichier'])) {
            $titre->setFichier($this->toWebPath($data['fichier']));
        }

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


    #[Route('/{id}', name: 'titres_update', methods: ['POST'])]
    public function updateTitre(Request $req, TitreFoncier $titre, LocaliteRepository $quartierRepository): Response
    {
        $isJson = str_starts_with($req->headers->get('Content-Type'), 'application/json');
        $data = $isJson ? json_decode($req->getContent(), true) : $req->request->all();

        // Gestion du fichier
        $uploaded = $req->files->get('fichier');
        if ($uploaded instanceof UploadedFile) {
            $dirFs = $this->getParameter('kernel.project_dir') . "/public/uploads/titres/{$titre->getId()}";
            $dirWeb = "/uploads/titres/{$titre->getId()}";

            if (!is_dir($dirFs)) {
                @mkdir($dirFs, 0775, true);
            }

            $ext = strtolower($uploaded->guessExtension() ?: $uploaded->getClientOriginalExtension() ?: 'pdf');
            $fileBase = sprintf(
                '%s-%s-%s',
                ($data['type'] ?? 'titre'),
                date('YmdHis'),
                bin2hex(random_bytes(4))
            );

            $uploaded->move($dirFs, "{$fileBase}.{$ext}");
            $titre->setFichier("{$dirWeb}/{$fileBase}.{$ext}");
        } elseif (!empty($data['fichierUrl'])) {
            $titre->setFichier($this->toWebPath($data['fichierUrl']));
        } elseif (!empty($data['fichier'])) {
            $titre->setFichier($this->toWebPath($data['fichier']));
        }

        // Mise à jour des autres champs
        if (isset($data['type']))
            $titre->setType($data['type']);
        if (isset($data['numero']))
            $titre->setNumero($data['numero']);
        if (isset($data['superficie']))
            $titre->setSuperficie($data['superficie'] !== '' ? (float) $data['superficie'] : null);
        if (isset($data['etatDroitReel']))
            $titre->setEtatDroitReel($data['etatDroitReel']);
        if (!empty($data['quartierId'])) {
            $quartier = $quartierRepository->find($data['quartierId']);
            if (!$quartier) {
                return $this->json(['message' => 'Quartier non trouvé'], Response::HTTP_NOT_FOUND);
            }
            $titre->setQuartier($quartier);
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'item' => $this->serializeItem($titre),
            'message' => 'Titre mis à jour avec succès'
        ]);
    }

    // Helper pour convertir un chemin/URL en chemin web
    private function toWebPath(string $input): string
    {
        $u = trim($input);
        if (empty($u))
            return $u;

        if (str_starts_with($u, 'http')) {
            $path = parse_url($u, PHP_URL_PATH);
            return $path ?: $u;
        }

        if ($u[0] !== '/') {
            $u = '/' . ltrim($u, '/');
        }

        if (!str_starts_with($u, '/tfs/')) {
            $u = '/tfs' . $u;
        }

        return $u;
    }


    #[Route('/{id}', name: 'titres_delete', methods: ['DELETE'])]
    public function delete(TitreFoncier $titre): Response
    {
        $this->em->remove($titre);
        $this->em->flush();
        return $this->json(['success' => true], Response::HTTP_NO_CONTENT);
    }

    /* ============================================================
     * Helpers
     * ============================================================ */

    /**
     * Enregistre un fichier sous /public/tfs/titres et renvoie le *web path* relatif (ex: /tfs/titres/xxx.pdf).
     * L'URL absolue sera obtenue via toAbsoluteUrl().
     */
    private function saveFileAndReturnWebPath(UploadedFile $file, string $prefix = 'titre'): string
    {
        $uploadDir = $this->getParameter('app.upload.titres_dir'); // ex: .../public/tfs/titres
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }

        $sanitize = fn(string $s) => (string) (new AsciiSlugger())->slug($s)->lower();
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'pdf');

        $filename = sprintf(
            '%s-%s.%s',
            $sanitize($prefix ?: 'titre'),
            bin2hex(random_bytes(6)),
            $ext
        );

        $file->move($uploadDir, $filename);

        // web path relatif (servi par le webserver)
        return '/tfs/titres/' . $filename;
    }

    /**
     * Convertit un input (URL absolue, URL externe, chemin relatif) en *web path* relatif sous /tfs/titres.
     * - Si absolu sur ton CDN et path commence par /tfs/titres => retourne ce path
     * - Si relatif => s'assure qu'il commence par /tfs/titres
     * - Sinon (URL externe hors répertoire attendu) => on la laisse telle quelle en *absolu* (mais on retourne quand même un path utilisable).
     */
    private function toWebPathFromAny(string $input): string
    {
        $u = trim($input);
        if ($u === '')
            return $u;

        $cdn = rtrim($this->fileBaseUrl, '/'); // https://cdn...
        if (str_starts_with($u, needle: $cdn . '/')) {
            $path = parse_url($u, PHP_URL_PATH) ?: '';
            if ($path && str_starts_with($path, '/tfs/titres/')) {
                return $path; // ok chemin relatif propre
            }
            // URL externe sur le même host mais autre path : on renvoie tel quel (absolu)
            return $u;
        }

        if (preg_match('#^https?://#i', $u)) {
            $path = parse_url($u, PHP_URL_PATH) ?: '';
            if ($path && str_starts_with($path, '/tfs/titres/')) {
                return $path;
            }
            return $u; // URL absolue hors répertoire prévu -> restera absolue en sortie API
        }

        // relatif
        if ($u[0] !== '/')
            $u = '/' . ltrim($u, '/');
        if (!str_starts_with($u, '/tfs/titres/'))
            $u = '/tfs/titres' . $u;
        return $u;
    }

    /** Transforme un *web path* relatif (/tfs/titres/xxx.pdf) en URL absolue CDN. */
    private function toAbsoluteUrl(?string $webPath): ?string
    {
        if (!$webPath)
            return null;
        if (preg_match('#^https?://#i', $webPath))
            return $webPath; // déjà absolu
        return rtrim($this->fileBaseUrl, '/') . $webPath;
    }

    /**
     * Sérialise un TitreFoncier :
     * - `fichier` => on renvoie ce qui est stocké (souvent relatif)
     * - `fichierUrl` => URL absolue pour ouvrir directement côté front
     */
    private function serializeItem(TitreFoncier $t): array
    {
        $fileStored = $t->getFichier();             // ex: /tfs/titres/xxx.pdf  OU  URL absolue si data externe
        $fileAbs = $this->toAbsoluteUrl($fileStored);

        return [
            'id' => $t->getId(),
            'numero' => $t->getNumero(),
            'superficie' => $t->getSuperficie(),
            'titreFigure' => $t->getTitreFigure(),
            'etatDroitReel' => $t->getEtatDroitReel(),
            'type' => $t->getType(),
            'fichier' => $fileStored,
            'fichierUrl' => $fileAbs,
            'quartier' => $t->getQuartier() ? [
                'id' => $t->getQuartier()->getId(),
                'nom' => method_exists($t->getQuartier(), 'getNom') ? $t->getQuartier()->getNom() : null,
            ] : null,
        ];
    }
}
