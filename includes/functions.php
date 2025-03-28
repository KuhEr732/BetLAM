<?php
function db_connection(){
    require "db_credentials.php";
    $db_connection = mysqli_connect(DB_HOST, DB_USER, DB_PW, DB_NAME);
    if (!$db_connection){
        return die("Verbindung Fehlgeschlagen: " . mysqli_connect_error());
    }
    mysqli_set_charset($db_connection, 'utf8');
    return $db_connection;
}
?>