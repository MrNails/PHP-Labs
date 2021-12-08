<?php
    session_start();
    require 'vendor/autoload.php';

    if ($_SESSION['current_user']) {
        header('Location: http://exp/');
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === "POST") {
        $errors = array();

        if (mb_strlen($_POST['firstname']) < 3 || mb_strlen($_POST['firstname']) > 20) {
            $errors['firstname'] = 'Ім\'я повинно бути не менше 3 і не більше 20 символів!';
        }

        if (mb_strlen($_POST['secondname']) < 3 || mb_strlen($_POST['secondname']) > 20) {
            $errors['secondname'] = 'Прізвище повинно бути не менше 3 і не більше 20 символів!';
        }

        if (!preg_match('/\w+@\w+\.\w+/', $_POST['login'])) {
            $errors['login'] = 'Неправильний формат електронної пошти!';
        }

        if (mb_strlen($_POST['password']) < 8 || mb_strlen($_POST['password']) > 20) {
            $errors['password'] = 'Пароль повинен бути не менше 8 і не більше 20 символів';
        }


        if (count($errors) > 0) {
            $GLOBALS['validation_errors'] = $errors;
        } else {
            require_once 'db.php';

            if ($conn->query("SELECT * FROM email_confirm WHERE user_email = '" . $_POST['login'] . "';")->num_rows > 0) {
                $GLOBALS['register_error'] = 'На дану пошту вже відправлене підтвердження!';
            } else if ($conn->query("SELECT * FROM users WHERE login = '" . $_POST['login'] . "';")->num_rows > 0) {
                $GLOBALS['register_error'] = 'На дану пошту вже зареєстрований користувач!';
            } else {
                $confirm_code = random_int(100000, 999999);

                $conn->query("
                    INSERT INTO email_confirm(user_firstname, user_secondname, user_email, user_password, send_date, confirm_code) 
                        VALUES('{$_POST['firstname']}', '{$_POST['secondname']}', '{$_POST['login']}', '{$_POST['password']}', NOW(), '{$confirm_code}');
                ");


                $email = new \SendGrid\Mail\Mail();
                $email->setFrom(getenv('FROM_EMAIL'), getenv('FROM_NAME'));
                $email->setSubject("Підтвердження реєстрації");
                $email->addTo("yra5176@gmail.com");
                $email->addTo($_POST['login']);
                $email->addContent("text/plain",
                    'Привіт, Вам необхідно перейти за посиланням, щоб підтвердити реєстрацію! '
                    . $_SERVER['HTTP_ORIGIN'] . '/confirm.php?confirm_code='
                    . $confirm_code . '&email=' . $_POST['login']
                );
                $sendgrid = new \SendGrid(getenv('SENDGRID_API_KEY'));
                try {
                    $response = $sendgrid->send($email);
                    $GLOBALS['register_success'] = 'На дану пошту надіслано лист для підтвердження!';
                } catch (Exception $e) {
                    $GLOBALS['register_error'] = 'Неможливо відправити повідомлення!';
                }
            }
        }

        $conn->close();
    }
?>

    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport"
              content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <link rel="stylesheet" href="css/register.css">
        <title>Php Labs</title>
    </head>
    <body>
    <header>
        <h1><a href="/">PHP Labs</a></h1>

        <div class="buttons">
            <button id="auth-btn" onclick="location.href='login.php'">Увійти</button>
        </div>
    </header>

    <main>
        <form action="register.php" method="post">
            <?php
            echo "<fieldset><label>Ім'я</label><input type=\"text\" name=\"firstname\" value=\"{$_POST['firstname']}\">";
            if (isset($GLOBALS['validation_errors']['firstname'])) {
                echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['firstname'] . "</p>";
            }
            echo "</fieldset>";

            echo "<fieldset><label>Прізвище</label><input type=\"text\" name=\"secondname\" value=\"{$_POST['secondname']}\">";
            if (isset($GLOBALS['validation_errors']['secondname'])) {
                echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['secondname'] . "</p>";
            }
            echo "</fieldset>";

            echo "<fieldset><label>Електронна пошта</label><input type=\"text\" name=\"login\" value=\"{$_POST['login']}\">";
            if (isset($GLOBALS['validation_errors']['login'])) {
                echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['login'] . "</p>";
            }
            echo "</fieldset>";

            echo "<fieldset><label>Пароль</label><input type=\"password\" name=\"password\" value=\"{$_POST['password']}\">";
            if (isset($GLOBALS['validation_errors']['password'])) {
                echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['password'] . "</p>";
            }
            echo "</fieldset><button class='register-btn'>Зареєструватись</button>";

            if (isset($GLOBALS['register_error'])) {
                echo "<p class='validation_error'>" . $GLOBALS['register_error'] . "</p>";
            } else if ($GLOBALS['register_success']) {
                echo "<p class='validation_success'>" . $GLOBALS['register_success'] . "</p>";
            }
            ?>
        </form>
    </main>

    <footer>
        Something about Lorem ipsum dolor sit amet, consectetur adipisicing elit. Esse isteerror, optio porro
        praesentium quibusdam reiciendis reprehenderit, saepe suscipit.
    </footer>
</body>
</html>

<?php
    session_start();
?>