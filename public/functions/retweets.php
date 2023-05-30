<?php
/**
 * $listに格納された投稿idに対応する投稿にそれぞれ何個リツイートされているかカウントする関数
 * @param $db PDOインスタンス
 * @param $list array
 * 
 * @return array<int, int>
 * 戻り値の連想配列はキーが投稿id、値がいいねの数
 * いいねがゼロの投稿の情報は格納されていません。
 */
function retweetNum($db, $list){
    $result = array();
    $PDOList = substr(str_repeat(',?', count($list)), 1);
    $statement = $db->prepare(sprintf('SELECT COUNT(p.id) AS cnt, p.id AS id FROM posts p JOIN retweets r ON p.id = r.post_id WHERE p.id IN (%s) GROUP BY p.id', $PDOList));
    $statement->execute($list);
    while($value = $statement->fetch()){
        $result[$value['id']] = $value['cnt'];
    }
    return $result;
}

/**
 * $listに格納された投稿idに対応する投稿をユーザがリツイートしているか判定する関数
 * @param $db PDOインスタンス
 * @param $list array
 * 
 * @return array<int, int>
 * 戻り値の連想配列はキーが投稿id、ユーザがいいねを押していたら値が1
 * いいねを押していない場合、empty
 */
function retweetFlag($db, $list){
    $result = array();
    $PDOList = substr(str_repeat(',?', count($list)), 1);
    $statement = $db->prepare(sprintf('SELECT COUNT(p.id) AS cnt, p.id AS id FROM posts p JOIN retweets r ON p.id = r.post_id WHERE r.member_id = ? AND p.id IN (%s) GROUP BY p.id', $PDOList));
    $statement->execute(array_merge(array($_SESSION['id']), $list));
    while($value = $statement->fetch()){
        $result[$value['id']] = $value['cnt'];
    }
    return $result;
}
?>