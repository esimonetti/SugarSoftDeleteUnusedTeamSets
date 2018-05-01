# SugarSoftDeleteUnusedTeamSets

CLI to soft delete unused team sets

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

## Generate installable package
*   Clone the repository
*   Run: `composer update` to retrieve the sugar-module-packager dependency
*   Generate the installable .zip Sugar module with: `./vendor/bin/package <version number>`
