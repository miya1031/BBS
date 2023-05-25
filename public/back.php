<?php
session_start();
require('dbconnection.php');

if (!empty($_REQUEST['id'])){
    $statement = $db->prepare('SELECT COUNT(*) AS cnt FROM posts WHERE id = ?');
    $statement->execute(array($_REQUEST['id']));
    $count = $statement->fetch();
    if ($count['cnt']==1){
        $num = $db->prepare('SELECT COUNT(*) AS cnt FROM posts WHERE id >= ?');//idは作成順に並んでいる。指定したidより大きいidの個数を調べる
        $num->execute(array($_REQUEST['id']));
        $postNum = $num->fetch();
        $page = ceil($postNum['cnt']/5);//$_REQUEST['id]の投稿があるページ番号
        if (!empty($_REQUEST['re'])){//$_REQUEST['re']の検査はindex.phpでやってくれる
            header('Location: index.php?page=' . $page . '&re=' . $_REQUEST['re']);
            exit();
        } elseif(!empty($_REQUEST['ed'])){
            header('Location: index.php?page=' . $page . '&ed=' . $_REQUEST['ed']);
            exit();
        }
        header('Location: index.php?page=' . $page);
        exit();
    } else{
        header('Location: index.php');
        exit();
    }
} else{
    header('Location: index.php');
    exit();
}
?>