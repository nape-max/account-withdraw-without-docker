<?php

namespace Service;

use Entity\Account;
use Mapper\AccountMapper;
use Repository\AccountRepository;

class AccountService
{
    /**
     * @var AccountRepository
     */
    private $accountRepository;

    private $accountMapper;

    public function __construct(AccountRepository $accountRepository, AccountMapper $accountMapper)
    {
        $this->accountRepository = $accountRepository;
        $this->accountMapper = $accountMapper;
    }

    /**
     * Found account by username
     *
     * @param string $username
     * @return Account|null
     */
    public function getAccountByUsername(string $username): ?Account
    {
        $result = $this->accountRepository->getAccountByUsername($username);

        if ($result === false) {
            $account = null;
        } else {
            $account = $this->accountMapper->databaseResultToEntity($result);
        }

        return $account;
    }

    /**
     * Found account by access token
     *
     * @param string $accessToken
     * @return Account|null
     */
    public function getAccountByAccessToken(string $accessToken): ?Account
    {
        $result = $this->accountRepository->getAccountByAccessToken($accessToken);

        // echo $account->getAccessToken();
        // exit;

        if ($result === false) {
            $account = null;
        } else {
            $account = $this->accountMapper->databaseResultToEntity($result);
        }

        return $account;
    }

    /**
     * Set access token to Account with specified username
     *
     * @param string|null $accessToken
     * @param string $username
     * @return void
     */
    public function setAccessTokenByUsername(?string $accessToken, string $username)
    {
        return $this->accountRepository->setAccessTokenByUsername($accessToken, $username);
    }

    /**
     * Verify specfied password by password in database, set new access_token and return it.
     *
     * @param Account $user
     * @param string $passwordFromRequest
     * @return string|bool
     */
    public function authorizeUserByPassword(Account $user, string $passwordFromRequest)
    {
        $isVerified = $this->verifyUser($passwordFromRequest, $user);

        if ($isVerified) {
            $accessToken = bin2hex($user->getUsername() . random_bytes(36));

            if ($this->accountRepository->setAccessTokenByUsername($accessToken, $user->getUsername())) {
                return $accessToken;
            }
        }

        return false;
    }

    public function withdrawFromBalance(
        float $withdrawAmount,
        string $passwordFromRequest,
        Account $userByUsername,
        Account $userByAccessToken
    ): bool {
        if ($this->isAllowWithdraw($passwordFromRequest, $userByUsername, $userByAccessToken)) {
            return $this->accountRepository->withdrawFromBalanceByAccessToken($userByAccessToken->getAccessToken(), $withdrawAmount);
        }

        return false;
    }

    /**
     * Check permissions to access with specified access token
     *
     * @param string|null $accessToken
     * @return bool
     */
    public function isAuthorized(?string $accessToken)
    {
        if (null === $accessToken) {
            return false;
        }

        $account = $this->accountRepository->getAccountByAccessToken($accessToken);
        if (false === $account) {
            return false;
        }

        return true;
    }

    private function isAllowWithdraw(string $passwordFromRequest, Account $userByUsername, Account $userByAccessToken)
    {
        $isVerified = $this->verifyUser($passwordFromRequest, $userByUsername);

        if ($isVerified !== false && $userByUsername->getAccessToken() === $userByAccessToken->getAccessToken()) {
            return true;
        }

        return false;
    }

    private function verifyUser(string $passwordFromRequest, Account $user)
    {
        if (true === password_verify($passwordFromRequest, $user->getPassword())) {
            return true;
        }

        return false;
    }
}
