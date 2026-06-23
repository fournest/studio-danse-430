<?php

namespace App\Command;

use App\Entity\Cours;
use App\Entity\Danseur;
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
    description: 'Crée un jeu de données de test : 1 parent, 2 danseurs, 2 cours et 2 inscriptions.',
)]
class SeedTestFamilyCommand extends Command
{
    private const PARENT_EMAIL = 'parent.test@studio430.fr';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (null !== $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::PARENT_EMAIL])) {
            $io->error(sprintf(
                'Un utilisateur "%s" existe déjà. Supprime-le avant de relancer le seed pour éviter les doublons.',
                self::PARENT_EMAIL
            ));

            return Command::FAILURE;
        }

        // 1. Le Parent
        $parent = new User();
        $parent->setEmail(self::PARENT_EMAIL);
        $parent->setTelephone('0611223344');
        $parent->setRoles(['ROLE_USER']);
        $parent->setPassword($this->passwordHasher->hashPassword($parent, 'Password123!'));
        $this->entityManager->persist($parent);

        // 2. Les Danseurs (enfants), liés au parent
        $lea = new Danseur();
        $lea->setPrenom('Léa');
        $lea->setNom('Test');
        $lea->setDateNaissance(new \DateTime('2018-04-12'));
        $lea->setParent($parent);
        $parent->addDanseur($lea);
        $this->entityManager->persist($lea);

        $lucas = new Danseur();
        $lucas->setPrenom('Lucas');
        $lucas->setNom('Test');
        $lucas->setDateNaissance(new \DateTime('2016-09-30'));
        $lucas->setParent($parent);
        $parent->addDanseur($lucas);
        $this->entityManager->persist($lucas);

        // 3. Les Cours
        $eveil = new Cours();
        $eveil->setNom('Éveil Danse');
        $eveil->setJour('Mercredi');
        $eveil->setHeure(\DateTime::createFromFormat('!H:i', '14:00'));
        $eveil->setProfesseur('Marie Dupont');
        $eveil->setCapaciteMax(15);
        $this->entityManager->persist($eveil);

        $modernJazz = new Cours();
        $modernJazz->setNom('Modern Jazz');
        $modernJazz->setJour('Lundi');
        $modernJazz->setHeure(\DateTime::createFromFormat('!H:i', '18:30'));
        $modernJazz->setProfesseur('Jean Martin');
        $modernJazz->setCapaciteMax(20);
        $this->entityManager->persist($modernJazz);

        // 4. Les Inscriptions
        $inscriptionLea = new Inscription();
        $inscriptionLea->setDanseur($lea);
        $inscriptionLea->setCours($eveil);
        $inscriptionLea->setSaison('2026/2027');
        $inscriptionLea->setStatutDossier(StatutDossier::EN_ATTENTE);
        $inscriptionLea->setStatutPaiement(StatutPaiement::NON_PAYE);
        $this->entityManager->persist($inscriptionLea);

        $inscriptionLucas = new Inscription();
        $inscriptionLucas->setDanseur($lucas);
        $inscriptionLucas->setCours($modernJazz);
        $inscriptionLucas->setSaison('2026/2027');
        $inscriptionLucas->setStatutDossier(StatutDossier::EN_ATTENTE);
        $inscriptionLucas->setStatutPaiement(StatutPaiement::NON_PAYE);
        $this->entityManager->persist($inscriptionLucas);

        // Enregistrement global
        $this->entityManager->flush();

        $io->success('Jeu de données de test créé avec succès !');
        $io->listing([
            'Parent : ' . self::PARENT_EMAIL . ' (mot de passe : Password123!)',
            'Danseurs : Léa Test, Lucas Test',
            'Cours : Éveil Danse (Mercredi 14h00), Modern Jazz (Lundi 18h30)',
            'Inscriptions : Léa → Éveil Danse, Lucas → Modern Jazz (saison 2026/2027)',
        ]);

        return Command::SUCCESS;
    }
}
