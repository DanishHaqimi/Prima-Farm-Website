<?php
session_start();
include('../dbconn.php'); // Your active Oracle database connection file ($dbconn)

// Ensure user is logged in, otherwise use test fallback
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "C001"; // Must match a valid numeric CUSTOMERID in DB
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Amir Nasri';

// Auto-generate a preview visual layout tag for UI display tracking
$display_order_id = "ORD-" . strtoupper(uniqid());

$message = "";
$message_class = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 1. Collect and Clean Input Fields from Form
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $livestock_id   = trim($_POST['menu_item']);
    $quantity       = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
    $total_price    = isset($_POST['total_price']) ? floatval($_POST['total_price']) : 0.00;
    $payment_method = trim($_POST['payment_method'] ?? 'COD');
    $special_notes  = trim($_POST['special_notes'] ?? '');
    
    // Default online background routing handler identifier setting (Staff Cashier)
    $assigned_staff_id = "S001";

    // Handle File Upload securely
    $uploaded_file_name = "";
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["receipt_file"]["name"], PATHINFO_EXTENSION);
        $uploaded_file_name = "REC_" . time() . "." . $file_extension;
        move_uploaded_file($_FILES["receipt_file"]["tmp_name"], $target_dir . $uploaded_file_name);
    }

    // Server-side verification
    if (empty($livestock_id) || $quantity <= 0 || empty($customer_name)) {
        $message = "❌ Please fill in all required fields and select an item.";
        $message_class = "alert-danger";
    } else {
        
        // 2. Fetch the Next Sequence Value from Oracle
        $seq_query = "SELECT 'ORD' || SEQ_ORDER.NEXTVAL AS ORDERID FROM DUAL";

        $seq_stmt = oci_parse($dbconn, $seq_query);

        if (!oci_execute($seq_stmt)) {
            $e = oci_error($seq_stmt);
            die($e['message']);
        }

        $seq_row = oci_fetch_assoc($seq_stmt);
        $new_oracle_order_id = $seq_row['ORDERID'];
        oci_free_statement($seq_stmt);

        // 3. Insert into ORDERS Table using parameter bindings
        $sql_order = "INSERT INTO ORDERS (ORDERID, ORDERDATE, STAFFID, CUSTOMERID, TOTAL_PRICE, PAYMENT_METHOD, ATTACHMENT, SPECIAL_NOTES) 
                      VALUES (:order_id, SYSDATE, :staff_id, :customer_id, :total_price, :payment_method, :attachment, :special_notes)";
        
        $stmt_order = oci_parse($dbconn, $sql_order);
        
        oci_bind_by_name($stmt_order, ":order_id", $new_oracle_order_id);
        oci_bind_by_name($stmt_order, ":staff_id", $assigned_staff_id);
        oci_bind_by_name($stmt_order, ":customer_id", $user_id);
        oci_bind_by_name($stmt_order, ":total_price", $total_price);
        oci_bind_by_name($stmt_order, ":payment_method", $payment_method);
        oci_bind_by_name($stmt_order, ":attachment", $uploaded_file_name);
        oci_bind_by_name($stmt_order, ":special_notes", $special_notes);
        
        $order_success = oci_execute($stmt_order, OCI_DEFAULT);

        if ($order_success) {
            // 4. Insert detailed item values into bridge table (ORDER_LIVESTOCK)
            $sql_item = "INSERT INTO ORDER_LIVESTOCK (ORDERID, LIVESTOCKID, WEIGHT_KG) 
                         VALUES (:order_id, :livestock_id, :weight)";
            
            $stmt_item = oci_parse($dbconn, $sql_item);
            oci_bind_by_name($stmt_item, ":order_id", $new_oracle_order_id);
            oci_bind_by_name($stmt_item, ":livestock_id", $livestock_id);
            oci_bind_by_name($stmt_item, ":weight", $quantity); 
            
            $item_success = oci_execute($stmt_item, OCI_DEFAULT);

            if ($item_success) {
                
                // 5. Reduce inventory balance values directly from LIVESTOCK table
                $sql_update_stock = "UPDATE LIVESTOCK 
                                     SET STOCK = STOCK - :quantity 
                                     WHERE LIVESTOCKID = :livestock_id";
                
                $stmt_update_stock = oci_parse($dbconn, $sql_update_stock);
                oci_bind_by_name($stmt_update_stock, ":quantity", $quantity);
                oci_bind_by_name($stmt_update_stock, ":livestock_id", $livestock_id);
                
                $stock_update_success = oci_execute($stmt_update_stock, OCI_DEFAULT);
                oci_free_statement($stmt_update_stock);

                if ($stock_update_success) {
                    oci_commit($dbconn); 
                    $message = "🎉 Purchase order successfully created! Track ID: #" . $new_oracle_order_id;
                    $message_class = "alert-success";
                    header("refresh:2;url=my_orders.php");
                } else {
                    oci_rollback($dbconn); 
                    $err = oci_error($dbconn);
                    $message = "❌ Inventory reduction failure: " . htmlspecialchars($err['message']);
                    $message_class = "alert-danger";
                }
                
            } else {
                oci_rollback($dbconn); 
                $err = oci_error($stmt_item);
                $message = "❌ Detail processing failure: " . htmlspecialchars($err['message']);
                $message_class = "alert-danger";
            }
            oci_free_statement($stmt_item);
        } else {
            oci_rollback($dbconn);
            $err = oci_error($stmt_order);
            $message = "❌ Header processing failure: " . htmlspecialchars($err['message']);
            $message_class = "alert-danger";
        }
        oci_free_statement($stmt_order);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place New Order - Livestock Portal</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --nav-bg: #2c3e50;
            --card-bg: #ffffff;
            --blue-accent: #3498db;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
            --green-accent: #2ecc71;
            --btn-grey: #95a5a6;
        }
        body { font-family: 'Segoe UI', Arial, sans-serif; background-color: var(--bg-color); margin: 0; padding: 0; color: var(--text-dark); }
        .navbar { background-color: var(--nav-bg); color: #ffffff; display: flex; justify-content: space-between; align-items: center; padding: 15px 40px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .navbar-brand { font-size: 1.35rem; font-weight: bold; }
        .form-container { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        .form-card { background-color: var(--card-bg); border-radius: 10px; padding: 35px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border-top: 6px solid var(--blue-accent); }
        h2 { margin-top: 0; font-size: 1.6rem; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.95rem; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #dcdde1; border-radius: 6px; font-size: 1rem; box-sizing: border-box; outline: none; }
        textarea.form-control { resize: vertical; height: 100px; }
        .btn-submit { background-color: var(--blue-accent); color: white; border: none; padding: 14px 20px; font-size: 1rem; font-weight: bold; border-radius: 6px; cursor: pointer; width: 100%; transition: background-color 0.2s; }
        .btn-submit:hover { background-color: #2980b9; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .qr-box { display: none; background: #f9f9f9; border: 1px dashed #ccc; padding: 15px; text-align: center; margin-top: 10px; border-radius: 6px; }
        .readonly-highlight { background-color: #e9ecef; cursor: not-allowed; }

        /* --- Order Confirmation Pop-up Overlay Styles --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .modal-overlay.show-modal {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background-color: var(--card-bg);
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 480px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
            transform: scale(0.85);
            transition: transform 0.3s ease;
        }
        .modal-overlay.show-modal .modal-content {
            transform: scale(1);
        }
        .modal-header {
            font-size: 1.35rem;
            font-weight: bold;
            margin-bottom: 15px;
            color: var(--text-dark);
            border-bottom: 2px solid #f1f2f6;
            padding-bottom: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 1rem;
            border-bottom: 1px dashed #f1f2f6;
            padding-bottom: 8px;
        }
        .summary-label { font-weight: 600; color: var(--text-muted); }
        .summary-value { font-weight: 700; color: var(--text-dark); }
        .summary-total { font-size: 1.2rem; color: #e74c3c; font-weight: bold; }
        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        .btn-modal {
            flex: 1;
            padding: 12px;
            font-size: 1rem;
            font-weight: bold;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-modal:hover { opacity: 0.9; }
        .btn-confirm { background-color: var(--green-accent); color: white; }
        .btn-cancel { background-color: var(--btn-grey); color: white; }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="navbar-brand">🌾 Livestock System Portal</div>
        <a href="customer_dashboard.php" style="color:#fff; text-decoration:none; padding:8px 16px; border:1px solid #fff; border-radius:6px;">⬅️ Back to Dashboard</a>
    </nav>

    <div class="form-container">
        <div class="form-card">
            <h2>➕ Place a New Order</h2>
            <p style="color: var(--text-muted); margin-bottom: 25px;">Ordering as: <strong><?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_id); ?>)</strong></p>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $message_class; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Added standard id attribute and an onsubmit listener interface -->
            <form id="livestockOrderForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data" onsubmit="return triggerOrderConfirmation(event)">
                
                <div class="form-group">
                    <label>Order Serial Tracking Identifier</label>
                    <input type="text" class="form-control readonly-highlight" value="<?php echo $display_order_id; ?>" readonly>
                </div>

                <div class="form-group">
                    <label for="customer_name">Customer Name</label>
                    <input type="text" name="customer_name" id="customer_name" class="form-control" value="<?php echo htmlspecialchars($user_name); ?>" required>
                </div>

                <div class="form-group">
                    <label for="livestock_type">Select Livestock Type</label>
                    <select id="livestock_type" class="form-control" onchange="updateMenuOptions()" required>
                        <option value="">-- Choose Category --</option>
                        <option value="Poultry">Poultry / Chicken 🐓</option>
                        <option value="Fishery">Fishery / Tilapia 🐟</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="menu_item">Select Specific Package / Menu</label>
                    <select name="menu_item" id="menu_item" class="form-control" onchange="calculateTotal()" required>
                        <option value="">-- Select Category First --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity (Units / Heads / KG)</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" min="1" placeholder="e.g. 10" oninput="calculateTotal()" required>
                </div>

                <div class="form-group">
                    <label for="display_total">Total Price (RM)</label>
                    <input type="text" id="display_total" class="form-control readonly-highlight" value="0.00" readonly>
                    <input type="hidden" name="total_price" id="total_price" value="0.00">
                </div>

                <div class="form-group">
                    <label for="payment_method">Payment Method</label>
                    <select name="payment_method" id="payment_method" class="form-control" onchange="togglePaymentView()" required>
                        <option value="COD">Cash on Delivery (COD)</option>
                        <option value="QR">QR Payment / Online Transfer</option>
                    </select>
                </div>

                <div id="qr_payment_box" class="qr-box">
                    <p>📸 Please scan the QR code to process your payment:</p>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=LivestockSystemPayment" alt="Payment QR Code" style="margin-bottom:10px;"><br>
                </div>

                <div class="form-group">
                    <label for="receipt_file">Upload Attachment / Proof of Payment (Optional)</label>
                    <input type="file" name="receipt_file" id="receipt_file" class="form-control" accept="image/*,.pdf">
                </div>

                <div class="form-group">
                    <label for="special_notes">Special Requirements / Notes (Optional)</label>
                    <textarea name="special_notes" id="special_notes" class="form-control" placeholder="e.g. Requesting organic feed history..."></textarea>
                </div>

                <button type="submit" class="btn-submit">Submit Purchase Order</button>
            </form>
        </div>
    </div>

    <!-- Order Confirmation Pop-up HTML Container -->
    <div id="confirmModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">📝 Confirm Your Purchase Order</div>
            <p style="color: var(--text-muted); font-size:0.95rem; margin-bottom:20px;">Please double-check your requested distribution payload configurations before dispatch processing:</p>
            
            <div class="summary-row">
                <span class="summary-label">Selected Item:</span>
                <span id="pop_item" class="summary-value">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Quantity:</span>
                <span id="pop_qty" class="summary-value">-</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Payment Mode:</span>
                <span id="pop_method" class="summary-value">-</span>
            </div>
            <div class="summary-row" style="border-bottom:none; margin-top:15px;">
                <span class="summary-label" style="font-size:1.1rem; color:var(--text-dark)">Total Amount:</span>
                <span id="pop_total" class="summary-value summary-total">RM 0.00</span>
            </div>

            <div class="modal-actions">
                <button class="btn-modal btn-cancel" onclick="dismissConfirmation()">Cancel</button>
                <button class="btn-modal btn-confirm" onclick="executeFinalFormSubmit()">Yes, Confirm Order</button>
            </div>
        </div>
    </div>

    <script>
        const menuDatabase = {
            "Poultry":[
                { id:"L001", name:"Ayam Kampung S (RM20)", price:20 },
                { id:"L003", name:"Ayam Kampung M (RM25)", price:25 },
                { id:"L004", name:"Ayam Kampung L (RM35)", price:35 }
            ],
            "Fishery":[
                { id:"L002", name:"Ikan Tilapia (RM12)", price:12 },
                { id:"L005", name:"Ikan Keli (RM10)", price:10 }
            ]
        };

        function updateMenuOptions() {
            const category = document.getElementById("livestock_type").value;
            const menuSelect = document.getElementById("menu_item");

            menuSelect.innerHTML = '<option value="">-- Choose Option --</option>';

            if (category && menuDatabase[category]) {
                menuDatabase[category].forEach(item => {
                    let option = document.createElement("option");
                    option.value = item.id; 
                    option.text = item.name;
                    option.setAttribute("data-price", item.price);
                    menuSelect.appendChild(option);
                });
            }
            calculateTotal();
        }

        function calculateTotal() {
            const menuSelect = document.getElementById("menu_item");
            const quantityInput = document.getElementById("quantity");
            const displayTotal = document.getElementById("display_total");
            const hiddenTotal = document.getElementById("total_price");

            const selectedOption = menuSelect.options[menuSelect.selectedIndex];
            const price = selectedOption ? parseFloat(selectedOption.getAttribute("data-price")) || 0 : 0;
            const quantity = parseInt(quantityInput.value) || 0;

            const finalTotal = price * quantity;
            
            displayTotal.value = finalTotal.toFixed(2);
            hiddenTotal.value = finalTotal.toFixed(2);
        }

        function togglePaymentView() {
            const method = document.getElementById("payment_method").value;
            document.getElementById("qr_payment_box").style.display = (method === "QR") ? "block" : "none";
        }

        // --- Confirmation Modal Logic Functions ---
        function triggerOrderConfirmation(event) {
            // Stop the form from executing postback variables instantly
            event.preventDefault();

            const menuSelect = document.getElementById("menu_item");
            const selectedItemText = menuSelect.options[menuSelect.selectedIndex].text;
            const quantityVal = document.getElementById("quantity").value;
            const paymentMethodVal = document.getElementById("payment_method").value;
            const finalPriceVal = document.getElementById("display_total").value;

            // Map data directly into target visual modal labels
            document.getElementById("pop_item").innerText = selectedItemText;
            document.getElementById("pop_qty").innerText = quantityVal;
            document.getElementById("pop_method").innerText = (paymentMethodVal === "QR") ? "Online / QR Transfer" : "Cash on Delivery";
            document.getElementById("pop_total").innerText = "RM " + finalPriceVal;

            // Launch popup structural transition wrapper
            document.getElementById("confirmModal").classList.add("show-modal");
            return false;
        }

        function dismissConfirmation() {
            document.getElementById("confirmModal").classList.remove("show-modal");
        }

        function executeFinalFormSubmit() {
            // Select layout structure directly and call submit bypass function safely
            document.getElementById("livestockOrderForm").submit();
        }
    </script>
</body>
</html>