<h2><a href="http://finance-app/">На главную</a></h2>
<?php
if (isset($data['errors'])) {
    echo "<div style='border: 1px solid black; border-radius: 10px; padding: 10px; margin-bottom: 10px'>";
    echo "<p>Возникли следующие ошибки:</p>";
    foreach ($data['errors'] as $error) {
        echo sprintf("<p>%s</p>", $error);
    }
    echo "</div>";
}
?>
<form action='http://finance-app/account/authorize' method='POST'>
    <label for='username'>Логин:</label>
    <input type='text' name='username' />
    <label for='password'>Пароль:</label>
    <input type='password' name='password' />
    <input type='submit' />
</form>