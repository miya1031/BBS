<?php
session_start();
require('dbconnection.php');


//投稿時間（リツイート時間も考慮）をもとに、戻ることにする
if (!empty($_GET['created'])){
    //UnixTimeをdate型に変換
    $postTime = date('Y-m-d H:i:s', $_GET['created']);
    if (empty($_GET['dp'])){
        //index.phpから投稿詳細に遷移してきた場合
        //同じ時刻に投稿が二つある場合は困った
        //本来はUNIONで結合した表にidを順番に振れれば楽だが、、
        $statement = $db->prepare('SELECT COUNT(*) AS cnt FROM
                        (SELECT p.created AS rtw_created FROM members m INNER JOIN posts p ON m.id=p.member_id 
                        UNION ALL
                        SELECT r.created AS rtw_created FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) t
                        WHERE t.rtw_created >= ?
                        ');
        $statement->execute(array($postTime));
        $postNum = $statement->fetch(PDO::FETCH_ASSOC);
        $page = ceil($postNum['cnt']/5);
        //post.php/individual.phpから返信/編集ボタンを押したときにもback.phpを使っている。
        if (!empty($_GET['re'])){
            header('Location: index.php?page=' . $page . '&re=' . $_GET['re']);
            exit();
        } elseif(!empty($_GET['ed'])){
            header('Location: index.php?page=' . $page . '&ed=' . $_GET['ed']);
            exit();
        }
        header('Location: index.php?page=' . $page);
        exit();
    } else{
        if ($_GET['dp']=='all'){
            //individual.phpのdp=allからきた場合
            $countNewerPosts = $db->prepare('SELECT COUNT(*) AS cnt FROM
                                        (SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_id, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id
                                        UNION ALL
                                        SELECT m_.name, m_.icon, m_.id AS mId, p_.* , r.created AS rtw_created, r.member_id AS rtw_id, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) AS t
                                        WHERE ((t.mId = ? AND t.rtw_id IS NULL) OR (t.rtw_id = ?)) AND t.rtw_created >= ?
                                        ');
            $countNewerPosts->execute(array($_GET['mId'], $_GET['mId'], $postTime));
        } elseif ($_GET['dp']=='tw'){
            //individual.phpのdp=twから来た場合
            $countNewerPosts = $db->prepare('SELECT COUNT(*) AS cnt FROM
                                        (SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_id, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id
                                        UNION ALL
                                        SELECT m_.name, m_.icon, m_.id AS mId, p_.* , r.created AS rtw_created, r.member_id AS rtw_id, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) AS t
                                        WHERE ((t.mId = ? AND t.rtw_id IS NULL AND t.reply_post_id IS NULL) OR (t.rtw_id = ?)) AND t.rtw_created >= ?
                                        ');
            $countNewerPosts->execute(array($_GET['mId'], $_GET['mId'], $postTime));
        } elseif ($_GET['dp']=='like'){
            //individual.phpのdp=likeから来た場合
            $countNewerPosts = $db->prepare('SELECT COUNT(*) AS cnt FROM members m INNER JOIN posts p ON m.id=p.member_id JOIN likes l ON p.id = l.post_id WHERE l.member_id = ? AND p.created >= ?');
            $countNewerPosts->execute(array($_GET['mId'], $postTime));

        } else{
            header('Location: index.php');
            exit();
        }
        $newerPostNum = $countNewerPosts->fetch(PDO::FETCH_ASSOC);
        $page = ceil($newerPostNum['cnt']/5);
        header('Location: individual.php?page=' . $page . '&dp=' . $_GET['dp'] . '&mId=' . $_GET['mId']);
        exit();
    }
} else{
    header('Location: index.php');
    exit();
}
?>