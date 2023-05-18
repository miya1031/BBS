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

if (!empty($_REQUEST['re'])){//$_REQUEST['re']に格納されている値が本当に存在するか
    $search = $db->prepare('SELECT * FROM posts WHERE id=?');
    $search->execute(array($_REQUEST['re']));
    $count =$search->rowCount();
    if ($count==1){
        $reply = $_REQUEST['re'];
        $m = $search->fetch();
        $statement = $db->prepare('SELECT name FROM members WHERE id = ?');
        $statement->execute(array($m['member_id']));
        $reply_name = $statement->fetch();//投稿画面に宛先を表示
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
$max_page = ceil($count['cnt']/5);
$page = min($page, $max_page);//ページ番号が最大ページ数より大きい場合

$start_num = ($page-1)*5;

$statement = $db->prepare('SELECT m.name, m.icon, p.* FROM members m INNER JOIN posts p ON m.id=p.member_id ORDER BY p.created DESC LIMIT ?, 5');
$statement->bindParam(1,$start_num,PDO::PARAM_INT);
$statement->execute();

function h($value){
    return htmlspecialchars($value, ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="ja" data-theme="lemonade">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BBS</title>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <header class='flex justify-center navbar-primary bg-primary'>
            <h1 class="p-4 text-3xl font-bold text-white">ひとこと掲示板</h1>
    </header>
    <main class='flex flex-col justify-center items-center'>
        <div class='bg-primary-content w-full text-center'>
            <p class='text-xl p-4'>ようこそ！<span class='text-3xl text-primary font-bold'><?php echo h($_SESSION['name'])?></span>さん</p>
        </div>
        <div class='flex grow justify-center text-center w-full border border-gray-200'>
            <div class='w-1/2 border-x border-l-0 border-gray-200'>
                <form action="" method="post">
                    <input type="hidden" name="page" value="<?php echo h($page) ;?>">
                    <dl>
                        <dt class='border-y border-t-0 border-gray-200'>
                            <p class='text-2xl p-4 font-bold'>メッセージ</p>
                        </dt>
                        <dd class='p-4'>
                        <textarea class="textarea textarea-primary w-96 h-48" placeholder="メッセージ" name='message'><?php if(!empty($p['message'])): echo h($p['message']); elseif(!empty($reply_name['name'])): echo '@' . $reply_name['name']; endif;?></textarea>
                        </dd>
                        <dt class='p-8'>
                            <input type="submit" value="投稿" class='btn btn-outline btn-primary w-full max-w-xs'>
                        </dt>
                    </dl>
                </form>
            </div>
            <div class='w-1/2'>
                <div  class='border-y border-t-0 border-gray-200'>
                    <p class='text-2xl p-4 font-bold'>投稿一覧</p>
                </div>
                <div class='p-2'>
                    <?php while($post = $statement->fetch()){ ?>
                        <div class='flex p-1 hover:bg-primary-content h-32'>
                            <div class='w-1/5'>
                                <?php if (!empty($post['icon'])):?>
                                <div class="avatar">
                                    <div class="w-24 rounded">
                                        <img src="./member_image/<?php echo h($post['icon']);?>" alt="アイコン画像">
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
                                        <span class='text-3xl py-2 px-4 font-bold'><?php echo h($post['name']);?></span>
                                        <span class='pl-2 text-base-300'><?php echo h($post['created']);?></span>
                                    </div>
                                    <div class='text-left'>
                                        <p class='text-xl px-4 text-left'><?php if (mb_strlen(h($post['message']))<60): echo url_check(h($post['message'])); else: echo url_check(mb_substr(h($post['message']),0,60)) . '&nbsp;...'; endif;?></p>
                                    </div>
                            </div>
                            <div class='flex flex-col w-1/5'>
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
                                            <a href="delete.php" class='badge badge-primary'>削除</a>
                                        </div>
                                    <?php endif;?>
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