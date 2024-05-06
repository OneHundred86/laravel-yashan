<?php

namespace Oh86\LaravelYashan\Query\Processors;

use DateTime;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Support\Carbon;
use Oh86\LaravelYashan\Schema\Sequence;
use PDO;

class YSProcessor extends Processor
{
    /**
     * Process the results of a "select" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        // var_dump(__METHOD__, get_class($query), $results);

        foreach ($results as &$obj) {
            foreach ($obj as $key => $val) {
                if ($this->handleTimestampValue($query, $obj, $key, $val)){
                    continue;
                }

                if ($this->handleNumericValue($query, $obj, $key, $val)){
                    continue;
                }
            }
        }

        return $results;
    }

    /**
     * 处理timestamp值
     * @param Builder $query
     * @param $obj
     * @param $key
     * @param $val
     * @return bool
     */
    protected function handleTimestampValue(Builder $query, $obj, $key, $val): bool
    {
        $dateFormat = $query->getGrammar()->getDateFormat();
        // 固定处理created_at、updated_at和deleted_at这三列数据
        $timestampColumns = ["created_at", "updated_at", "deleted_at"];

        if (in_array($key, $timestampColumns)) {
            $obj->$key = Carbon::parse($val)->format($dateFormat);
            return true;
        }

        return false;
    }

    /**
     * 处理数字值
     * @param Builder $query
     * @param $obj
     * @param $key
     * @param $val
     * @return bool
     */
    protected function handleNumericValue(Builder $query, $obj, $key, $val): bool
    {
        if (is_numeric($val)) {
            if ($this->isFloat($val)){
                $obj->$key = doubleval($val);
            }else {
                $obj->$key = intval($val);
            }

            return true;
        }

        return false;
    }

    protected function isFloat($val): bool
    {
        return (bool) preg_match('/^-?\d+\.\d+$/', $val);
    }

    /**
     * Process an "insert get ID" query.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        // var_dump($sql, $values, $sequence);
        // die();

        $connection->insert($sql, $values);

        // if ($sequence && !isset($values[$sequence])) {
        if ($sequence) {
            $pos = $this->getPropertyPosOfInsertSql($sql, $sequence);

            // 没有指定自增列
            if ($pos === false) {
                $table = $query->from;
                $col = $sequence;

                $sequenceName = Sequence::genName($table, $col);
                $sequence = new Sequence($connection);
                return $sequence->lastInsertId($sequenceName);
            }else {
                return $values[$pos];
            }
        }

        return 0;
    }

    /**
     * @param string $sql
     * @param string $propertyName
     * @return false | int
     */
    private function getPropertyPosOfInsertSql(string $sql, string $propertyName)
    {
        if (preg_match('/\(.*?\)/', $sql, $m)){
            $propertyNamesSql = $m[0];

            if (preg_match_all('/"([\w-]+?)"/', $propertyNamesSql, $matches)) {
                $propertyNames = $matches[1];
                foreach ($propertyNames as $pos => $name) {
                    if ($propertyName == $name) {
                        return $pos;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Get prepared statement.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @return \PDOStatement|Statement
     */
    private function prepareStatement(Builder $query, $sql)
    {
        /** @var Oci8Connection $connection */
        $connection = $query->getConnection();
        $pdo = $connection->getPdo();

        return $pdo->prepare($sql);
    }

    /**
     * Bind values to PDO statement.
     *
     * @param  array  $values
     * @param  \PDOStatement  $statement
     * @param  int  $parameter
     * @return int
     */
    private function bindValues(&$values, $statement, $parameter)
    {
        $count = count($values);
        for ($i = 0; $i < $count; $i++) {
            if (is_object($values[$i])) {
                if ($values[$i] instanceof DateTime) {
                    $values[$i] = $values[$i]->format('Y-m-d H:i:s');
                } else {
                    $values[$i] = (string) $values[$i];
                }
            }
            $type = $this->getPdoType($values[$i]);
            $statement->bindParam($parameter, $values[$i], $type);
            $parameter++;
        }

        return $parameter;
    }

    /**
     * Get PDO Type depending on value.
     *
     * @param  mixed  $value
     * @return int
     */
    private function getPdoType($value)
    {
        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        return PDO::PARAM_STR;
    }

    /**
     * Save Query with Blob returning primary key value.
     *
     * @param  Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  array  $binaries
     * @return int
     */
    public function saveLob(Builder $query, $sql, array $values, array $binaries)
    {
        $connection = $query->getConnection();

        // $connection->recordsHaveBeenModified();
        $start = microtime(true);

        $id = 0;
        $parameter = 1;
        $statement = $this->prepareStatement($query, $sql);

        $parameter = $this->bindValues($values, $statement, $parameter);

        $countBinary = count($binaries);
        for ($i = 0; $i < $countBinary; $i++) {
            $statement->bindParam($parameter, $binaries[$i], PDO::PARAM_LOB, -1);
            $parameter++;
        }

        // bind output param for the returning clause.
        $statement->bindParam($parameter, $id, PDO::PARAM_INT, -1);

        if (! $statement->execute()) {
            return false;
        }

        $connection->logQuery($sql, $values, $start);

        return (int) $id;
    }

    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        $mapping = function ($r) {
            $r = (object) $r;

            return strtolower($r->COLUMN_NAME);
        };

        return array_map($mapping, $results);
    }
}
