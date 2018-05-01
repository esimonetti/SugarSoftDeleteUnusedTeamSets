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

class TeamSetsCleanup
{
    protected $db;
    protected $tables;
    protected $deleted_teamsets;
    protected $undelete_queries = array();

    protected $valid_fields = array(
        'team_set_id',
        'acl_team_set_id',
    );

    protected $tables_to_ignore = array(
        'team_sets_modules',
        'team_sets_teams',
        'team_sets_users_1',
        'team_sets_users_2',
    );

    private $soft_delete_queries = array(
        'UPDATE team_sets SET deleted = 1 WHERE deleted = 0 AND id = ?',
        'UPDATE team_sets_teams SET deleted = 1 WHERE deleted = 0 AND team_set_id = ?',
    );

    private $revert_soft_delete_queries = array(
        'UPDATE team_sets SET deleted = 0 WHERE id = \'?\' AND deleted = 1;',
        'UPDATE team_sets_teams SET deleted = 0 WHERE team_set_id = \'?\' and deleted = 1;',
    );

    public $max_sleep_time = 20;

    public function __construct()
    {
        $this->db = \DBManagerFactory::getInstance();
        $this->tables = $this->getTablesWithTeams();
    }

    public function microSleep()
    {
        // sleep a little, to reduce db load
        $time = rand(0, $this->max_sleep_time);
        usleep($time);
    }

    public function verifyTeamSetExistancePerFieldOnTable($table, $team_set_id, $field)
    {
        if (in_array($field, $this->valid_fields) && in_array($table, $this->tables)) {

            $this->microSleep();

            $builder = $this->db->getConnection()->createQueryBuilder();

            $builder->select('id')
                ->from($table)
                ->where('deleted = ' . $builder->createPositionalParameter(0))
                ->andWhere($field . ' = ' . $builder->createPositionalParameter($team_set_id))
                ->setMaxResults(1);

            $res = $builder->execute();

            $output = $this->convertSingleResultSet($res->fetchAll(), 'id');
            if (!empty($output['0'])) {
                return $output['0'];
            }
        }

        return 0;
    }

    public function verifyTeamSetExistanceOnTable($table, $team_set_id)
    {
        $team_set_id = $this->verifyTeamSetExistancePerFieldOnTable($table, $team_set_id, 'team_set_id');
        if ($team_set_id) {
            // we have the team set
            return true;
        } else {
            // we should first check if TBP is enabled to optimise performance
            $acl_team_set_id = $this->verifyTeamSetExistancePerFieldOnTable($table, $team_set_id, 'acl_team_set_id');
            if ($acl_team_set_id) {
                // we have an acl team set
                return true;
            }
        }

        return 0;
    }

    public function getAllTeamSets()
    {
        $builder = $this->db->getConnection()->createQueryBuilder();
        $builder->select('team_set_id')
            ->from('team_sets_teams')
            ->where('deleted = ' . $builder->createPositionalParameter(0))
            ->groupBy('team_set_id');
          
        $res = $builder->execute();
        $output = $this->convertSingleResultSet($res->fetchAll(), 'team_set_id');
        return $output;
    }

    public function isTeamSetATeam($team_set_id)
    {
        if (!empty($team_set_id)) {
            $builder = $this->db->getConnection()->createQueryBuilder();
            $builder->select('id')
                ->from('teams')
                ->where('deleted = ' . $builder->createPositionalParameter(0))
                ->andWhere('id = ' . $builder->createPositionalParameter($team_set_id));
              
            $res = $builder->execute();
            $output = $this->convertSingleResultSet($res->fetchAll(), 'id');
            if (!empty($output)) {
                return true;
            }
        }
        return false;
    }

    public function getTablesWithTeams()
    {
        $db_tables = $this->db->getTablesArray();
        $tables_with_teams = array();

        foreach ($db_tables as $table) {
            if (!in_array($table, $this->tables_to_ignore)) {
                $columns = $this->db->get_columns($table);
                if (!empty($columns['team_set_id']) && !empty($columns['acl_team_set_id'])) {
                    $tables_with_teams[] = $table;
                }
            }
        }

        return $tables_with_teams;
    }

    public function findUnusedTeamSets()
    {
        $team_sets = $this->getAllTeamSets();
        $tables = $this->tables;

        $unused_teamsets = array();
        if (!empty($team_sets) && !empty($tables)) {
            foreach ($team_sets as $team_set_id) {
                $keep_teamset = false;
                // keep if it is equal to team id
                if ($this->isTeamSetATeam($team_set_id)) {
                    $keep_teamset = true;
                } else {
                    // look inside all tables randomised until we find it, and break as soon as possible
                    shuffle($tables); 

                    foreach ($tables as $table) {
                        $exists = $this->verifyTeamSetExistanceOnTable($table, $team_set_id);
                        if ($exists) {
                            // a record has it
                            $keep_teamset = true;
                            break;
                        }
                    }
                }

                if (!$keep_teamset) {
                    // the team set id wasn't found across all tables, mark as unused
                    $unused_teamsets[$team_set_id] = $team_set_id;
                }
            }
        }

        return $unused_teamsets;
    }

    public function getDeletedTeamSets()
    {
        return $this->deleted_teamsets;
    }

    protected function setDeletedTeamSets($teamsets)
    {
        if (!is_array($teamsets)) {
            $teamsets = array();
        }
        $this->deleted_teamsets = $teamsets;
    }

    public function softDeleteTeamSet($team_set_id)
    {
        if (!empty($team_set_id)) {

            $this->microSleep();

            $conn = $this->db->getConnection();

            foreach ($this->soft_delete_queries as $query) {
                $stmt = $conn->executeUpdate($query, array($team_set_id));
            }

            // produce revert query
            $this->addUndeleteQueryForTeamSet($team_set_id);
        }
    }

    public function addUndeleteQueryForTeamSet($team_set_id)
    {
        if (!empty($team_set_id)) {
            foreach ($this->revert_soft_delete_queries as $query) {
                $this->undelete_queries[] = str_replace('?', $team_set_id, $query);
            }
        }
    }

    public function getUndeleteTeamSetsQueries()
    {
        return $this->undelete_queries;
    }

    public function softDeleteUnusedTeamSets()
    {
        $teamsets = $this->findUnusedTeamSets();
        $this->setDeletedTeamSets($teamsets);

        if (!empty($teamsets)) {
            // soft delete
            foreach ($teamsets as $team_set_id) {
                $this->softDeleteTeamSet($team_set_id);
            }
        }

        return count($teamsets);
    }

    protected function convertSingleResultSet($results, $fieldname)
    {
        $output = array();
        if (!empty($results)) {
            foreach ($results as $result) {
                if (isset($result[$fieldname])) {
                    $output[] = $result[$fieldname];
                }          
            }
        }

        return $output;
    }
}
