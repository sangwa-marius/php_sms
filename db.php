<?php
$host = 'localhost';
$username = 'root';
$password = 'sanMariento1';
$db = 'mis';

$conn = mysqli_connect($host,$username,$password,$db);
if(!$conn){
    die('connection failed'.mysqli_connect_error());
}


?>