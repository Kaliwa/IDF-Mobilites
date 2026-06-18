<?php

namespace App\DataFixtures;

use App\Entity\Abonnement;
use App\Entity\Contract;
use App\Entity\Journey;
use App\Entity\LineSubscription;
use App\Entity\Payeur;
use App\Entity\Payment;
use App\Entity\SubscriptionDossier;
use App\Entity\SupportAccountRequest;
use App\Entity\SupportConversation;
use App\Entity\SupportMessage;
use App\Entity\TransitLine;
use App\Entity\User;
use App\Entity\UserNotification;
use App\Enum\LienBeneficiaire;
use App\Enum\MoyenPaiement;
use App\Enum\Periodicite;
use App\Enum\StatutAbonnement;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $lines = $this->createLines($manager);

        $support = $this->createUser($manager, 'support@comutitres.fr', 'Motdepasse12', ['ROLE_SUPPORT']);
        $jean    = $this->createUser($manager, 'jean.dupont@demo.comutitres.fr',    'Demo1234!', ['ROLE_USER']);
        $marie   = $this->createUser($manager, 'marie.martin@demo.comutitres.fr',   'Demo1234!', ['ROLE_USER']);
        $lucas   = $this->createUser($manager, 'lucas.bernard@demo.comutitres.fr',  'Demo1234!', ['ROLE_USER']);
        $sophie  = $this->createUser($manager, 'sophie.leblanc@demo.comutitres.fr', 'Demo1234!', ['ROLE_USER']);
        $thomas  = $this->createUser($manager, 'thomas.petit@demo.comutitres.fr',   'Demo1234!', ['ROLE_USER']);
        $emma    = $this->createUser($manager, 'emma.dubois@demo.comutitres.fr',    'Demo1234!', ['ROLE_USER']);
        $hugo    = $this->createUser($manager, 'hugo.moreau@demo.comutitres.fr',    'Demo1234!', ['ROLE_USER']);
        $alice   = $this->createUser($manager, 'alice.roux@demo.comutitres.fr',     'Demo1234!', ['ROLE_USER']);
        $antoine = $this->createUser($manager, 'antoine.blanc@demo.comutitres.fr',  'Demo1234!', ['ROLE_USER']);

        // Line subscriptions
        $this->createSubscription($manager, $jean,    $lines['A'],   true);
        $this->createSubscription($manager, $jean,    $lines['B'],   true);
        $this->createSubscription($manager, $marie,   $lines['14'],  true);
        $this->createSubscription($manager, $marie,   $lines['1'],   false);
        $this->createSubscription($manager, $lucas,   $lines['T3a'], true);
        $this->createSubscription($manager, $sophie,  $lines['B'],   true);
        $this->createSubscription($manager, $thomas,  $lines['1'],   true);
        $this->createSubscription($manager, $emma,    $lines['A'],   true);
        $this->createSubscription($manager, $hugo,    $lines['14'],  true);
        $this->createSubscription($manager, $alice,   $lines['T1'],  true);
        $this->createSubscription($manager, $alice,   $lines['A'],   false);
        $this->createSubscription($manager, $antoine, $lines['A'],   true);

        // Payeur tiers pour Sophie
        $payeurLeblanc = $this->createPayeur($manager, 'Frédéric', 'Leblanc', 'frederic.leblanc@email.fr',
            LienBeneficiaire::PARENT, MoyenPaiement::PRELEVEMENT);

        // Contracts + dossiers
        $this->createContract($manager, $jean, $lines['A'], 'active', '-45 days');
        $this->createDossier($manager, $jean, 'subscription_request', 'carte_identite', 0.92, [], 'pending', '-2 days', null,
            ['lastName' => 'DUPONT', 'firstName' => 'Jean', 'birthDate' => '1992-03-15', 'nationality' => 'Française', 'documentNumber' => 'F12345678', 'expiryDate' => '2030-03-14']);

        $this->createContract($manager, $marie, $lines['14'], 'suspended', '-60 days',
            suspensionReason: 'Justificatif de domicile non conforme — adresse non vérifiée.');
        $this->createDossier($manager, $marie, 'renewal', 'justificatif_domicile', 0.55, ['address_mismatch', 'low_quality'], 'rejected', '-20 days', $support,
            ['lastName' => 'MARTIN', 'firstName' => 'Marie', 'address' => '23 rue Lecourbe', 'city' => 'Paris', 'postalCode' => '75015', 'issuer' => 'EDF', 'documentDate' => '2026-04-01'],
            "L'adresse extraite (23 rue Lecourbe, 75015) ne correspond pas à l'adresse enregistrée. Document flou. Resoumission requise.");

        $this->createContract($manager, $lucas, $lines['T3a'], 'active', '-30 days');
        $this->createDossier($manager, $lucas, 'subscription_request', 'certificat_scolarite', 0.88, [], 'pending', '-1 days', null,
            ['lastName' => 'BERNARD', 'firstName' => 'Lucas', 'school' => 'Université Paris-Saclay', 'program' => 'Licence Informatique', 'year' => '2025-2026', 'studentId' => 'P198234762']);

        $this->createContract($manager, $sophie, $lines['B'], 'active', '-90 days', payeur: $payeurLeblanc);
        $this->createDossier($manager, $sophie, 'payer_change', 'rib', 0.97, [], 'approved', '-10 days', $support,
            ['holderName' => 'LEBLANC Frédéric', 'iban' => 'FR76 3000 6000 0112 3456 7890 189', 'bic' => 'BNPAFRPPXXX', 'bankName' => 'BNP Paribas']);

        $this->createContract($manager, $thomas, $lines['1'], 'cancelled', '-75 days');
        $this->createDossier($manager, $thomas, 'subscription_request', 'carte_identite', 0.22, ['expired_document', 'low_quality'], 'rejected', '-70 days', $support,
            ['lastName' => 'PETIT', 'firstName' => 'Thomas', 'birthDate' => '2000-11-22', 'nationality' => 'Française', 'documentNumber' => 'G98765432', 'expiryDate' => '2024-08-01'],
            "Carte d'identité expirée depuis 2024-08-01. Document très flou, données illisibles. Demande annulée.");

        $this->createContract($manager, $emma, $lines['A'], 'active', '-15 days');
        $this->createDossier($manager, $emma, 'renewal', 'justificatif_domicile', 0.71, [], 'pending', '-3 days', null,
            ['lastName' => 'DUBOIS', 'firstName' => 'Emma', 'address' => '4 avenue des Gobelins', 'city' => 'Paris', 'postalCode' => '75013', 'issuer' => 'Bouygues Telecom', 'documentDate' => '2026-05-15']);

        $this->createContract($manager, $hugo, $lines['14'], 'pending', '-5 days');
        $this->createDossier($manager, $hugo, 'subscription_request', 'carte_identite', 0.43, ['minor_detected'], 'pending', '-5 days', null,
            ['lastName' => 'MOREAU', 'firstName' => 'Hugo', 'birthDate' => '2010-07-08', 'nationality' => 'Française', 'documentNumber' => 'H11223344', 'expiryDate' => '2028-07-07']);

        $this->createContract($manager, $alice, $lines['T1'], 'active', '-120 days');
        $this->createDossier($manager, $alice, 'subscription_request', 'certificat_scolarite', 0.66, ['name_mismatch'], 'pending', '-4 days', null,
            ['lastName' => 'ROUX-LAMBERT', 'firstName' => 'Alice', 'school' => 'Sciences Po Paris', 'program' => 'Master Relations Internationales', 'year' => '2025-2026', 'studentId' => 'SP987654']);
        $this->createDossier($manager, $alice, 'renewal', 'justificatif_domicile', 0.89, [], 'approved', '-100 days', $support,
            ['lastName' => 'ROUX', 'firstName' => 'Alice', 'address' => '7 rue Saint-Guillaume', 'city' => 'Paris', 'postalCode' => '75007', 'issuer' => 'ENGIE', 'documentDate' => '2025-11-01']);

        $this->createContract($manager, $antoine, $lines['A'], 'active', '-8 days');
        $this->createDossier($manager, $antoine, 'subscription_request', 'rib', 0.94, [], 'pending', '-8 days', null,
            ['holderName' => 'BLANC Antoine', 'iban' => 'FR76 1027 8060 0000 1234 5678 912', 'bic' => 'CMCIFR2A', 'bankName' => 'Crédit Mutuel']);

        // Abonnements & payments
        $this->createAbonnementAndPayments($manager, $jean, null, [
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 45],
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 15],
        ]);
        $this->createAbonnementAndPayments($manager, $marie, null, [
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 50],
            ['amount' => '86.40', 'status' => 'failed', 'daysAgo' => 18],
            ['amount' => '86.40', 'status' => 'failed', 'daysAgo' => 11],
        ]);
        $this->createAbonnementAndPayments($manager, $sophie, $payeurLeblanc, [
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 90],
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 60],
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 30],
        ]);
        $this->createAbonnementAndPayments($manager, $alice, null, [
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 120],
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 90],
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 60],
            ['amount' => '86.40', 'status' => 'paid', 'daysAgo' => 30],
        ]);

        // Journeys
        $this->createJourney($manager, $jean, 'Domicile → Travail',
            'Châtelet-Les Halles', 48.8598, 2.3471, 'La Défense Grande Arche', 48.8918, 2.2381, ['STIF:Line::C01742:']);
        $this->createJourney($manager, $marie, 'Maison → Bureau',
            'Gare de Lyon', 48.8448, 2.3737, 'Châtelet', 48.8598, 2.3471, ['STIF:Line::C01384:']);
        $this->createJourney($manager, $lucas, 'Fac → Maison',
            'Massy-Palaiseau', 48.7253, 2.2527, 'Antony', 48.7530, 2.2977, ['STIF:Line::C01895:']);
        $this->createJourney($manager, $sophie, 'Maison → Travail',
            'Roissy CDG Terminal 2', 49.0097, 2.5479, 'Gare du Nord', 48.8809, 2.3553, ['STIF:Line::C01743:']);
        $this->createJourney($manager, $emma, 'Domicile → École',
            'Place d\'Italie', 48.8308, 2.3553, 'Nation', 48.8484, 2.3960, ['STIF:Line::C01742:']);
        $this->createJourney($manager, $alice, 'Sciences Po → Chez moi',
            'Saint-Germain-des-Prés', 48.8540, 2.3337, 'Châtelet', 48.8598, 2.3471, ['STIF:Line::C01140:']);
        $this->createJourney($manager, $antoine, 'Travail → Gym',
            'La Défense', 48.8918, 2.2381, 'Vincennes', 48.8483, 2.4391, ['STIF:Line::C01742:']);

        // Support conversations
        $conv1 = $this->createConversation($manager, $jean,  'Problème de paiement - Navigo mensuel', 'open', '-5 days');
        $this->addMessage($manager, $conv1, 'user',    'Bonjour, mon paiement de janvier a été prélevé deux fois sur mon compte.', '-5 days');
        $this->addMessage($manager, $conv1, 'support', 'Bonjour Jean, je regarde votre dossier. Pouvez-vous confirmer le montant et la date exacte du double prélèvement ?', '-4 days');
        $this->addMessage($manager, $conv1, 'user',    'Oui, le 15 janvier, deux prélèvements de 86,40 € chacun.', '-4 days');

        $conv2 = $this->createConversation($manager, $marie, 'Justificatif de domicile refusé', 'resolved', '-25 days');
        $this->addMessage($manager, $conv2, 'user',    'Mon justificatif de domicile a été refusé, je ne comprends pas pourquoi.', '-25 days');
        $this->addMessage($manager, $conv2, 'support', "Bonjour Marie, l'adresse sur le document ne correspond pas à celle enregistrée. Merci de resoumettre un document plus récent.", '-24 days');
        $this->addMessage($manager, $conv2, 'user',    "D'accord, je vais en renvoyer un. Merci.", '-23 days');

        $conv3 = $this->createConversation($manager, $alice, 'Demande de changement de forfait', 'open', '-2 days');
        $this->addMessage($manager, $conv3, 'user',    'Bonjour, je souhaite passer du forfait Mois au forfait Annuel. Est-ce possible en cours d\'abonnement ?', '-2 days');
        $this->addMessage($manager, $conv3, 'support', "Bonjour Alice, oui c'est possible. Nous proratiserons le montant restant du mois en cours. Je prépare la modification.", '-1 days');

        // User notifications
        $this->createNotification($manager, $jean,    $lines['A'],   'Perturbation RER A ce soir',
            'Des travaux sont prévus sur la ligne A entre 21h et 5h. Des trains directs circulent toutes les 20 minutes.',
            'incident', 'high', false, '-1 days', 'Voir les horaires alternatifs');
        $this->createNotification($manager, $jean,    null,           'Renouvellement dans 15 jours',
            'Votre abonnement expire le 2 juillet. Pensez à le renouveler pour ne pas interrompre votre abonnement.',
            'renewal', 'medium', true, '-3 days', 'Renouveler maintenant');
        $this->createNotification($manager, $marie,   $lines['14'],  'Trafic perturbé — Métro 14',
            'Suite à un incident voyageur, le trafic est interrompu entre Olympiades et Gare de Lyon.',
            'incident', 'high', false, '-2 hours');
        $this->createNotification($manager, $marie,   null,           'Échec de paiement',
            "Votre prélèvement de 86,40 € du 6 juin n'a pas pu être traité. Mettez à jour vos coordonnées bancaires.",
            'payment', 'high', false, '-11 days', 'Mettre à jour mes coordonnées');
        $this->createNotification($manager, $emma,    $lines['A'],   'Perturbation RER A — Grève',
            'Grève nationale le 5 juin. Le RER A circulera sur service minimum. Prévoir des délais importants.',
            'incident', 'high', true, '-12 days');
        $this->createNotification($manager, $alice,   $lines['T1'],  'Travaux Tram T1 ce week-end',
            'Des travaux de maintenance sont prévus samedi et dimanche. Des bus de remplacement circuleront sur tout le tracé.',
            'incident', 'medium', false, '-3 days');
        $this->createNotification($manager, $alice,   null,           'Renouvellement abonnement',
            'Votre abonnement Navigo Mois arrive à échéance dans 10 jours.',
            'renewal', 'medium', false, '-5 days', 'Renouveler mon abonnement');
        $this->createNotification($manager, $antoine, $lines['A'],   'Retards RER A',
            'Des retards de 15 à 20 minutes sont à prévoir sur le RER A en direction de Cergy-Le Haut / Poissy.',
            'incident', 'low', false, '-6 hours');

        // Pending support account request
        $request = new SupportAccountRequest();
        $request->setEmail('n.martin@comutitres.fr');
        $request->setHashedPassword($this->passwordHasher->hashPassword(new User(), 'Newagent2026!'));
        $manager->persist($request);

        $manager->flush();
    }

    /** @return array<string, TransitLine> */
    private function createLines(ObjectManager $manager): array
    {
        $data = [
            ['code' => 'A',   'name' => 'RER A',    'primRef' => 'STIF:Line::C01742:'],
            ['code' => '14',  'name' => 'Métro 14',  'primRef' => 'STIF:Line::C01384:'],
            ['code' => 'T3a', 'name' => 'Tram T3a',  'primRef' => 'STIF:Line::C01895:'],
            ['code' => 'B',   'name' => 'RER B',     'primRef' => 'STIF:Line::C01743:'],
            ['code' => '1',   'name' => 'Métro 1',   'primRef' => 'STIF:Line::C01140:'],
            ['code' => 'T1',  'name' => 'Tram T1',   'primRef' => 'STIF:Line::C01430:'],
            ['code' => 'T3b', 'name' => 'Tram T3b',  'primRef' => 'STIF:Line::C01896:'],
            ['code' => 'D',   'name' => 'RER D',     'primRef' => 'STIF:Line::C01728:'],
        ];

        $lines = [];
        foreach ($data as $item) {
            $line = (new TransitLine())->setCode($item['code'])->setName($item['name'])->setPrimRef($item['primRef']);
            $manager->persist($line);
            $lines[$item['code']] = $line;
        }

        return $lines;
    }

    /** @param list<string> $roles */
    private function createUser(ObjectManager $manager, string $email, string $plainPassword, array $roles): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setRoles($roles)
            ->setCreatedAt(new \DateTimeImmutable());
        $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        $manager->persist($user);

        return $user;
    }

    private function createPayeur(
        ObjectManager $manager,
        string $prenom,
        string $nom,
        string $email,
        LienBeneficiaire $lien,
        MoyenPaiement $moyen,
    ): Payeur {
        $payeur = (new Payeur())
            ->setPrenom($prenom)
            ->setNom($nom)
            ->setEmail($email)
            ->setLienBeneficiaire($lien)
            ->setMoyenPaiement($moyen);
        $manager->persist($payeur);

        return $payeur;
    }

    private function createSubscription(ObjectManager $manager, User $user, TransitLine $line, bool $enabled): void
    {
        $sub = (new LineSubscription())
            ->setUser($user)
            ->setLine($line)
            ->setEnabled($enabled)
            ->setChannels(['inApp'])
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($sub);
    }

    private function createContract(
        ObjectManager $manager,
        User $user,
        TransitLine $line,
        string $status,
        string $createdAgo,
        ?Payeur $payeur = null,
        ?string $suspensionReason = null,
    ): Contract {
        $contract = (new Contract())
            ->setUser($user)
            ->setLine($line)
            ->setStatus($status)
            ->setPayeur($payeur)
            ->setCreatedAt(new \DateTimeImmutable($createdAgo));

        if ('suspended' === $status && null !== $suspensionReason) {
            $contract
                ->setSuspendedAt(new \DateTimeImmutable('-15 days'))
                ->setSuspensionReason($suspensionReason);
        }

        if ('cancelled' === $status) {
            $contract->setCancelledAt(new \DateTimeImmutable('-5 days'));
        }

        $manager->persist($contract);

        return $contract;
    }

    /**
     * @param list<string> $ocrFlags
     * @param array<string, mixed> $ocrData
     */
    private function createDossier(
        ObjectManager $manager,
        User $user,
        string $type,
        string $documentType,
        float $ocrScore,
        array $ocrFlags,
        string $status,
        string $createdAgo,
        ?User $reviewedBy,
        array $ocrData,
        ?string $agentNote = null,
    ): void {
        $dossier = (new SubscriptionDossier())
            ->setUser($user)
            ->setType($type)
            ->setDocumentType($documentType)
            ->setDocumentRef(sprintf('uploads/dossiers/%s_%s.jpg', $documentType, substr(md5(uniqid()), 0, 8)))
            ->setOcrData($ocrData)
            ->setOcrScore($ocrScore)
            ->setOcrFlags($ocrFlags)
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable($createdAgo));

        if (in_array($status, ['approved', 'rejected'], true)) {
            $dossier
                ->setReviewedBy($reviewedBy)
                ->setReviewedAt(new \DateTimeImmutable($createdAgo . ' +4 hours'));
            if (null !== $agentNote) {
                $dossier->setAgentNote($agentNote);
            }
        }

        $manager->persist($dossier);
    }

    /**
     * @param list<array{amount: string, status: string, daysAgo: int}> $payments
     */
    private function createAbonnementAndPayments(ObjectManager $manager, User $user, ?Payeur $payeur, array $payments): void
    {
        $abonnement = (new Abonnement())
            ->setBeneficiaire($user)
            ->setPayeur($payeur)
            ->setTypeOffre('navigo_mois')
            ->setMontant('86.40')
            ->setPeriodicite(Periodicite::MENSUEL)
            ->setStatut(StatutAbonnement::ACTIF)
            ->setDateDebut(new \DateTimeImmutable('-30 days'))
            ->setDateFin(new \DateTimeImmutable('+15 days'));
        $manager->persist($abonnement);

        foreach ($payments as $p) {
            $payment = (new Payment())
                ->setUser($user)
                ->setAbonnement($abonnement)
                ->setAmount($p['amount'])
                ->setStatus($p['status'])
                ->setProcessedAt(new \DateTimeImmutable(sprintf('-%d days', $p['daysAgo'])));
            $manager->persist($payment);
        }
    }

    /** @param list<string>|null $lines */
    private function createJourney(
        ObjectManager $manager,
        User $user,
        string $label,
        string $originName,
        float $originLat,
        float $originLng,
        string $destinationName,
        float $destinationLat,
        float $destinationLng,
        ?array $lines = null,
    ): void {
        $journey = (new Journey())
            ->setUser($user)
            ->setLabel($label)
            ->setOriginName($originName)
            ->setOriginLat($originLat)
            ->setOriginLng($originLng)
            ->setDestinationName($destinationName)
            ->setDestinationLat($destinationLat)
            ->setDestinationLng($destinationLng)
            ->setLines($lines);
        $manager->persist($journey);
    }

    private function createConversation(
        ObjectManager $manager,
        User $user,
        string $subject,
        string $status,
        string $createdAgo,
    ): SupportConversation {
        $conv = (new SupportConversation())
            ->setUser($user)
            ->setSubject($subject)
            ->setStatus($status)
            ->setCreatedAt(new \DateTimeImmutable($createdAgo))
            ->setUpdatedAt(new \DateTimeImmutable($createdAgo));
        $manager->persist($conv);

        return $conv;
    }

    private function addMessage(
        ObjectManager $manager,
        SupportConversation $conv,
        string $author,
        string $content,
        string $sentAgo,
    ): void {
        $msg = (new SupportMessage())
            ->setConversation($conv)
            ->setAuthor($author)
            ->setContent($content)
            ->setSentAt(new \DateTimeImmutable($sentAgo));
        $manager->persist($msg);
    }

    private function createNotification(
        ObjectManager $manager,
        User $user,
        ?TransitLine $line,
        string $title,
        string $body,
        string $category,
        string $priority,
        bool $isRead,
        string $createdAgo,
        ?string $actionLabel = null,
    ): void {
        $notif = (new UserNotification())
            ->setUser($user)
            ->setLine($line)
            ->setTitle($title)
            ->setBody($body)
            ->setCategory($category)
            ->setPriority($priority)
            ->setIsRead($isRead)
            ->setCreatedAt(new \DateTimeImmutable($createdAgo));
        if (null !== $actionLabel) {
            $notif->setActionLabel($actionLabel);
        }
        $manager->persist($notif);
    }
}
