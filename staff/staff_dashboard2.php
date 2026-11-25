<?php
// Secure the backend link out to your root Oracle connection file
include('../dbconn.php'); //

// Initialize dynamic counters
$chicken_count = 0;
$fish_count = 0;

// Fetch active poultry metrics from Oracle
$stmt1 = oci_parse($dbconn, "SELECT COUNT(*) AS TOTAL FROM CHICKEN"); //
if (oci_execute($stmt1)) {
    $row = oci_fetch_array($stmt1, OCI_ASSOC);
    $chicken_count = $row['TOTAL'];
}
oci_free_statement($stmt1);

// Fetch active marine metrics from Oracle 
$stmt2 = oci_parse($dbconn, "SELECT COUNT(*) AS TOTAL FROM FISH"); //
if (oci_execute($stmt2)) {
    $row = oci_fetch_array($stmt2, OCI_ASSOC);
    $fish_count = $row['TOTAL'];
}
oci_free_statement($stmt2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Dashboard</title>
    <style>
        :root {
            --bg-neutral: #f4f7f6;
            --text-main: #2c3e50;
            --text-secondary: #7f8c8d;
            --card-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            --green-primary: #27ae60;
            --green-hover: #219653;
            --blue-marine: #2980b9;
            --blue-hover: #2471a3;
            --dark-btn: #34495e;
            --dark-btn-hover: #2c3e50;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-neutral);
            margin: 0;
            padding: 0;
            color: var(--text-main);
        }

        /* Top Navigation Bar Component */
        .navbar {
            background-color: #ffffff;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .navbar h2 { margin: 0; font-size: 1.3rem; font-weight: 700; color: #2c3e50; }
        .back-hub-btn { text-decoration: none; color: var(--text-secondary); font-weight: 600; font-size: 0.9rem; }
        .back-hub-btn:hover { color: var(--text-main); }

        /* Outer Dashboard Container Grid layout */
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .welcome-ribbon h1 { margin: 0 0 5px 0; font-size: 2rem; }
        .welcome-ribbon p { color: var(--text-secondary); margin: 0; }

        /* Banner Status Counters Component */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin: 25px 0 40px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid #bdc3c7;
        }
        .stat-card.status { border-left-color: var(--green-primary); }
        .stat-card.chicken { border-left-color: var(--green-primary); }
        .stat-card.fish { border-left-color: var(--blue-marine); }
        .stat-card p { margin: 0; color: var(--text-secondary); font-size: 0.85rem; text-transform: uppercase; font-weight: 600; }
        .stat-card h3 { margin: 5px 0 0 0; font-size: 1.6rem; }

        /* Operations Control Cards Grid Layout */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 25px;
        }
        .menu-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }
        .card-desc h3 { margin: 0 0 10px 0; font-size: 1.25rem; display: flex; align-items: center; gap: 8px; }
        .card-desc p { color: var(--text-secondary); font-size: 0.95rem; line-height: 1.5; margin: 0 0 25px 0; }

        /* Integrated Dual Action Button Layout */
        .btn-flex-container {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            text-align: center;
            padding: 12px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.95rem;
            transition: background-color 0.2s;
            box-sizing: border-box;
        }
        .btn-full { width: 100%; }
        .btn-left { flex: 3; } /* Slightly wider for inventory descriptions */
        .btn-right { flex: 2; } /* Compact button action for adding items */

        /* Context Color Styles */
        .btn-green { background-color: var(--green-primary); color: white; }
        .btn-green:hover { background-color: var(--green-hover); }
        .btn-blue { background-color: var(--blue-marine); color: white; }
        .btn-blue:hover { background-color: var(--blue-hover); }
        .btn-dark { background-color: var(--dark-btn); color: white; }
        .btn-dark:hover { background-color: var(--dark-btn-hover); }
    </style>
</head>
<body>

    <nav class="navbar">
        <h2>💼 Internal Staff System</h2>
        <a href="../index.php" class="back-hub-btn">← Switch to Main Hub Portal</a>
    </nav>

    <div class="dashboard-container">
        
        <div class="welcome-ribbon">
            <h1>Management Dashboard</h1>
            <p>Welcome back! Monitor farm production queues and process operations updates.</p>
            
            <div class="stats-grid">
                <div class="stat-card status">
                    <p>Database Status</p>
                    <h3 style="color: var(--green-primary); font-size: 1.15rem; margin-top:8px;">🟢 Connected (OCI8)</h3>
                </div>
                <div class="stat-card chicken">
                    <p>Active Poultry Items</p>
                    <h3><?php echo $chicken_count; ?> Breeds</h3>
                </div>
                <div class="stat-card fish">
                    <p>Active Marine Items</p>
                    <h3><?php echo $fish_count; ?> Species</h3>
                </div>
            </div>
        </div>

        <div class="menu-grid">
            
            <div class="menu-card">
                <div class="card-desc">
                    <h3>🐔 Manage Chicken Stocks</h3>
                    <p>Review total live birds list, track current warehouse stock depths, analyze individual slaughter cost fields, or register fresh production lots.</p>
                </div>
                <div class="btn-flex-container">
                    <a href="manage_chicken.php" class="btn btn-left btn-green">Open Poultry Inventory</a> <a href="add_chicken.php" class="btn btn-right btn-dark">➕ Add</a> </div>
            </div>

            <div class="menu-card">
                <div class="card-desc">
                    <h3>🐟 Manage Fish Stocks</h3>
                    <p>Review active aquatic species list, track current tank storage capacity weights, evaluate raw gutting fees, or register deep sea hauls.</p>
                </div>
                <div class="btn-flex-container">
                    <a href="manage_fish.php" class="btn btn-left btn-blue">Open Marine Inventory</a> <a href="add_fish.php" class="btn btn-right btn-dark">➕ Add</a> </div>
            </div>

            <div class="menu-card">
                <div class="card-desc">
                    <h3>📋 Manage Active Orders</h3>
                    <p>Track incoming consumer transaction invoices, review current order distributions, and allocate processing responsibilities to riders.</p>
                </div>
                <a href="manage_orders.php" class="btn btn-full btn-green">Launch Order Ledger</a> </div>

            <div class="menu-card">
                <div class="card-desc">
                    <h3>🚚 Manage Logistics & Delivery</h3>
                    <p>Monitor assigned courier status parameters, calculate shipping pricing costs, and mark distribution routes as successfully fulfilled.</p>
                </div>
                <a href="manage_delivery.php" class="btn btn-full btn-green">View Fleet Control</a> </div>

        </div>
    </div>

</body>
</html>
<?php 
// Close the oracle connection cleanly at the very end
oci_close($dbconn); 
?>