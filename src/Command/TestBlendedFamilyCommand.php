<?php

namespace App\Command;

use App\Entity\Danseur;
use App\Entity\Foyer;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-blended-family',
    description: 'Crée une famille recomposée de test : Chloé (titulaire), Mathieu (2ᵉ parent) et Léa.',
)]
class TestBlendedFamilyCommand extends Command
{
    private const CHLOE_EMAIL = 'chloe.parent@studio430.fr';
    private const MATHIEU_EMAIL = 'mathieu.parent@studio430.fr';
    private const PASSWORD = 'Password123!';

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

        if (null !== $userRepository->findOneBy(['email' => self::CHLOE_EMAIL])
            || null !== $userRepository->findOneBy(['email' => self::MATHIEU_EMAIL])) {
            $io->error('Les comptes Chloé / Mathieu existent déjà. Supprimez-les avant de relancer la commande.');

            return Command::FAILURE;
        }

        // Parent titulaire : Chloé
        $chloe = new User();
        $chloe->setEmail(self::CHLOE_EMAIL);
        $chloe->setTelephone('0611111111');
        $chloe->setRoles(['ROLE_USER']);
        $chloe->setPassword($this->passwordHasher->hashPassword($chloe, self::PASSWORD));
        $chloe->setIsVerified(true);
        $chloe->setIsActif(true);

        $foyer = new Foyer();
        $foyer->setNom('Famille Berthonneau');
        $foyer->setAdresse('8 Rue des Lilas');
        $foyer->setCodePostal('85000');
        $foyer->setVille('La Roche-sur-Yon');
        $foyer->setParent2IsDifferent(true);
        $foyer->setParent2Prenom('Mathieu');
        $foyer->setParent2Nom('Berthonneau');
        $foyer->setParent2Email(self::MATHIEU_EMAIL);
        $foyer->setParent2Telephone('0622222222');

        $chloe->setFoyer($foyer);
        $this->entityManager->persist($chloe);
        $this->entityManager->persist($foyer);

        // 2ᵉ parent : Mathieu (compte propre, sans foyer titulaire)
        $mathieu = new User();
        $mathieu->setEmail(self::MATHIEU_EMAIL);
        $mathieu->setTelephone('0622222222');
        $mathieu->setRoles(['ROLE_USER']);
        $mathieu->setPassword($this->passwordHasher->hashPassword($mathieu, self::PASSWORD));
        $mathieu->setIsVerified(true);
        $mathieu->setIsActif(true);
        $this->entityManager->persist($mathieu);

        // Enfant Léa : rattachement explicite à Mathieu via parent2Email
        $lea = new Danseur();
        $lea->setPrenom('Léa');
        $lea->setNom('Berthonneau');
        $lea->setDateNaissance(new \DateTime('2017-03-21'));
        $lea->setFoyer($foyer);
        $lea->setParent2Prenom('Mathieu');
        $lea->setParent2Nom('Berthonneau');
        $lea->setParent2Email(self::MATHIEU_EMAIL);
        $lea->setParent2Telephone('0622222222');
        $this->entityManager->persist($lea);

        $this->entityManager->flush();

        $io->success([
            'Famille recomposée créée avec succès.',
            sprintf('Titulaire  : %s / %s', self::CHLOE_EMAIL, self::PASSWORD),
            sprintf('2ᵉ parent  : %s / %s (lecture seule sur Léa)', self::MATHIEU_EMAIL, self::PASSWORD),
            'Danseur    : Léa Berthonneau (parent2Email → Mathieu)',
        ]);

        return Command::SUCCESS;
    }
}
