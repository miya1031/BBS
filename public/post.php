<?php
session_start();
require('dbconnection.php');
//SESSIONにidを保持していないゲストはログイン画面へ移動させる
if (empty($_SESSION['id'])){
    header('Location: login.php');
    exit();
}

function url_check($value){
    return preg_replace('/((http|https):\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+)/','<a href="$1" target="_blank">$1</a>',$value);
}
function h($value){
    return htmlspecialchars($value, ENT_QUOTES);
}

if (!empty($_REQUEST['id'])){
    $statement = $db->prepare('SELECT * FROM posts WHERE id=?');
    $statement->execute(array($_REQUEST['id']));
    $count = $statement->rowCount();
    if ($count==1){
        $post = $statement->fetch();
        $start = $post['reply_post_id'];//返信先のid　返信でない場合はNULL
        if (empty($start)){//返信ではない場合
            $search = $db->prepare('SELECT m.name, m.icon, p.* FROM members m INNER JOIN posts p ON m.id = p.member_id WHERE reply_post_id=? ORDER BY created');//LIMITは使わない。返信は膨大にならない想定
            $search->execute(array($_REQUEST['id']));
            $start = $_REQUEST['id'];
        } else{//返信の場合
            $search = $db->prepare('SELECT m.name, m.icon, p.* FROM members m INNER JOIN posts p ON m.id = p.member_id WHERE reply_post_id=? ORDER BY created');
            $search->execute(array($start));
        }
        //全返信が$searchに入っているので、それをfetchメソッドで取り出す
        // $posts = $search->fetch();
        //$startに返信元のidが格納されている
        $top = $db->prepare('SELECT m.name, m.icon, p.* FROM members m INNER JOIN posts p ON m.id = p.member_id WHERE p.id=?');
        $top->execute(array($start));
        $topPost = $top->fetch();

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
?>
<!DOCTYPE html>
<html lang="ja" data-theme="lemonade">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BBS</title>
    <link href="output.css" rel="stylesheet">
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
                <div class="avatar">
                    <div class="w-24 rounded">
                        <img src="./member_image/<?php echo h($topPost['icon']);?>" alt="アイコン画像">
                    </div>
                </div>
                <?php else: ?>
                <div class="avatar placeholder">
                    <div class="bg-primary text-primary-content w-24 rounded">
                        <span class="text-3xl">no<br>image</span>
                    </div>
                </div> 
                <?php endif; ;?>
            </div>
            <div class='flex flex-col items-start w-3/5'>
                    <div>
                        <span class='text-3xl py-2 px-4 font-bold'><?php echo h($topPost['name']);?></span>
                        <span class='pl-2 text-base-300'><?php echo h($topPost['created']);?></span>
                    </div>
                    <div class='text-left'>
                        <p class='text-xl px-4 text-left'><?php echo url_check(h($topPost['message']));?></p>
                    </div>
            </div>
            <div class='flex flex-col w-1/5 h-32'>
                    <div class='h-1/3'>
                        <a href="back.php?id=<?php echo h($topPost['id'])?>&re=<?php echo h($topPost['id']);?>" class='badge badge-primary'>返信</a>
                    </div>
                    <?php if ($_SESSION['id'] == $topPost['member_id']):?>
                        <div class='h-1/3'>
                            <a href="back.php?id=<?php echo h($topPost['id'])?>&ed=<?php echo h($topPost['id']);?>" class='badge badge-primary'>編集</a>
                        </div>
                    <?php endif;?>
                    <?php if ($_SESSION['id'] == $topPost['member_id']):?>
                        <div class='h-1/3'>
                            <a href="delete.php?id=<?php echo h($topPost['id'])?>" class='badge badge-primary'>削除</a>
                        </div>
                    <?php endif;?>
            </div>
        </div>
        <?php while ($replyPost = $search->fetch()) {?>
            <div class='flex p-1 border-y border-t-0 border-gray-200'>
                <div class='w-1/5'>
                    <?php if (!empty($replyPost['icon'])):?>
                    <div class="avatar">
                        <div class="w-24 rounded">
                            <img src="./member_image/<?php echo h($replyPost['icon']);?>" alt="アイコン画像">
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="avatar placeholder">
                        <div class="bg-primary text-primary-content w-24 rounded">
                            <span class="text-3xl">no<br>image</span>
                        </div>
                    </div> 
                    <?php endif; ;?>
                </div>
                <div class='flex flex-col items-start w-3/5'>
                        <div>
                            <span class='text-3xl py-2 px-4 font-bold'><?php echo h($replyPost['name']);?></span>
                            <span class='pl-2 text-base-300'><?php echo h($replyPost['created']);?></span>
                        </div>
                        <div class='text-left'>
                            <p class='text-xl px-4 text-left'><?php echo url_check(h($replyPost['message']));?></p>
                        </div>
                </div>
                <div class='flex flex-col w-1/5 h-32'>
                        <div class='h-1/3'>
                            <a href="back.php?id=<?php echo h($replyPost['id'])?>&re=<?php echo h($replyPost['id']);?>" class='badge badge-primary'>返信</a>
                        </div>
                        <?php if ($_SESSION['id'] == $replyPost['member_id']):?>
                            <div class='h-1/3'>
                                <a href="back.php?id=<?php echo h($replyPost['id'])?>&ed=<?php echo h($replyPost['id']);?>" class='badge badge-primary'>編集</a>
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
            <a href="back.php?id=<?php echo h($_REQUEST['id'])?>" class='btn btn-outline btn-primary w-full max-w-xs'>戻る</a>
        </dt>
    </main>
    <footer class='footer footer-center p-4 bg-primary-content text-base-content'>
        <div>
            <p>Copyright © 2023 - All right reserved by YUJIRO MIYAKE</p>
        </div>
    </footer>
</body>
</html>