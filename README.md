# SugarSoftDeleteUnusedTeamSets

CLI to soft delete unused team sets

1. Use at your own risk
2. It is highly recommended to complete a database backup before using this tool
3. It is highly recommended to complete thorough testing, on separate environments. It should be aimed at:
    * Verifying that the tool works correctly
    * Verifying that the system works correctly after the tool has been used
    * Verifying the infrastructure resources utilisation while the tool is running
    * Verifying the timing required to run the tool

## Usage
Run via command line only, with: ./bin/sugarcrm teamsets-cleanup:unused

## Sample output
```
Soft deleted 2 unused team sets in 1.84 seconds.

Deleted the following team sets:
078b1a5c-4c02-11e8-94d7-fbb08c459bbc
30f22aa0-49f7-11e8-bb7e-48e6adc360b5

To revert, execute the following SQL queries:
UPDATE team_sets SET deleted = 0 WHERE id = '078b1a5c-4c02-11e8-94d7-fbb08c459bbc' AND deleted = 1;
UPDATE team_sets_teams SET deleted = 0 WHERE team_set_id = '078b1a5c-4c02-11e8-94d7-fbb08c459bbc' and deleted = 1;
UPDATE team_sets SET deleted = 0 WHERE id = '30f22aa0-49f7-11e8-bb7e-48e6adc360b5' AND deleted = 1;
UPDATE team_sets_teams SET deleted = 0 WHERE team_set_id = '30f22aa0-49f7-11e8-bb7e-48e6adc360b5' and deleted = 1;
```

## What this will and won't do
* It does the basics, to help get the job at hand done
    * It only runs via CLI, not via UI
    * It looks for every team_sets into every record of every module and detects unused ones
    * It soft deletes the unused team_sets from team_sets and team_sets_teams
    * It does not consider soft deleted records as valid records (e.g.: if a Contact has the deleted flag set to 1, and it is the only record across the whole database that leverages a specific team_set, the team_set will be soft deleted)
    * It provides the list of soft deleted team_sets as output
    * It provides as output, SQL queries to revert the soft delete of those records if necessary. Note that if the scheduler "Prune Database on 1st of Month" runs, it will hard delete soft deleted records (including the soft deleted team sets), therefore there won't be any way to restore deleted team sets after that moment
    * It will take a long time to run through a big data set. Please test the timing and resource utilisation
* It does not look into User's Preferences (e.g.: if a User leverages a team_set on any of his/her settings)
* It does not look into Advanced Workflows rules
* It does not hard delete the records from the database. The scheduler "Prune Database on 1st of Month" will. Alternatively this action can be completed manually
* As it requires CLI access, it only runs on On-Site systems

## Generate installable package
* Clone the repository
* Run: `composer update` to retrieve the sugar-module-packager dependency
* Generate the installable .zip Sugar module with: `./vendor/bin/package <version number>`
