<?php
/**
 * ある投稿に何個のいいねが押されているかカウントする関数
 * @param $db PDOインスタンス
 * @param $post 投稿のレコード
 * 
 * @return likesテーブルにpost_idが$post['id']であるレコードがいくつあるか
 */
function likeNum($db, $post){
    $likes = $db->prepare('SELECT COUNT(*) AS cnt FROM likes WHERE post_id = ?');
    $likes->execute(array($post['id']));
    $count = $likes->fetch();
    return $count['cnt'];
}

/**
 * ある投稿にユーザがいいねを押しているか判定する関数
 * @param $db PDOインスタンス
 * @param $post 投稿のレコード
 * 
 * @return likesテーブルにpost_idが$post['id']で、member_idが$_SESSION['id']であるあるレコードがいくつあるか
 */
function likerFlag($db, $post){
    $liker = $db->prepare('SELECT COUNT(*) as cnt FROM likes WHERE post_id=? AND member_id=?');
    $liker->execute(array($post['id'], $_SESSION['id']));
    $count = $liker->fetch();
    return $count['cnt'];
}
?>