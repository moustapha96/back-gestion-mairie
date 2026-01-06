<?php

namespace App\services;

use App\Entity\AttributionParcelle;
use App\Entity\Request;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MimeTypes;

class AttributionMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromEmail,              // param app.mail_sender
        private string $frontBaseUrl = '',
        private string $fromName = 'Mairie de Kaolack' // optionnel
    ) {
    }

    public function notifyStatusChange(AttributionParcelle $ap, string $event, array $extras = []): void
    {
        // Récup destinataire principal (demandeur/utilisateur)
        $demande = $ap->getDemande();
        $user = $demande?->getUtilisateur();
        $to = $user?->getEmail() ?: $demande?->getEmail() ?: null;

        if (!$to) {
            // rien à envoyer si on n'a pas d’email (tu peux lever une exception si tu veux rendre ça obligatoire)
            return;
        }

        // URL vers la page détail (front)
        $detailUrl = rtrim($this->frontBaseUrl, '/') . '/auth/sign-in';

        // Données "confort" pour le template
        $parcelle = $ap->getParcelle();
        $lot = $parcelle?->getLotissement();
        $loc = $lot?->getLocalite();

        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($to)
            ->subject($this->buildSubject($event, $ap))
            ->htmlTemplate('mail_request/attribution_status.html.twig') // template générique (voir plus bas)
            // App/Service/AttributionMailer.php (extrait)
            ->context([
                'event' => $event,
                'ap' => $ap,
                'demande' => $demande,
                'user' => $user,
                'parcelle' => $parcelle,
                'lotissement' => $lot,
                'localite' => $loc,
                'detailUrl' => $detailUrl,

                // Extras (PV, décision, date…)
                'pv' => $extras['pv'] ?? null,
                'decisionConseil' => $extras['decisionConseil'] ?? $ap->getDecisionConseil(),
                'date' => $extras['date'] ?? null,

                // Ajouts pour faciliter Twig
                'statutValue' => $ap->getStatutAttribution()->value,
                'prenomDisplay' => ($user && $user->getPrenom()) ? $user->getPrenom() : (($demande && $demande->getPrenom()) ? $demande->getPrenom() : null),
                'nomDisplay' => ($user && $user->getNom()) ? $user->getNom() : (($demande && $demande->getNom()) ? $demande->getNom() : null),
                'emailDisplay' => ($user && $user->getEmail()) ? $user->getEmail() : (($demande && $demande->getEmail()) ? $demande->getEmail() : null),
            ]);


        $this->mailer->send($email);
    }


    public function notifyDemandeCreation(Request $demande, array $extras = []): void
    {
        // Destinataire (utilisateur rattaché > fallback champs libres sur Demande)
        $user = $demande?->getUtilisateur();
        $to = $user?->getEmail() ?: $demande?->getEmail() ?: null;
        if (!$to) {
            return;
        }

        // URL (front) vers l’espace de suivi / authent
        $detailUrl = rtrim($this->frontBaseUrl, '/') . '/auth/sign-in';

        // Helpers pour fabriquer des URLs publiques
        $publicUrl = function (?string $path): ?string {
            if (!$path)
                return null;
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                return $path;
            }
            return rtrim($this->backBaseUrl ?? '', '/') . $path;
        };

        $rectoUrl = $publicUrl($demande->getRecto());
        $versoUrl = $publicUrl($demande->getVerso());

        // Sujet
        $subject = $this->buildDemandeSubject('CREATED', $demande);

        // Localité / quartier
        $quartier = $demande->getQuartier();
        $localite = $quartier?->getNom() ?? null;

        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($to)
            ->subject($subject)
            ->htmlTemplate('mail_request/nouveau_demande.html.twig')   // même logique que attribution_status
            ->context([
                'event' => 'CREATED',
                'demande' => $demande,
                'user' => $user,
                'quartier' => $quartier,
                'localite' => $localite,
                'detailUrl' => $detailUrl,

                // Extras éventuels
                'date' => $extras['date'] ?? null,

                // Docs
                'rectoUrl' => $rectoUrl,
                'versoUrl' => $versoUrl,

                // Facilités d’affichage (comme dans ton code attribution)
                'prenomDisplay' => ($user && $user->getPrenom()) ? $user->getPrenom() : ($demande->getPrenom() ?: null),
                'nomDisplay' => ($user && $user->getNom()) ? $user->getNom() : ($demande->getNom() ?: null),
                'emailDisplay' => ($user && $user->getEmail()) ? $user->getEmail() : ($demande->getEmail() ?: null),

                // Infos demande utiles dans le template
                'statut' => $demande->getStatut(),
                'typeDemande' => $demande->getTypeDemande(),
                'typeTitre' => $demande->getTypeTitre(),
                'typeDocument' => $demande->getTypeDocument(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'localiteText' => $demande->getLocalite() ?: ($quartier?->getNom() ?: null),
                'createdAt' => $demande->getDateCreation(),
            ]);

        $this->mailer->send($email);
    }



    public function notifyDemandeCreationToNewUser(Request $demande, array $extras = []): void
    {
        $user = $demande->getUtilisateur();
        $to = $user?->getEmail() ?: $demande->getEmail() ?: null;
        if (!$to) {
            return; // pas d'email -> on sort sans lever d’exception
        }

        // Lien de connexion (page front d’auth / tableau de bord)
        $loginUrl = rtrim($this->frontBaseUrl, '/') . '/auth/sign-in';
        // si tu as une page “mes demandes” une fois connecté :
        $mesDemandesUrl = rtrim($this->frontBaseUrl, '/') . '/demandeur/demandes';

        // Contexte additionnel (fourni par le contrôleur)
        $isNewUser = (bool) ($extras['isNewUser'] ?? false);
        $rawPassword = $extras['rawPassword'] ?? null;         // mot de passe en clair (si nouveau compte)
        $activation = $extras['activationToken'] ?? null;     // token d’activation si tu envoies un lien dédié
        $activationUrl = $activation
            ? rtrim($this->frontBaseUrl, '/') . '/activate?token=' . urlencode((string) $activation)
            : null;

        $email = (new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($to)
            ->subject(sprintf('Votre demande #%d a été enregistrée', $demande->getId()))
            ->htmlTemplate('mail_request/demande_creation_new_user.html.twig')
            ->context([
                // Données principales
                'demande' => $demande,
                'user' => $user,
                'loginUrl' => $loginUrl,
                'mesDemandesUrl' => $mesDemandesUrl,

                // Identité affichable
                'prenomDisplay' => $user?->getPrenom() ?: $demande->getPrenom(),
                'nomDisplay' => $user?->getNom() ?: $demande->getNom(),
                'emailDisplay' => $user?->getEmail() ?: $demande->getEmail(),

                // Bloc “Nouveau compte”
                'isNewUser' => $isNewUser,
                'rawPassword' => $rawPassword,     // null si compte existant
                'activationUrl' => $activationUrl,   // null si non requis

                // Récapitulatif demande
                'typeDemande' => $demande->getTypeDemande(),
                'typeDocument' => $demande->getTypeDocument(),
                'typeTitre' => $demande->getTypeTitre(),
                'superficie' => $demande->getSuperficie(),
                'usagePrevu' => $demande->getUsagePrevu(),
                'localite' => $demande->getLocalite(),
                'statut' => $demande->getStatut(),
                'dateCreation' => $demande->getDateCreation(),
            ]);

        $this->mailer->send($email);
    }

    private function buildSubject(string $event, AttributionParcelle $ap): string
    {
        return match ($event) {
            'VALIDATION_PROVISOIRE' => 'Attribution – Validation provisoire',
            'ATTRIBUTION_PROVISOIRE' => 'Attribution – Attribution provisoire',
            'APPROBATION_PREFET' => 'Attribution – Approbation du Préfet',
            'APPROBATION_CONSEIL' => 'Attribution – Approbation du Conseil',
            'ATTRIBUTION_DEFINITIVE' => 'Attribution – Attribution définitive',
            'REOUVERTURE' => 'Attribution – Réouverture du dossier',
            default => 'Attribution – Mise à jour',
        };
    }

    private function buildDemandeSubject(string $event, Request $demande): string
    {
        return match ($event) {
            'CREATED' => sprintf('Votre demande #%d a été enregistrée', $demande->getId()),
            'STATUS_CHANGED' => sprintf('Mise à jour du statut de votre demande #%d', $demande->getId()),
            default => sprintf('Notification demande #%d', $demande->getId()),
        };
    }




    public function notifyStatusChangeAttributionDefinitive(AttributionParcelle $ap, string $event, array $extras = []): void
    {
        // Destinataire (utilisateur lié > fallback champs libres sur Demande)
        $demande = $ap->getDemande();
        $user = $demande?->getUtilisateur();
        $to = $user?->getEmail() ?: $demande?->getEmail() ?: null;
        if (!$to)
            return;

        // URL front (tableau de bord / authent)
        $detailUrl = rtrim($this->frontBaseUrl, '/') . '/auth/sign-in';

        // Données confort pour Twig
        $parcelle = $ap->getParcelle();
        $lot = $parcelle?->getLotissement();
        $loc = $lot?->getLocalite();

        // Contexte attendu par ton template Twig
        $context = [
            'event' => $event,
            'ap' => $ap,
            'demande' => $demande,
            'user' => $user,
            'parcelle' => $parcelle,
            'lotissement' => $lot,
            'localite' => $loc,
            'detailUrl' => $detailUrl,

            // Extras (PV, décision, date…)
            'pv' => $extras['pv'] ?? null,
            'decisionConseil' => $extras['decisionConseil'] ?? $ap->getDecisionConseil(),
            'date' => $extras['date'] ?? null,

            // Aides d’affichage
            'statutValue' => $ap->getStatutAttribution()->value,
            'prenomDisplay' => ($user && $user->getPrenom()) ? $user->getPrenom()
                : (($demande && $demande->getPrenom()) ? $demande->getPrenom() : null),
            'nomDisplay' => ($user && $user->getNom()) ? $user->getNom()
                : (($demande && $demande->getNom()) ? $demande->getNom() : null),
            'emailDisplay' => ($user && $user->getEmail()) ? $user->getEmail()
                : (($demande && $demande->getEmail()) ? $demande->getEmail() : null),
        ];

        // Pièces jointes (optionnelles) depuis ton service de génération
        $attachments = array_filter([
            $extras['pdfPath'] ?? null,
            $extras['docxPath'] ?? null,
        ]);

        $this->sendWithAttachments(
            to: $to,
            subject: $this->buildSubject($event, $ap),
            html: null,
            paths: [$extras['pdfPath'] ?? null, $extras['docxPath'] ?? null],
            template: 'mail_request/notification_attribution_parcelle.html.html.twig',
            context: $context,
            opts: ['reply_to' => $this->fromEmail]
        );
    }




    public function notifyStatusChangeAttributionDefinitiveLiquidation(AttributionParcelle $ap, string $event, array $extras = []): void
    {
        // Destinataire (utilisateur lié > fallback champs libres sur Demande)
        $demande = $ap->getDemande();
        $user = $demande?->getUtilisateur();
        $to = $user?->getEmail() ?: $demande?->getEmail() ?: null;
        if (!$to)
            return;

        // URL front (tableau de bord / authent)
        $detailUrl = rtrim($this->frontBaseUrl, '/') . '/auth/sign-in';

        // Données confort pour Twig
        $parcelle = $ap->getParcelle();
        $lot = $parcelle?->getLotissement();
        $loc = $lot?->getLocalite();

        // Contexte attendu par ton template Twig
        $context = [
            'event' => $event,
            'ap' => $ap,
            'demande' => $demande,
            'user' => $user,
            'parcelle' => $parcelle,
            'lotissement' => $lot,
            'localite' => $loc,
            'detailUrl' => $detailUrl,

            // Extras (PV, décision, date…)
            'pv' => $extras['pv'] ?? null,
            'decisionConseil' => $extras['decisionConseil'] ?? $ap->getDecisionConseil(),
            'date' => $extras['date'] ?? null,

            // Aides d’affichage
            'statutValue' => $ap->getStatutAttribution()->value,
            'prenomDisplay' => ($user && $user->getPrenom()) ? $user->getPrenom()
                : (($demande && $demande->getPrenom()) ? $demande->getPrenom() : null),
            'nomDisplay' => ($user && $user->getNom()) ? $user->getNom()
                : (($demande && $demande->getNom()) ? $demande->getNom() : null),
            'emailDisplay' => ($user && $user->getEmail()) ? $user->getEmail()
                : (($demande && $demande->getEmail()) ? $demande->getEmail() : null),
        ];

        // Pièces jointes (optionnelles) depuis ton service de génération
        $attachments = array_filter([
            $extras['pdfPath'] ?? null,
            $extras['docxPath'] ?? null,
        ]);

        $this->sendWithAttachments(
            to: $to,
            subject: $this->buildSubject($event, $ap),
            html: null,
            paths: [$extras['pdfPath'] ?? null, $extras['docxPath'] ?? null],
            template: 'mail_request/notification_attribution_parcelle.html.html.twig',
            context: $context,
            opts: ['reply_to' => $this->fromEmail]
        );

    }


    public function sendWithAttachments(
        string|array $to,
        string $subject,
        ?string $html,
        array $paths,
        ?string $template = null,
        array $context = [],
        array $opts = []
    ): void {
        // Adresse expéditeur lisible
        $from = isset($this->fromName)
            ? new Address($this->fromEmail, $this->fromName)
            : new Address($this->fromEmail);

        $email = (new TemplatedEmail())
            ->from($from)
            ->to(...(is_array($to) ? $to : [$to]))
            ->subject($subject);

        // Corps: template Twig OU HTML direct
        if ($template) {
            $email->htmlTemplate($template)->context($context);
        } else {
            $email->html($html ?? '');
        }

        // Options (cc, bcc, reply-to, headers...)
        if (!empty($opts['cc']))
            $email->cc(...(is_array($opts['cc']) ? $opts['cc'] : [$opts['cc']]));
        if (!empty($opts['bcc']))
            $email->bcc(...(is_array($opts['bcc']) ? $opts['bcc'] : [$opts['bcc']]));
        if (!empty($opts['reply_to']))
            $email->replyTo(...(is_array($opts['reply_to']) ? $opts['reply_to'] : [$opts['reply_to']]));
        if (!empty($opts['headers']) && is_array($opts['headers'])) {
            foreach ($opts['headers'] as $name => $value) {
                $email->getHeaders()->addTextHeader($name, (string) $value);
            }
        }

        // Pièces jointes (avec détection MIME)
        $mimeGuesser = MimeTypes::getDefault();
        foreach (array_filter($paths) as $p) {
            if (!is_string($p) || !is_file($p))
                continue;
            $mime = $mimeGuesser->guessMimeType($p) ?? 'application/octet-stream';
            $email->attachFromPath($p, basename($p), $mime);
        }

        $this->mailer->send($email);
    }


}
