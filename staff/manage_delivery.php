<?php
session_start();

// Connect to the shared database profile
include('../dbconn.php');

// Catch logged-in user context
$current_user_id = $_SESSION['user_id'] ?? $_SESSION['staff_id'] ?? '';
$user_role       = $_SESSION['user_role'] ?? 'staff';

// Process delivery completion request securely if triggered by the delivery staff member
if (isset($_POST['action']) && $_POST['action'] === 'complete_delivery' && isset($_POST['order_id'])) {
    $order_to_complete = $_POST['order_id'];

    // Update logistics tracking context to DELIVERED
    $update_query = "UPDATE ORDERS SET APPROVAL_STATUS = 'DELIVERED' WHERE ORDERID = :order_id";
    $stmt_update = oci_parse($dbconn, $update_query);
    oci_bind_by_name($stmt_update, ':order_id', $order_to_complete);
    
    if (oci_execute($stmt_update)) {
        echo "<script>alert('Order delivery confirmed successfully!'); window.location.href=window.location.href;</script>";
    }
    oci_free_statement($stmt_update);
}

// Select everything from ds, avoiding hardcoded names that cause ORA-00904
$query = "SELECT ds.*, s.StaffName 
          FROM DELIVERY_STAFF ds
          JOIN STAFF s ON ds.StaffID = s.StaffID
          ORDER BY ds.StaffID ASC";

$stmt = oci_parse($dbconn, $query);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🚚 Fleet Logistics Control</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 40px;
            color: #2c3e50;
        }
        .container {
            max-width: 950px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        h1 { margin: 0; font-size: 1.8rem; color: #2c3e50; }
        .back-btn {
            text-decoration: none;
            color: #7f8c8d;
            font-weight: bold;
            transition: color 0.2s;
        }
        .back-btn:hover { color: #2c3e50; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid #eef2f5;
            vertical-align: middle;
        }
        th {
            background-color: #fafbfc;
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        tr:hover { background-color: #fcfdfe; }
        
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
            background-color: #e8f8f0; 
            color: #27ae60;
        }
        .badge-blue {
            background-color: #ebf5fb;
            color: #2980b9;
        }
        .action-section {
            background-color: #fcf8f2;
            border: 1px dashed #e67e22;
            padding: 15px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .history-section {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        .action-title {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
            color: #d35400;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .history-title {
            margin: 0 0 15px 0;
            font-size: 1.1rem;
            color: #334155;
        }
        .inline-form {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        .select-input {
            padding: 10px;
            border: 2px solid #eef2f5;
            border-radius: 6px;
            font-size: 0.95rem;
            color: #2c3e50;
            background: white;
        }
        .btn-action {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-action:hover { background-color: #219653; }
        
        .toggle-btn {
            background-color: #34495e;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            font-weight: 600;
            margin-top: 15px;
        }
        .toggle-btn:hover { background-color: #2c3e50; }
    </style>
    <script>
        // Simple client side function to show/hide the history ledger area
        function toggleHistoryArea() {
            var ledger = document.getElementById("completedLedgerBox");
            if (ledger.style.display === "none") {
                ledger.style.display = "block";
                document.getElementById("toggleBtnText").innerText = "🙈 Hide Delivered History";
            } else {
                ledger.style.display = "none";
                document.getElementById("toggleBtnText").innerText = "📋 View My Delivered History";
            }
        }
    </script>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🚚 Active Dispatch Courier Fleet</h1>
        <a href="<?php echo ($user_role === 'manager') ? 'manager_dashboard.php' : 'staff_dashboard2.php'; ?>" class="back-btn">← Back to Panel</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Courier Staff ID</th>
                <th>Courier Full Name</th>
                <th>Delivery Commission Fee</th>
                <th>Duty Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $hasFleet = false;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
                $hasFleet = true;
                
                $staffId = '';
                foreach ($row as $key => $val) {
                    if (strpos(strtoupper($key), 'STAFFID') !== false || strtoupper($key) === 'ID') {
                        $staffId = htmlspecialchars($val);
                        break;
                    }
                }
                
                $staffName = htmlspecialchars($row['STAFFNAME'] ?? 'Unknown Staff');
                
                $feeValue = 0;
                foreach ($row as $key => $val) {
                    $upper_key = strtoupper($key);
                    if (strpos($upper_key, 'FEE') !== false || strpos($upper_key, 'COMM') !== false || strpos($upper_key, 'PRICE') !== false) {
                        $feeValue = $val;
                        break;
                    }
                }
                
                $is_current_rider = ($current_user_id === $staffId);
                $row_style = $is_current_rider ? "style='background-color: #f1f9f5;'" : "";
                
                echo "<tr {$row_style}>";
                echo "<td><strong>{$staffId} " . ($is_current_rider ? " (You)" : "") . "</strong></td>";
                echo "<td>{$staffName}</td>";
                echo "<td>RM " . number_format((float)$feeValue, 2) . "</td>";
                echo "<td><span class='badge'>Active Duty</span></td>";
                echo "</tr>";
            }
            if (!$hasFleet) {
                echo "<tr><td colspan='4' style='text-align:center; color:#7f8c8d; padding:20px;'>No logistics delivery records configured.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <?php 
    // Fetch approved orders that are ready to be dispatched or completed
    $order_query = "SELECT ORDERID, CUSTOMERID FROM ORDERS WHERE APPROVAL_STATUS = 'APPROVED'";
    $stmt_orders = oci_parse($dbconn, $order_query);
    oci_execute($stmt_orders);
    
    $pending_deliveries = [];
    while ($o_row = oci_fetch_array($stmt_orders, OCI_ASSOC)) {
        $pending_deliveries[] = $o_row;
    }
    oci_free_statement($stmt_orders);

    if (count($pending_deliveries) > 0):
    ?>
        <div class="action-section">
            <h3 class="action-title">📦 Operational Courier Actions Panel</h3>
            <p style="margin: 0; font-size: 0.9rem; color: #7f8c8d;">Select your assigned outbound package cargo tracking record to process drop-off route completion status:</p>
            
            <form action="" method="POST" class="inline-form">
                <input type="hidden" name="action" value="complete_delivery">
                
                <select name="order_id" class="select-input" required>
                    <option value="">-- Choose Order ID to Complete --</option>
                    <?php foreach ($pending_deliveries as $delivery): ?>
                        <option value="<?php echo htmlspecialchars($delivery['ORDERID']); ?>">
                            <?php echo htmlspecialchars($delivery['ORDERID'] . " (Customer: " . $delivery['CUSTOMERID'] . ")"); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn-action">✅ Confirm Order Delivered</button>
            </form>
        </div>
    <?php endif; ?>

    <button id="toggleBtnText" class="toggle-btn" onclick="toggleHistoryArea()">📋 View My Delivered History</button>

    <div id="completedLedgerBox" class="history-section" style="display: none;">
        <h3 class="history-title">✅ Completed Order Log History</h3>
        
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Account</th>
                    <th>Fulfillment Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch only the entries where status matches DELIVERED
                $history_query = "SELECT ORDERID, CUSTOMERID, APPROVAL_STATUS FROM ORDERS WHERE APPROVAL_STATUS = 'DELIVERED' ORDER BY ORDERID DESC";
                $stmt_history = oci_parse($dbconn, $history_query);
                oci_execute($stmt_history);
                
                $hasHistoryRows = false;
                while ($h_row = oci_fetch_array($stmt_history, OCI_ASSOC)) {
                    $hasHistoryRows = true;
                    echo "<tr>";
                    echo "<td><strong>" . htmlspecialchars($h_row['ORDERID']) . "</strong></td>";
                    echo "<td>" . htmlspecialchars($h_row['CUSTOMERID']) . "</td>";
                    echo "<td><span class='badge badge-blue'>📦 Successfully Delivered</span></td>";
                    echo "</tr>";
                }
                oci_free_statement($stmt_history);

                if (!$hasHistoryRows) {
                    echo "<tr><td colspan='3' style='text-align:center; color:#94a3b8; padding:15px;'>No items have been updated to delivered status yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
<?php
oci_free_statement($stmt);
oci_close($dbconn);
?>