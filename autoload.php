<?php

spl_autoload_register(function ($class) {
    $classPath = str_replace('\\', '/', $class);
    include './src/' . $classPath . '.php';
});
