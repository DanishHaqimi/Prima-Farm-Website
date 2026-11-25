<?php
$service_name = "localhost:1521/FREEPDB1"; // Tells PHP exactly where to route (host:port/service)
$username = "bakery_admin";                 // Make sure this matches your created user
$password = "BakeryPassword123";           // Your password

// Pass the combined string directly into oci_connect
$dbconn = oci_connect($username, $password, $service_name, 'AL32UTF8');

//if (!$dbconn) {
//    $e = oci_error();
//    trigger_error(htmlentities($e['message'], ENT_QUOTES), E_USER_ERROR);
//} else {
//    echo "Connected to Oracle Database Bakery Project successfully!";
//}
?>