<?php
// Include connection from the parent folder root
include('../dbconn.php');

// SQL Join query pulling related fields across ORDERS, CUSTOMER, and STAFF profiles
$query = "SELECT o.OrderID, o.OrderDate, c.CustomerName, s.StaffName 
          FROM ORDERS o
          LEFT JOIN CUSTOMER c ON o.CustomerID = c.CustomerID
          LEFT JOIN STAFF s ON o.StaffID = s.StaffID
          ORDER BY o.OrderID DESC";

$stmt = oci_parse($dbconn, $query);
oci_execute($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Incoming Client Orders</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 40px;
            color: #2c3e50;
        }
        .container {
            max-width: 1000px;
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
        .order-badge {
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
            background-color: #eaf2f8;
            color: #2980b9;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>📋 Customer Orders Ledger</h1>
        <a href="staff_dashboard2.php" class="back-btn">← Back to Panel</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Placement Date</th>
                <th>Purchasing Customer</th>
                <th>Assigned Handler</th>
                <th>Process Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $hasRows = false;
            while ($row = oci_fetch_array($stmt, OCI_ASSOC)) {
                $hasRows = true;
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($row['ORDERID']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($row['ORDERDATE']) . "</td>";
                echo "<td>" . htmlspecialchars(!empty($row['CUSTOMERNAME']) ? $row['CUSTOMERNAME'] : 'Walking Customer') . "</td>";
                echo "<td>" . htmlspecialchars(!empty($row['STAFFNAME']) ? $row['STAFFNAME'] : 'Unassigned') . "</td>";
                echo "<td><span class='order-badge'>Received</span></td>";
                echo "</tr>";
            }
            if (!$hasRows) {
                echo "<tr><td colspan='5' style='text-align:center; color:#7f8c8d; padding:20px;'>No active database orders found.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>
<?php
oci_free_statement($stmt);
oci_close($dbconn);
?>