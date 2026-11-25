<?php
session_start();
include('../dbconn.php'); // Moves up one level out of /customer to find dbconn.php

// Session validation fallback mechanism
if (!isset($_SESSION['user_id'])) {
    // If no active login data exists, redirect them back to the portal entry gateway
    header("Location: ../index.php");
    exit();
}

$id = $_SESSION['user_id'];
$customer_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Valued Customer';

// FIX: Select APPROVAL_STATUS directly from the ORDERS table instead of the separate DELIVERY table
$sql = "SELECT o.ORDERID, o.ORDERDATE, o.APPROVAL_STATUS 
        FROM ORDERS o
        WHERE o.CUSTOMERID = :id
        ORDER BY o.ORDERDATE DESC";

$stmt = oci_parse($dbconn, $sql);
oci_bind_by_name($stmt, ":id", $id);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Livestock Portal</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 20px;
            color: #2c3e50;
        }
        .container {
            max-width: 800px;
            margin: 30px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eef2f5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        h2 { margin: 0; color: #2c3e50; }
        .btn-back {
            text-decoration: none;
            background-color: #1abc9c;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .order-row {
            background-color: #f8fafb;
            border-left: 4px solid #2c3e50;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .order-id { font-weight: bold; color: #2c3e50; }
        .order-date { color: #7f8c8d; font-size: 0.9rem; }
        
        /* Stylized Status Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            display: inline-block;
        }
        .status-preparing { background-color: #ffe6cc; color: #d35400; }
        .status-shipping { background-color: #e1f5fe; color: #0288d1; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        
        .no-data {
            text-align: center;
            color: #95a5a6;
            padding: 30px 0;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header-area">
        <h2>📦 Order Tracking History</h2>
        <a href="customer_dashboard.php" class="btn-back">⬅️ Back to Dashboard</a>
    </div>

    <p>Logged in account identity details: <strong><?php echo htmlspecialchars($customer_name); ?> (<?php echo htmlspecialchars($id); ?>)</strong></p>

    <?php 
    $has_records = false;
    while ($row = oci_fetch_assoc($stmt)) {
        $has_records = true;
        
        // Fetch raw DB value or default to PENDING
        $db_status = !empty($row['APPROVAL_STATUS']) ? trim($row['APPROVAL_STATUS']) : 'PENDING';
        
        // Map backend database status keys to clean user facing text and style themes
        if ($db_status === 'DELIVERED') {
            $status_text = 'Delivered';
            $badge_class = 'status-delivered';
        } elseif ($db_status === 'APPROVED') {
            $status_text = 'Out For Delivery';
            $badge_class = 'status-shipping';
        } else {
            $status_text = 'Preparing Order';
            $badge_class = 'status-preparing';
        }
        ?>
        <div class="order-row">
            <div class="order-info">
                <span class="order-id">📋 Order #<?php echo htmlspecialchars($row['ORDERID']); ?></span>
                <span class="order-date">📅 Placed on: <?php echo htmlspecialchars($row['ORDERDATE']); ?></span>
            </div>
            <div>
                <span class="status-badge <?php echo $badge_class; ?>">
                    <?php echo htmlspecialchars($status_text); ?>
                </span>
            </div>
        </div>
        <?php 
    } 

    if (!$has_records) {
        echo "<div class='no-data'>No processed orders found matching your account ID tracking code.</div>";
    }

    oci_free_statement($stmt);
    oci_close($dbconn);
    ?>
</div>

</body>
</html>