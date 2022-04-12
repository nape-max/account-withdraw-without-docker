<?php

namespace Controller;

use DatabaseConnection\MysqlConnection;
use Exception;
use View\View;
use Model\AccountModel;
use PDOException;

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
            setcookie('is_authorize_failure', 1, 0, "/account/authorize", "finance-app", false, true);
        }

        $view = new View();

        $view->generate('Account', 'Authorize/AuthorizeView', $data);
    }

    public static function logoutAction()
    {
        if (isset($_COOKIE['access_token'])) {
            $accountModel = new AccountModel(new MysqlConnection());
            $user = $accountModel->getUserByAccessToken($_COOKIE['access_token']);

            if ($accountModel->setAccessTokenByUsername($user->username, null)) {
                setcookie('access_token', false);
            }
        }

        header("Location: /account/authorize");
    }

    public static function authorizeActionPost()
    {
        $accountModel = new AccountModel(new MysqlConnection());

        $user = $accountModel->getUserByUsername($_POST['username']);

        if (null !== $user) {
            $accessToken = $accountModel->authorizeUser($user);

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
        self::checkIsAuthorized();

        $data = [];

        if (isset($_COOKIE['is_withdraw_failed'])) {
            $data['errors'][] = "Недостаточно средств на счету.";
            setcookie('is_withdraw_failed', false, 0, "/account", "finance-app", false, true);
        }

        $accountModel = new AccountModel(new MysqlConnection());
        $view = new View();

        $user = $accountModel->getUserByAccessToken($_COOKIE['access_token']);

        $data['username'] = $user->username;
        $data['balance'] = $user->balance;

        $view->generate('Account', 'AccountView', $data);
    }

    public static function withdrawFromBalanceAction()
    {
        self::checkIsAuthorized();

        $accountModel = new AccountModel(new MysqlConnection());

        $userByAccessToken = $accountModel->getUserByAccessToken($_COOKIE['access_token']);
        $userByUsername = $accountModel->getUserByUsername($_POST['username']);

        if (null !== $userByUsername) {
            if ($accountModel->isAllowWithdraw($userByUsername, $userByAccessToken)) {
                $isWithdrawed = $accountModel->withdrawFromBalanceByAccessToken($userByAccessToken->accessToken, $_POST['amount']);

                if ($isWithdrawed) {
                    header("Location: /account");
                    exit;
                } else {
                    setcookie('is_withdraw_failed', 1, 0, "/account", "finance-app", false, true);
                    header("Location: /account");
                }
            }
        }
    }

    public static function checkIsAuthorized()
    {
        $accountModel = new AccountModel(new MysqlConnection());
        $view = new View();

        if (!$accountModel->isAuthorized()) {
            setcookie('access_token', false, 0, "/", "finance-app", false, true);
            $view->generate('Account', 'NotAuthorized');

            exit;
        }
    }
};
