<?php

namespace Oh86\LaravelYashan\DBAL;
use Oh86\LaravelYashan\DBAL\YSPlatform;
use Oh86\LaravelYashan\DBAL\YSSchemaManager;

class YSDriver implements \Doctrine\DBAL\Driver
{

    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        return new \Doctrine\DBAL\Driver\PDOConnection(
            $this->_constructPdoDsn($params),
            $username,
            $password,
            $driverOptions
        );
    }

    /**
     * Constructs the PDO DSN.
     *
     * @return string  The DSN.
     */
    private function _constructPdoDsn(array $params)
    {
        $dsn = 'dm:';
        if (isset($params['host'])) {
            $dsn .= 'dbname=(DESCRIPTION=(ADDRESS_LIST=(ADDRESS=(PROTOCOL=TCP)' .
            '(HOST=' . $params['host'] . ')';

            if (isset($params['port'])) {
                $dsn .= '(PORT=' . $params['port'] . ')';
            } else {
                $dsn .= '(PORT=5236)';
            }

            if (isset($params['service']) && $params['service'] == true) {
                $dsn .= '))(CONNECT_DATA=(SERVICE_NAME=' . $params['dbname'] . ')))';
            } else {
                $dsn .= '))(CONNECT_DATA=(SID=' . $params['dbname'] . ')))';
            }
        } else {
            $dsn .= 'dbname=' . $params['dbname'];
        }

        if (isset($params['charset'])) {
            $dsn .= ';charset=' . $params['charset'];
        }

        return $dsn;
    }

    public function getDatabasePlatform()
    {
        return new YSPlatform();
    }

    public function getSchemaManager(\Doctrine\DBAL\Connection $conn)
    {
        return new YSSchemaManager($conn);
    }

    public function getName()
    {
        return 'yashan';
    }

    public function getDatabase(\Doctrine\DBAL\Connection $conn)
    {
        $params = $conn->getParams();
        return $params['dbname'];
    }
}
