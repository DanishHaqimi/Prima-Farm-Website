<?php
session_start();
include('dbconn.php');
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $profile = $_POST["profile"];
    $userid = $_POST["userid"];
    $password = $_POST["password"];

    if ($profile == "staff") {
        header("Location: staff/staff_login.php");
        exit();
    } elseif ($profile == "customer") {
        header("Location: customer/customer_login.php");
        exit();
    } else {
        $error = "Please select a portal profile.";
    }
            if ($role === 'staff') {
            $query = "SELECT * FROM STAFF WHERE StaffID = :userid";
            $redirect_url = "staff/staff_dashboard.php";
        } else {
            $query = "SELECT * FROM CUSTOMER WHERE CUSTID = :userid";
            $redirect_url = "customer/customer_dashboard.php";
        }

        $stmt = oci_parse($dbconn, $query);
        oci_bind_by_name($stmt, ':userid', $userid);
        oci_execute($stmt);

        $row = oci_fetch_array($stmt, OCI_ASSOC);

        if ($row) {
            // DYNAMIC SCANNER: Find the password column regardless of case or spelling variations (e.g., CUSTPASSWORD, CustPassword, CUST_PASSWORD)
            $db_password = null;
            foreach ($row as $key => $value) {
                if (strpos(strtoupper($key), 'PASS') !== false) {
                    $db_password = $value;
                    break;
                }
            }

            // DYNAMIC SCANNER: Fallbacks for IDs and Names to avoid case-sensitivity issues
            $db_id = '';
            $db_name = '';
            foreach ($row as $key => $value) {
                $upper_key = strtoupper($key);
                if (strpos($upper_key, 'ID') !== false && strpos($upper_key, 'SUPERVISOR') === false) {
                    $db_id = $value;
                }
                if (strpos($upper_key, 'NAME') !== false) {
                    $db_name = $value;
                }
            }

            // Verify credentials securely
            if ($db_password !== null && $db_password === $password) {
                $_SESSION['user_id']   = $db_id;
                $_SESSION['user_name'] = $db_name;
                $_SESSION['user_role'] = $role;

                oci_free_statement($stmt);
                oci_close($dbconn);

                header("Location: " . $redirect_url);
                exit();
            } else {
                $error_message = "Invalid security credential password.";
            }
        } else {
            $error_message = "Identification ID not found for this system role.";
        }
        oci_free_statement($stmt);
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dessert By Ranna Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <h1>🌸 Dessert By Ranna</h1>
    <p>Bakery Ordering System Login</p>

    <?php if ($error != "") { ?>
        <div class="error-box">
            <?php echo $error; ?>
        </div>
    <?php } ?>

    <form method="POST">

        <label>Select Portal Profile</label>

        <div class="portal-box">
            <label class="portal-option">
                <input type="radio" name="profile" value="customer" required>
                🎂 Customer
            </label>

            <label class="portal-option">
                <input type="radio" name="profile" value="staff" required>
                👩‍🍳 Staff 
            </label>
        </div>

        <label>Account / Identification ID</label>
        <input type="text" name="userid" placeholder="e.g., C001 or S001" required>

        <label>Security Password</label>
        <input type="password" name="password" placeholder="Enter password" required>

        <button type="submit">Secure Log In</button>

        <hr>

        <p class="small-text">Don't have a customer profile yet?</p>

        <a class="btn-outline" href="customer/register_customer.php">
            Create an Account
        </a>

    </form>
</div>

</body>
</html>