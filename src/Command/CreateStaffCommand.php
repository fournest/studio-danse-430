<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-staff',
    description: 'Crée un utilisateur de l\'équipe (bureau, tresorier, prof) avec le bon rôle haché.',
)]
class CreateStaffCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail de l\'utilisateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe en clair')
            ->addArgument('type', InputArgument::REQUIRED, 'Le type de compte (bureau, tresorier, prof)')
            ->addArgument('telephone', InputArgument::OPTIONAL, 'Numéro de téléphone', '0000000000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');
        $type = strtolower((string) $input->getArgument('type'));
        $telephone = (string) $input->getArgument('telephone');

        // Cartographie du type vers le vrai rôle Symfony
        $rolesMapping = [
            'bureau' => 'ROLE_BUREAU',
            'tresorier' => 'ROLE_TRESORIER',
            'prof' => 'ROLE_PROF'
        ];

        if (!array_key_exists($type, $rolesMapping)) {
            $io->error(sprintf('Le type "%s" n\'est pas valide. Choisissez parmi : bureau, tresorier, prof.', $type));
            return Command::INVALID;
        }

        $chosenRole = $rolesMapping[$type];
        $repository = $this->entityManager->getRepository(User::class);

        if (null !== $repository->findOneBy(['email' => $email])) {
            $io->error(sprintf('Un utilisateur existe déjà avec l\'adresse "%s".', $email));
            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setTelephone($telephone);
        $user->setRoles([$chosenRole]);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Utilisateur "%s" créé avec le rôle %s.', $email, $chosenRole));

        return Command::SUCCESS;
    }
}