<?php

// Enrico Simonetti
// enricosimonetti.com
//
// 2018-05-01 on 8.0.0
//
// CLI to soft delete unused team sets
//
// Run with: ./bin/sugarcrm teamsets-cleanup:unused

$commandregistry = Sugarcrm\Sugarcrm\Console\CommandRegistry\CommandRegistry::getInstance();
$commandregistry->addCommands(array(new Sugarcrm\Sugarcrm\custom\Console\Command\TeamSetsCleanup\TeamSetsCleanupUnusedCommand()));
