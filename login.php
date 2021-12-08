<?php
    session_start();

    if ($_SESSION['current_user']) {
        header('Location: http://exp/');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $errors = array();

        if ($_POST['login'] === "") {
            $errors['login'] = 'Логін не може бути порожнім!';
        }

        if ($_POST['password'] === "") {
            $errors['password'] = 'Пароль не може бути порожнім!';
        }

        if (count($errors) > 0) {
            $GLOBALS['validation_errors'] = $errors;
        } else {
            require_once 'db.php';

            $result = $conn->query("
                SELECT * FROM users WHERE login = '" . $_POST['login'] . "' AND password = '" . $_POST['password'] . "'
            ");


            if ($result->num_rows > 0) {
                $current_user = (array)$result->fetch_object();
                $_SESSION['current_user'] = $current_user;
                header('Location: /');
            } else {
                $GLOBALS['login_error'] = 'Неправильний логін або пароль!';
            }

            $conn->close();
        }
    }
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/login.css">
    <title>Php Labs</title>
</head>
<body>
    <header>
        <h1><a href="/">PHP Labs</a></h1>

        <div class="buttons">
            <button id="auth-btn" onclick="location.href='register.php'">Зареєструватися</button>
        </div>
    </header>

    <main>
        <form action="login.php" method="post">
            <?php
                echo "<fieldset><label>Електронна пошта</label><input type=\"text\" name=\"login\" value=\"{$_POST['login']}\">";
                if (isset($GLOBALS['validation_errors']['login'])) {
                    echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['login'] . "</p>";
                }
                echo "</fieldset>";

                echo "<fieldset><label>Пароль</label><input type=\"password\" name=\"password\" value=\"{$_POST['password']}\">";
                if (isset($GLOBALS['validation_errors']['password'])) {
                    echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['password'] . "</p>";
                }
                echo "</fieldset>";
            ?>

            <button>Увійти</button>

            <?php
                if (isset($GLOBALS['login_error'])) {
                    echo "<p class='validation_error'>" . $GLOBALS['login_error'] . "</p>";
                }
            ?>
        </form>
    </main>

    <footer>
        Something about Lorem ipsum dolor sit amet, consectetur adipisicing elit. Esse isteerror, optio porro praesentium quibusdam reiciendis reprehenderit, saepe suscipit.
    </footer>

</body>
</html>