<?php
session_start();
include('../dbconn.php'); // Establish backend connection context

// Query data by joining core properties from FISH and quantities from LIVESTOCK
$query = "SELECT f.FISHID, l.NAME, l.PRICE, l.STOCK, f.GUTTINGCOST 
          FROM FISH f 
          JOIN LIVESTOCK l ON f.FISHID = l.LIVESTOCKID 
          ORDER BY f.FISHID ASC";

$stmt = oci_parse($dbconn, $query);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>🐟 Marine Live Inventory Stock Ledger</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; margin: 40px; }
        .ledger-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 950px; margin: 0 auto; }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        h2 { color: #1e6091; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #eef2f5; }
        th { background-color: #f8f9fa; color: #34495e; text-transform: uppercase; font-size: 0.85rem; font-weight: 700; }
        .stock-badge { background-color: #d8f3dc; color: #1b4332; padding: 6px 12px; border-radius: 6px; font-weight: bold; }
        .btn-adjust { background-color: #3498db; color: white; border: none; padding: 8px 14px; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 0.85rem; display: inline-block; }
        .btn-adjust:hover { background-color: #2980b9; }
        .btn-nav { background-color: #eef2f5; color: #34495e; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 0.9rem; }

        /* Dynamic Visual Status Message Boxes */
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
        <h2>🐟 Marine Live Inventory Stock Ledger</h2>
        <a href="staff_dashboard2.php" class="btn-nav">⬅️ Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fish ID</th>
                <th>Species Name</th>
                <th>Market Price (RM)</th>
                <th>Current Stock</th>
                <th>Gutting Cost (RM)</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = oci_fetch_array($stmt, OCI_ASSOC)): 
                // Capture indices reliably matching upper-case formats
                $id = $row['FISHID'] ?? '';
                $name = $row['NAME'] ?? '';
                $price = $row['PRICE'] ?? 0.00;
                $stock = $row['STOCK'] ?? 0;
                $cost = $row['GUTTINGCOST'] ?? 0.00;
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($id); ?></strong></td>
                <td><?php echo htmlspecialchars($name); ?></td>
                <td><?php echo htmlspecialchars(number_format((float)$price, 2)); ?></td>
                <td><span class="stock-badge"><?php echo htmlspecialchars($stock); ?> units</span></td>
                <td>RM <?php echo htmlspecialchars(number_format((float)$cost, 2)); ?></td>
                <td>
                    <a href="adjust_stock_fish.php?id=<?php echo urlencode($id); ?>" class="btn-adjust">⚙️ Adjust Stock</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>