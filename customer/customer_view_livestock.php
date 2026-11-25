<?php
session_start();
include('../dbconn.php'); // Connects to your Oracle Database instance

// Query details from LIVESTOCK and check matching IDs inside CHICKEN and FISH
$query = "SELECT l.LIVESTOCKID, l.NAME, l.PRICE, l.STOCK,
                 CASE 
                    WHEN c.CHICKENID IS NOT NULL THEN 'Poultry'
                    WHEN f.FISHID IS NOT NULL THEN 'Marine'
                    ELSE 'General Livestock'
                 END AS ASSET_TYPE,
                 f.GUTTINGCOST,
                 c.SLAUGHTERCOST
          FROM LIVESTOCK l
          LEFT JOIN CHICKEN c ON l.LIVESTOCKID = c.CHICKENID
          LEFT JOIN FISH f ON l.LIVESTOCKID = f.FISHID
          ORDER BY l.LIVESTOCKID ASC";

$stmt = oci_parse($dbconn, $query);
oci_execute($stmt);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>📋 Comprehensive Livestock Assets Registry</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; margin: 40px; }
        .ledger-card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 1050px; margin: 0 auto; }
        .header-area { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f4f7f6; padding-bottom: 15px; }
        .title-group h2 { color: #2c3e50; margin: 0; font-size: 1.6rem; }
        .title-group p { color: #7f8c8d; margin: 5px 0 0 0; font-size: 0.95rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 14px; text-align: left; border-bottom: 1px solid #eef2f5; }
        th { background-color: #f8f9fa; color: #34495e; text-transform: uppercase; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.5px; }
        
        /* Badges for Asset Classification Categorization Types */
        .badge { padding: 5px 10px; border-radius: 6px; font-weight: bold; font-size: 0.8rem; display: inline-block; }
        .badge-poultry { background-color: #ffe6cc; color: #d35400; }
        .badge-marine { background-color: #e1f5fe; color: #0288d1; }
        .badge-general { background-color: #e2e3e5; color: #383d41; }
        
        .stock-count { font-weight: bold; color: #2c3e50; }
        .text-muted { color: #b5b9bd; font-style: italic; font-size: 0.85rem; }
        .btn-nav { background-color: #eef2f5; color: #34495e; border: none; padding: 10px 16px; border-radius: 6px; cursor: pointer; text-decoration: none; font-weight: bold; font-size: 0.9rem; transition: background 0.2s; }
        .btn-nav:hover { background-color: #dfe4ea; }
    </style>
</head>
<body>

<div class="ledger-card">
    <div class="header-area">
        <div class="title-group">
            <h2>🛍️ Master Livestock Asset Inventory</h2>
            <p>Browse active listings of livestock assets, chicken stocks, and fish yields.</p>
        </div>
        <a href="customer_dashboard.php" class="btn-nav">⬅️ Dashboard</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Asset ID</th>
                <th>Item Classification</th>
                <th>Item Specification Name</th>
                <th>Market Price</th>
                <th>Current Stock Balance</th>
                <th>Processing Fee</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = oci_fetch_array($stmt, OCI_ASSOC)): 
                // Reliable indexing fallback logic for returning values in uppercase columns
                $id = $row['LIVESTOCKID'] ?? '';
                $name = $row['NAME'] ?? '';
                $price = $row['PRICE'] ?? 0.00;
                $stock = $row['STOCK'] ?? 0;
                $type = $row['ASSET_TYPE'] ?? 'General Livestock';
                
                // Determine category badges dynamically
                if ($type === 'Poultry') {
                    $badgeClass = 'badge-poultry';
                    $feeText = 'Slaughter: RM ' . number_format((float)($row['SLAUGHTERCOST'] ?? 0.00), 2);
                } elseif ($type === 'Marine') {
                    $badgeClass = 'badge-marine';
                    $feeText = 'Gutting: RM ' . number_format((float)($row['GUTTINGCOST'] ?? 0.00), 2);
                } else {
                    $badgeClass = 'badge-general';
                    $feeText = '<span class="text-muted">N/A</span>';
                }
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($id); ?></strong></td>
                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($type); ?></span></td>
                <td><?php echo htmlspecialchars($name); ?></td>
                <td>RM <?php echo htmlspecialchars(number_format((float)$price, 2)); ?></td>
                <td><span class="stock-count"><?php echo htmlspecialchars($stock); ?> units</span></td>
                <td><?php echo $feeText; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

</body>
</html>