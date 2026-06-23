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
    name: 'app:create-admin',
    description: 'Crée un utilisateur administrateur (ROLE_ADMIN) avec un mot de passe haché.',
)]
class CreateAdminCommand extends Command
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
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail de l\'administrateur')
            ->addArgument('password', InputArgument::REQUIRED, 'Mot de passe en clair (il sera haché)')
            ->addArgument('telephone', InputArgument::OPTIONAL, 'Numéro de téléphone', '0000000000');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string) $input->getArgument('email');
        $password = (string) $input->getArgument('password');
        $telephone = (string) $input->getArgument('telephone');

        $repository = $this->entityManager->getRepository(User::class);

        if (null !== $repository->findOneBy(['email' => $email])) {
            $io->error(sprintf('Un utilisateur existe déjà avec l\'adresse "%s".', $email));

            return Command::FAILURE;
        }

        $user = new User();
        $user->setEmail($email);
        $user->setTelephone($telephone);
        $user->setRoles(['ROLE_ADMIN']);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Administrateur "%s" créé avec succès.', $email));

        return Command::SUCCESS;
    }
}
