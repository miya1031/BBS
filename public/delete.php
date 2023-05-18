<?php
session_start();
require('dbconnection.php');

if (!empty($_REQUEST['id'])){
    $statement = $db->prepare('SELECT * FROM posts WHERE id = ?');
    $statement->execute(array($_REQUEST['id']));
    $count = $statement->rowCount();
    if ($count == 1){//存在する投稿idかチェック
        $search = $statement->fetch();//削除者と投稿者のidが一致するかチェック
        if ($_SESSION['id'] = $search['member_id']){
            $delete = $db->prepare('DELETE FROM posts WHERE id = ?');
            $delete->execute(array($_REQUEST['id']));
        }
    }
}
header('Location: index.php');
exit();
?>