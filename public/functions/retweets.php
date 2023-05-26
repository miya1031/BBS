<?php
/**
 * ある投稿が何回リツイートされているかカウントする関数
 * @param $db PDOインスタンス
 * @param $post 投稿のレコード
 * 
 * @return retweetsテーブルにpost_idが$post['id']であるレコードがいくつあるか
 */
function retweetNum($db, $post){
    $retweets = $db->prepare('SELECT COUNT(*) AS cnt FROM retweets WHERE post_id = ?');
    $retweets->execute(array($post['id']));
    $count = $retweets->fetch();
    return $count['cnt'];
}

/**
 * ある投稿をユーザがリツイートしているか判定する関数
 * @param $db PDOインスタンス
 * @param $post 投稿のレコード
 * 
 * @return retweetsテーブルにpost_idが$post['id']で、member_idが$_SESSION['id']であるあるレコードがいくつあるか
 */
function retweetFlag($db, $post){
    $retweet = $db->prepare('SELECT COUNT(*) as cnt FROM retweets WHERE post_id=? AND member_id=?');
    $retweet->execute(array($post['id'], $_SESSION['id']));
    $count = $retweet->fetch();
    return $count['cnt'];
}
?>