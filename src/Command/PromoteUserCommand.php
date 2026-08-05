<?php

namespace App\Command;

use App\Entity\User;
use App\Security\ClubRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:promote-user',
    description: 'Attribue ou modifie le rôle officiel d\'un utilisateur (renouvellement AG, bureau, profs).',
)]
final class PromoteUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Adresse e-mail du compte à modifier')
            ->addArgument('role', InputArgument::OPTIONAL, 'Rôle cible (alias ou ROLE_* — voir --list-roles)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule le changement sans enregistrer en base')
            ->addOption('list-roles', null, InputOption::VALUE_NONE, 'Affiche les alias disponibles et quitte');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('list-roles')) {
            $io->title('Rôles disponibles pour app:promote-user');
            $io->writeln(ClubRole::cliAliasesHelpLines());
            $io->note('Vous pouvez aussi passer directement un rôle Symfony (ex. ROLE_TRESORIER).');

            return Command::SUCCESS;
        }

        $emailArg = $input->getArgument('email');
        $roleArg = $input->getArgument('role');
        if (null === $emailArg || null === $roleArg || $emailArg === '' || $roleArg === '') {
            $io->error('Les arguments email et role sont requis. Ex. : app:promote-user bureau@club.fr president');
            $io->note('Lancez app:promote-user --list-roles pour la liste des rôles.');

            return Command::INVALID;
        }

        $email = mb_strtolower(trim((string) $emailArg));
        $roleInput = (string) $roleArg;
        $dryRun = (bool) $input->getOption('dry-run');

        $targetRole = ClubRole::resolveAlias($roleInput);
        if (null === $targetRole) {
            $io->error(sprintf(
                'Le rôle « %s » est inconnu. Lancez app:promote-user --list-roles pour la liste.',
                $roleInput
            ));

            return Command::INVALID;
        }

        /** @var User|null $user */
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (null === $user) {
            $io->error(sprintf('Aucun utilisateur trouvé avec l\'adresse « %s ».', $email));

            return Command::FAILURE;
        }

        $previousRole = $user->getPrimaryClubRole();
        $previousLabel = ClubRole::label($previousRole);
        $targetLabel = ClubRole::label($targetRole);

        if ($previousRole === $targetRole) {
            $io->warning(sprintf(
                '%s possède déjà le rôle « %s » (%s). Aucune modification.',
                $email,
                $targetLabel,
                $targetRole
            ));

            return Command::SUCCESS;
        }

        $io->section('Changement de rôle');
        $io->table(
            ['Compte', 'Ancien rôle', 'Nouveau rôle'],
            [[$email, sprintf('%s (%s)', $previousLabel, $previousRole), sprintf('%s (%s)', $targetLabel, $targetRole)]]
        );

        if (ClubRole::isBureauMember($targetRole) || ClubRole::isBureauMember($previousRole)) {
            $io->warning(
                'Rappel LDC : toute modification de la composition du Bureau doit faire l\'objet '
                .'d\'une déclaration officielle en préfecture. '
                .'Avez-vous déposé la nouvelle déclaration LDC via Admin → Déclarations LDC ?'
            );
        }

        if ($dryRun) {
            $io->note('Mode --dry-run : aucune modification enregistrée.');

            return Command::SUCCESS;
        }

        $user->setPrimaryClubRole($targetRole);
        $this->entityManager->flush();

        $io->success(sprintf(
            'Rôle de %s mis à jour : %s → %s.',
            $email,
            $previousLabel,
            $targetLabel
        ));

        return Command::SUCCESS;
    }
}
