<?php
session_start();
require('dbconnection.php');

if (!empty($_SESSION['email'])){
    $_POST['email'] = $_SESSION['email'];
    $_POST['password'] = $_SESSION['password'];
}

if (!empty($_POST['email']) && !empty($_POST['password'])){
    $error = array();
    $statement = $db->prepare('SELECT * FROM members WHERE email=?');
    $statement->execute(array($_POST['email']));
    $members = $statement->fetch();
    $count = $statement->rowCount();//メールアドレスの重複はないことを登録段階で確認しているのでここで得られるレコードは 0 or 1
    
    if ($count == 1){
        if (password_verify($_POST['password'], $members['password'])){
            $_SESSION['id'] = $members['id'];
            header('Location: index.php');
            exit();
        } else{
            $error['password'] = 'disagreement';
        }

    } else{//$membersがからの場合は未登録
        $error['email'] = 'unregistered';
    }
} else{
    //email,password両方とも空欄の時は初期状態であるから警告は出さないようにする
    if (empty($_POST['email']) && !empty($_POST['password'])){
        $error['email'] = 'blank';
    }
    if (empty($_POST['password'] && !empty($_POST['email']))) {
        $error['password'] = 'blank';
    }
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
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <header class='flex justify-center navbar-primary bg-primary'>
            <h1 class="p-4 text-3xl font-bold text-white">ひとこと掲示板</h1>
    </header>
    <main class='flex flex-col justify-center items-center'>
        <div id = 'page-info' class='m-4 p-4 bg-accent border border-accent-content'>
            <h2 class='p-4 text-2xl font-bold text-center underline'>ログイン</h2>
            <p>メールアドレスとパスワードを
                <br>入力してください。
            </p>
        </div>
        <div>
            <form action="" method="post">
                <dl>
                    <dt>
                        <p class='text-xl p-4'>メールアドレス</p>
                    </dt>
                    <dd>
                        <input type="text" name="email" id="" placeholder="taro.yamada@example.co.jp" class='input input-bordered input-primary w-full max-w-xs'>
                        <?php if (!empty($error['email'])):?>
                                <div class="alert alert-warning shadow-lg my-2">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <?php if ($error['email'] == 'blank'):?>
                                            <span>メールアドレスを入力してください</span>
                                        <?php elseif($error['email'] == 'unregistered'): ?>
                                            <span>正しいメールアドレスを入力してください</span>
                                        <?php endif; ?>
                                    </div>
                                </div>   
                        <?php endif; ;?>
                    </dd>
                    <dt>
                        <p class='text-xl p-4'>パスワード</p>
                    </dt>
                    <dd>
                        <input type="text" name="password" id="" placeholder="123456789" class='input input-bordered input-primary w-full max-w-xs'>
                        <?php if (!empty($error['password'])):?>
                                <div class="alert alert-warning shadow-lg my-2">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <?php if ($error['password'] == 'blank'):?>
                                            <span>パスワードを入力してください</span>
                                        <?php elseif($error['password'] == 'disagreement'): ?>
                                            <span>正しいパスワードを入力してください</span>
                                        <?php endif; ?>
                                    </div>
                                </div>   
                        <?php endif; ;?>
                    </dd>
                    <dt class='p-8'>
                        <input type="submit" value="ログイン" class='btn btn-outline btn-primary w-full max-w-xs'>
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