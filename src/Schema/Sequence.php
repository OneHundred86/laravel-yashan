<?php

namespace Oh86\LaravelYashan\Schema;

use Illuminate\Database\Connection;
use Oh86\LaravelYashan\YSConnection;

class Sequence
{
    /**
     * @var YSConnection
     */
    protected $connection;

    /**
     * @param  Connection  $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function create($name, $start = 1, $increment = 1)
    {
        if (! $name) {
            return false;
        }

        $sql = sprintf('CREATE SEQUENCE "%s" START WITH %s INCREMENT BY %s', $name, $start, $increment);
        return $this->connection->statement($sql);
    }

    /**
     * function to safely drop sequence db object.
     *
     * @param  string  $name
     * @return bool
     */
    public function drop($name)
    {
        // check if a valid name and sequence exists
        if (! $name || ! $this->exists($name)) {
            return false;
        }

        return $this->connection->statement(sprintf('DROP SEQUENCE "%s"', $name));
    }

    /**
     * function to check if sequence exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function exists($name)
    {
        $database = $this->connection->getDatabaseName();
        return (bool)$this->connection->selectOne(
            "SELECT * FROM ALL_SEQUENCES WHERE SEQUENCE_OWNER = '{$database}' AND SEQUENCE_NAME = '{$name}'"
        );
    }

    /**
     * get sequence next value.
     *
     * @param  string  $name
     * @return int
     */
//    public function nextValue($name)
//    {
//        if (! $name) {
//            return 0;
//        }
//
//        $name = $this->wrap($name);
//
//        return $this->connection->selectOne("SELECT $name.NEXTVAL as \"id\" FROM DUAL")->id;
//    }

    /**
     * same function as lastInsertId. added for clarity with oracle sql statement.
     *
     * @param  string  $name
     * @return int
     */
//    public function currentValue($name)
//    {
//        return $this->lastInsertId($name);
//    }
//
//    /**
//     * function to get oracle sequence last inserted id.
//     *
//     * @param  string  $name
//     * @return int
//     */
//    public function lastInsertId($name)
//    {
//        // check if a valid name and sequence exists
//        if (! $name || ! $this->exists($name)) {
//            return 0;
//        }
//
//        $name = $this->wrap($name);
//
//        return $this->connection->selectOne("select {$name}.currval as \"id\" from dual")->id;
//    }
}
