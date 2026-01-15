<?php

$host = 'localhost';
$username = 'root';
$password = 'sanMariento';
$db ='school';

$conn = mysqli_connect($host,$username,$password,$db);

if(! $conn){
    die ("Connection Failed: ". mysqli_connect_error());
}


?>