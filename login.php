<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>login</title>
    <link rel="stylesheet" href="css/login.css">
</head>

<body>
    <div class="glass-wrapper">
        <form action="loginValidation.php" method="post" class="glass-form">
            <label for="name">Имя</label>
            <input type="text" name="name" placeholder="Введите имя" required>
            <label for="surname">Фамилия</label>
            <input type="text" name="surname" placeholder="Введите фамилия" required>
            <label for="password">Пароль</label>
            <input type="text" name="password" placeholder="Введите пароль" required>
            <button type="submit" class="btn primary">Войти</button>
        </form>
    </div>

</body>

</html>