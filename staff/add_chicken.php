<?php
// Secure the backend link out to your root Oracle connection file
include('../dbconn.php'); //

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") { //
    // Grab data from your HTML input fields
    $id = $_POST['chicken_id']; //
    $name = $_POST['name']; //
    $price = $_POST['price']; //
    $stock = $_POST['stock']; //
    $slaughter_cost = $_POST['slaughter_cost']; //

    // Step A: Insert into the parent LIVESTOCK table
    $sql1 = "INSERT INTO LIVESTOCK (LiveStockID, Name, Price, Stock) VALUES (:id, :name, :price, :stock)"; //
    $stmt1 = oci_parse($dbconn, $sql1); //
    oci_bind_by_name($stmt1, ':id', $id); //
    oci_bind_by_name($stmt1, ':name', $name); //
    oci_bind_by_name($stmt1, ':price', $price); //
    oci_bind_by_name($stmt1, ':stock', $stock); //

    // Step B: Insert into the subtype CHICKEN table
    $sql2 = "INSERT INTO CHICKEN (ChickenID, SlaughterCost) VALUES (:id, :scost)"; //
    $stmt2 = oci_parse($dbconn, $sql2); //
    oci_bind_by_name($stmt2, ':id', $id); //
    oci_bind_by_name($stmt2, ':scost', $slaughter_cost); //

    // Execute both in sequence
    $r1 = oci_execute($stmt1, OCI_NO_AUTO_COMMIT); //
    $r2 = oci_execute($stmt2, OCI_NO_AUTO_COMMIT); //

    // Commit only if both statements succeeded perfectly
    if ($r1 && $r2) { //
        oci_commit($dbconn); //
        $message = "🎉 New chicken product added successfully!";
        $message_type = "success";
    } else {
        oci_rollback($dbconn); //
        $message = "❌ Error saving data. Transaction rolled back.";
        $message_type = "error";
    }
    
    oci_free_statement($stmt1);
    oci_free_statement($stmt2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Chicken Stock</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 40px 20px;
            color: #2c3e50;
        }
        .form-container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .header-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f1f5f4;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        h2 { margin: 0; font-size: 1.6rem; }
        .btn-back {
            text-decoration: none;
            color: #7f8c8d;
            font-weight: bold;
            font-size: 0.9rem;
            transition: color 0.2s;
        }
        .btn-back:hover { color: #2c3e50; }
        
        /* Form Field Components */
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #dcdde1;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        input:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .btn-submit {
            width: 100%;
            padding: 14px;
            background-color: #27ae60;
            border: none;
            color: white;
            font-weight: bold;
            font-size: 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.2s;
            margin-top: 10px;
        }
        .btn-submit:hover { background-color: #219653; }

        /* Notification Alerts */
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>

<div class="form-container">
    <div class="header-group">
        <h2>🐔 Add Poultry Stock Variety</h2>
        <a href="staff_dashboard2.php" class="btn-back">⬅️ Dashboard</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form action="add_chicken.php" method="POST">
        <div class="form-group">
            <label for="chicken_id">Livestock ID Code / Tag</label>
            <input type="text" id="chicken_id" name="chicken_id" placeholder="e.g., C004" required>
        </div>

        <div class="form-group">
            <label for="name">Breed Stock Description Name</label>
            <input type="text" id="name" name="name" placeholder="e.g., Kampung Chicken" required>
        </div>

        <div class="form-group">
            <label for="price">Market Price Unit (RM)</label>
            <input type="number" step="0.01" id="price" name="price" placeholder="0.00" required>
        </div>

        <div class="form-group">
            <label for="stock">Available Head Count Inventory</label>
            <input type="number" id="stock" name="stock" placeholder="0" required>
        </div>

        <div class="form-group">
            <label for="slaughter_cost">Slaughter Overhead Processing Cost (RM)</label>
            <input type="number" step="0.01" id="slaughter_cost" name="slaughter_cost" placeholder="0.00" required>
        </div>

        <button type="submit" class="btn-submit">Save to Oracle Ledger</button>
    </form>
</div>

</body>
</html>
<?php oci_close($dbconn); ?>