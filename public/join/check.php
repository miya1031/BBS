<?php
session_start();
require('../dbconnection.php');

$error = array();
//SESSION情報[name]がなかったら登録画面に移動させる
if (empty($_SESSION['name'])){
    header('Location: index.php');
    exit();
}

function h($value){
    return htmlspecialchars($value, ENT_QUOTES);
}
if (!empty($_POST)){//登録ボタンが押されたらこれが動く
    $statement = $db->prepare('INSERT INTO members SET name=?, email=?, password=?, icon=?');
    $pass = password_hash($_SESSION['password'], PASSWORD_DEFAULT);
    $statement->execute(array($_SESSION['name'], $_SESSION['email'], $pass, $_SESSION['image']));

    header('Location: thanks.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ja" data-theme="lemonade">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BBS</title>
    <link href="../output.css" rel="stylesheet">
</head>
<body>
    <header class='flex justify-center navbar-primary bg-primary'>
            <h1 class="p-4 text-3xl font-bold text-white">ひとこと掲示板</h1>
    </header>
    <main class='flex flex-col justify-center items-center'>
        <div id = 'page-info' class='m-4 p-4 bg-accent border border-accent-content'>
            <h2 class='p-4 text-2xl font-bold text-center underline'>登録の確認</h2>
            <p>記入事項を確認してください。
                <br>確認したら「登録」ボタンを押してください。
            </p>
        </div>
        <div>
            <form action="" method="post">
                <input type="hidden" name="submit">
                <dl class='flex flex-col w-full'>
                    <div class='grid card bg-base-300 rounded-box place-items-center px-10'>
                        <dt>
                            <p class='text-xl p-4 underline'>ニックネーム</p>
                        </dt>
                        <dd>
                            <p class='text-xl pb-4 px-4'><?php echo h($_SESSION['name']);?></p>
                        </dd>
                    </div>
                    <div class='divider'></div>
                    <div class='grid card bg-base-300 rounded-box place-items-center'>
                        <dt>
                            <p class='text-xl p-4 underline'>メールアドレス</p>
                        </dt>
                        <dd>
                            <p class='text-xl pb-4 px-4'><?php echo h($_SESSION['email']);?></p>
                        </dd>
                    </div>
                    <div class='divider'></div>
                    <div class='grid card bg-base-300 rounded-box place-items-center'>
                        <dt>
                            <p class='text-xl p-4 underline'>パスワード</p>
                        </dt>
                        <dd>
                            <p class='text-xl pb-4 px-4'>パスワードは表示しません。</p>
                        </dd>
                    </div>
                    <div class='divider'></div>
                    <div class='grid card bg-base-300 rounded-box place-items-center'>
                        <dt>
                            <p class='text-xl p-4 underline'>アイコン</p>
                        </dt>
                        <dd>
                        <?php if (!empty($_SESSION['image'])):?>
                        <div class="avatar">
                            <div class="w-24 rounded">
                                <img src="../member_image/<?php echo $_SESSION['image'];?>" alt="アイコン画像" class='w-24 h-auto'>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="avatar placeholder">
                            <div class="bg-primary text-primary-content w-24 rounded">
                                <span class="text-3xl">no<br>image</span>
                            </div>
                        </div> 
                        <?php endif; ;?>
                        </dd>
                    </div>
                    <div class='divider'></div>
                    <dt class='p-4'>
                        <a href="index.php" class='btn btn-outline btn-primary w-full max-w-xs'>修正</a>
                    </dt>
                    <dt class='p-4'>
                        <input type="submit" value="登録" class='btn btn-outline btn-primary w-full max-w-xs'>
                    </dt>
                </dl>
            </form>
        </div>
    </main>
    <footer class='footer footer-center p-4 bg-primary-content text-base-content'>
        <div>
            <p>Copyright © 2023 - All right reserved by YUJIRO MIYAKE</p>
        </div>
    </footer>
</body>
</html>