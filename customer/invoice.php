<?php
session_start();
include('../dbconn.php'); // Your active Oracle database connection file ($dbconn)

// Ensure user is logged in, otherwise use fallback for testing
$current_user = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "C003"; 
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Laila';

// Capture the Order ID from the URL string securely
$order_id = isset($_GET['order_id']) ? trim($_GET['order_id']) : '';

// =========================================================================
// VIEW MODE 1: Show a list of all historical invoices if no specific ID is chosen
// =========================================================================
if (empty($order_id)) {
    // Calculates the total price by multiplying weight and asset cost dynamically across your real schema columns
    $list_sql = "SELECT o.ORDERID, TO_CHAR(o.ORDERDATE, 'DD-MON-YYYY HH24:MI') as ORDER_DATE,
                        SUM(ol.WEIGHT_KG * l.PRICE) AS TOTAL_PRICE
                 FROM ORDERS o
                 LEFT JOIN ORDER_LIVESTOCK ol ON o.ORDERID = ol.ORDERID
                 LEFT JOIN LIVESTOCK l ON ol.LIVESTOCKID = l.LIVESTOCKID
                 WHERE o.CUSTOMERID = :customer_id
                 GROUP BY o.ORDERID, o.ORDERDATE
                 ORDER BY o.ORDERDATE DESC";
                 
    $list_stmt = oci_parse($dbconn, $list_sql);
    oci_bind_by_name($list_stmt, ":customer_id", $current_user);
    oci_execute($list_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices & Receipts History</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; padding: 30px; color: #2c3e50; margin: 0; }
        .history-box { max-width: 900px; margin: auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header-title { border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 25px; }
        .history-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .history-table th { background-color: #2c3e50; color: #fff; padding: 12px; text-align: left; }
        .history-table td { padding: 14px 12px; border-bottom: 1px solid #ecf0f1; }
        .history-table tr:hover { background-color: #f9fbfb; }
        .btn { padding: 8px 16px; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 0.85rem; display: inline-block; }
        .btn-view { background-color: #3498db; color: white; }
        .btn-view:hover { background-color: #2980b9; }
        .btn-secondary { background-color: #7f8c8d; color: white; padding: 12px 25px; font-size: 0.95rem; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>

<div class="history-box">
    <div class="header-title">
        <h2>📋 Your Invoices & Receipts History</h2>
        <p>Logged in as: <strong><?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($current_user); ?>)</strong></p>
    </div>

    <table class="history-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date Placed</th>
                <th>Payment Method</th>
                <th>Total Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $has_orders = false;
            while ($row = oci_fetch_assoc($list_stmt)): 
                $has_orders = true;
            ?>
                <tr>
                    <td><strong>#<?php echo htmlspecialchars($row['ORDERID']); ?></strong></td>
                    <td><?php echo $row['ORDER_DATE']; ?></td>
                    <td><span style="color:#7f8c8d; font-style:italic;">COD / Counter Cash</span></td>
                    <td><strong>RM <?php echo number_format($row['TOTAL_PRICE'] ?? 0, 2); ?></strong></td>
                    <td>
                        <a href="invoice.php?order_id=<?php echo urlencode($row['ORDERID']); ?>" class="btn btn-view">👁️ View Invoice</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            
            <?php if (!$has_orders): ?>
                <tr>
                    <td colspan="5" style="text-align: center; color: #7f8c8d; padding: 30px;">You haven't placed any purchase orders yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="margin-top: 30px;">
        <a href="customer_dashboard.php" class="btn btn-secondary">⬅️ Back to Dashboard</a>
    </div>
</div>

</body>
</html>
<?php
    oci_free_statement($list_stmt);
    oci_close($dbconn);
    exit;
}

// =========================================================================
// VIEW MODE 2: Single Invoice Detail View (Runs when an order_id exists in URL)
// =========================================================================

// Query strictly utilizing columns matching your actual database schema layout
$header_sql = "SELECT o.ORDERID, TO_CHAR(o.ORDERDATE, 'DD-MON-YYYY HH24:MI') as FLM_DATE, 
                     c.CUSTOMERNAME, c.PHONENUMBER, c.CUSTADDRESS
              FROM ORDERS o
              JOIN CUSTOMER c ON o.CUSTOMERID = c.CUSTOMERID
              WHERE o.ORDERID = :order_id";

$header_stmt = oci_parse($dbconn, $header_sql);
oci_bind_by_name($header_stmt, ":order_id", $order_id);
oci_execute($header_stmt);
$header = oci_fetch_assoc($header_stmt);

if (!$header) {
    die("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>
            <h3 style='color:red;'>❌ Invoice Not Found</h3>
            <p>No transaction history matching Reference <strong>#" . htmlspecialchars($order_id) . "</strong> exists.</p>
            <a href='invoice.php' style='color:#3498db; text-decoration:none; font-weight:bold;'>Return to Invoice History</a>
         </div>");
}

// Fetch item details associated with this receipt from your bridge structure
$items_sql = "SELECT ol.LIVESTOCKID, l.NAME, ol.WEIGHT_KG, l.PRICE as BASE_PRICE
              FROM ORDER_LIVESTOCK ol
              JOIN LIVESTOCK l ON ol.LIVESTOCKID = l.LIVESTOCKID
              WHERE ol.ORDERID = :order_id";

$items_stmt = oci_parse($dbconn, $items_sql);
oci_bind_by_name($items_stmt, ":order_id", $order_id);
oci_execute($items_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Invoice - #<?php echo $header['ORDERID']; ?></title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; padding: 30px; color: #2c3e50; margin: 0; }
        .invoice-box { max-width: 800px; margin: auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .header-title { border-bottom: 2px dashed #bdc3c7; padding-bottom: 20px; margin-bottom: 25px; text-align: center; }
        .info-grid { display: flex; justify-content: space-between; margin-bottom: 30px; line-height: 1.6; }
        .data-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .data-table th { background-color: #34495e; color: #fff; padding: 12px; text-align: left; }
        .data-table td { padding: 12px; border-bottom: 1px solid #ecf0f1; }
        .total-banner { text-align: right; margin-top: 30px; font-size: 1.4rem; font-weight: bold; padding-top: 15px; border-top: 2px dashed #bdc3c7; }
        .btn-row { display: flex; justify-content: space-between; margin-top: 40px; }
        .btn { padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; text-decoration: none; font-size: 0.95rem; }
        .btn-secondary { background-color: #7f8c8d; color: white; }
        .btn-primary { background-color: #1abc9c; color: white; }
        @media print { .btn-row { display: none; } body { padding: 0; background: #fff; } .invoice-box { box-shadow: none; padding: 0; } }
    </style>
</head>
<body>

<div class="invoice-box">
    <div class="header-title">
        <h2>🧾 LIVESTOCK SYSTEM INVOICE SUMMARY</h2>
        <p>Official Billing & Account Log Record</p>
    </div>

    <div class="info-grid">
        <div>
            <strong>Billed To:</strong><br>
            Name: <?php echo htmlspecialchars($header['CUSTOMERNAME']); ?><br>
            Phone: <?php echo htmlspecialchars($header['PHONENUMBER'] ?? 'N/A'); ?><br>
            Address: <?php echo htmlspecialchars($header['CUSTADDRESS'] ?? 'Walk-In Customer'); ?>
        </div>
        <div style="text-align: right;">
            <strong>Order References:</strong><br>
            Order ID: <strong><?php echo htmlspecialchars($header['ORDERID']); ?></strong><br>
            Date Stamp: <?php echo $header['FLM_DATE']; ?><br>
            Payment Type: <strong>Counter Payment / COD</strong>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity (Units / KG)</th>
                <th>Price / Unit Base</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $grand_total = 0;
            while ($row = oci_fetch_assoc($items_stmt)): 
                $row_total = $row['WEIGHT_KG'] * $row['BASE_PRICE'];
                $grand_total += $row_total;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['NAME'] ?? $row['LIVESTOCKID']); ?></td>
                    <td><?php echo number_format($row['WEIGHT_KG'], 0); ?> Units</td>
                    <td>RM <?php echo number_format($row['BASE_PRICE'], 2); ?></td>
                    <td><strong>RM <?php echo number_format($row_total, 2); ?></strong></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <div class="total-banner">
        Grand Total Payment: RM <?php echo number_format($grand_total, 2); ?>
    </div>

    <div class="btn-row">
        <a href="invoice.php" class="btn btn-secondary">⬅️ All Invoices</a>
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Receipt</button>
    </div>
</div>

</body>
</html>
<?php
oci_free_statement($header_stmt);
oci_free_statement($items_stmt);
oci_close($dbconn);
?>