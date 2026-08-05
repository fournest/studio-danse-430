<?php

namespace App\Command;

use App\Data\PageLegaleSeedData;
use App\Entity\PageLegale;
use App\Repository\PageLegaleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-pages-legales',
    description: 'Initialise les 3 pages légales obligatoires (mentions, RGPD, CGU) si elles n\'existent pas.',
)]
final class SeedPagesLegalesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PageLegaleRepository $pageLegaleRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Réinitialise le contenu des pages existantes avec le squelette');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $created = 0;
        $updated = 0;
        $skipped = 0;

        foreach (PageLegaleSeedData::pages() as $data) {
            $existing = $this->pageLegaleRepository->findOneBy(['slug' => $data['slug']]);

            if (null === $existing) {
                $page = new PageLegale();
                $page->setTitre($data['titre']);
                $page->setSlug($data['slug']);
                $page->setContenu($data['contenu']);
                $this->entityManager->persist($page);
                ++$created;
                $io->writeln(sprintf('  <info>+ Créée</info> : %s (%s)', $data['titre'], $data['slug']));

                continue;
            }

            if ($force) {
                $existing->setTitre($data['titre']);
                $existing->setContenu($data['contenu']);
                $existing->setUpdatedAt(new \DateTimeImmutable());
                ++$updated;
                $io->writeln(sprintf('  <comment>~ Mise à jour</comment> : %s', $data['slug']));

                continue;
            }

            ++$skipped;
            $io->writeln(sprintf('  <fg=gray>= Inchangée</> : %s', $data['slug']));
        }

        $this->entityManager->flush();

        $io->success(sprintf(
            'Pages légales : %d créée(s), %d mise(s) à jour, %d inchangée(s).',
            $created,
            $updated,
            $skipped
        ));

        if (0 === $created && 0 === $updated) {
            $io->note('Utilisez --force pour réinjecter les textes squelettes sur les pages existantes.');
        }

        return Command::SUCCESS;
    }
}
