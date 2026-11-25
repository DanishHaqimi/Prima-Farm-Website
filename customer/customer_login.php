<?php
session_start();
include('../dbconn.php');

if(isset($_POST['login']))
{
    $phone = $_POST['phone'];

    $sql = "SELECT * FROM CUSTOMER
            WHERE PHONENUMBER = :phone";

    $stmt = oci_parse($dbconn,$sql);

    oci_bind_by_name($stmt,":phone",$phone);

    oci_execute($stmt);

    if($row = oci_fetch_assoc($stmt))
    {
        $_SESSION['customer_id'] = $row['CUSTOMERID'];
        header("Location: customer_dashboard.php");
    }
}
?>

<form method="post">
Phone:
<input type="text" name="phone">

<input type="submit" name="login" value="Login">
</form>