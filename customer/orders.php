<?php
include('../dbconn.php');

// Grab the specific item ID passed from the dashboard link safely
$item_id = isset($_GET['id']) ? $_GET['id'] : '';

if (!empty($item_id)) {
    // Look up the specific details for the item selected
    $query = "SELECT Name, Price FROM LIVESTOCK WHERE LiveStockID = :id";
    $statement = oci_parse($dbconn, $query);
    oci_bind_by_name($statement, ':id', $item_id);
    oci_execute($statement);
    $item = oci_fetch_array($statement, OCI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Confirm Order</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f7f6; }
        .order-box { max-width: 400px; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin: 0 auto; }
        h2 { color: #2c3e50; }
        .details { margin: 20px 0; font-size: 1.1em; }
        .btn { display: block; text-align: center; background: #27ae60; color: white; padding: 10px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>

<div class="order-box">
    <h2>Confirm Your Purchase</h2>
    
    <?php if (!empty($item)): ?>
        <div class="details">
            <p><strong>Product ID:</strong> <?php echo htmlspecialchars($item_id); ?></p>
            <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item['NAME']); ?></p>
            <p><strong>Price:</strong> RM <?php echo number_format($item['PRICE'], 2); ?></p>
        </div>
        <p>Would you like to finalize this purchase processing request?</p>
        <a class="btn" href="my_orders.php">Confirm & Pay</a>
    <?php else: ?>
        <p style="color: red;">No valid item selected. Please head back to the store dashboard.</p>
    <?php endif; ?>
    
    <a href="customer_dashboard2.php" style="display:block; text-align:center; margin-top:10px; color:#7f8c8d;">Cancel</a>
</div>

</body>
</html>

<?php
if (!empty($item_id)) {
    oci_free_statement($statement);
}
oci_close($dbconn);
?>