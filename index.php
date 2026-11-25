<?php
session_start();
include('dbconn.php'); // Core database connection file

$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userid   = trim($_POST['userid']);
    $password = trim($_POST['password']);
    $role     = $_POST['role']; // 'staff' or 'customer'

    if (empty($userid) || empty($password)) {
        $error_message = "Please fill in all credential fields.";
    } else {
        // Decide default landing pages based on structural role
        if ($role === 'staff') {
            $query = "SELECT * FROM STAFF WHERE StaffID = :userid";
            
            // MANAGER OVERRIDE ROUTE: Check for explicit supervisor/manager bypass
            if ($userid === 'S001' && $password === 'staffpass123') {
                $redirect_url = "staff/manager_dashboard.php";
            } else {
                $redirect_url = "staff/staff_dashboard2.php";
            }
        } else {
            $query = "SELECT * FROM CUSTOMER WHERE CustomerID = :userid";
            $redirect_url = "customer/customer_dashboard.php";
        }

        $stmt = oci_parse($dbconn, $query);
        oci_bind_by_name($stmt, ':userid', $userid);
        oci_execute($stmt);

        $row = oci_fetch_array($stmt, OCI_ASSOC);

        if ($row) {
            // DYNAMIC SCANNER: Find the password column regardless of case variations
            $db_password = null;
            foreach ($row as $key => $value) {
                if (strpos(strtoupper($key), 'PASS') !== false) {
                    $db_password = $value;
                    break;
                }
            }

            // DYNAMIC SCANNER: Fallbacks for IDs and Names
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
                
                // Track explicitly if this individual is the manager in their session profile
                if ($userid === 'S001') {
                    $_SESSION['user_role'] = 'manager';
                } else {
                    $_SESSION['user_role'] = $role;
                }

                oci_free_statement($stmt);
                oci_close($dbconn);

                header("Location: " . $redirect_url);
                exit();
            } else {
                $error_message = "Invalid security credential password.";
            }
        } else {
            // Fallback structural check left intact from original legacy template
            if ($role === 'customer') {
                $query = "SELECT CUSTOMERID, CUSTPASSWORD FROM CUSTOMER WHERE CUSTOMERID = :id";
                $stmt = oci_parse($dbconn, $query);
                oci_bind_by_name($stmt, ':id', $userid);
                oci_execute($stmt);

                if ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
                    $db_password = $row['CUSTPASSWORD']; 
                    if ($password === $db_password) {
                        $_SESSION['customer_id'] = $row['CUSTOMERID'];
                        $_SESSION['user_role'] = 'customer';
                        header("Location: customer/customer_dashboard.php");
                        exit;
                    }
                }
            } else if ($role === 'staff') {
                $query = "SELECT STAFFID, STAFFPASSWORD FROM STAFF WHERE STAFFID = :id";
                $stmt = oci_parse($dbconn, $query);
                oci_bind_by_name($stmt, ':id', $userid);
                oci_execute($stmt);

                if ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
                    $db_password = $row['STAFFPASSWORD']; 
                    if ($password === $db_password) {
                        $_SESSION['staff_id'] = $row['STAFFID'];
                        
                        if ($userid === 'S001' && $password === 'staffpass123') {
                            $_SESSION['user_role'] = 'manager';
                            header("Location: staff/manager_dashboard.php");
                        } else {
                            $_SESSION['user_role'] = 'staff';
                            header("Location: staff/staff_dashboard2.php");
                        }
                        exit;
                    }
                }
            }
            $error_message = "Identification ID not found for this system role.";
        }
        if (isset($stmt) && $stmt) {
            oci_free_statement($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Livestock Portal Gateway</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #2c3e50, #1abc9c);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .login-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        h2 { margin-top: 0; color: #2c3e50; text-align: center; font-size: 1.6rem; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #7f8c8d; font-weight: 600; font-size: 0.9rem; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px; border: 2px solid #eef2f5; border-radius: 6px; box-sizing: border-box; font-size: 1rem; transition: border-color 0.2s;
        }
        input:focus { border-color: #1abc9c; outline: none; }
        .role-selector { display: flex; gap: 15px; margin-top: 5px; }
        .role-option { flex: 1; text-align: center; }
        .role-option input { display: none; }
        .role-option label {
            display: block; padding: 10px; border: 2px solid #eef2f5; border-radius: 6px; cursor: pointer; transition: all 0.2s; color: #2c3e50; font-weight: bold;
        }
        .role-option input:checked + label { background-color: #1abc9c; border-color: #1abc9c; color: #ffffff; }
        .btn-submit {
            width: 100%; background-color: #2c3e50; color: #ffffff; border: none; padding: 14px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background-color 0.2s; margin-bottom: 10px;
        }
        .btn-submit:hover { background-color: #1a252f; }
        .error-banner { background-color: #fdadad; color: #c0392b; padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; font-weight: bold; text-align: center; }
        .register-box { text-align: center; margin-top: 25px; border-top: 1px solid #eef2f5; padding-top: 20px; }
        .register-text { font-size: 0.85rem; color: #95a5a6; margin-bottom: 10px; }
        .btn-reg-link { display: inline-block; width: 100%; padding: 11px; border: 2px solid #1abc9c; color: #1abc9c; text-decoration: none; font-weight: bold; border-radius: 6px; box-sizing: border-box; transition: all 0.2s; }
        .btn-reg-link:hover { background-color: #1abc9c; color: white; }
    </style>
</head>
<body>

<div class="login-card">
    <h2>🌾 Livestock System Login</h2>
    
    <?php if(!empty($error_message)): ?>
        <div class="error-banner"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form action="index.php" method="POST">
        
        <div class="form-group">
            <label>Select Portal Profile</label>
            <div class="role-selector">
                <div class="role-option">
                    <input type="radio" id="role_customer" name="role" value="customer" checked>
                    <label for="role_customer">🛍️ Customer</label>
                </div>
                <div class="role-option">
                    <input type="radio" id="role_staff" name="role" value="staff">
                    <label for="role_staff">💼 Staff Panel</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="userid">Account / Identification ID</label>
            <input type="text" id="userid" name="userid" placeholder="e.g., S001 or C001" required>
        </div>

        <div class="form-group">
            <label for="password">Security Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-submit">Secure Log In</button>

        <div class="register-box">
            <div class="register-text">Don't have a customer profile yet?</div>
            <a href="register.php" class="btn-reg-link">Create an Account</a>
        </div>
    </form>
</div>

</body>
</html>