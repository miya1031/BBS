<?php
session_start();
require('dbconnection.php');

if (isset($_SESSION['id'])){
    $statement = $db->prepare('DELETE FROM members WHERE id = ?');
    $statement->execute(array($_SESSION['id']));
} else{
    header('Location: login.php');
    exit();
}

//退会したらセッションを終了して会員登録画面に飛ばす
$_SESSION = array();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
header('Location: ./join/index.php');
exit();
?>