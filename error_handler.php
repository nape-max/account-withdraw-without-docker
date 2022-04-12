<?php

set_exception_handler(function ($errorOrException) {
    echo "Что-то пошло не так, попробуйте еще раз позже. Ошибка: {$errorOrException->getMessage()}";
});
