<?php

namespace Route;

class Request
{
    private string $method;
    private string $route;
    private ?string $query;

    public function __construct()
    {
        $this->parseRoute();
    }

    public function parseRoute()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];

        $uriSplittedByQuerySymbol = explode('?', $_SERVER['REQUEST_URI']);

        $this->route = sprintf("/%s/", trim($uriSplittedByQuerySymbol[0], "\/"));
        $this->query = $uriSplittedByQuerySymbol[1];
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function getSplittedRoute()
    {
        return explode('/', $this->getRoute());
    }

    public function getQuery()
    {
        return $this->query;
    }
}
