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

        $account = new Account();

        $account->id = $databaseResult['id'];
        $account->username = $databaseResult['username'];
        $account->password = $databaseResult['password'];
        $account->balance = $databaseResult['balance'];
        $account->accessToken = $databaseResult['access_token'];

        return $account;
    }
}
