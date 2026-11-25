<?php
session_start();
include('../dbconn.php'); // Connects to your core Oracle database connection handler

// 1. Verify that a valid ID parameter was passed over
if (!isset($_GET['id']) && !isset($_POST['fish_id'])) {
    die("<div style='font-family:sans-serif; padding:20px; color:#c0392b;'><strong>Error:</strong> No ID specified for inventory adjustment.</div>");
}

$fish_id = isset($_GET['id']) ? $_GET['id'] : $_POST['fish_id'];

// 2. Handle Form Updates when the user clicks 'Save Adjustment'
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_inventory'])) {
    $new_stock_value = intval($_POST['current_stock']);

    // Update the parent LIVESTOCK table where the STOCK counts reside
    $update_query = "UPDATE LIVESTOCK SET STOCK = :new_stock WHERE LIVESTOCKID = :TARGET_ID";
    $stmt = oci_parse($dbconn, $update_query);

    oci_bind_by_name($stmt, ':new_stock', $new_stock_value);
    oci_bind_by_name($stmt, ':TARGET_ID', $fish_id);

    if (oci_execute($stmt)) {
        oci_free_statement($stmt);
        // Forward back to your management view with a success url token parameters
        header("Location: manage_fish.php?status=success&msg=" . urlencode("Stock level updated successfully!"));
        exit;
    } else {
        $e = oci_error($stmt);
        header("Location: manage_fish.php?status=error&msg=" . urlencode("Database execution failed: " . $e['message']));
        exit;
    }
}

// 3. Fetch data by joining FISH and LIVESTOCK tables
$select_query = "SELECT f.FISHID, l.NAME, l.STOCK 
                 FROM FISH f 
                 JOIN LIVESTOCK l ON f.FISHID = l.LIVESTOCKID 
                 WHERE f.FISHID = :TARGET_ID";

$stmt = oci_parse($dbconn, $select_query);
oci_bind_by_name($stmt, ':TARGET_ID', $fish_id);
oci_execute($stmt);

$fish = oci_fetch_array($stmt, OCI_ASSOC);
oci_free_statement($stmt);

// Fallback lookup directly within LIVESTOCK case
if (!$fish) {
    $select_query = "SELECT LIVESTOCKID AS FISHID, NAME, STOCK FROM LIVESTOCK WHERE LIVESTOCKID = :TARGET_ID";
    $stmt = oci_parse($dbconn, $select_query);
    oci_bind_by_name($stmt, ':TARGET_ID', $fish_id);
    oci_execute($stmt);
    $fish = oci_fetch_array($stmt, OCI_ASSOC);
    oci_free_statement($stmt);
}

if (!$fish) {
    die("<div style='font-family:sans-serif; padding:20px; color:#c0392b;'><strong>Error:</strong> Fish inventory item details not found for ID: " . htmlspecialchars($fish_id) . "</div>");
}

$id    = $fish['FISHID'] ?? '';
$name  = $fish['NAME'] ?? '';
$stock = $fish['STOCK'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Adjust Fish Stock</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 50px; display: flex; justify-content: center; align-items: center; height: 80vh; }
        .form-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        h2 { color: #1e6091; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #f4f7f6; padding-bottom: 10px; }
        .meta-info { background-color: #f0f8ff; border-left: 4px solid #3498db; padding: 15px; border-radius: 6px; margin-bottom: 25px; color: #495057; font-size: 0.95rem; }
        .meta-info div { margin-bottom: 8px; }
        .meta-info div:last-child { margin-bottom: 0; }
        label { display: block; font-weight: bold; color: #34495e; margin-bottom: 8px; text-transform: uppercase; font-size: 0.85rem; }
        .counter-group { display: flex; align-items: center; gap: 10px; margin-bottom: 30px; }
        .btn-step { background: #e2e6ea; border: 1px solid #ccd1d9; font-size: 1.5rem; width: 45px; height: 45px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .btn-step:hover { background: #ccd1d9; }
        .stock-input { flex: 1; text-align: center; font-size: 1.3rem; font-weight: bold; padding: 8px; border: 1px solid #ccd1d9; border-radius: 6px; }
        .action-row { display: flex; gap: 15px; }
        .btn-submit { background-color: #3498db; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 2; font-size: 1rem; transition: background 0.2s; }
        .btn-submit:hover { background-color: #2980b9; }
        .btn-cancel { background-color: #95a5a6; color: white; border: none; padding: 12px 20px; border-radius: 6px; cursor: pointer; font-weight: bold; flex: 1; text-decoration: none; text-align: center; transition: background 0.2s; }
        .btn-cancel:hover { background-color: #7f8c8d; }
    </style>
</head>
<body>

<div class="form-card">
    <h2>🐟 Adjust Fish Stock</h2>

    <div class="meta-info">
        <div><strong>Fish ID Reference:</strong> <?php echo htmlspecialchars($id); ?></div>
        <div><strong>Species Variety Name:</strong> <?php echo htmlspecialchars($name); ?></div>
    </div>

    <form method="POST" action="adjust_stock_fish.php">
        <input type="hidden" name="fish_id" value="<?php echo htmlspecialchars($id); ?>">
        
        <label for="current_stock">Modify Total Available Quantity</label>
        <div class="counter-group">
            <button type="button" class="btn-step" onclick="adjustCount(-1)">−</button>
            <input type="number" name="current_stock" id="current_stock" class="stock-input" min="0" value="<?php echo intval($stock); ?>" required>
            <button type="button" class="btn-step" onclick="adjustCount(1)">+</button>
        </div>

        <div class="action-row">
            <button type="submit" name="update_inventory" class="btn-submit">Save Adjustment</button>
            <a href="manage_fish.php" class="btn-cancel">Back</a>
        </div>
    </form>
</div>

<script>
function adjustCount(value) {
    const stockInput = document.getElementById('current_stock');
    let currentVal = parseInt(stockInput.value) || 0;
    currentVal += value;
    if (currentVal < 0) currentVal = 0;
    stockInput.value = currentVal;
}
</script>

</body>
</html>