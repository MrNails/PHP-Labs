<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === "POST") {
    $errors = array();

    switch ($_POST['action']) {
        case 'add':
            if (mb_strlen($_POST['add_name']) < 8 || mb_strlen($_POST['add_name']) > 100) $errors['add_name'] = 'Назва повинна бути від 8 до 100 символів!';
            if ($_POST['add_description'] === "") $errors['add_description'] = 'Опис не може бути порожнім!';
            if ($_POST['add_price'] === "") $errors['add_price'] = 'Ціна не може бути порожньою!';
            break;

        case 'edit':
            if ($_POST['edit_id'] === "" || (int)$_POST['edit_id'] <= 0) $errors['edit_id'] = 'Номер товару повинен бути більше 0!';
            if (mb_strlen($_POST['edit_name']) < 8 || mb_strlen($_POST['edit_name']) > 100) $errors['edit_name'] = 'Назва повинна бути від 8 до 100 символів!';
            if ($_POST['edit_description'] === "") $errors['edit_description'] = 'Опис не може бути порожнім!';
            if ($_POST['edit_price'] === "") $errors['edit_price'] = 'Ціна не може бути порожньою!';
            break;

        case 'remove':
            if ($_POST['remove_id'] === "" || (int)$_POST['remove_id'] <= 0) $errors['remove_id'] = 'Номер товару повинен бути більше 0!';
            break;
    }



    if (count($errors) > 0) {
        $GLOBALS['validation_errors'] = $errors;
    } else {
        require 'db.php';

        switch ($_POST['action']) {
            case 'add':
                if (
                    $conn->query("
                        INSERT INTO products(name, description, date_add, price, user_id) 
                            VALUES
                            (
                                '" . $_POST['add_name'] . "', 
                                '" . $_POST['add_description'] . "', 
                                NOW(), 
                                '" . (int)$_POST['add_price'] . "', 
                                '" . $_SESSION['current_user']['id'] . "'
                            );
                    ")
                ) {
                    unset($_POST['add_name']);
                    unset($_POST['add_description']);
                    unset($_POST['add_price']);

                    $GLOBALS['validation_success_add'] = 'Успішно додано!';
                } else {
                    $GLOBALS['validation_error_add'] = 'Не вдалося додати товар!';
                }

                break;

            case 'edit':
                if (
                    $conn->query("
                        UPDATE products 
                            SET 
                                name='" . $_POST['edit_name'] . "', 
                                description='" . $_POST['edit_description'] . "',
                                price='" . (int)$_POST['edit_price'] . "'
                            WHERE 
                                id='" . (int)$_POST['edit_id'] . "';
                    ") && $conn->affected_rows > 0
                ) {
                    unset($_POST['edit_id']);
                    unset($_POST['edit_name']);
                    unset($_POST['edit_description']);
                    unset($_POST['edit_price']);

                    $GLOBALS['validation_success_edit'] = 'Успішно змінено!';
                } else {
                    $GLOBALS['validation_error_edit'] = 'Не вдалося змінити товар!';
                }
                break;

            case 'remove':
                if ($conn->query("DELETE FROM products WHERE id='" . (int)$_POST['remove_id'] . "';") && $conn->affected_rows > 0) {
                    unset($_POST['remove_id']);

                    $GLOBALS['validation_success_remove'] = 'Успішно видалено!';
                } else {
                    $GLOBALS['validation_error_remove'] = 'Не вдалося видалити товар!';
                }
                break;
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
    <link rel="stylesheet" href="css/index.css">
    <title>Php Labs</title>
</head>
<body>
<header>
    <h1><a href="/">PHP Labs</a></h1>

    <div class="buttons">
        <?php
        if (isset($_SESSION['current_user'])) {
            echo "<button id='exit-btn' onclick='location.href=&quot;logout.php&quot;'>Вийти</button>";
        } else {
            echo "
                <button id='login-btn' onclick='location.href=&quot;login.php&quot;'>Увійти</button>
                <button id='register-btn' onclick='location.href=&quot;register.php&quot;'>Зареєструватися</button>
            ";
        }
        ?>
    </div>
</header>

<main>
    <?php
    require 'db.php';

    $products_count = (int)$conn->query("SELECT COUNT(*) FROM products;")->fetch_row()[0];
    $limit = (int)$_GET['limit'] ?: 3;
    $pages_count = ceil($products_count / $limit);
    $current_page = (int)$_GET['page'] ?: 1;
    $offset = ($current_page - 1) * $limit;



    $limit_query_param = isset($_GET['limit']) ? "limit=" . $_GET['limit'] : '';
    $page_query_param = isset($_GET['page']) ? "page=" . $_GET['page'] : '';
    $delimiter_query_param = $limit_query_param != '' && $page_query_param != '' ? '&' : '';
    $question_query_param = isset($_GET['limit']) || isset($_GET['page']) ? '?' : '';

    $result = $conn->query("
                SELECT products.id, products.name, products.description, products.price, products.date_add, CONCAT(users.firstname, ' ', users.secondname) as user
                    FROM products 
                    JOIN users ON products.user_id = users.id
                    LIMIT " . $offset . "," . $limit . ";
            ");


    echo "
        <table>
            <tr>
                <th width='3%'>№п\п</th>
                <th>Назва товару</th>
                <th>Опис</th>
                <th>Ціна</th>
                <th>Дата додання</th>
                <th>Користувач</th>
            </tr>
    ";
    foreach ($result as $row) {
        echo "
            <tr>
                <th>{$row['id']}</th>
                <th>{$row['name']}</th>
                <th>{$row['description']}</th>
                <th>{$row['price']}</th>
                <th>{$row['date_add']}</th>
                <th>{$row['user']}</th>
            </tr>
        ";
    }
    echo "</table>";
    ?>

    <div class="table-nav">
        <div class="table-nav__info">
            <?php
            $until = $current_page * $limit <= $products_count ? $current_page * $limit : $products_count;
            $from = $products_count > 0 ? ($current_page - 1) * $limit + 1 : 0;
            echo "Показано з " . $from . " по " . $until . ". Всього: " . $products_count . ".";
            ?>
        </div>

        <div class="table-nav__limit">
            <?php
            echo "
                <input type='number' id='limit-input' min='1' value='" . ((int)$_GET['limit'] ?: 3) . "'>
                <button id='limit-btn'>Зберегти значення</button>
            ";
            ?>
        </div>

        <div class="table-nav__pages">
            <?php
            $limit_temp = isset($_GET['limit']) ? '&limit=' . $_GET['limit'] : '';
            echo "
                <a class='page-link nav' " . ($current_page != 1 ? 'href=\'index.php?page=1' . $limit_temp . "'" : '') . ">Початок</a>
                <a class='page-link nav' " . ($current_page != 1 ? "href='index.php?page=" . ($current_page - 1) . $limit_temp . "'" : '') . ">Назад</a>
            ";

            for ($i = 0; $i < $pages_count; $i++) {
                echo "<a class='page-link" . ($i + 1 == $current_page ? ' active' : '') . "' href='index.php?page=" . ($i + 1) . $limit_temp . "'>" . ($i + 1) . "</a>";
            }



            echo "
                <a class='page-link nav' " . ($current_page != $pages_count && $pages_count != 0 ? "href='index.php?page=" . ($current_page + 1) . $limit_temp . "'" : '') . ">Вперед</a>
                <a class='page-link nav' " . ($current_page != $pages_count && $pages_count != 0 ? "href='index.php?page=" . $pages_count . $limit_temp . "'" : '') . ">Кінець</a>
            ";
            ?>
        </div>
    </div>

    <?php
    if ($_SESSION['current_user']) {
        echo "<div class='forms-block'>";

        // 1 форма========================================================================
        echo "
            <form id='form-add'>
                <h3>Додати товар</h3>
                
                <fieldset>
                    <label>Назва</label>
                    <input type='text' name='add_name' value='{$_POST['add_name']}'>
        ";
        if (isset($GLOBALS['validation_errors']['add_name'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['add_name'] . "</p>";
        }
        echo "</fieldset>";


        echo "
            <fieldset>
                <label>Опис</label>
                <input type='text' name='add_description' value='{$_POST['add_description']}'>
        ";
        if (isset($GLOBALS['validation_errors']['add_description'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['add_description'] . "</p>";
        }
        echo "</fieldset>";


        echo "
            <fieldset>
                <label>Ціна</label>
                <input type='text' name='add_price' value='{$_POST['add_price']}'>
        ";
        if (isset($GLOBALS['validation_errors']['add_price'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['add_price'] . "</p>";
        }
        echo "</fieldset>";

        echo "<button id='add-btn'>Додати</button>";
        if (isset($GLOBALS['validation_success_add'])) {
            echo "<p class='validation_success'>" . $GLOBALS['validation_success_add'] . "</p>";
        } else if(isset($GLOBALS['validation_error_add'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_error_add'] . "</p>";
        }
        echo "</form>";
        // 1 форма========================================================================


        // 2 форма========================================================================
        echo "
            <form id='form-edit'>
                <h3>Редагувати товар</h3>
                
                <fieldset>
                    <label>Номер товару</label>
                    <input type='number' name='edit_id' value='{$_POST['edit_id']}'>
        ";
        if (isset($GLOBALS['validation_errors']['edit_id'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['edit_id'] . "</p>";
        }
        echo "</fieldset>";

        echo "
            <fieldset>
                <label>Назва</label>
                <input type='text' name='edit_name' value='{$_POST['edit_name']}'>
        ";
        if (isset($GLOBALS['validation_errors']['edit_name'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['edit_name'] . "</p>";
        }
        echo "</fieldset>";

        echo "
            <fieldset>
                <label>Опис</label>
                <input type='text' name='edit_description' value='{$_POST['edit_description']}'>
        ";
        if (isset($GLOBALS['validation_errors']['edit_description'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['edit_description'] . "</p>";
        }
        echo "</fieldset>";


        echo "
            <fieldset>
                <label>Ціна</label>
                <input type='text' name='edit_price' value='{$_POST['edit_price']}'>
        ";
        if (isset($GLOBALS['validation_errors']['edit_price'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['edit_price'] . "</p>";
        }
        echo "</fieldset>";

        echo "<button id='edit-btn'>Редагувати</button>";
        if (isset($GLOBALS['validation_success_edit'])) {
            echo "<p class='validation_success'>" . $GLOBALS['validation_success_edit'] . "</p>";
        } else if(isset($GLOBALS['validation_error_edit'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_error_edit'] . "</p>";
        }

        echo "</form>";
        // 2 форма========================================================================


        // 3 форма========================================================================
        echo "
            <form id='form-remove'>
                <h3>Видалити товар</h3>
                
                <fieldset>
                    <label>Номер товару</label>
                    <input type='number' name='remove_id' value='{$_POST['remove_id']}'>
        ";
        if (isset($GLOBALS['validation_errors']['remove_id'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_errors']['remove_id'] . "</p>";
        }
        echo "</fieldset>";


        echo "<button id='remove-btn'>Видалити</button>";
        if (isset($GLOBALS['validation_success_remove'])) {
            echo "<p class='validation_success'>" . $GLOBALS['validation_success_remove'] . "</p>";
        } else if (isset($GLOBALS['validation_error_remove'])) {
            echo "<p class='validation_error'>" . $GLOBALS['validation_error_remove'] . "</p>";
        }
        echo "</form>";
        // 2 форма========================================================================

        echo "</div>";
    }
    ?>

    <?php
    if (isset($_SESSION['current_user'])) {
        echo "<h2>Вітаю, {$_SESSION['current_user']['firstname']} {$_SESSION['current_user']['secondname']}!</h2>";
    } else {
        echo "<h2>Увійдіть або зареєструйтесь!</h2>";
    }
    ?>
</main>

<footer>
    Something about Lorem ipsum dolor sit amet, consectetur adipisicing elit. Esse isteerror, optio porro praesentium
    quibusdam reiciendis reprehenderit, saepe suscipit.
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.js" integrity="sha256-H+K7U5CnXl1h5ywQfKtSj8PCmoN9aaq30gDh27Xc0jk="
        crossorigin="anonymous"></script>
<script>
    $('#limit-btn').click(function () {
        let limitInput = $('#limit-input');
        if (limitInput.val() < 1) limitInput.val(1);
        location.href = 'index.php?limit=' + limitInput.val();
    });

    $('#add-btn').click(function (event) {
        event.preventDefault();
        let inputs = $('#form-add input'),
            inputsHtml = '<input type="text" name="action" value="add">';

        for (let i = 0; i < inputs.length; i++) {
            inputsHtml += `<input type="${inputs[i].type}" name="${inputs[i].name}" value="${inputs[i].value}">`;
        }

        $('<form style="display: none" action="' + location.href + '" method="POST">' + inputsHtml + '</form>').appendTo($(document.body)).submit();
    });

    $('#edit-btn').click(function (event) {
        event.preventDefault();
        let inputs = $('#form-edit input'),
            inputsHtml = '<input type="text" name="action" value="edit">';

        for (let i = 0; i < inputs.length; i++) {
            inputsHtml += `<input type="${inputs[i].type}" name="${inputs[i].name}" value="${inputs[i].value}">`;
        }

        $('<form style="display: none" action="' + location.href + '" method="POST">' + inputsHtml + '</form>').appendTo($(document.body)).submit();
    });

    $('#remove-btn').click(function (event) {
        event.preventDefault();
        let input = $('#form-remove input')[0],
            inputsHtml = `<input type="text" name="action" value="remove"><input type="${input.type}" name="${input.name}" value="${input.value}">`;
        
        $('<form style="display: none" action="' + location.href + '" method="POST">' + inputsHtml + '</form>').appendTo($(document.body)).submit();
    });

</script>
</body>
</html>