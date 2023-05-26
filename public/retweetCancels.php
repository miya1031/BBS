<?php
session_start();
require('dbconnection.php');

if (!isset($_SESSION['id']) | !isset($_REQUEST['post'])){
    header('Location: index.php');
    exit();
}

$exist = $db->prepare('SELECT COUNT(*) cnt FROM posts WHERE id = ?');
$exist->execute(array($_REQUEST['post']));
$count = $exist->fetch();
if ($count['cnt']==1){
    $retweetCancel = $db->prepare('DELETE FROM retweets WHERE post_id = ? AND member_id = ?');
    $retweetCancel->execute(array($_REQUEST['post'], $_SESSION['id']));
} else{
    header('Location: index.php');
    exit();
}

if (empty($_REQUEST['back'])){
    header('Location: back.php?id=' . $_REQUEST['post']);
    exit();
} else{
    header('Location: post.php?id=' . $_REQUEST['post']);
    exit();
}
?>