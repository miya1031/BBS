<?php
try{
    $db = new PDO('mysql:dbname=bbs;host=db;charset=utf8mb4','root','example');
} catch (PDOException $e){
    header('Content-Type: text/plain; charset=UTF-8', true, 500);
    exit($e->getMessage()); 
}
?>