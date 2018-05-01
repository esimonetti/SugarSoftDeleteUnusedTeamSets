<?php

// Enrico Simonetti
// enricosimonetti.com
//
// 2018-05-01 on 8.0.0
//
// CLI to soft delete unused team sets
//
// Run with: ./bin/sugarcrm teamsets-cleanup:unused

namespace Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetsCleanup;

use Sugarcrm\Sugarcrm\Console\CommandRegistry\Mode\InstanceModeInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class TeamSetsCleanupUnusedCommand extends Command implements InstanceModeInterface
{
    protected $teamsetscleanup = null;

    protected function teamsets()
    {
        if (empty($this->teamsetscleanup)) {        
            $this->teamsetscleanup = new TeamSetsCleanup();
        }
    
        return $this->teamsetscleanup;
    }

    protected function configure()
    {
        $this
            ->setName('teamsets-cleanup:unused')
            ->setDescription('Soft delete unused team sets');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start_time = microtime(true);
        $deleted = $this->teamsets()->softDeleteUnusedTeamSets();
        $output->writeln('Soft deleted ' . $deleted . ' unused team sets in ' . round(microtime(true) - $start_time, 2) . ' seconds.');
        if ($deleted > 0) {
            $output->writeln('');
            $output->writeln('Deleted the following team sets:');
            $output->writeln($this->teamsets()->getDeletedTeamSets());
            $output->writeln('');
            $output->writeln('To revert, execute the following SQL queries:');
            $output->writeln($this->teamsets()->getUndeleteTeamSetsQueries());
            $output->writeln('');
        }
    }
}
