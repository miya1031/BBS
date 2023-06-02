<?php
session_start();
require('dbconnection.php');
require('./functions/likes.php');
require('./functions/retweets.php');
require('./functions/posts.php');
//SESSIONにidを保持していないゲストはログイン画面へ移動させる
if (empty($_SESSION['id'])){
    header('Location: login.php');
    exit();
}


if (!empty($_REQUEST['id'])){
    $statement = $db->prepare('SELECT * FROM posts WHERE id=?');
    $statement->execute(array($_REQUEST['id']));
    $count = $statement->rowCount();
    if ($count==1){
        $post = $statement->fetch();
        $start = $post['reply_post_id'];//返信先のid　返信でない場合はNULL
        if (empty($start)){//返信ではない場合
            $search = $db->prepare('SELECT m.id AS mId, m.name, m.icon, p.* FROM members m INNER JOIN posts p ON m.id = p.member_id WHERE reply_post_id=? ORDER BY created');//LIMITは使わない。返信は膨大にならない想定
            $search->execute(array($_REQUEST['id']));
            $start = $_REQUEST['id'];
        } else{//返信の場合
            $search = $db->prepare('SELECT m.id AS mId, m.name, m.icon, p.* FROM members m INNER JOIN posts p ON m.id = p.member_id WHERE reply_post_id=? ORDER BY created');
            $search->execute(array($start));
        }
        //全返信が$searchに入っているので、それをfetchメソッドで取り出す
        // $posts = $search->fetch();
        //$startに返信元のidが格納されている
        $top = $db->prepare('SELECT m.id AS mId, m.name, m.icon, p.* FROM members m INNER JOIN posts p ON m.id = p.member_id WHERE p.id=?');
        $top->execute(array($start));
        $topPost = $top->fetch(PDO::FETCH_ASSOC);

        //投稿詳細から戻るとき、その投稿があったページ番号に戻りたい
        //投稿詳細を見ている間に、投稿が増える可能性があるので、投稿詳細に飛ぶ前にページ番号をSESSIONに保存するやり方はよくなさそう。
        //戻るボタンを押した週刊誌計算するのが良さそうだが。
        //=>back.phpを作成
    }
    else{
        header('Location: index.php');
        exit();
    }
} else{
    header('Location: index.php');
    exit();
}

$table = $search->fetchAll(PDO::FETCH_ASSOC);//全返信
$postList = array();
$mIdList = array();
$postList[] = $start;//返信元の投稿idを追加
$mIdList[] = $topPost['mId'];//返信元の投稿者idを追加
foreach ($table as $record){
    $postList[] = $record['id'];
    $mIdList[] = $record['mId'];
}

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

//遷移前のURLのクエリパラメータを取得する
/**
 * index.phpから来た場合はクエリパラメータはpage,re,ed
 * individual.phpから来た場合はクエリパラメータはpage,dp,id
 */
$url = $_SERVER['HTTP_REFERER'];
$queryStr = parse_url($url, PHP_URL_QUERY);
parse_str($queryStr, $queryArray);
if (empty($queryArray['dp'])){
    //index.phpから来た場合
    $queryStrToFunction = "created=" . $_GET['created'];
} else{
    //individual.phpから来た場合
    $queryStrToFunction = "created=" . $_GET['created'] . "&dp=" . $queryArray['dp'] . "&mId=" . $queryArray['mId'];
}

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
        <div class='flex p-1 border-y border-t-0 border-gray-200'>
            <div class='w-1/5'>
                <?php if (!empty($topPost['icon'])):?>
                    <div class="avatar m-2 md:m-0 dropdown dropdown-right">
                        <label tabindex="0">
                            <div class="w-16 md:w-24 rounded">
                                <img src="./member_image/<?php echo h($topPost['icon']);?>" alt="アイコン画像">
                            </div>
                        </label>
                        <label tabindex="0" class='dropdown-content'>
                            <div class='card w-96 bg-base-100 shadow-xl'>
                                <figure><img src="./member_image/<?php echo h($topPost['icon']);?>" alt="アイコン画像"></figure>
                                <div class="card-body">
                                    <h2 class='card-title'><?php echo h($topPost['name'])?></h2>
                                    <div class='flex justify-center justify-items-center w-full m-0 text-xs'>
                                        <div class='w-1/3'>
                                            <p>&nbsp;</p>
                                            <p class='text-xs'>投稿数</p>
                                            <p class='font-bold'><?php if (empty($allPostNum[$topPost['mId']])): echo 0; else: echo $allPostNum[$topPost['mId']]; endif; ;?></p>
                                        </div>
                                        <div class='w-1/3'>
                                            <p>いいね<br>された数</p>
                                            <p class='font-bold'><?php if (empty($likedNum[$topPost['mId']])): echo 0; else: echo $likedNum[$topPost['mId']]; endif; ;?></p>
                                        </div>
                                        <div class='w-1/3'>
                                            <p>リツイート<br>された数</p>
                                            <p class='font-bold'><?php if (empty($retweetedNum[$topPost['mId']])): echo 0; else: echo $retweetedNum[$topPost['mId']]; endif; ;?></p>
                                        </div>
                                    </div>
                                    <div class="card-actions justify-end">
                                        <a href="individual.php?mId=<?php echo $topPost['mId'] ;?>&dp=tw&page=1" class='btn btn-primary'>プロフィールを見る</a>
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
                                    <h2 class='card-title'><?php echo h($topPost['name'])?></h2>
                                    <div class='flex justify-center justify-items-center w-full m-0 text-xs'>
                                        <div class='w-1/3'>
                                            <p>&nbsp;</p>
                                            <p class='text-xs'>投稿数</p>
                                            <p class='font-bold'><?php if (empty($allPostNum[$topPost['mId']])): echo 0; else: echo $allPostNum[$topPost['mId']]; endif; ;?></p>
                                        </div>
                                        <div class='w-1/3'>
                                            <p>いいね<br>された数</p>
                                            <p class='font-bold'><?php if (empty($likedNum[$topPost['mId']])): echo 0; else: echo $likedNum[$topPost['mId']]; endif; ;?></p>
                                        </div>
                                        <div class='w-1/3'>
                                            <p>リツイート<br>された数</p>
                                            <p class='font-bold'><?php if (empty($retweetedNum[$topPost['mId']])): echo 0; else: echo $retweetedNum[$topPost['mId']]; endif; ;?></p>
                                        </div>
                                    </div>
                                    <div class="card-actions justify-end">
                                        <a href="individual.php?mId=<?php echo $topPost['mId'] ;?>&dp=tw&page=1" class='btn btn-primary'>プロフィールを見る</a>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                <?php endif; ;?>
            </div>
            <div class='flex flex-col items-start w-3/5'>
                    <div class='flex flex-col md:flex-row'>
                        <span class='text-xl md:text-3xl py-2 px-4 font-bold'><?php echo h($topPost['name']);?></span>
                        <span class='pl-2 text-base-300 pt-0 md:pt-2'><?php echo h($topPost['created']);?></span>
                    </div>
                    <div class='text-left pt-2 md:pt-0'>
                        <p class='text-xl px-4 text-left'><?php echo url_check(h($topPost['message']));?></p>
                    </div>
                    <div class='pt-4 w-full'>
                        <div class='flex justify-between justify-items-center w-full'>
                            <div class='flex w-1/2'>
                                <div>
                                    <?php if(empty($likeFlagList[$topPost['id']])):?>
                                        <a href="likes.php?post=<?php echo $topPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>"><i class="fa-regular fa-heart" style="color: #515251;"></i></a>
                                    <?php else: ?>
                                        <a href="dislikes.php?post=<?php echo $topPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>"><i class="fa-solid fa-heart" style="color: #31c21e;"></i></a>
                                    <?php endif; ;?>
                                </div>
                                <div class='pl-2'>
                                    <?php if(!empty($likeList[$topPost['id']])): echo($likeList[$topPost['id']]); endif; ;?>
                                </div>
                            </div>
                            <div class='flex w-1/2'>
                                <div>
                                    <?php if(empty($retweetFlagList[$topPost['id']])):?>
                                        <a href="retweets.php?post=<?php echo $topPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>"><i class="fa-solid fa-retweet" style="color: #515251;"></i></a>
                                    <?php else: ?>
                                        <a href="retweetCancels.php?post=<?php echo $topPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>" class='text-primary'><i class="fa-solid fa-retweet" style="color: #31c21e;"></i></a>
                                    <?php endif; ;?>
                                </div>
                                <div class='pl-2'>
                                    <?php if(!empty($retweetList[$topPost['id']])): echo($retweetList[$topPost['id']]); endif; ;?>
                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            <div class='flex flex-col w-1/5 h-32'>
                    <div class='h-1/3'>
                        <a href="back.php?created=<?php echo strtotime($topPost['created'])?>&re=<?php echo h($topPost['id']);?>" class='badge badge-primary'>返信</a>
                    </div>
                    <?php if ($_SESSION['id'] == $topPost['member_id']):?>
                        <div class='h-1/3'>
                            <a href="back.php?created=<?php echo strtotime($topPost['created'])?>&ed=<?php echo h($topPost['id']);?>" class='badge badge-primary'>編集</a>
                        </div>
                    <?php endif;?>
                    <?php if ($_SESSION['id'] == $topPost['member_id']):?>
                        <div class='h-1/3'>
                            <a href="delete.php?id=<?php echo h($topPost['id'])?>" class='badge badge-primary'>削除</a>
                        </div>
                    <?php endif;?>
            </div>
        </div>
        <?php foreach ($table as $replyPost) {?>
            <div class='flex p-1 border-y border-t-0 border-gray-200'>
                <div class='w-1/5'>
                    <?php if (!empty($replyPost['icon'])):?>
                        <div class="avatar m-2 md:m-0 dropdown dropdown-right">
                            <label tabindex="0">
                                <div class="w-16 md:w-24 rounded">
                                    <img src="./member_image/<?php echo h($replyPost['icon']);?>" alt="アイコン画像">
                                </div>
                            </label>
                            <label tabindex="0" class='dropdown-content'>
                                <div class='card w-96 bg-base-100 shadow-xl'>
                                    <figure><img src="./member_image/<?php echo h($replyPost['icon']);?>" alt="アイコン画像"></figure>
                                    <div class="card-body">
                                        <h2 class='card-title'><?php echo h($replyPost['name'])?></h2>
                                        <div class='flex justify-center justify-items-center w-full m-0 text-xs'>
                                            <div class='w-1/3'>
                                                <p>&nbsp;</p>
                                                <p class='text-xs'>投稿数</p>
                                                <p class='font-bold'><?php if (empty($allPostNum[$replyPost['mId']])): echo 0; else: echo $allPostNum[$replyPost['mId']]; endif; ;?></p>
                                            </div>
                                            <div class='w-1/3'>
                                                <p>いいね<br>された数</p>
                                                <p class='font-bold'><?php if (empty($likedNum[$replyPost['mId']])): echo 0; else: echo $likedNum[$replyPost['mId']]; endif; ;?></p>
                                            </div>
                                            <div class='w-1/3'>
                                                <p>リツイート<br>された数</p>
                                                <p class='font-bold'><?php if (empty($retweetedNum[$replyPost['mId']])): echo 0; else: echo $retweetedNum[$replyPost['mId']]; endif; ;?></p>
                                            </div>
                                        </div>
                                        <div class="card-actions justify-end">
                                            <a href="individual.php?mId=<?php echo $replyPost['mId'] ;?>&dp=tw&page=1" class='btn btn-primary'>プロフィールを見る</a>
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
                                        <h2 class='card-title'><?php echo h($replyPost['name'])?></h2>
                                        <div class='flex justify-center justify-items-center w-full m-0 text-xs'>
                                            <div class='w-1/3'>
                                                <p>&nbsp;</p>
                                                <p class='text-xs'>投稿数</p>
                                                <p class='font-bold'><?php if (empty($allPostNum[$replyPost['mId']])): echo 0; else: echo $allPostNum[$replyPost['mId']]; endif; ;?></p>
                                            </div>
                                            <div class='w-1/3'>
                                                <p>いいね<br>された数</p>
                                                <p class='font-bold'><?php if (empty($likedNum[$replyPost['mId']])): echo 0; else: echo $likedNum[$replyPost['mId']]; endif; ;?></p>
                                            </div>
                                            <div class='w-1/3'>
                                                <p>リツイート<br>された数</p>
                                                <p class='font-bold'><?php if (empty($retweetedNum[$replyPost['mId']])): echo 0; else: echo $retweetedNum[$replyPost['mId']]; endif; ;?></p>
                                            </div>
                                        </div>
                                        <div class="card-actions justify-end">
                                            <a href="individual.php?mId=<?php echo $replyPost['mId'] ;?>&dp=tw&page=1" class='btn btn-primary'>プロフィールを見る</a>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    <?php endif; ;?>
                </div>
                <div class='flex flex-col items-start w-3/5'>
                        <div class='flex flex-col md:flex-row'>
                            <span class='text-xl md:text-3xl py-2 px-4 font-bold'><?php echo h($replyPost['name']);?></span>
                            <span class='pl-2 text-base-300 pt-2 md:pt-0'><?php echo h($replyPost['created']);?></span>
                        </div>
                        <div class='text-left pt-0 md:pt-2'>
                            <p class='text-xl px-4 text-left'><?php echo url_check(h($replyPost['message']));?></p>
                        </div>
                        <div class='pt-4 w-full'>
                            <div class='flex justify-between justify-items-center w-full'>
                                <div class='flex w-1/2'>
                                    <div>
                                        <?php if(empty($likeFlagList[$replyPost['id']])):?>
                                            <a href="likes.php?post=<?php echo $replyPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>"><i class="fa-regular fa-heart" style="color: #515251;"></i></a>
                                        <?php else: ?>
                                            <a href="dislikes.php?post=<?php echo $replyPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>"><i class="fa-solid fa-heart" style="color: #31c21e;"></i></a>
                                        <?php endif; ;?>
                                    </div>
                                    <div class='pl-2'>
                                        <?php if(!empty($likeList[$replyPost['id']])): echo($likeList[$replyPost['id']]); endif; ;?>
                                    </div>
                                </div>
                                <div class='flex w-1/2'>
                                    <div>
                                        <?php if(empty($retweetFlagList[$replyPost['id']])):?>
                                            <a href="retweets.php?post=<?php echo $replyPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>"><i class="fa-solid fa-retweet" style="color: #515251;"></i></a>
                                        <?php else: ?>
                                            <a href="retweetCancels.php?post=<?php echo $replyPost['id'];?>&back=post&<?php echo $queryStrToFunction; ?>" class='text-primary'><i class="fa-solid fa-retweet" style="color: #31c21e;"></i></a>
                                        <?php endif; ;?>
                                    </div>
                                    <div class='pl-2'>
                                        <?php if(!empty($retweetList[$replyPost['id']])): echo($retweetList[$replyPost['id']]); endif; ;?>
                                    </div>
                                </div>
                            </div>
                        </div>
                </div>
                <div class='flex flex-col w-1/5 h-32'>
                        <div class='h-1/3'>
                            <a href="back.php?created=<?php echo strtotime($replyPost['rtw_created'])?>&re=<?php echo h($replyPost['id']);?>" class='badge badge-primary'>返信</a>
                        </div>
                        <?php if ($_SESSION['id'] == $replyPost['member_id']):?>
                            <div class='h-1/3'>
                                <a href="back.php?created=<?php echo strtotime($replyPost['rtw_created'])?>&ed=<?php echo h($replyPost['id']);?>" class='badge badge-primary'>編集</a>
                            </div>
                        <?php endif;?>
                        <?php if ($_SESSION['id'] == $replyPost['member_id']):?>
                            <div class='h-1/3'>
                                <a href="delete.php?id=<?php echo h($replyPost['id'])?>" class='badge badge-primary'>削除</a>
                            </div>
                        <?php endif;?>
                </div>
            </div>
        <?php } ;?>
        <dt class='p-8 mx-auto'>
            <?php if (empty($queryArray['dp'])){ ?>
                <a href="back.php?<?php echo $queryStrToFunction; ?>" class='btn btn-outline btn-primary w-full max-w-xs'>戻る</a>
            <?php } else{ ?>
                <!-- individual.phpから来た場合、individual.phpのクエリパラメータをback.phpに送る-->
                <a href="back.php?<?php echo $queryStrToFunction; ?>" class='btn btn-outline btn-primary w-full max-w-xs'>戻る</a>
            <?php } ?>
        </dt>
    </main>
    <footer class='footer footer-center p-4 bg-primary-content text-base-content'>
        <div>
            <p>Copyright © 2023 - All right reserved by YUJIRO MIYAKE</p>
        </div>
    </footer>
</body>
</html>