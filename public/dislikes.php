<?php
session_start();
require('dbconnection.php');

if (!isset($_SESSION['id']) | !isset($_REQUEST['post'])){
    header('Location: index.php');
    exit();
}

$exist = $db->prepare('SELECT COUNT(*) cnt FROM posts WhERE id = ?');
$exist->execute(array($_REQUEST['post']));
$count = $exist->fetch();
if ($count['cnt']==1){
    $dislike = $db->prepare('DELETE FROM likes WHERE post_id = ? AND member_id = ?');
    $dislike->execute(array($_REQUEST['post'], $_SESSION['id']));
} else{
    header('Location: index.php');
    exit();
}

if (empty($_REQUEST['back'])){
    if (empty($_GET['dp'])){
        header('Location: back.php?created=' . $_GET['created']);
        exit();
    } else{
        header('Location: back.php?created=' . $_GET['created'] . '&dp=' . $_GET['dp'] . '&mId=' . $_GET['mId']);
        exit();
    }
} else{
    if (empty($_GET['dp'])){
        ?>
        <script type='text/javascript'>
            location.href = 'post.php?id=<?php echo $_REQUEST['post']; ?>&created=<?php echo $_GET['created'];?>';
        </script>
        <?php
    } else{
        ?>
        <script type='text/javascript'>
            location.href = 'post.php?id=<?php echo $_REQUEST['post']; ?>&created=<?php echo $_GET['created'];?>&dp=<?php echo $_GET['dp']; ?>&mId=<?php echo $_GET['mId'] ?>';
        </script>
        <?php
        exit();
    }
}
?>