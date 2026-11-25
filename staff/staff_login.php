<?php
session_start();
include('../dbconn.php');

if(isset($_POST['login']))
{
    $staffid = $_POST['staffid'];

    $sql = "
    SELECT *
    FROM STAFF
    WHERE STAFFID = :id
    ";

    $stmt = oci_parse($dbconn,$sql);

    oci_bind_by_name($stmt,":id",$staffid);

    oci_execute($stmt);

    if($row = oci_fetch_assoc($stmt))
    {
        $_SESSION['staff_id'] = $staffid;
        header("Location: staff_dashboard.php");
    }
}
?>

<form method="post">
Staff ID:
<input type="text" name="staffid">

<input type="submit" name="login">
</form>