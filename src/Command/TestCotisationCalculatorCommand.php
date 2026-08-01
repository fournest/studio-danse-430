<?php

namespace App\Command;

use App\Entity\Cours;
use App\Entity\Danseur;
use App\Entity\Foyer;
use App\Entity\User;
use App\Service\CotisationCalculatorService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-cotisation-calculator',
    description: 'Valide le CotisationCalculatorService sur les scénarios Excel saison 2026-2027.',
)]
class TestCotisationCalculatorCommand extends Command
{
    public function __construct(
        private readonly CotisationCalculatorService $calculator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Test CotisationCalculatorService — Saison 2026/2027');

        $results = [];
        $results[] = $this->runScenario1($io);
        $results[] = $this->runScenario2($io);
        $results[] = $this->runScenario3($io);
        $results[] = $this->runScenario4($io);

        $io->newLine();
        if (in_array(false, $results, true)) {
            $io->error('Au moins un scénario a échoué.');

            return Command::FAILURE;
        }

        $io->success('Tous les scénarios Excel / bureau sont validés.');

        return Command::SUCCESS;
    }

    private function runScenario1(SymfonyStyle $io): bool
    {
        $io->section('Scénario 1 — 1 enfant × 1 cours 1h30 (190 €)');

        $foyer = $this->makeFoyer('Scénario 1');
        $danseur = $this->makeDanseur($foyer, 'Alice', 'Martin', '2015-06-01');
        $cours = $this->makeCours('Classique 3', 90, 190.0, 2008, 2022);
        $danseur->addCours($cours);

        $detail = $this->calculator->calculateForFoyer($foyer);
        $expected = 190.0;

        return $this->assertScenario($io, $detail, $expected, [
            'Sous-total' => 190.0,
            'Gratuité 2020' => 0.0,
            'Remise foyer %' => 0,
            'Remise foyer €' => 0.0,
        ]);
    }

    private function runScenario2(SymfonyStyle $io): bool
    {
        $io->section('Scénario 2 — Enfant né en 2020 × 2 cours 1h (164 €) → 2ᵉ gratuit');

        $foyer = $this->makeFoyer('Scénario 2');
        $danseur = $this->makeDanseur($foyer, 'Lina', 'Dupont', '2020-03-15');
        $coursA = $this->makeCours('Éveil A', 60, 164.0, 2008, 2022);
        $coursB = $this->makeCours('Éveil B', 60, 164.0, 2008, 2022);
        $danseur->addCours($coursA);
        $danseur->addCours($coursB);

        $detail = $this->calculator->calculateForFoyer($foyer);
        $expected = 164.0;

        return $this->assertScenario($io, $detail, $expected, [
            'Sous-total' => 328.0,
            'Gratuité 2020' => 164.0,
            'Remise foyer %' => 0,
            'Remise foyer €' => 0.0,
        ]);
    }

    private function runScenario3(SymfonyStyle $io): bool
    {
        $io->section('Scénario 3 — Foyer 3 cours (190 + 190 + 177) − 30 % → 389,90 €');

        $foyer = $this->makeFoyer('Scénario 3');
        $enfant1 = $this->makeDanseur($foyer, 'Tom', 'Bernard', '2014-01-10');
        $enfant2 = $this->makeDanseur($foyer, 'Emma', 'Bernard', '2016-08-22');

        $classique3 = $this->makeCours('Classique 3', 90, 190.0, 2008, 2022);
        $jazz3 = $this->makeCours('Jazz 3', 90, 190.0, 2008, 2022);
        $jazz2 = $this->makeCours('Jazz 2', 75, 177.0, 2008, 2022);

        $enfant1->addCours($classique3);
        $enfant1->addCours($jazz3);
        $enfant2->addCours($jazz2);

        $detail = $this->calculator->calculateForFoyer($foyer);
        $expected = 389.90;

        return $this->assertScenario($io, $detail, $expected, [
            'Sous-total' => 557.0,
            'Gratuité 2020' => 0.0,
            'Remise foyer %' => 30,
            'Remise foyer €' => 167.10,
            'Remise manuelle' => 0.0,
        ]);
    }

    private function runScenario4(SymfonyStyle $io): bool
    {
        $io->section('Scénario 4 — Remise manuelle bureau (−50 €) sur 1 cours 190 € → 140 €');

        $foyer = $this->makeFoyer('Scénario 4');
        $foyer->setRemiseManuelle(50);
        $foyer->setMotifRemise('Membre du bureau');

        $danseur = $this->makeDanseur($foyer, 'Nora', 'Bureau', '2013-02-01');
        $cours = $this->makeCours('Jazz 3', 90, 190.0, 2008, 2022);
        $danseur->addCours($cours);

        $detail = $this->calculator->calculateForFoyer($foyer);
        $expected = 140.0;

        $ok = $this->assertScenario($io, $detail, $expected, [
            'Sous-total' => 190.0,
            'Gratuité 2020' => 0.0,
            'Remise foyer %' => 0,
            'Remise foyer €' => 0.0,
            'Remise manuelle' => 50.0,
        ]);

        if ($ok && $detail->motifRemise !== 'Membre du bureau') {
            $io->writeln('<error>✘ Motif de remise manquant ou incorrect</error>');

            return false;
        }

        return $ok;
    }

    /**
     * @param array<string, float|int> $expectedParts
     */
    private function assertScenario(
        SymfonyStyle $io,
        \App\Dto\CotisationDetail $detail,
        float $expectedTotal,
        array $expectedParts,
    ): bool {
        $expectedRemiseManuelle = (float) ($expectedParts['Remise manuelle'] ?? 0.0);

        $io->table(
            ['Indicateur', 'Obtenu', 'Attendu'],
            [
                ['Sous-total', $this->fmt($detail->subtotal), $this->fmt((float) $expectedParts['Sous-total'])],
                ['Gratuité 2020', $this->fmt($detail->gratuit2020Amount), $this->fmt((float) $expectedParts['Gratuité 2020'])],
                ['Remise foyer %', (string) $detail->foyerDiscountPercentage . ' %', (string) $expectedParts['Remise foyer %'] . ' %'],
                ['Remise foyer €', $this->fmt($detail->foyerDiscountAmount), $this->fmt((float) $expectedParts['Remise foyer €'])],
                ['Remise manuelle', $this->fmt($detail->remiseManuelleAmount), $this->fmt($expectedRemiseManuelle)],
                ['Total net', $this->fmt($detail->total), $this->fmt($expectedTotal)],
                ['Cours payants', (string) $detail->payingCoursesCount, '—'],
            ]
        );

        $ok = abs($detail->total - $expectedTotal) < 0.001
            && abs($detail->subtotal - (float) $expectedParts['Sous-total']) < 0.001
            && abs($detail->gratuit2020Amount - (float) $expectedParts['Gratuité 2020']) < 0.001
            && $detail->foyerDiscountPercentage === (int) $expectedParts['Remise foyer %']
            && abs($detail->foyerDiscountAmount - (float) $expectedParts['Remise foyer €']) < 0.001
            && abs($detail->remiseManuelleAmount - $expectedRemiseManuelle) < 0.001;

        if ($ok) {
            $io->writeln('<info>✔ Scénario OK</info>');
        } else {
            $io->writeln('<error>✘ Écart détecté</error>');
        }

        return $ok;
    }

    private function makeFoyer(string $nom): Foyer
    {
        $user = new User();
        $user->setEmail(sprintf('test-%s@studio430.local', md5($nom . microtime())));
        $user->setTelephone('0600000000');
        $user->setPassword('unused');
        $user->setRoles(['ROLE_USER']);

        $foyer = new Foyer();
        $foyer->setNom($nom);
        $foyer->setAdresse('1 Rue Test');
        $foyer->setCodePostal('85000');
        $foyer->setVille('La Roche-sur-Yon');
        $foyer->setUser($user);
        $user->setFoyer($foyer);

        return $foyer;
    }

    private function makeDanseur(Foyer $foyer, string $prenom, string $nom, string $dateNaissance): Danseur
    {
        $danseur = new Danseur();
        $danseur->setPrenom($prenom);
        $danseur->setNom($nom);
        $danseur->setDateNaissance(new \DateTimeImmutable($dateNaissance));
        $danseur->setFoyer($foyer);
        $foyer->addDanseur($danseur);

        return $danseur;
    }

    private function makeCours(
        string $nom,
        int $dureeMinutes,
        float $tarif,
        ?int $anneeMin,
        ?int $anneeMax,
    ): Cours {
        $cours = new Cours();
        $cours->setNom($nom);
        $cours->setJour('Lundi');
        $cours->setHeure(\DateTime::createFromFormat('!H:i', '17:00'));
        $cours->setProfesseur('Prof Test');
        $cours->setCapaciteMax(20);
        $cours->setDureeMinutes($dureeMinutes);
        $cours->setTarif($tarif);
        $cours->setAnneeNaissanceMin($anneeMin);
        $cours->setAnneeNaissanceMax($anneeMax);

        return $cours;
    }

    private function fmt(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }
}
