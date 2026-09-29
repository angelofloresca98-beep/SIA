<?php
$serverhost = "localhost";
$username = "root";
$password = "";

try{
    $conn = new PDO("mysql:host=$serverhost;dbname=market", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    echo $e->getMessage();
}




?>