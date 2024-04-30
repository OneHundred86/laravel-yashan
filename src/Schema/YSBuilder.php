<?php

namespace Oh86\LaravelYashan\Schema;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder;
use Oh86\LaravelYashan\Schema\YSBlueprint;

class YSBuilder extends Builder
{
    /**
     * @var OracleAutoIncrementHelper
     */
    public $helper;

    /**
     * @var Comment
     */
    public $comment;

    /**
     * @param  Connection  $connection
     */
    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->helper = new YSAutoIncrementHelper($connection);
        $this->comment = new Comment($connection);
    }

    /**
     * Create a new table on the schema.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public function create($table, Closure $callback)
    {
        $blueprint = $this->createBlueprint($table);

        $blueprint->create();

        $callback($blueprint);

        $this->build($blueprint);

        $this->comment->setComments($blueprint);

        $this->helper->createAutoIncrementObjects($blueprint, $table);
    }

    /**
     * Create a new command set with a Closure.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @return \Illuminate\Database\Schema\Blueprint
     */
    protected function createBlueprint($table, Closure $callback = null)
    {
        $blueprint = new YSBlueprint($table, $callback);
        $blueprint->setTablePrefix($this->connection->getTablePrefix());

        return $blueprint;
    }

    /**
     * Changes an existing table on the schema.
     *
     * @param  string  $table
     * @param  Closure  $callback
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public function table($table, Closure $callback)
    {
        $blueprint = $this->createBlueprint($table);

        $callback($blueprint);

        foreach ($blueprint->getCommands() as $command) {
            if ($command->get('name') == 'drop') {
                $this->helper->dropAutoIncrementObjects($table);
            }
        }

        $this->build($blueprint);

        $this->comment->setComments($blueprint);
    }

    /**
     * Drop a table from the schema.
     *
     * @param  string  $table
     * @return \Illuminate\Database\Schema\Blueprint
     */
    public function drop($table)
    {
        $this->helper->dropAutoIncrementObjects($table);
        parent::drop($table);
    }

    /**
     * Drop all tables from the database.
     *
     * @return void
     */
    public function dropAllTables()
    {
        // $this->connection->statement($this->grammar->compileDropAllTables());

        foreach ($this->getAllTables() as $table) {
            $this->drop($table);
        }
    }

    public function getAllTables()
    {
        $results = $this->connection->select($this->grammar->compileGetAllTables());
        $tables = [];
        foreach ($results as $result) {
            $tables[] = $result->TABLE_NAME;
        }
        return $tables;
    }

    /**
     * Indicate that the table should be dropped if it exists.
     *
     * @param  string  $table
     */
    public function dropIfExists($table)
    {
        $this->helper->dropAutoIncrementObjects($table);
//        parent::dropIfExists($table);
        if ($this->hasTable($table)) {
            $this->drop($table);
        }
    }

    /**
     * Determine if the given table exists.
     *
     * @param  string  $table
     * @return bool
     */
    public function hasTable($table)
    {
        $grammar = $this->grammar;
        $sql = $grammar->compileTableExists();

        $database = $this->connection->getConfig('database');
        if ($this->connection->getConfig('prefix_schema')) {
            $database = $this->connection->getConfig('prefix_schema');
        }
        $table = $this->connection->getTablePrefix().$table;

        return count($this->connection->select($sql, [$database, $table])) > 0;
    }

    /**
     * Get the column listing for a given table.
     *
     * @param  string  $table
     * @return array
     */
    public function getColumnListing($table)
    {
        $database = $this->connection->getConfig('database');
        $table = $this->connection->getTablePrefix().$table;
        $grammar = $this->grammar;
        $results = $this->connection->select($grammar->compileColumnExists($database, $table));

        return $this->connection->getPostProcessor()->processColumnListing($results);
    }
}
