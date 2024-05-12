<?php 

   define('DB_HOST', 'localhost');
   define('DB_USER', 'uday');
   define('DB_PASS', '123456');
   define('DB_NAME', 'bank'); 


$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if($conn->connect_error){
   die('Connection_error'. $conn->connect_error);
}

?>