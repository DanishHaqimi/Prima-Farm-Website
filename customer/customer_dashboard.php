<?php
session_start();
include('../dbconn.php');

// Session fallbacks if navigating directly during testing
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'C001';
$user_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Amir Nasri';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livestock System Portal - Customer Dashboard</title>
    <style>
        :root {
            --bg-color: #f4f7f6;
            --nav-bg: #2c3e50;
            --card-bg: #ffffff;
            --teal-accent: #1abc9c;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
            --btn-danger: #e74c3c;
            --btn-danger-hover: #c0392b;
            --blue-accent: #3498db; 
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: var(--bg-color);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
        }

        /* --- Navbar --- */
        .navbar {
            background-color: var(--nav-bg);
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-size: 1.35rem;
            font-weight: bold;
        }

        .btn-signout {
            background-color: var(--btn-danger);
            color: #ffffff;
            text-decoration: none;
            padding: 10px 22px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.95rem;
            transition: background-color 0.2s;
        }

        .btn-signout:hover {
            background-color: var(--btn-danger-hover);
        }

        /* --- Dashboard Container --- */
        .dashboard-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            box-sizing: border-box;
        }

        /* --- Welcome Banner Card --- */
        .welcome-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 30px 40px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-left: 6px solid var(--teal-accent);
            margin-bottom: 30px;
        }

        .welcome-card h2 {
            margin: 0 0 15px 0;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .account-info {
            font-size: 1rem;
            margin-bottom: 15px;
        }

        .welcome-card p {
            color: var(--text-muted);
            line-height: 1.6;
            margin: 0;
            font-size: 1.05rem;
        }

        /* --- Grid Navigation --- */
        .menu-grid {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }

        .menu-card {
            background-color: var(--card-bg);
            border-radius: 10px;
            padding: 35px 25px;
            flex: 1;
            min-width: 250px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            text-decoration: none;
            color: inherit;
            transition: transform 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .menu-card.highlight-card {
            border-top: 4px solid var(--blue-accent);
        }

        .menu-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
        }

        .menu-card h3 {
            margin: 0 0 12px 0;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
        }

        .menu-card p {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.5;
            margin: 0;
        }

        /* --- Promotion Pop-up Styles --- */
        .promo-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000; /* High z-index to overlay above everything, including chat layout */
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        /* Class toggle managed via your JavaScript functions */
        .promo-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        .promo-content {
            background-color: var(--card-bg);
            padding: 35px 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 450px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            position: relative;
            transform: scale(0.8);
            transition: transform 0.3s ease;
        }

        .promo-overlay.show .promo-content {
            transform: scale(1);
        }

        .promo-close-x {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            color: var(--text-muted);
            cursor: pointer;
            border: none;
            background: none;
        }

        .promo-close-x:hover {
            color: var(--text-dark);
        }

        .promo-badge {
            background-color: #27ae60;
            color: white;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: bold;
            display: inline-block;
            margin: 15px 0;
            letter-spacing: 0.5px;
        }

        .promo-btn {
            background-color: var(--teal-accent);
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 1rem;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            margin-top: 10px;
            transition: background-color 0.2s;
        }

        .promo-btn:hover {
            background-color: #16a085;
        }

        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; }
            .menu-grid { flex-direction: column; }
        }
    </style>
</head>
<body>

    <!-- Promotion Pop-up Modal HTML -->
    <div id="promoPopup" class="promo-overlay">
        <div class="promo-content">
            <button class="promo-close-x" onclick="closePromo()">&times;</button>
            <h2 style="margin-top: 10px;">🎉 Exclusive Seasonal Offer! 🎉</h2>
            <p style="color: var(--text-muted); line-height: 1.5; font-size: 1rem;">
                Get an instant premium markdown on high-yield fresh assets and livestock selections ordered through the system dashboard layout this week.
            </p>
            <div class="promo-badge">PROMO CODE: PRIMAFARM15</div>
            <button class="promo-btn" onclick="location.href='customer_view_livestock.php'">Claim Deals Now</button>
        </div>
    </div>

    <nav class="navbar">
        <div class="navbar-brand">
            🌾 Livestock System Portal
        </div>
        <a href="../index.php" class="btn-signout">Sign Out</a>
    </nav>

    <main class="dashboard-container">
        
        <div class="welcome-card">
            <h2>Welcome Back, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
            <div class="account-info">
                <strong>Customer Account ID:</strong> <?php echo htmlspecialchars($user_id); ?>
            </div>
            <p>Manage your orders, view catalog distributions, and track live dispatch deliveries directly from your custom client dashboard window context.</p>
        </div>

        <div class="menu-grid">
            
            <a href="create_order.php" class="menu-card highlight-card">
                <h3>➕ Make New Order</h3>
                <p>Place a new purchase order for livestock, chicken stocks, or fresh yields.</p>
            </a>

            <a href="customer_view_livestock.php" class="menu-card">
                <h3>🛍️ View Livestock</h3>
                <p>Browse our active listings of livestock assets, chicken stocks, and fish yields.</p>
            </a>

            <a href="my_orders.php" class="menu-card">
                <h3>📦 Order Tracking</h3>
                <p>Monitor your incoming delivery statuses and check dispatch schedules.</p>
            </a>

            <a href="invoice.php" class="menu-card" >
                <h3>📜 Invoices & Receipts</h3>
                <p>Review past billing parameters and successfully committed payment forms.</p>
            </a>

        </div>
    </main>

    <div id="chat-widget-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000; font-family: Arial, sans-serif;">
        <button id="chat-button" style="background-color: #2b3e50; color: white; border: none; padding: 15px 20px; border-radius: 30px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
            💬 Chat Support
        </button>

        <div id="chat-window" style="display: none; width: 320px; height: 400px; background-color: white; border-radius: 10px; position: fixed; bottom: 80px; right: 20px; box-shadow: 0px 4px 15px rgba(0,0,0,0.2); flex-direction: column; overflow: hidden;">
            <div style="background-color: #2b3e50; color: white; padding: 12px; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                <span>Prima Farm Assistant</span>
                <button id="close-chat" style="background: none; border: none; color: white; font-size: 16px; cursor: pointer;">✕</button>
            </div>
            
            <div id="chat-body" style="flex: 1; padding: 10px; overflow-y: auto; background-color: #f9f9f9; font-family: Arial, sans-serif; font-size: 14px;">
                <div style="margin-bottom: 8px; color: #555;"><strong>Bot:</strong> Hello! How can I help you today?</div>
            </div>
            
            <div style="padding: 10px; border-top: 1px solid #eee; display: flex; background: white;">
                <input type="text" id="chat-input" placeholder="Type a message..." style="flex: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px; outline: none;">
                <button id="send-chat" style="background-color: #2b3e50; color: white; border: none; padding: 8px 12px; margin-left: 5px; border-radius: 4px; cursor: pointer;">Send</button>
            </div>
        </div>
    </div>

    <script>
        const chatButton = document.getElementById('chat-button');
        const chatWindow = document.getElementById('chat-window');
        const closeChat = document.getElementById('close-chat');
        const chatBody = document.getElementById('chat-body');
        const chatInput = document.getElementById('chat-input');
        const sendChat = document.getElementById('send-chat');

        // Toggle Chat Window View
        chatButton.addEventListener('click', () => { 
            chatWindow.style.display = 'flex'; 
        });
        
        closeChat.addEventListener('click', () => { 
            chatWindow.style.display = 'none'; 
        });

        // Function to handle sending message
        async function sendMessage() {
            const message = chatInput.value.trim();
            if (!message) return;

            // 1. Append User Message to UI
            chatBody.innerHTML += `<div style="margin-bottom: 8px; text-align: right;"><strong>You:</strong> ${message}</div>`;
            chatInput.value = '';
            chatBody.scrollTop = chatBody.scrollHeight;

            try {
                // 2. Send request to your chatbot_handler.php file
                const response = await fetch('chatbot_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'message=' + encodeURIComponent(message)
                });

                // 3. Parse the JSON reply
                const data = await response.json();

                // 4. Append Bot Response to UI
                const botMessageElement = document.createElement('div');
                botMessageElement.style.marginBottom = '8px';
                botMessageElement.style.color = '#555';
                botMessageElement.style.textAlign = 'left';
                botMessageElement.innerHTML = `<strong>Bot:</strong> ${data.reply}`;

                chatBody.appendChild(botMessageElement);
                chatBody.scrollTop = chatBody.scrollHeight;
            } catch (error) {
                console.error('Error fetching chatbot response:', error);
            }
        }

        // Trigger on click or Enter key
        sendChat.addEventListener('click', sendMessage);
        chatInput.addEventListener('keypress', (e) => { 
            if (e.key === 'Enter') sendMessage(); 
        });

        // Function to show the promo pop-up 1 second after the page fully loads
        window.addEventListener('DOMContentLoaded', (event) => {
            // Added check to prevent spamming pop-ups on subsequent refreshes during the session
            if (!sessionStorage.getItem('promoShown')) {
                setTimeout(() => {
                    document.getElementById('promoPopup').classList.add('show');
                }, 1000); // 1000 milliseconds = 1 second
            }
        });

        // Function to hide the pop-up when clicking the 'X' or closing action button
        function closePromo() {
            document.getElementById('promoPopup').classList.remove('show');
            sessionStorage.setItem('promoShown', 'true');
        }
    </script>

</body>
</html>