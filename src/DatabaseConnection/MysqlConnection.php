<?php

namespace DatabaseConnection;

use PDO;
use PDOException;

class MysqlConnection
{
    private const DSN = 'mysql:host=db;dbname=finance-app';
    private const MYSQL_USERNAME = 'root';
    private const MYSQL_PASSWORD = 'password';

    public function getConnection(): PDO
    {
        return new PDO(self::DSN, self::MYSQL_USERNAME, self::MYSQL_PASSWORD);
    }
}
