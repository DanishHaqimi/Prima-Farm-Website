<?php
/* PHP & Oracle DB connection test */
$user = "hr";        // Oracle username
$pass = "oracle";    // Oracle password
$host = "localhost/freepdb1"; // Oracle service name (XE is common for Express Edition)

$dbconn = oci_connect($user, $pass, $host);
$conn = $dbconn;
