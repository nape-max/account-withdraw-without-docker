<?php

namespace Mapper;

use Entity\Account;

class AccountMapper
{
    public function databaseResultToEntity($databaseResult): ?Account
    {
        if ($databaseResult === false) {
            return null;
        }

        $account = (new Account())
            ->setId($databaseResult['id'])
            ->setUsername($databaseResult['username'])
            ->setPassword($databaseResult['password'])
            ->setBalance($databaseResult['balance'])
            ->setAccessToken($databaseResult['access_token']);

        return $account;
    }
}
