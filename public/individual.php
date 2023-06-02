<?php
session_start();
require('dbconnection.php');
require('./functions/likes.php');
require('./functions/retweets.php');
require('./functions/posts.php');


//バリテーション?
//SESSIONにidを保持していないゲストはログイン画面へ移動させる
if (empty($_SESSION['id'])){
    header('Location: login.php');
    exit();
}

if (empty($_REQUEST['mId'])){
    //クエリパラメータが指定されていない場合
    header('Location: index.php');
    exit();
}

if (!empty($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
} else{
    $page = 1;
}
$page = max($page,1);//ページ番号がマイナスだった場合

//displayクエリパラメータが設定されていない場合はデフォルトで投稿のみの表示とする。
if (empty($_REQUEST['dp'])){
    $_REQUEST['dp'] = 'tw';
}

//最大ページ数を取得
if ($_REQUEST['dp'] == 'all'){
    //返信も含んだレコードをカウントする
    $maxPageCount = $db->prepare('SELECT COUNT(*) AS cnt FROM
                            (SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_id, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id
                            UNION ALL
                            SELECT m_.name, m_.icon, m_.id AS mId, p_.* , r.created AS rtw_created, r.member_id AS rtw_id, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) AS t
                            WHERE (t.mId = ? AND t.rtw_id IS NULL) OR (t.rtw_id = ?)
                            ');
    $maxPageCount->execute(array($_REQUEST['mId'],$_REQUEST['mId']));
} elseif($_REQUEST['dp'] == 'tw'){
    //返信を除いた（ただし、返信のリツイートは取得する）レコードをカウントする。
    $maxPageCount = $db->prepare('SELECT COUNT(*) AS cnt FROM
                            (SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_id, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id
                            UNION ALL
                            SELECT m_.name, m_.icon, m_.id AS mId, p_.* , r.created AS rtw_created, r.member_id AS rtw_id, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) AS t
                            WHERE (t.mId = ? AND t.rtw_id IS NULL AND t.reply_post_id IS NULL) OR (t.rtw_id = ?)
                            ');
    $maxPageCount->execute(array($_REQUEST['mId'],$_REQUEST['mId']));
} elseif($_REQUEST['dp'] == 'like'){
    //ユーザがいいねを押した投稿をカウントする
    $maxPageCount = $db->prepare('SELECT COUNT(*) AS cnt FROM members m INNER JOIN posts p ON m.id=p.member_id JOIN likes l ON p.id = l.post_id WHERE l.member_id = ?');
    $maxPageCount->execute(array($_REQUEST['mId']));
}


$postNum = $maxPageCount->fetch();
$maxPage = ceil($postNum['cnt']/5);
if ($maxPage==0){//投稿がひとつもない場合はmaxPage=0になってしまい、エラーが出るので1を代入する。
    $maxPage = 1;
}
$page = min($page, $maxPage);//ページ番号が最大ページ数より大きい場合

$startPostNum = ($page-1)*5;


if ($_REQUEST['dp'] == 'all'){
    //返信も含んだレコードを取得
    $statement = $db->prepare('SELECT * FROM
                            (SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_id, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id
                            UNION ALL
                            SELECT m_.name, m_.icon, m_.id AS mId, p_.* , r.created AS rtw_created, r.member_id AS rtw_id, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) AS t
                            WHERE (t.mId = ? AND t.rtw_id IS NULL) OR (t.rtw_id = ?) ORDER BY t.rtw_created DESC LIMIT ?, 5
                            ');
    $statement->bindParam(1,$_REQUEST['mId'],PDO::PARAM_INT);
    $statement->bindParam(2,$_REQUEST['mId'],PDO::PARAM_INT);
    $statement->bindParam(3,$startPostNum,PDO::PARAM_INT);
} elseif($_REQUEST['dp'] == 'tw'){
    //返信のレコードは取得しない。ただし、返信のリツイートは取得する。
    $statement = $db->prepare('SELECT * FROM
                            (SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_id, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id
                            UNION ALL
                            SELECT m_.name, m_.icon, m_.id AS mId, p_.* , r.created AS rtw_created, r.member_id AS rtw_id, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id) AS t
                            WHERE (t.mId = ? AND t.rtw_id IS NULL AND t.reply_post_id IS NULL) OR (t.rtw_id = ?) ORDER BY t.rtw_created DESC LIMIT ?, 5
                            ');
        $statement->bindParam(1,$_REQUEST['mId'],PDO::PARAM_INT);
        $statement->bindParam(2,$_REQUEST['mId'],PDO::PARAM_INT);
        $statement->bindParam(3,$startPostNum,PDO::PARAM_INT);
} elseif($_REQUEST['dp'] == 'like'){
    //ユーザがいいねを押した投稿を取得する。
    $statement = $db->prepare('SELECT m.name, m.icon, m.id AS mId, p.* , p.created AS rtw_created, NULL AS rtw_id FROM members m INNER JOIN posts p ON m.id=p.member_id JOIN likes l ON p.id = l.post_id WHERE l.member_id = ? ORDER BY p.created DESC LIMIT ?, 5');
    $statement->bindParam(1,$_REQUEST['mId'],PDO::PARAM_INT);
    $statement->bindParam(2,$startPostNum,PDO::PARAM_INT);
}

$statement->execute();
$table = $statement->fetchAll(PDO::FETCH_ASSOC);
//$tableのレコードがゼロの場合は２つ考えられる
// 1. 一つもツイート/いいねをしていない
// 2. 指定した$_REQUEST['mId']が存在していない
if (count($table)==0){
    $statement = $db->prepare('SELECT * FROM members m WHERE m.id = ?');
    $statement->execute(array($_REQUEST['mId']));
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    if (count($data) == 0){
        //1の場合
        header('Location: index.php');
        exit();
    } else{
        //2の場合
    }
}

//画面に表示する投稿のpost_idを$postListに格納する
$postList = array();
$mIdList = array();
foreach ($table as $record){
    $postList[] = $record['id'];
    $mIdList[] = $record['mId'];
}
//ページのuser_idも格納する
$mIdList[] = $_GET['mId'];

//いいね情報が格納された連想配列
$likeList = likeNum($db, $postList);
$likeFlagList = likerFlag($db, $postList);
//リツイート情報が格納された連想配列
$retweetList = retweetNum($db, $postList);
$retweetFlagList = retweetFlag($db, $postList);

//プロフィール欄に表示する情報（投稿数/いいねされた数/リツイートされた数）を取得する
//投稿数
$allPostNum = countUserPostNum($db, $mIdList);
//いいねされた数
$likedNum = countUserLikedNum($db, $mIdList);
//リツイートされた数
$retweetedNum = countUserRetweetedNum($db, $mIdList);

//参加した日時/ニックネーム/アイコン
$searchJoinTime = $db->prepare('SELECT name, icon, created FROM members WHERE id = ?');
$searchJoinTime->execute(array($_REQUEST['mId']));
$postTable = $searchJoinTime->fetch(PDO::FETCH_ASSOC);
$joinTime = $postTable['created'];
$name = $postTable['name'];
$icon = $postTable['icon'];
?>
<!DOCTYPE html>
<html lang="ja" data-theme="lemonade">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BBS</title>
    <link href="output.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/de14c321d7.js" crossorigin="anonymous"></script>
</head>
<body>
    <header class='navbar bg-primary'>
        <div class='flex-1'>
            <a href="index.php"><h1 class="p-4 text-3xl font-bold text-white">ひとこと掲示板</h1></a>
        </div>
        <div class="flex-none">
            <div class="dropdown dropdown-end">
                <?php if (!empty($_SESSION['icon'])):?>
                    <label tabindex="0" class="avatar">
                        <div class="w-16 rounded">
                            <img src="./member_image/<?php echo h($_SESSION['icon']);?>" alt="アイコン画像">
                        </div>
                    </label>
                <?php else: ?>
                    <label tabindex="0" class="avatar placeholder">
                        <div class="bg-white text-black w-16 rounded">
                            <span class="text-xl">no<br>image</span>
                        </div>
                    </label> 
                <?php endif; ;?>
                <ul tabindex="0" class='mt-3 p-2 shadow menu menu-compact dropdown-content bg-base-200 rounded-box w-52'>
                    <li>
                        <a href="individual.php?mId=<?php echo $_SESSION['id']; ?>&dp=tw&page=1">マイページ</a>
                    </li>
                    <li>
                        <a href="logout.php">ログアウト</a>
                    </li>
                    <li>
                        <a href="delete-account.php">退会</a>
                    </li>
                </ul>
            </div>
        </div>
    </header>
    <main class='flex flex-col justify-center justify-items-center max-w-3xl mx-auto'>
        <div class='flex flex-col justify-center justify-items-center w-full mx-auto bg-accent border-b border-gray-200'>
            <div class='w-full m-2 flex'>
                <div class='w-1/2 flex justify-center justify-items-center'>
                    <?php if (!empty($icon)):?>
                    <div class="avatar">
                        <div class="w-24 h-24 md:w-36 md:h-36 rounded">
                            <img src="./member_image/<?php echo $icon;?>" alt="アイコン画像">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content w-24 h-24 md:w-36 md:h-36 rounded">
                            <span class="text-3xl">no<br>image</span>
                        </div>
                    </div> 
                    <?php endif; ;?>
                </div>
                <div class='w-1/2 flex flex-col justify-items-center justify-center'>
                    <div class='m-2'>
                        <div class='underline'>ニックネーム</div>
                        <div class='text-3xl md:text-5xl py-2 px-4 font-bold'><?php echo $name;?></div>
                    </div>
                    <div class='m-2'>
                        <div class='underline'>参加日時</div>
                        <div class='px-4 py-2'><?php echo $joinTime;?></div>
                    </div>
                </div>
            </div>
            <div class='flex justify-center justify-items-center w-full m-2'>
                <div class='w-1/3'>
                    <p>投稿数&nbsp;<i class="fa-light fa-colon"></i>&nbsp;<span class='font-bold'><?php echo $allPostNum[$_GET['mId']];?></span></p>
                </div>
                <div class='w-1/3'>
                    <p>いいねされた数&nbsp;<i class="fa-light fa-colon"></i>&nbsp;<span class='font-bold'><?php echo $likedNum[$_GET['mId']];?></span></p>
                </div>
                <div class='w-1/3'>
                    <p>リツイートされた数&nbsp;<i class="fa-light fa-colon"></i>&nbsp;<span class='font-bold'><?php echo $retweetedNum[$_GET['mId']];?></span></p>
                </div>
            </div>
        </div>
        
        <div class='w-full m-2 flex justify-center justify-items-center'>
            <div class="tabs">
                <!-- タブを押したらそのタブの1ページ目に戻ることにする。 -->
                <?php if ($_REQUEST['dp'] == 'tw'){ ?>
                    <a href="individual.php?mId=<?php echo $_REQUEST['mId'];?>&dp=tw&page=1" class='tab tab-bordered text-primary tab-active'>投稿</a>
                <?php } else{?>
                    <a href="individual.php?mId=<?php echo $_REQUEST['mId'];?>&dp=tw&page=1" class='tab tab-bordered text-primary'>投稿</a>
                <?php } ?>
                <?php if ($_REQUEST['dp'] == 'all'){ ?>
                    <a href="individual.php?mId=<?php echo $_REQUEST['mId'];?>&dp=all&page=1" class='tab tab-bordered text-primary tab-active'>返信</a>
                <?php } else{?>
                    <a href="individual.php?mId=<?php echo $_REQUEST['mId'];?>&dp=all&page=1" class='tab tab-bordered text-primary'>返信</a>
                <?php } ?>
                <?php if ($_REQUEST['dp'] == 'like'){ ?>
                    <a href="individual.php?mId=<?php echo $_REQUEST['mId'];?>&dp=like&page=1" class='tab tab-bordered text-primary tab-active'>いいね</a>
                <?php } else{?>
                    <a href="individual.php?mId=<?php echo $_REQUEST['mId'];?>&dp=like&page=1" class='tab tab-bordered text-primary'>いいね</a>
                <?php } ?>
            </div>
        </div>
        <!-- pythonのenumerateのように要素とインデックスを両方取得 -->
        <?php foreach (array_values($table) as $i => $post) {?>
            <div class='flex flex-col p-1 hover:bg-primary-content h-auto border-b border-gray-200'>
                <div>
                    <p class='text-primary p-2'>
                        <?php if(!empty($post['rtw_name'])):;?><i class="fa-solid fa-retweet" style="color: #31c21e;"></i>&nbsp;<?php echo h($post['rtw_name']) . 'さんがリツイートしました'; endif; ;?>
                    </p>
                </div>
                <div class='flex p-1'>
                    <div class='w-1/5'>
                        <?php if (!empty($post['icon'])):?>
                            <div class="avatar m-2 md:m-0 dropdown dropdown-right">
                                <label tabindex="0">
                                    <div class="w-16 md:w-24 rounded">
                                        <img src="./member_image/<?php echo h($post['icon']);?>" alt="アイコン画像">
                                    </div>
                                </label>
                                <label tabindex="0" class='dropdown-content'>
                                    <div class='card w-96 bg-base-100 shadow-xl'>
                                        <figure><img src="./member_image/<?php echo h($post['icon']);?>" alt="アイコン画像"></figure>
                                        <div class="card-body">
                                            <h2 class='card-title'><?php echo h($post['name'])?></h2>
                                            <div class='flex justify-center justify-items-center w-full m-0 text-xs'>
                                                <div class='w-1/3'>
                                                    <p>&nbsp;</p>
                                                    <p class='text-xs'>投稿数</p>
                                                    <p class='font-bold'><?php if (empty($allPostNum[$post['mId']])): echo 0; else: echo $allPostNum[$post['mId']]; endif; ;?></p>
                                                </div>
                                                <div class='w-1/3'>
                                                    <p>いいね<br>された数</p>
                                                    <p class='font-bold'><?php if (empty($likedNum[$post['mId']])): echo 0; else: echo $likedNum[$post['mId']]; endif; ;?></p>
                                                </div>
                                                <div class='w-1/3'>
                                                    <p>リツイート<br>された数</p>
                                                    <p class='font-bold'><?php if (empty($retweetedNum[$post['mId']])): echo 0; else: echo $retweetedNum[$post['mId']]; endif; ;?></p>
                                                </div>
                                            </div>
                                            <div class="card-actions justify-end">
                                                <a href="individual.php?mId=<?php echo $post['mId'] ;?>&dp=tw&page=1" class='btn btn-primary'>プロフィールを見る</a>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <?php else: ?>
                            <div class="avatar m-2 md:m-0 dropdown dropdown-right">
                                <label tabindex="0">
                                    <div class="bg-primary text-primary-content w-16 h-16 md:w-24 md:h-24 rounded">
                                        <span class="text-3xl">no<br>image</span>
                                    </div>
                                </label>
                                <label tabindex="0" class='dropdown-content'>
                                    <div class='card w-96 bg-base-100 shadow-xl'>
                                        <figure class='p-10'><span class="bg-primary text-primary-content text-3xl p-10">no<br>image</span></figure>
                                        <div class="card-body">
                                            <h2 class='card-title'><?php echo h($post['name'])?></h2>
                                            <div class='flex justify-center justify-items-center w-full m-0 text-xs'>
                                                <div class='w-1/3'>
                                                    <p>&nbsp;</p>
                                                    <p class='text-xs'>投稿数</p>
                                                    <p class='font-bold'><?php if (empty($allPostNum[$post['mId']])): echo 0; else: echo $allPostNum[$post['mId']]; endif; ;?></p>
                                                </div>
                                                <div class='w-1/3'>
                                                    <p>いいね<br>された数</p>
                                                    <p class='font-bold'><?php if (empty($likedNum[$post['mId']])): echo 0; else: echo $likedNum[$post['mId']]; endif; ;?></p>
                                                </div>
                                                <div class='w-1/3'>
                                                    <p>リツイート<br>された数</p>
                                                    <p class='font-bold'><?php if (empty($retweetedNum[$post['mId']])): echo 0; else: echo $retweetedNum[$post['mId']]; endif; ;?></p>
                                                </div>
                                            </div>
                                            <div class="card-actions justify-end">
                                                <a href="individual.php?mId=<?php echo $post['mId'] ;?>&dp=tw&page=1" class='btn btn-primary'>プロフィールを見る</a>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endif; ;?>
                    </div>
                    <div class='flex flex-col items-start w-3/5'>
                            <div class='flex flex-col md:flex-row'>
                                <span class='text-xl md:text-3xl py-2 px-4 font-bold'><?php echo h($post['name']);?></span>
                                <span class='pl-2 text-base-300 pt-2 md:pt-0'><?php echo h($post['created']);?></span>
                            </div>
                            <a class='text-left' href='post.php?id=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']);?>'>
                                <p class='text-xl px-4 text-left'><?php if (mb_strlen(h($post['message']))<40): echo url_check(h($post['message'])); else: echo url_check(mb_substr(h($post['message']),0,40)) . '&nbsp;...'; endif;?></p>
                            </a>
                            <div class='pt-4 w-full'>
                                <div class='flex justify-between justify-items-center w-full'>
                                    <div class='flex w-1/2'>
                                        <div>
                                            <?php if(empty($likeFlagList[$post['id']])):?>
                                                <a href="likes.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']); ?>&dp=<?php echo $_GET['dp']; ?>&mId=<?php echo $_GET['mId']; ?>"><i class="fa-regular fa-heart" style="color: #515251;"></i></a>
                                            <?php else: ?>
                                                <a href="dislikes.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']); ?>&dp=<?php echo $_GET['dp']; ?>&mId=<?php echo $_GET['mId']; ?>"><i class="fa-solid fa-heart" style="color: #31c21e;"></i></a>
                                            <?php endif; ;?>
                                        </div>
                                        <div class='pl-2'>
                                            <?php if(!empty($likeList[$post['id']])): echo($likeList[$post['id']]); endif; ;?>
                                        </div>
                                    </div>
                                    <div class='flex w-1/2'>
                                        <div>
                                            <?php if(empty($retweetFlagList[$post['id']])):?>
                                                <a href="retweets.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']); ?>&dp=<?php echo $_GET['dp']; ?>&mId=<?php echo $_GET['mId']; ?>"><i class="fa-solid fa-retweet" style="color: #515251;"></i></a>
                                            <?php else: ?>
                                                <a href="retweetCancels.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']); ?>&dp=<?php echo $_GET['dp']; ?>&mId=<?php echo $_GET['mId']; ?>" class='text-primary'><i class="fa-solid fa-retweet" style="color: #31c21e;"></i></a>
                                            <?php endif; ;?>
                                        </div>
                                        <div class='pl-2'>
                                            <?php if(!empty($retweetList[$post['id']])): echo($retweetList[$post['id']]); endif; ;?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    </div>
                    <div class='flex flex-col w-1/5 h-32'>
                            <div class='h-1/3'>
                                <a href="back.php?created=<?php echo strtotime($post['rtw_created'])?>&re=<?php echo h($post['id']);?>" class='badge badge-primary'>返信</a>
                            </div>
                            <?php if ($_SESSION['id'] == $post['member_id']):?>
                                <div class='h-1/3'>
                                    <a href="back.php?created=<?php echo strtotime($post['rtw_created'])?>&ed=<?php echo h($post['id']);?>" class='badge badge-primary'>編集</a>
                                </div>
                            <?php endif;?>
                            <?php if ($_SESSION['id'] == $post['member_id']):?>
                                <div class='h-1/3'>
                                    <a href="delete.php?id=<?php echo h($post['id'])?>" class='badge badge-primary'>削除</a>
                                </div>
                            <?php endif;?>
                    </div>
                </div>
            </div>
        <?php } ;?>
        <div class='flex justify-center'>
            <div class='p-8'>
                <?php if($page>1):?>
                    <a href="individual.php?page=<?php echo h($page)-1;?>&dp=<?php echo h($_REQUEST['dp']);?>&mId=<?php echo h($_REQUEST['mId'])?>" class='btn btn-primary'>前ページ</a>
                <?php else: ?>
                    <button class="btn btn-active btn-ghost">前ページ</button>
                <?php endif; ;?>
            </div>
            <div class='p-8'>
                <?php if($page<$maxPage):?>
                    <a href="individual.php?page=<?php echo h($page)+1;?>&dp=<?php echo h($_REQUEST['dp']);?>&mId=<?php echo h($_REQUEST['mId'])?>" class='btn btn-primary'>次ページ</a>
                <?php else: ?>
                    <button class="btn btn-active btn-ghost">次ページ</button>
                <?php endif; ;?>
            </div>
        </div>
    </main>
    <footer class='footer footer-center p-4 bg-primary-content text-base-content'>
        <div>
            <p>Copyright © 2023 - All right reserved by YUJIRO MIYAKE</p>
        </div>
    </footer>
</body>
</html>