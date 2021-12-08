<?php
    session_start();

    if (!isset($_GET['confirm_code']) || !isset($_GET['email'])) {
        header('Location: http://exp/');
        exit();
    }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/confirm.css">
    <title>Php Labs</title>
</head>
<body>
    <nav class="navigation">
        <h1><a href="/">PHP Labs</a></h1>

        <div class="buttons">
            <?php
            if (isset($_SESSION['current_user'])) {
                echo "<button id=\"exit-btn\">Вийти</button>";
            } else {
                echo "
                    <button id=\"login-btn\" onclick=\"location.href='login.php'\">Увійти</button>
                    <button id=\"register-btn\" onclick=\"location.href='register.php'\">Зареєструватися</button>
                ";
            }
            ?>
        </div>
    </nav>

    <main>
        <?php
            require_once 'db.php';

            $rows = $conn->query("SELECT * FROM email_confirm WHERE user_email = '{$_GET['email']}' AND confirm_code = '{$_GET['confirm_code']}';");

            if ($rows->num_rows) {
                /* Указать mysqli выбрасывать исключение в случае возникновения ошибки */
                mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


                $current_user = $rows->fetch_object();

                $conn->query("SET autocommit=0;");
                $conn->query("SET SQL_SAFE_UPDATES = 0;");

                $conn->begin_transaction();
                try {
                    $conn->query("SET SQL_SAFE_UPDATES = 0;");
                    $conn->query("DELETE FROM php_labs.email_confirm WHERE user_email = '{$current_user->user_email}';");
                    $conn->query("SET SQL_SAFE_UPDATES = 1;");
                    $conn->query("
                        INSERT INTO php_labs.users(firstname, secondname, login, password) 
                            VALUES('{$current_user->user_firstname}', '{$current_user->user_secondname}', '{$current_user->user_email}', '{$current_user->user_password}');
                    ");

                    $conn->commit();
                    echo "<h2>Пошта {$current_user->user_email} підтверджена!</h2>";
                } catch (mysqli_sql_exception $exception) {
                    $conn->rollback();
                    throw $exception;
                }

                $conn->query("SET autocommit = 1;");
                $conn->query("SET SQL_SAFE_UPDATES = 1;");
            } else {
                echo "<h2>Помилка!</h2>";
            }

            $conn->close();
        ?>
    </main>
</body>
</html>