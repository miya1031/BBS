<?php
session_start();
require('dbconnection.php');

if (!empty($_REQUEST['id'])){
    $statement = $db->prepare('SELECT COUNT(*) AS cnt FROM posts WHERE id = ?');
    $statement->execute(array($_REQUEST['id']));
    $count = $statement->fetch();
    if ($count['cnt']==1){
        $statement = $db->prepare('SELECT created FROM posts WHERE id = ?');
        $statement->execute(array($_REQUEST['id']));
        $c_time = $statement->fetch();
        $postTime = $c_time['created'];
        //同じ時刻に投稿が二つある場合は困った
        //本来はUNIONで結合した表にidを順番に振れれば楽だが、、
        $statement = $db->prepare('SELECT COUNT(*) AS cnt FROM
                        (SELECT p.created AS rtw_created FROM members m INNER JOIN posts p ON m.id=p.member_id 
                        UNION ALL
                        SELECT r.created AS rtw_created FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) t
                        WHERE t.rtw_created >= ?
                        ');
        $statement->execute(array($postTime));
        $postNum = $statement->fetch();
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