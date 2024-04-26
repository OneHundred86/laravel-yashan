<?php

namespace Oh86\LaravelYashan\Schema;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;

class YSAutoIncrementHelper
{
    /**
     * @var \Illuminate\Database\Connection
     */
    protected $connection;

    /**
     * @var Trigger
     */
    protected $trigger;

    /**
     * @var Sequence
     */
    protected $sequence;

    /**
     * @param  \Illuminate\Database\Connection  $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->sequence = new Sequence($connection);
        $this->trigger = new Trigger($connection);
    }

    /**
     * create sequence and trigger for autoIncrement support.
     *
     * @param  Blueprint  $blueprint
     * @param  string  $table
     */
    public function createAutoIncrementObjects(Blueprint $blueprint, $table)
    {
        $column = $this->getQualifiedAutoIncrementColumn($blueprint);

        // return if no qualified AI column
        if (is_null($column)) {
            return;
        }

        $col = $column->name;
        $start = isset($column->start) ? $column->start : 1;

        // get table prefix
        $prefix = $this->connection->getTablePrefix();

        // create sequence for auto increment
        $sequenceName = Sequence::genName($prefix.$table, $col);
        $this->sequence->create($sequenceName, $start);

        // 序列作用到表的列
        $sql = sprintf('ALTER TABLE "%s" MODIFY "%s" DEFAULT "%s".NEXTVAL', $table, $col, $sequenceName);
        $this->connection->statement($sql);
    }

    /**
     * Get qualified autoincrement column.
     *
     * @param  Blueprint  $blueprint
     * @return \Illuminate\Support\Fluent|null
     */
    public function getQualifiedAutoIncrementColumn(Blueprint $blueprint)
    {
        $columns = $blueprint->getColumns();

        // search for primary key / autoIncrement column
        foreach ($columns as $column) {
            // if column is autoIncrement set the primary col name
            if ($column->autoIncrement) {
                return $column;
            }
        }
    }

    /**
     * Drop sequence and triggers if exists, autoincrement objects.
     *
     * @param  string  $table
     */
    public function dropAutoIncrementObjects($table)
    {
        // drop sequence and trigger object
        $prefix = $this->connection->getTablePrefix();
        // get the actual primary column name from table
        $col = $this->getPrimaryKey($prefix.$table);
        // var_dump($table, $col);
        // if primary key col is set, drop auto increment objects
        if (isset($col) && ! empty($col)) {
            // drop sequence for auto increment
            $sequenceName = Sequence::genName($prefix.$table, $col);

            if ($this->sequence->exists($sequenceName)) {
                $this->sequence->drop($sequenceName);
            }
        }
    }

    /**
     * Get table's primary key.
     *
     * @param  string  $table
     * @return string
     */
    public function getPrimaryKey($table)
    {
        if (! $table) {
            return '';
        }

        $database = $this->connection->getDatabaseName();

        $sql = "SELECT cols.COLUMN_NAME
            FROM all_constraints cons, all_cons_columns cols
            WHERE cons.OWNER = cols.OWNER AND cons.CONSTRAINT_NAME = cols.CONSTRAINT_NAME
            AND cons.TABLE_NAME = cols.TABLE_NAME
            AND cons.CONSTRAINT_TYPE = 'P'
            AND cons.OWNER = '{$database}' AND cols.TABLE_NAME = '{$table}'";

        $data = $this->connection->selectOne($sql);

        if ($data) {
            return $data->COLUMN_NAME;
        }

        return '';
    }

    /**
     * Get sequence instance.
     *
     * @return Sequence
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Set sequence instance.
     *
     * @param  Sequence  $sequence
     */
    public function setSequence($sequence)
    {
        $this->sequence = $sequence;
    }

    /**
     * Get trigger instance.
     *
     * @return Trigger
     */
    public function getTrigger()
    {
        return $this->trigger;
    }

    /**
     * Set the trigger instance.
     *
     * @param  Trigger  $trigger
     */
    public function setTrigger($trigger)
    {
        $this->trigger = $trigger;
    }
}
