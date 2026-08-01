<?php

namespace App\Command;

use App\Entity\Cours;
use App\Entity\Danseur;
use App\Entity\Foyer;
use App\Entity\Inscription;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:seed-test-family',
    description: 'Crée un jeu de données complet : 2 familles (dont une membre du bureau), 3 danseurs, 2 cours et 3 inscriptions.',
)]
class SeedTestFamilyCommand extends Command
{
    private const STANDARD_PARENT_EMAIL = 'parent.test@studio430.fr';
    private const BUREAU_PARENT_EMAIL = 'bureau.parent@studio430.fr';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $userRepository = $this->entityManager->getRepository(User::class);
        if (null !== $userRepository->findOneBy(['email' => self::STANDARD_PARENT_EMAIL])) {
            $io->error('Des données de test existent déjà. Vide ta base de données avant de relancer le seed.');
            return Command::FAILURE;
        }

        // ==========================================
        // 1. CRÉATION DES COURS
        // ==========================================
        $eveil = new Cours();
        $eveil->setNom('Éveil Danse');
        $eveil->setJour('Mercredi');
        $eveil->setHeure(\DateTime::createFromFormat('!H:i', '14:00'));
        $eveil->setProfesseur('Marie Dupont');
        $eveil->setCapaciteMax(15);
        $eveil->setDureeMinutes(60);
        $eveil->setTarif(164);
        $eveil->setAnneeNaissanceMin(2008);
        $eveil->setAnneeNaissanceMax(2022);
        $this->entityManager->persist($eveil);

        $modernJazz = new Cours();
        $modernJazz->setNom('Modern Jazz');
        $modernJazz->setJour('Lundi');
        $modernJazz->setHeure(\DateTime::createFromFormat('!H:i', '18:30'));
        $modernJazz->setProfesseur('Jean Martin');
        $modernJazz->setCapaciteMax(20);
        $modernJazz->setDureeMinutes(90);
        $modernJazz->setTarif(190);
        $modernJazz->setAnneeNaissanceMin(2008);
        $modernJazz->setAnneeNaissanceMax(2022);
        $this->entityManager->persist($modernJazz);


        // ==========================================
        // 2. FAMILLE 1 : LA FAMILLE STANDARD (ROLE_USER)
        // ==========================================
        $foyerStandard = new Foyer();
        $foyerStandard->setNom('Famille Test');
        $foyerStandard->setAdresse('12 Rue de la Danse');
        $foyerStandard->setCodePostal('85310');
        $foyerStandard->setVille('La Chaize-le-Vicomte');
        $this->entityManager->persist($foyerStandard);

        $parentStandard = new User();
        $parentStandard->setEmail(self::STANDARD_PARENT_EMAIL);
        $parentStandard->setTelephone('0611223344');
        $parentStandard->setRoles(['ROLE_USER']);
        $parentStandard->setPassword($this->passwordHasher->hashPassword($parentStandard, 'Password123!'));
        $parentStandard->setIsVerified(true);
        $parentStandard->setFoyer($foyerStandard);
        $this->entityManager->persist($parentStandard);

        // Danseurs de la famille Standard
        $lea = new Danseur();
        $lea->setPrenom('Léa');
        $lea->setNom('Test');
        $lea->setDateNaissance(new \DateTime('2018-04-12'));
        $lea->setFoyer($foyerStandard);
        $this->entityManager->persist($lea);

        $lucas = new Danseur();
        $lucas->setPrenom('Lucas');
        $lucas->setNom('Test');
        $lucas->setDateNaissance(new \DateTime('2016-09-30'));
        $lucas->setFoyer($foyerStandard);
        $this->entityManager->persist($lucas);


        // ==========================================
        // 3. FAMILLE 2 : LA FAMILLE DU BUREAU (ROLE_BUREAU)
        // ==========================================
        $foyerBureau = new Foyer();
        $foyerBureau->setNom('Famille Responsable');
        $foyerBureau->setAdresse('45 Avenue du Bureau');
        $foyerBureau->setCodePostal('85000');
        $foyerBureau->setVille('La Roche-sur-Yon');
        $this->entityManager->persist($foyerBureau);

        $parentBureau = new User();
        $parentBureau->setEmail(self::BUREAU_PARENT_EMAIL);
        $parentBureau->setTelephone('0699887766');
        $parentBureau->setRoles(['ROLE_BUREAU']);
        $parentBureau->setPassword($this->passwordHasher->hashPassword($parentBureau, 'Password123!'));
        $parentBureau->setIsVerified(true);
        $parentBureau->setFoyer($foyerBureau);
        $this->entityManager->persist($parentBureau);

        // Danseur de la famille Bureau
        $emma = new Danseur();
        $emma->setPrenom('Emma');
        $emma->setNom('Responsable');
        $emma->setDateNaissance(new \DateTime('2014-05-15'));
        $emma->setFoyer($foyerBureau);
        $this->entityManager->persist($emma);


        // ==========================================
        // 4. CRÉATION DES INSCRIPTIONS AUX COURS
        // ==========================================
        $insLea = new Inscription();
        $insLea->setDanseur($lea);
        $insLea->setCours($eveil);
        $insLea->setSaison('2026/2027');
        $insLea->setStatutDossier(StatutDossier::EN_ATTENTE);
        $insLea->setStatutPaiement(StatutPaiement::NON_PAYE);
        $this->entityManager->persist($insLea);

        $insLucas = new Inscription();
        $insLucas->setDanseur($lucas);
        $insLucas->setCours($modernJazz);
        $insLucas->setSaison('2026/2027');
        $insLucas->setStatutDossier(StatutDossier::EN_ATTENTE);
        $insLucas->setStatutPaiement(StatutPaiement::NON_PAYE);
        $this->entityManager->persist($insLucas);

        $insEmma = new Inscription();
        $insEmma->setDanseur($emma);
        $insEmma->setCours($modernJazz);
        $insEmma->setSaison('2026/2027');
        $insEmma->setStatutDossier(StatutDossier::EN_ATTENTE);
        $insEmma->setStatutPaiement(StatutPaiement::NON_PAYE);
        $this->entityManager->persist($insEmma);


        // Enregistrement global en BDD
        $this->entityManager->flush();

        $io->success('Jeu de données de test étendu créé avec succès !');
        
        return Command::SUCCESS;
    }
}