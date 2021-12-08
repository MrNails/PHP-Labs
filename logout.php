<?php

session_start();

unset($_SESSION['current_user']);

header("HTTP/1.1 301 Moved Permanently");
header("Location: " . $_SERVER["HTTP_REFERER"]);