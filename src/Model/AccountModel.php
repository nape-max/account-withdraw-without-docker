<?php

namespace Model;

use Entity\Account;
use Mapper\AccountMapper;
use DatabaseConnection\MysqlConnection;

class AccountModel
{
    private $connection;

    public function __construct(MysqlConnection $connection)
    {
        $this->connection = $connection->getConnection();
    }

    public function getUserByUsername($username): ?Account
    {
        $statement = $this
            ->connection
            ->prepare("
                SELECT * FROM account WHERE username = :username;
            ");

        $statement->bindValue(':username', $username);
        $statement->execute();

        $result = $statement->fetch();

        $mapper = new AccountMapper();

        $account = $mapper->databaseResultToEntity($result);

        return $account;
    }

    public function getUserByAccessToken($accessToken): ?Account
    {
        $statement = $this
            ->connection
            ->prepare("
                SELECT * FROM account WHERE access_token = :accessToken;
            ");

        $statement->bindValue(':accessToken', $accessToken);
        $statement->execute();

        $result = $statement->fetch();

        $mapper = new AccountMapper();

        $account = $mapper->databaseResultToEntity($result);

        return $account;
    }

    public function setAccessTokenByUsername($username, $accessToken)
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

    public function authorizeUser(Account $user)
    {
        $isVerified = $this->verifyUser($user);

        if ($isVerified) {
            $accessToken = bin2hex($user->username . random_bytes(36));

            if ($this->setAccessTokenByUsername($user->username, $accessToken)) {
                return $accessToken;
            }
        }

        return false;
    }

    public function isAllowWithdraw(Account $userByUsername, Account $userByAccessToken)
    {
        $isVerified = $this->verifyUser($userByUsername);

        if ($isVerified !== false && $userByUsername->accessToken === $userByAccessToken->accessToken) {
            return true;
        }

        return false;
    }

    public function verifyUser(Account $user)
    {
        if (true === password_verify($_POST['password'], $user->password)) {
            return true;
        }

        return false;
    }

    public function withdrawFromBalanceByAccessToken($accessToken, $withdrawAmount)
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

        $newBalance = $balance - $withdrawAmount;

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

        sleep(1);

        $this
            ->connection
            ->query("COMMIT;")
            ->execute();

        return true;
    }

    public function isAuthorized()
    {
        if (!isset($_COOKIE['access_token'])) {
            return false;
        }

        $user = $this->getUserByAccessToken($_COOKIE['access_token']);
        if (null === $user) {
            return false;
        }

        return true;
    }
}
