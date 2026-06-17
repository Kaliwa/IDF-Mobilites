<?php

namespace App\Command;

use App\Service\TransitLinesImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:lines:sync',
    description: 'Fetch metro, RER and tram lines from IDFM open data and upsert them in the database',
)]
final class LinesImportCommand extends Command
{
    public function __construct(private readonly TransitLinesImporter $importer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Syncing transit lines from IDFM open data…');

        $counts = $this->importer->import();

        $io->success(sprintf(
            'Done. %d line(s) created, %d updated.',
            $counts['created'],
            $counts['updated'],
        ));

        return Command::SUCCESS;
    }
}
