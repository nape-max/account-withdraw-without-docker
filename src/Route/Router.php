<?php

namespace Route;

use Controller\AccountController;
use Route\Request;

class Router
{
    public static function start(Request $request)
    {
        $currentNode = self::controllers();

        foreach ($request->getSplittedRoute() as $routeKey) {
            if (isset($currentNode[$routeKey])) {
                $currentNode = $currentNode[$routeKey];
            } else {
                self::ErrorPage404();
            }
        }

        if (!isset($currentNode[$request->getMethod()])) {
            self::ErrorPage405();
        } else {
            $currentNode = $currentNode[$request->getMethod()];

            $currentNode();
        }
    }

    private static function ErrorPage404()
    {
        http_response_code(404);
        exit;
    }

    private static function ErrorPage405()
    {
        http_response_code(405);
        exit;
    }

    private static function controllers()
    {
        return [
            '' => [
                '' => [
                    '' => [
                        'GET' => function () {
                            return AccountController::indexAction();
                        }
                    ]
                ],
                'account' => [
                    '' => [
                        'GET' => function () {
                            return AccountController::getProfile();
                        }
                    ],
                    'authorize' => [
                        '' => [
                            'GET' => function () {
                                return AccountController::authorizeAction();
                            },
                            'POST' => function () {
                                return AccountController::authorizeActionPost();
                            }
                        ]
                    ],
                    'logout' => [
                        '' => [
                            'GET' => function () {
                                return AccountController::logoutAction();
                            }
                        ]
                    ],
                    'withdraw' => [
                        '' => [
                            'POST' => function () {
                                return AccountController::withdrawFromBalanceAction();
                            }
                        ]
                    ]
                ]
            ],
        ];
    }
}
