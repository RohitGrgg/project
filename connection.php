<?php
$servername = "localhost";
    $db_username = "root";
    $db_passowrd= "";
    $database = "bikemarket";

    //create connection
    $conn = new mysqli ($servername,$db_username,$db_passowrd,$database);

    if ($conn->connect_error){
        die("connection failed: ".$conn->connect_error);
    }
    // else{
    //     echo"connection sucessful";
    // }

    ?>