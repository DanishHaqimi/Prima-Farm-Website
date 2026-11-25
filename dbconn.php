<?php
/* PHP & Oracle DB connection for Livestock Project */
$user = "livestock_app";        // Your fresh isolated user workspace
$pass = "password123";        // Your workspace password
$host = "localhost/freepdb1"; // Your Pluggable Database Service layout

// Establish connection with UTF8 string rendering support
$dbconn = oci_connect($user, $pass, $host, 'AL32UTF8');

//if (!$dbconn) {
//    $e = oci_error();
//    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
//} else {
    // You can keep this echo active temporarily to verify it works in your browser!
//    echo "ORACLE DATABASE CONNECTED SUCCESSFULLY TO LIVESTOCK_APP!!!";
//}
?>

