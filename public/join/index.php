<?php
session_start();
require('../dbconnection.php');

$error = array();
if (!empty($_POST)){
    if (empty($_POST['name'])){
        $error['name'] = 'blank';
    }
    if (empty($_POST['email'])){
        $error['email'] = 'blank';
    }else{
        //入力されたメールアドレスがすでに登録されていないか確認
        $statement = $db->prepare('SELECT COUNT(email) AS num FROM members WHERE email = ?');
        $statement->execute(array($_POST['email']));
        $emails = $statement->fetch();
        if ($emails['num'] >= 1){
            $error['email'] = 'duplication';
        }
    }
    if (empty($_POST['password'])){
        $error['password'] = 'blank';
    } elseif (mb_strlen($_POST['password']) < 4){
        $error['password'] = 'length';
    }
    if (!empty($_FILES['icon']['name'])){
        $fileName = $_FILES['icon']['name'];
        $tempName = $_FILES['icon']['tmp_name'];
        if (!in_array(substr($fileName,-4), array('.gif','.jpg'))){
            $fileName = '';
            $tempName = '';
            $error['icon'] = 'extension';
        }
    }else{
        $fileName = '';
        $tempName = '';
    }

    if (empty($error)){
        if (!empty($fileName) && !empty($tempName)){
            $image= date('YmdHis') . $fileName;
            move_uploaded_file($tempName,'../member_image/'.$image);
        }else{
            $image='';
        }
        $_SESSION['name'] = $_POST['name'];
        $_SESSION['email'] = $_POST['email'];
        $_SESSION['password'] = $_POST['password'];
        $_SESSION['image'] = $image;
        header('Location: check.php');
        exit();
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
    <link href="../style.css" rel="stylesheet">
</head>
<body>
    <header class='flex justify-center navbar-primary bg-primary'>
            <h1 class="p-4 text-3xl font-bold text-white">ひとこと掲示板</h1>
    </header>
    <main class='flex flex-col justify-center items-center'>
        <div id = 'page-info' class='m-4 p-4 bg-accent border border-accent-content'>
            <h2 class='p-4 text-2xl font-bold text-center underline'>会員登録</h2>
            <p>必要事項を記入してください。</p>
        </div>
        <div>
            <form action="" method="post" enctype="multipart/form-data">
                <dl>
                    <dt>
                        <p class='text-xl p-4'>ニックネーム&nbsp;<span class='badge badge-secondary'>必須</span></p>
                    </dt>
                    <dd>
                        <input type="text" name="name" id="" placeholder="山田 太郎" class='input input-bordered input-primary w-full max-w-xs' value="<?php if(isset($_POST['name'])): echo h($_POST['name']); endif;;?>">
                        <?php if (!empty($error['name'])):
                            if ($error['name'] == 'blank'):?>
                                <div class="alert alert-warning shadow-lg my-2">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <span>ニックネームを入力してください</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ;?>
                    </dd>
                    <dt>
                        <p class='text-xl p-4'>メールアドレス&nbsp;<span class='badge badge-secondary'>必須</span></p>
                    </dt>
                    <dd>
                        <input type="text" name="email" id="" placeholder="taro.yamada@example.co.jp" class='input input-bordered input-primary w-full max-w-xs' value="<?php if(isset($_POST['email'])): echo h($_POST['email']); endif;;?>">
                        <?php if (!empty($error['email'])):?>
                                <div class="alert alert-warning shadow-lg my-2">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <?php if ($error['email'] == 'blank'):?>
                                            <span>メールアドレスを入力してください</span>
                                        <?php elseif($error['email'] == 'duplication'):?>
                                            <span>すでに登録されている<br>メールアドレスです</span>
                                        <?php endif; ?>
                                    </div>
                                </div>   
                        <?php endif; ;?>
                    </dd>
                    <dt>
                        <p class='text-xl p-4'>パスワード&nbsp;<span class='badge badge-secondary'>必須</span></p>
                    </dt>
                    <dd>
                        <input type="text" name="password" id="" placeholder="123456789" class='input input-bordered input-primary w-full max-w-xs' value="<?php if(isset($_POST['password'])): echo h($_POST['password']); endif;;?>">
                        <?php if (!empty($error['password'])):?>
                                <div class="alert alert-warning shadow-lg my-2">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <?php if ($error['password'] == 'blank'):?>
                                            <span>パスワードを入力してください</span>
                                        <?php elseif($error['password'] == 'length'): ?>
                                            <span>パスワードは4文字以上で<br>入力してください</span>
                                        <?php endif; ?>
                                    </div>
                                </div>   
                        <?php endif; ;?>
                    </dd>
                    <dt>
                        <p class='text-xl p-4'>アイコン</p>
                    </dt>
                    <dd>
                        <input type="file" name="icon" id="" class='file-input file-input-bordered file-input-primary w-full max-w-xs'>
                        <?php if (!empty($error['icon'])):
                            if ($error['icon'] == 'extension'):?>
                                <div class="alert alert-warning shadow-lg my-2">
                                    <div>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        <span>有効な拡張子（gif/jpg）のファイルを<br>指定してください</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ;?>
                    </dd>
                    <dt class='p-8'>
                        <input type="submit" value="入力内容を確認する" class='btn btn-outline btn-primary w-full max-w-xs'>
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