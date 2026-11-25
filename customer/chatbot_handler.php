<?php
// customer/chatbot_handler.php
header('Content-Type: application/json');
require_once 'db.php'; // Included in case you want to query orders or livestock later

// Get the raw POST data
$inputData = json_decode(file_get_contents('php://input'), true);
$userMessage = isset($inputData['message']) ? strtolower(trim($inputData['message'])) : '';

$response = "I'm sorry, I didn't quite get that. You can ask me about 'order', 'livestock', 'tracking', or 'billing'!";

// Basic keyword routing matching your dashboard features
if (stripos($userMessage, 'staff') !== false || stripos($userMessage, 'phone') !== false) {
    // Menyediakan link html berbentuk butang/pautan di dalam JSON string
    $reply = "You can contact our staff directly via phone. Click here: <a href='tel:+60123456789' style='color: #1abc9c; font-weight: bold; text-decoration: underline;'>+6012-3456789</a>";
}
elseif (strpos($userMessage, 'order') !== false || strpos($userMessage, 'buy') !== false) {
    $response = "To place a new order for livestock or chicken stocks, simply click on the **'Make New Order'** card on your dashboard!";
} elseif (strpos($userMessage, 'livestock') !== false || strpos($userMessage, 'asset') !== false) {
    $response = "You can browse our live listings, chicken stocks, and fish yields by clicking on **'View Livestock'**.";
} elseif (strpos($userMessage, 'track') !== false || strpos($userMessage, 'delivery') !== false) {
    $response = "To monitor your incoming deliveries and check dispatch schedules, navigate to **'Order Tracking'**.";
} elseif (strpos($userMessage, 'invoice') !== false || strpos($userMessage, 'receipt') !== false || strpos($userMessage, 'billing') !== false) {
    $response = "Your past billing details and completed payments can be reviewed under the **'Invoices & Receipts'** section.";
} elseif (strpos($userMessage, 'hi') !== false || strpos($userMessage, 'hello') !== false) {
    $response = "Hello! Welcome to the Prima Farm customer support assistant. How can I help you today?";
}

echo json_encode(['reply' => $response]);