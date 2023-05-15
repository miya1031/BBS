<?php
session_start();

$error = array();
if ($_POST){
    if (empty($_POST['name'])){
        $error['name'] = 'blank';
    }
    if (empty($_POST['email'])){
        $error['email'] = 'blank';
    }
    if (empty($_POST['password'])){
        $error['password'] = 'blank';
    } elseif (mb_strlen($_POST['password']) < 4){
        $error['password'] = 'length';
    }
}
if (empty($error)){
    $_SESSION['join'] = $_POST;
}

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
    <link href="../style.css" rel="stylesheet">
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
                    <div class='grid h-20 card bg-base-300 rounded-box place-items-center'>
                        <dt>
                            <p class='text-xl p-4 underline'>アイコン</p>
                        </dt>
                        <dd>

                        </dd>
                    </div>
                    <div class='divider'></div>
                    <dt class='p-8'>
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