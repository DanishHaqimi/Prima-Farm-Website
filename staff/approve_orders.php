<?php
session_start();
// 1. Security Guard: Ensure ONLY the manager can load this script execution 
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'manager') {
    die("Access Denied: Highly classified operations console area.");
}

// 2. Connect to the database
include('../dbconn.php'); 

// 3. Process an approval action if a button is clicked
if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['order_id'])) {
    $order_id = $_GET['order_id'];
    
    // CHANGED: Fixed column target to update 'APPROVAL_STATUS' instead of 'STATUS'
    $update_query = "UPDATE ORDERS SET APPROVAL_STATUS = 'APPROVED' WHERE ORDERID = :order_id";
    $stmt = oci_parse($dbconn, $update_query);
    oci_bind_by_name($stmt, ':order_id', $order_id);
    
    if (oci_execute($stmt)) {
        echo "<script>alert('Order successfully approved!'); window.location.href='approve_orders.php';</script>";
    }
    oci_free_statement($stmt);
}

// 4. Fetch pending orders waiting for manager approval
$query = "SELECT * FROM ORDERS WHERE APPROVAL_STATUS = 'PENDING'"; 
$stmt = oci_parse($dbconn, $query);
oci_execute($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manager Approval Hub</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; padding: 40px; color: #2c3e50; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h1 { margin-top: 0; color: #2c3e50; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eef2f5; }
        th { background-color: #fcfcfc; color: #7f8c8d; font-weight: 600; }
        .btn-approve { background-color: #e67e22; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; font-size: 0.85rem; }
        .btn-approve:hover { background-color: #d35400; }
        .back-btn { display: inline-block; margin-bottom: 20px; color: #7f8c8d; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <a href="manager_dashboard.php" class="back-btn">← Back to Dashboard</a>
    <h1>📋 Pending Order Approvals Portal</h1>
    <p>Logged in as: <strong>Manager Control Hub</strong></p>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer ID</th>
                <th>Total Price</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $has_orders = false;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC)): 
                $has_orders = true;
                
                // CHANGED: Dynamic price scanner fallback to accurately catch variations like TOTALPRICE or TOTAL_PRICE
                $total_price = '0.00';
                foreach ($row as $key => $value) {
                    $upper_key = strtoupper($key);
                    if (strpos($upper_key, 'PRICE') !== false || strpos($upper_key, 'TOTAL') !== false) {
                        if ($upper_key !== 'ORDERID' && $upper_key !== 'CUSTOMERID' && $upper_key !== 'APPROVAL_STATUS') {
                            $total_price = number_format((float)$value, 2);
                        }
                    }
                }
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['ORDERID'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($row['CUSTOMERID'] ?? 'N/A'); ?></td>
                    <td>RM <?php echo $total_price; ?></td>
                    <td><span style="color:#e67e22; font-weight:bold;"><?php echo htmlspecialchars($row['APPROVAL_STATUS'] ?? 'PENDING'); ?></span></td>
                    <td>
                        <a href="approve_orders.php?action=approve&order_id=<?php echo urlencode($row['ORDERID']); ?>" class="btn-approve">Confirm Approve</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            
            <?php if (!$has_orders): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #95a5a6; padding: 30px;">No pending orders waiting for approval at this time.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php 
oci_free_statement($stmt);
oci_close($dbconn); 
?>