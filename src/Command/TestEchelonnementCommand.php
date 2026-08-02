<?php

namespace App\Command;

use App\Entity\Cours;
use App\Entity\Danseur;
use App\Entity\Foyer;
use App\Entity\Inscription;
use App\Entity\StatutDossier;
use App\Entity\StatutPaiement;
use App\Entity\User;
use App\Service\EchelonnementService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-echelonnement',
    description: 'Valide la génération d\'échéances (1x, 3x, 10x) : sommes exactes et dates.',
)]
class TestEchelonnementCommand extends Command
{
    private const SAISON = '2026/2027';

    private const MONTANTS = [100.00, 175.00, 333.33];

    private const NB_ECHEANCES = [1, 3, 10];

    /** @var array<int, list<string>> */
    private const DATES_ATTENDUES = [
        1 => ['2026-10-10'],
        3 => ['2026-10-10', '2027-01-10', '2027-04-10'],
        10 => [
            '2026-10-10', '2026-11-10', '2026-12-10',
            '2027-01-10', '2027-02-10', '2027-03-10',
            '2027-04-10', '2027-05-10', '2027-06-10', '2027-07-10',
        ],
    ];

    public function __construct(
        private readonly EchelonnementService $echelonnementService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Test EchelonnementService — montants indivisibles');

        $results = [];
        foreach (self::MONTANTS as $montant) {
            foreach (self::NB_ECHEANCES as $nb) {
                $results[] = $this->testScenario($io, $montant, $nb);
            }
        }

        $io->newLine();
        if (\in_array(false, $results, true)) {
            $io->error('Au moins un scénario a échoué.');

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Tous les scénarios sont validés (%d cas : %s € × %s).',
            \count($results),
            implode(' / ', array_map(static fn (float $m) => number_format($m, 2, ',', ' '), self::MONTANTS)),
            implode(', ', array_map(static fn (int $n) => $n . 'x', self::NB_ECHEANCES))
        ));

        return Command::SUCCESS;
    }

    private function testScenario(SymfonyStyle $io, float $montant, int $nb): bool
    {
        $io->section(sprintf('%.2f € en %dx', $montant, $nb));

        $inscription = $this->makeInscription(self::SAISON, $montant);
        $paiements = $this->echelonnementService->generateEcheances($inscription, $nb, $montant, 'Test Parent');
        $datesAttendues = self::DATES_ATTENDUES[$nb];

        $okCount = \count($paiements) === $nb;
        $somme = round(array_sum(array_map(static fn ($p) => $p->getMontant(), $paiements)), 2);
        $okSomme = abs($somme - $montant) < 0.001;

        $rows = [];
        $okDates = true;
        foreach ($paiements as $i => $paiement) {
            $date = $paiement->getDateEncaissementPrevue()?->format('Y-m-d') ?? '—';
            $attendue = $datesAttendues[$i] ?? '—';
            $dateOk = $date === $attendue;
            $okDates = $okDates && $dateOk;
            $rows[] = [
                sprintf('%d/%d', $i + 1, $nb),
                $date,
                $attendue,
                $this->fmt($paiement->getMontant()),
                $dateOk && $paiement->getMode()->value === 'cheque' && $paiement->getStatut()->value === 'en_attente' ? '✔' : '✘',
            ];
        }
        $rows[] = [
            'Σ',
            '—',
            '—',
            $this->fmt($somme) . ' / ' . $this->fmt($montant),
            $okSomme ? '✔' : '✘',
        ];

        $io->table(['#', 'Date obtenue', 'Date attendue', 'Montant', ''], $rows);

        $ok = $okCount && $okSomme && $okDates;
        if ($ok) {
            $io->writeln('<info>✔ Somme exacte + dates + nombre d\'échéances OK</info>');
        } else {
            $io->writeln(sprintf(
                '<error>✘ Échec (count=%s, somme=%s, dates=%s)</error>',
                $okCount ? 'ok' : 'ko',
                $okSomme ? 'ok' : 'ko',
                $okDates ? 'ok' : 'ko'
            ));
        }

        return $ok;
    }

    private function makeInscription(string $saison, float $montant): Inscription
    {
        $user = new User();
        $user->setEmail(sprintf('test-ech-%s@studio430.local', md5((string) microtime(true))));
        $user->setTelephone('0600000000');
        $user->setPassword('unused');
        $user->setRoles(['ROLE_USER']);

        $foyer = new Foyer();
        $foyer->setNom('Test');
        $foyer->setAdresse('1 Rue Test');
        $foyer->setCodePostal('85000');
        $foyer->setVille('La Roche-sur-Yon');
        $foyer->setUser($user);

        $danseur = new Danseur();
        $danseur->setPrenom('Test');
        $danseur->setNom('Echelle');
        $danseur->setDateNaissance(new \DateTimeImmutable('2015-01-01'));
        $danseur->setFoyer($foyer);

        $cours = new Cours();
        $cours->setNom('Jazz Test');
        $cours->setJour('Lundi');
        $cours->setHeure(\DateTime::createFromFormat('!H:i', '17:00'));
        $cours->setProfesseur('Prof');
        $cours->setCapaciteMax(20);
        $cours->setDureeMinutes(90);
        $cours->setTarif($montant);

        $inscription = new Inscription();
        $inscription->setDanseur($danseur);
        $inscription->setCours($cours);
        $inscription->setSaison($saison);
        $inscription->setStatutDossier(StatutDossier::EN_ATTENTE);
        $inscription->setStatutPaiement(StatutPaiement::NON_PAYE);
        $inscription->setMontantTotal($montant);

        return $inscription;
    }

    private function fmt(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }
}
