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

if (!empty($_REQUEST['re'])){//$_REQUEST['re']に格納されている値が本当に存在するか
    $search = $db->prepare('SELECT * FROM posts WHERE id=?');
    $search->execute(array($_REQUEST['re']));
    $count =$search->rowCount();
    if ($count==1){
        $reply = $_REQUEST['re'];//返信先post_id
        $m = $search->fetch();
        $statement = $db->prepare('SELECT name FROM members WHERE id = ?');
        $statement->execute(array($m['member_id']));
        $reply_name = $statement->fetch();//投稿画面に宛先を表示
        $reply_post_id = $m['reply_post_id'];//返信先のreply_post_id
        if (!empty($reply_post_id)){
            $reply = $reply_post_id;
        }
    } else{
        $reply =NULL;
    }
} else{
    $reply = NULL;
}
if (!empty($_REQUEST['ed'])){
    $modify = $db->prepare('SELECT * FROM posts WHERE id=?');
    $modify->execute(array($_REQUEST['ed']));
    $count =$modify->rowCount();
    if ($count==1){//$_POST['ed']が有効な値かどうか
        $modifiedPostId = $_REQUEST['ed'];
        $p = $modify->fetch();
        $modifiedPostMemberID = $p['member_id'];
    }
}

if (!empty($_POST['message'])){
    if (isset($_REQUEST['ed'])){//編集モードの場合
        if ($_SESSION['id'] == $modifiedPostMemberID){//編集者が投稿者と同じIDがチェック
            $statement = $db->prepare('UPDATE posts SET message=? WHERE id = ?');
            $statement->execute(array(h($_POST['message']),$modifiedPostId));
        }
    
    } else{
        $insert = $db->prepare('INSERT INTO posts SET member_id=?, message=?, reply_post_id=?');
        $insert->execute(array($_SESSION['id'], h($_POST['message']), $reply));
    }
    //投稿したら$_REQUEST['re'],$_REQUEST['ed']を消す必要がある
    if (!empty($_POST['page'])){
        header('Location: index.php?page=' . $_POST['page']);
    } else{
        header('Location: index.php');
    }
    exit();
}

if (!empty($_REQUEST['page'])) {
    $page = $_REQUEST['page'];
} else{
    $page = 1;
}
$page = max($page,1);//ページ番号がマイナスだった場合

$statement = $db->query('SELECT COUNT(*) AS cnt FROM posts');
$count = $statement->fetch();
$statement = $db->query('SELECT COUNT(*) AS rtw_cnt FROM retweets');//リツイートの数も投稿数に加算する
$rtw_count = $statement->fetch();
$max_page = ceil(($count['cnt']+$rtw_count['rtw_cnt'])/5);
if ($max_page==0){//投稿がひとつもない場合はmax_page=0になってしまい、エラーが出るので1を代入する。
    $max_page = 1;
}
$page = min($page, $max_page);//ページ番号が最大ページ数より大きい場合

$start_num = ($page-1)*5;

$statement = $db->prepare('SELECT m.id AS mId, m.name, m.icon, p.* , p.created AS rtw_created, NULL AS rtw_name FROM members m INNER JOIN posts p ON m.id=p.member_id 
                        UNION ALL
                        SELECT m_.id AS mId, m_.name, m_.icon, p_.* , r.created AS rtw_created, e.name AS rtw_name FROM members m_ INNER JOIN posts p_ ON m_.id=p_.member_id INNER JOIN retweets r ON p_.id=r.post_id INNER JOIN members e ON r.member_id = e.id
                        ORDER BY rtw_created DESC LIMIT ?, 5
                        ');
$statement->bindParam(1,$start_num,PDO::PARAM_INT);
$statement->execute();
$table = $statement->fetchAll(PDO::FETCH_ASSOC);
$postList = array();
$mIdList = array();
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
    <main class='flex flex-col justify-center items-center'>
        <div class='bg-primary-content w-full text-center'>
            <p class='text-xl p-4'>ようこそ！<span class='text-3xl text-primary font-bold'><?php echo h($_SESSION['name'])?></span>さん</p>
        </div>
        <div class='flex flex-col xl:flex-row justify-center text-center border-y w-full border-gray-200'>
            <div class='w-full xl:w-1/2 border-x border-l-0 border-gray-200'>
                <form action="" method="post">
                    <input type="hidden" name="page" value="<?php echo h($page) ;?>">
                    <dl>
                        <dt class='border-y border-t-0 border-gray-200'>
                            <p class='text-2xl p-4 font-bold'>メッセージ</p>
                        </dt>
                        <dd class='p-4'>
                        <textarea class="textarea textarea-primary w-4/5 md:w-96 h-48" placeholder="メッセージ" name='message'><?php if(!empty($p['message'])): echo h($p['message']); elseif(!empty($reply_name['name'])): echo '@' . $reply_name['name']; endif;?></textarea>
                        </dd>
                        <dt class='p-8'>
                            <input type="submit" value="投稿" class='btn btn-outline btn-primary w-full max-w-xs'>
                        </dt>
                    </dl>
                </form>
            </div>
            <div class='w-full xl:w-1/2 border-y border-b-0 border-gray-200 xl:border-t-0'>
                <div  class='border-y border-t-0 border-gray-200'>
                    <p class='text-2xl p-4 font-bold'>投稿一覧</p>
                </div>
                <div class='p-2'>
                    <?php foreach($table as $post){ ?>
                        <div class='flex flex-col p-1 hover:bg-primary-content h-auto border-b border-gray-200'>
                            <div>
                                <p class='text-primary p-2'>
                                    <?php if(!empty($post['rtw_name'])):;?><i class="fa-solid fa-retweet" style="color: #31c21e;"></i>&nbsp;<?php echo h($post['rtw_name']) . 'さんがリツイートしました'; endif; ;?>
                                </p>
                            </div>
                            <div class='flex'>
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
                                            <span class='pl-2 text-base-300 pt-0 md:pt-2'><?php echo h($post['created']);?></span>
                                        </div>
                                        <a class='text-left' href='post.php?id=<?php echo h($post['id'])?>&created=<?php echo strtotime($post['rtw_created']);?>'>
                                            <p class='text-xl px-4 text-left'><?php if (mb_strlen(h($post['message']))<40): echo url_check(h($post['message'])); else: echo url_check(mb_substr(h($post['message']),0,40)) . '&nbsp;...'; endif;?></p>
                                        </a>
                                        <div class='pt-4 w-full'>
                                            <div class='flex justify-between justify-items-center w-full'>
                                                <div class='flex w-1/2'>
                                                    <div>
                                                        <?php if(empty($likeFlagList[$post['id']])):?>
                                                            <a href="likes.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']);?>"><i class="fa-regular fa-heart" style="color: #515251;"></i></a>
                                                        <?php else: ?>
                                                            <a href="dislikes.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']);?>" class='text-primary'><i class="fa-solid fa-heart" style="color: #31c21e;"></i></a>
                                                        <?php endif; ;?>
                                                    </div>
                                                    <div class='pl-2'>
                                                        <?php if(!empty($likeList[$post['id']])): echo($likeList[$post['id']]); endif; ;?>
                                                    </div>
                                                </div>
                                                <div class='flex w-1/2'>
                                                    <div>
                                                        <?php if(empty($retweetFlagList[$post['id']])):?>
                                                            <a href="retweets.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']);?>"><i class="fa-solid fa-retweet" style="color: #515251;"></i></a>
                                                        <?php else: ?>
                                                            <a href="retweetCancels.php?post=<?php echo $post['id'];?>&created=<?php echo strtotime($post['rtw_created']);?>"><i class="fa-solid fa-retweet" style="color: #31c21e;"></i></a>
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
                                            <a href="index.php?page=<?php echo h($page)?>&re=<?php echo h($post['id'])?>" class='badge badge-primary'>返信</a>
                                        </div>
                                        <?php if ($_SESSION['id'] == $post['member_id']):?>
                                            <div class='h-1/3'>
                                                <a href="index.php?page=<?php echo h($page)?>&ed=<?php echo h($post['id'])?>" class='badge badge-primary'>編集</a>
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
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class='flex'>
            <div class='p-8'>
                <?php if($page>1):?>
                    <a href="index.php?page=<?php echo h($page)-1;?>" class='btn btn-primary'>前ページ</a>
                <?php else: ?>
                    <button class="btn btn-active btn-ghost">前ページ</button>
                <?php endif; ;?>
            </div>
            <div class='p-8'>
                <?php if($page<$max_page):?>
                    <a href="index.php?page=<?php echo h($page)+1;?>" class='btn btn-primary'>次ページ</a>
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