<?php
session_start();
include('../dbconn.php'); // Database handler initialization hook

// Query items joining CHICKEN properties and parent LIVESTOCK quantities cleanly
$query = "SELECT c.CHICKENID, l.NAME, l.PRICE, l.STOCK 
          FROM CHICKEN c 
          JOIN LIVESTOCK l ON c.CHICKENID = l.LIVESTOCKID 
          ORDER BY c.CHICKENID ASC";

$stmt = oci_parse($dbconn, $query);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🐔 Poultry Inventory Ledgers</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; margin: 40px; }
        .ledger-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto; }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        h2 { color: #d35400; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #eef2f5; }
        th { background-color: #f8f9fa; color: #34495e; text-transform: uppercase; font-size: 0.85rem; font-weight: 700; }
        .stock-badge { background-color: #ffe6cc; color: #d35400; padding: 6px 12px; border-radius: 6px; font-weight: bold; }
        .btn-adjust { background-color: #e67e22; color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 0.85rem; display: inline-block; }
        .btn-adjust:hover { background-color: #d35400; }
        .btn-nav { background-color: #eef2f5; color: #34495e; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 0.9rem; }

        /* Dynamic Status Feedback Notifications UI style */
        .status-alert { padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 0.95rem; }
        .status-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="ledger-card">

    <?php if (isset($_GET['status']) && isset($_GET['msg'])): ?>
        <?php $alertClass = ($_GET['status'] === 'success') ? 'status-success' : 'status-error'; ?>
        <div class="status-alert <?php echo $alertClass; ?>">
            <?php echo ($_GET['status'] === 'success') ? '✅' : '❌'; ?> 
            <?php echo htmlspecialchars($_GET['msg']); ?>
        </div>
    <?php endif; ?>

    <div class="header-area">
        <h2>🐔 Poultry Stock Inventory Ledger</h2>
        <a href="staff_dashboard2.php" class="btn-nav">⬅️ Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Chicken ID</th>
                <th>Product Description</th>
                <th>Market Price (RM)</th>
                <th>Current Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = oci_fetch_array($stmt, OCI_ASSOC)): 
                // Capture indices reliably using uppercase keys returned from Oracle
                $id = $row['CHICKENID'] ?? '';
                $name = $row['NAME'] ?? '';
                $price = $row['PRICE'] ?? 0.00;
                $stock = $row['STOCK'] ?? 0;
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($id); ?></strong></td>
                <td><?php echo htmlspecialchars($name); ?></td>
                <td><?php echo htmlspecialchars(number_format((float)$price, 2)); ?></td>
                <td><span class="stock-badge"><?php echo htmlspecialchars($stock); ?> units</span></td>
                <td>
                    <a href="adjust_stock_chicken.php?id=<?php echo urlencode($id); ?>" class="btn-adjust">⚙️ Adjust Stock</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>