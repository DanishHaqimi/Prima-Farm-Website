<?php
include('dbconn.php'); // Core configuration file

$message = "";
$msg_type = ""; // 'error' or 'success'

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cust_id   = trim($_POST['customer_id']);
    $cust_name = trim($_POST['customer_name']);
    $password  = trim($_POST['password']);

    if (empty($cust_id) || empty($cust_name) || empty($password)) {
        $message = "All form inputs are strictly required.";
        $msg_type = "error";
    } else {
        // Step 1: Duplicate structural verification step
        $check_query = "SELECT CustomerID FROM CUSTOMER WHERE CustomerID = :cust_id";
        $check_stmt = oci_parse($dbconn, $check_query);
        oci_bind_by_name($check_stmt, ':cust_id', $cust_id);
        oci_execute($check_stmt);
        
        if (oci_fetch_array($check_stmt, OCI_ASSOC)) {
            $message = "Registration failed: This Customer ID is already taken!";
            $msg_type = "error";
            oci_free_statement($check_stmt);
        } else {
            oci_free_statement($check_stmt);

            // Step 2: Updated query matching your new column name: CUSTPASSWORD
            $insert_query = "INSERT INTO CUSTOMER (CustomerID, CustomerName, CustPassword) 
                             VALUES (:cust_id, :cust_name, :password)";
            
            $insert_stmt = oci_parse($dbconn, $insert_query);
            
            oci_bind_by_name($insert_stmt, ':cust_id', $cust_id);
            oci_bind_by_name($insert_stmt, ':cust_name', $cust_name);
            oci_bind_by_name($insert_stmt, ':password', $password);
            
            $result = oci_execute($insert_stmt, OCI_NO_AUTO_COMMIT);

            if ($result) {
                oci_commit($dbconn); // Save modifications to the DB configuration schema
                $message = "Registration successful! You can now log in.";
                $msg_type = "success";
            } else {
                oci_rollback($dbconn);
                $error = oci_error($insert_stmt);
                $message = "Database Error: " . $error['message'];
                $msg_type = "error";
            }
            oci_free_statement($insert_stmt);
        }
    }
    oci_close($dbconn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📝 Customer Account Registration</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #1abc9c, #2c3e50);
            height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .reg-card {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 400px;
        }
        h2 { margin-top: 0; color: #2c3e50; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #7f8c8d; font-weight: 600; font-size: 0.9rem; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 12px; border: 2px solid #eef2f5; border-radius: 6px; box-sizing: border-box; font-size: 1rem;
        }
        input:focus { border-color: #1abc9c; outline: none; }
        .btn-submit {
            width: 100%; background-color: #1abc9c; color: #ffffff; border: none; padding: 14px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; transition: background 0.2s;
        }
        .btn-submit:hover { background-color: #16a085; }
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem; font-weight: bold; text-align: center; }
        .error { background-color: #fdadad; color: #c0392b; }
        .success { background-color: #d4edda; color: #155724; }
        .login-link { display: block; text-align: center; margin-top: 20px; color: #2c3e50; text-decoration: none; font-size: 0.9rem; font-weight: bold; }
        .login-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<div class="reg-card">
    <h2>📝 Customer Signup</h2>
    
    <?php if(!empty($message)): ?>
        <div class="alert <?php echo $msg_type; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="form-group">
            <label for="customer_id">Preferred Customer ID</label>
            <input type="text" id="customer_id" name="customer_id" placeholder="e.g., C001" required>
        </div>

        <div class="form-group">
            <label for="customer_name">Full Profile Name</label>
            <input type="text" id="customer_name" name="customer_name" placeholder="e.g., Jane Doe" required>
        </div>

        <div class="form-group">
            <label for="password">Account Password</label>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-submit">Register Account</button>
    </form>

    <a href="index.php" class="login-link">Already have an account? Return to Login</a>
</div>

</body>
</html>