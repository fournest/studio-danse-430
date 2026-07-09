<?php

namespace App\Command;

use App\Entity\Sponsor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-sponsors',
    description: 'Génère les partenaires et sponsors en BDD à partir des images réelles.',
)]
class SeedSponsorsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $repository = $this->entityManager->getRepository(Sponsor::class);

        // Cartographie exacte de tes fichiers présents dans logos\sponsors
        $sponsorsData = [
            ['nom' => 'GitHub', 'logo' => 'git-github-1783272580.jpg'],
            ['nom' => 'S1 Digital', 'logo' => 'logo-s1digital-1783269830.png'],
            ['nom' => 'S1 Digital', 'logo' => 's1-digital-1783272450.png'],
            ['nom' => 'Progression', 'logo' => 'progression-1783272514.png'],
        ];

        foreach ($sponsorsData as $data) {
            // Sécurité anti-doublon basée sur le nom de l'image
            $existing = $repository->findOneBy(['logo' => $data['logo']]);
            
            if (null === $existing) {
                $sponsor = new Sponsor();
                $sponsor->setNom($data['nom']);
                $sponsor->setLogo($data['logo']);
                
                $this->entityManager->persist($sponsor);
                $io->writeln(sprintf('✓ Sponsor ajouté : <info>%s</info> (Image: %s)', $data['nom'], $data['logo']));
            } else {
                $io->writeln(sprintf('○ Sponsor déjà présent : %s', $data['nom']));
            }
        }

        $this->entityManager->flush();
        $io->success('La synchronisation des sponsors est terminée.');

        return Command::SUCCESS;
    }
}