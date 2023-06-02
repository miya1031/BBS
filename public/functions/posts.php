<?php
function h($value){
    return htmlspecialchars($value, ENT_QUOTES);
}

function url_check($value){
    return preg_replace('/((http|https):\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+)/','<a href="$1" target="_blank">$1</a>',$value);
}
/**
 * $あるユーザの前投稿数を数える関数
 * @param $db PDOインスタンス
 * @param $list array
 * 
 * @return array<int, int>
 * 戻り値の連想配列はキーが投稿id、ユーザがいいねを押していたら値が1
 * いいねを押していない場合、empty
 */
function countUserPostNum($db, $list){
    $result = array();
    $place_holders = implode(',', array_fill(0, count($list), '?'));
    $countAllPostNum = $db->prepare('SELECT COUNT(*) AS cnt,  mId AS id FROM
                                    (SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id
                                    UNION ALL
                                    SELECT m_.name, m_.icon, m_.id AS mId, p_.* , r.created AS rtw_created, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) AS t
                                    WHERE mId IN (' . $place_holders .') GROUP BY mId
                                    ');
    $countAllPostNum->execute($list);
    while ($postRecord = $countAllPostNum->fetch()){
        $result[$postRecord['id']] = $postRecord['cnt'];
    }
    return $result;
}

/**
 * $あるユーザのいいねされた総数を数える関数
 * @param $db PDOインスタンス
 * @param $list array
 * 
 * @return array<int, int>
 * 戻り値の連想配列はキーがユーザid、値はユーザがいいねされた数
 * リツイートされていない場合、empty
 */
function countUserLikedNum($db, $list){
    $result = array();
    $place_holders = implode(',', array_fill(0, count($list), '?'));
    $countLikedNum = $db->prepare('SELECT COUNT(*) AS cnt, p.member_id AS id FROM members m INNER JOIN posts p ON m.id=p.member_id JOIN likes l ON p.id = l.post_id WHERE p.member_id IN (' . $place_holders . ') GROUP BY id');
    $countLikedNum->execute($list);
    while ($postRecord = $countLikedNum->fetch()){
        $result[$postRecord['id']] = $postRecord['cnt'];
    }
    return $result;
}

/**
 * $あるユーザのリツイートされた総数を数える関数
 * @param $db PDOインスタンス
 * @param $list array
 * 
 * @return array<int, int>
 * 戻り値の連想配列はキーがユーザid、値はユーザがリツイートされた数
 * リツイートされていない場合、empty
 */
function countUserRetweetedNum($db, $list){
    $result = array();
    $place_holders = implode(',', array_fill(0, count($list), '?'));
    $countRetweetedNum = $db->prepare('SELECT COUNT(*) AS cnt, p.member_id AS id FROM members m INNER JOIN posts p ON m.id=p.member_id JOIN retweets r ON p.id = r.post_id WHERE p.member_id IN (' . $place_holders . ') GROUP BY id');
    $countRetweetedNum->execute($list);
    while ($postRecord = $countRetweetedNum->fetch()){
        $result[$postRecord['id']] = $postRecord['cnt'];
    }
    return $result;
}
?>