<?php

namespace Controller;

use DatabaseConnection\MysqlConnection;
use View\View;
use Service\AccountService;
use Repository\AccountRepository;
use Mapper\AccountMapper;

class AccountController
{
    public static function indexAction()
    {
        $view = new View();

        $view->generate('Index', 'MainView');
    }

    public static function authorizeAction()
    {
        $data = [];

        if (isset($_COOKIE['access_token'])) {
            header("Location: /account");
            exit;
        }

        if (isset($_COOKIE['is_authorize_failure'])) {
            $data['errors'][] = "Введенное имя пользователя или пароль не совпадают, проверьте введенные данные.";
            setcookie('is_authorize_failure', false, 0, "/account/authorize", "finance-app", false, true);
        }

        $view = new View();

        $view->generate('Account', 'Authorize/AuthorizeView', $data);
    }

    public static function logoutAction()
    {
        if (isset($_COOKIE['access_token'])) {
            $accountService = new AccountService(new AccountRepository(new MysqlConnection()), new AccountMapper());
            $account = $accountService->getAccountByAccessToken($_COOKIE['access_token']);

            if ($accountService->setAccessTokenByUsername(null, $account->getUsername())) {
                setcookie('access_token', false);
            }
        }

        header("Location: /account/authorize");
    }

    public static function authorizeActionPost()
    {
        $accountService = new AccountService(new AccountRepository(new MysqlConnection()), new AccountMapper());

        $usernameFromRequest = $_POST['username'];
        $passwordFromRequest = $_POST['password'];

        $account = $accountService->getAccountByUsername($usernameFromRequest);

        if (null !== $account) {
            $accessToken = $accountService->authorizeUserByPassword($account, $passwordFromRequest);

            if ($accessToken !== false) {
                header("Location: /account");
                setcookie('access_token', $accessToken, 0, "/", "finance-app", false, true);

                exit;
            }
        }

        header("Location: /account/authorize");
        setcookie('is_authorize_failure', 1, 0, "/account/authorize", "finance-app", false, true);
    }

    public static function getProfile()
    {
        self::checkIsAuthorized($_COOKIE['access_token']);

        $data = [];

        if (isset($_COOKIE['is_withdraw_failed'])) {
            $data['errors'][] = "Недостаточно средств на счету.";
            setcookie('is_withdraw_failed', false, 0, "/account", "finance-app", false, true);
        }

        if (isset($_COOKIE['is_amount_wrong'])) {
            $data['errors'][] = "Неверное значение суммы к списанию. Необходимо ввести положительное число. Примеры: 500; 500.0; 500.05; 500,05";
            setcookie('is_amount_wrong', false, 0, "/account", "finance-app", false, true);
        }

        $accountService = new AccountService(new AccountRepository(new MysqlConnection()), new AccountMapper());
        $view = new View();

        $account = $accountService->getAccountByAccessToken($_COOKIE['access_token']);

        $data['username'] = $account->getUsername();
        $data['balance'] = (string) $account->getBalance();

        $view->generate('Account', 'AccountView', $data);
    }

    public static function withdrawFromBalanceAction()
    {
        self::checkIsAuthorized($_COOKIE['access_token']);

        $accessTokenFromCookie = $_COOKIE['access_token'];

        $usernameFromRequest = $_POST['username'];
        $passwordFromRequest = $_POST['password'];
        $amountFromRequest = $_POST['amount'];

        $amountFromRequest = str_replace(',', '.', $_POST['amount']);

        if (preg_match('/^[+]?[0-9]+([.][0-9]{1,2})?$/', $amountFromRequest) === 0) {
            header("Location: /account");
            setcookie('is_amount_wrong', 1, 0, "/account", "finance-app", false, true);
        }

        $accountService = new AccountService(new AccountRepository(new MysqlConnection()), new AccountMapper());

        $accountByAccessToken = $accountService->getAccountByAccessToken($accessTokenFromCookie);
        $accountByUsername = $accountService->getAccountByUsername($usernameFromRequest);

        if (null !== $accountByUsername) {
            if ($accountService->withdrawFromBalance($amountFromRequest, $passwordFromRequest, $accountByUsername, $accountByAccessToken)) {
                header("Location: /account");
                sleep(1); // Чтобы страница с успехом отображалась позже и на обоих страницах не было ошибки "Не удалось списать средства из-за куки установленной на другой странице", не влияет на логику - просто недоработка механизма вывода ошибок на фронтенд и общения между View.
                setcookie('is_withdraw_failed', false, 0, "/account", "finance-app", false, true);
            } else {
                setcookie('is_withdraw_failed', 1, 0, "/account", "finance-app", false, true);
                header("Location: /account");
            }
        }
    }

    public static function checkIsAuthorized(?string $accessToken)
    {
        $accountService = new AccountService(new AccountRepository(new MysqlConnection()), new AccountMapper());
        $view = new View();

        if (!$accountService->isAuthorized($accessToken)) {
            setcookie('access_token', false, 0, "/", "finance-app", false, true);
            $view->generate('Account', 'NotAuthorized');

            exit;
        }
    }
};
