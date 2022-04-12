<?php

namespace Repository;

use DatabaseConnection\MysqlConnection;
use Entity\Account;

class AccountRepository
{
    /**
     * @var PDO
     */
    protected $connection;

    public function __construct(MysqlConnection $mysqlConnection)
    {
        $this->connection = $mysqlConnection->getConnection();
    }

    /**
     * Found Account by username
     *
     * @param string $username
     * @return stdclass|false
     */
    public function getAccountByUsername(string $username)
    {
        $statement = $this
            ->connection
            ->prepare("
                SELECT * FROM account WHERE username = :username;
            ");

        $statement->bindValue(':username', $username);
        $statement->execute();

        return $statement->fetch();
    }

    /**
     * Found Account by access token
     *
     * @return stdclass|false
     */
    public function getAccountByAccessToken(string $accessToken)
    {
        $statement = $this
            ->connection
            ->prepare("
                SELECT * FROM account WHERE access_token = :accessToken;
            ");

        $statement->bindValue(':accessToken', $accessToken);
        $statement->execute();

        return $statement->fetch();
    }

    /**
     * @param string|null $accessToken
     * @param string $username
     * @return bool
     */
    public function setAccessTokenByUsername(?string $accessToken, string $username)
    {
        $statement = $this
            ->connection
            ->prepare("
                UPDATE account SET access_token = :accessToken WHERE username = :username;
            ");

        $statement->bindValue(':accessToken', $accessToken);
        $statement->bindValue(':username', $username);
        $statement->execute();

        return true;
    }

    /**
     * Withdraw money using repository with transaction in Serialaziable isolation level
     *
     * @param string $accessToken
     * @param string $withdrawAmount
     * @return bool
     */
    public function withdrawFromBalanceByAccessToken(string $accessToken, string $withdrawAmount)
    {
        $this
            ->connection
            ->query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE; START TRANSACTION;")
            ->execute();

        $balanceStatement = $this
            ->connection
            ->prepare("
                SELECT balance FROM account WHERE access_token = :accessToken;
            ");

        $balanceStatement->bindValue(':accessToken', $accessToken);
        $balanceStatement->execute();
        $balance = $balanceStatement->fetchColumn();

        $newBalance = (float) bcsub($balance, $withdrawAmount, 2);

        if ($newBalance < 0) {
            $this
                ->connection
                ->query("ROLLBACK;")
                ->execute();

            return false;
        }

        $statement = $this
            ->connection
            ->prepare("
                UPDATE account SET balance = :balance WHERE access_token = :accessToken;
            ");

        $statement->bindValue(':balance', $newBalance);
        $statement->bindValue(':accessToken', $accessToken);
        $statement->execute();

        $this
            ->connection
            ->query("COMMIT;")
            ->execute();

        return true;
    }
}
