<?php
session_start();
require('dbconnection.php');

if (!isset($_SESSION['id']) | !isset($_REQUEST['post'])){
    header('Location: index.php');
    exit();
}

$exit = $db->prepare('SELECT * FROM posts WhERE id = ?');
$exit->execute(array($_REQUEST['post']));
if (!empty($exit->rowCount())){
    $dislike = $db->prepare('DELETE FROM likes WHERE post_id = ? AND liker_id = ?');
    $dislike->execute(array($_REQUEST['post'], $_SESSION['id']));
}

if (empty($_REQUEST['back'])){
    header('Location: back.php?id=' . $_REQUEST['post']);
    exit();
} else{
    header('Location: post.php?id=' . $_REQUEST['post']);
    exit();
}
?>