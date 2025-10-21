<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Sales Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 p-8">
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-md mb-6">
        <h1 class="text-xl font-bold">🚀 Sales Ticket</h1>
        <div class="flex items-center space-x-2">
            <span id="wsStatus" class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-300 text-gray-700">
                ⏳ Connecting...
            </span>
            <span id="updateIndicator" class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600">
                ⏱️ Last update: --
            </span>
            <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">🏠 Dashboard</a>
            <a href="./test-api.php" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">🧪 Test API</a>
        </div>
    </div>

    <h1 class="text-3xl font-bold text-center">📊 Sales Dashboard</h1>

    <!-- Live Orders Section -->
    <div class="bg-white shadow-md rounded-xl p-6">
        <h2 class="text-xl font-semibold mb-4">🆕 Live Orders</h2>
        <button onclick="createRandomOrder()"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:bg-gray-400"
                id="orderBtn">
            ➕ Create Random Order
        </button>
        <div id="log" class="mt-4 h-64 overflow-y-scroll bg-gray-50 border border-gray-300 rounded p-4 space-y-2"></div>
    </div>

    <!-- Real-Time Analytics Section -->
    <div class="bg-white shadow-md rounded-xl p-6 relative">
        <div id="analyticsFlash"
             class="absolute top-0 left-0 w-full h-full bg-green-400 opacity-0 rounded-xl pointer-events-none transition-opacity duration-300"></div>
        <h2 class="text-xl font-semibold mb-4">📈 Real-Time Analytics</h2>
        <div id="analytics" class="space-y-2 text-sm">
            <div>💰 <strong>Total Revenue:</strong> <span id="totalRevenue">--</span></div>
            <div>📦 <strong>Orders Last Minute:</strong> <span id="ordersLastMinute">--</span></div>
            <div>⏱ <strong>Revenue Last Minute:</strong> <span id="revenueLastMinute">--</span></div>
            <div>🔥 <strong>Top Products:</strong>
                <ul id="topProducts" class="list-disc list-inside ml-4 mt-1 text-gray-700"></ul>
            </div>
        </div>
    </div>
</div>

<footer class="bg-gray-100 py-4 mt-10">
    <div class="container mx-auto text-center space-x-4">
        <a href="https://github.com/yahongie2014/PentavalueTask" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center bg-black text-white px-4 py-2 rounded hover:bg-gray-900 space-x-2 transition">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd"
                      d="M8 .198a8 8 0 00-2.53 15.59c.4.074.547-.174.547-.387 0-.19-.007-.693-.01-1.36-2.226.483-2.695-1.073-2.695-1.073-.364-.925-.89-1.17-.89-1.17-.727-.497.055-.487.055-.487.803.057 1.225.825 1.225.825.715 1.223 1.875.87 2.33.666.072-.518.28-.87.508-1.07-1.776-.2-3.644-.888-3.644-3.952 0-.873.312-1.587.823-2.147-.083-.202-.357-1.015.078-2.116 0 0 .672-.215 2.2.82a7.688 7.688 0 012.002-.27c.68.003 1.364.092 2.002.27 1.527-1.035 2.198-.82 2.198-.82.437 1.101.163 1.914.08 2.116.513.56.823 1.274.823 2.147 0 3.072-1.87 3.75-3.65 3.947.288.247.543.735.543 1.48 0 1.068-.01 1.93-.01 2.193 0 .215.144.464.55.385A8.001 8.001 0 008 .198z"></path>
            </svg>
            <span>GitHub</span>
        </a>
        <a href="../docs/api.json" download
           class="inline-flex items-center bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 space-x-2 transition">
            <img src="https://www.svgrepo.com/show/354202/postman-icon.svg" alt="Postman" class="w-5 h-5">
            <span>Download Postman</span>
        </a>
    </div>
</footer>

<script>
    const getWebSocketUrl = () => {
        const hostname = window.location.hostname;
        const PORT = '8000';
        if (hostname === 'localhost' || hostname === '127.0.0.1') {
            console.log('🏠 Running in LOCAL mode');
            return `ws://localhost:${PORT}`;
        }
        if (hostname.includes('railway.app') || hostname === '66.33.22.111') {
            console.log('🚂 Running on RAILWAY');
            return `wss://${hostname}`;
        }
        console.log('🌐 Running on CUSTOM DOMAIN');
        return `wss://${hostname}`;
    };

    const log = document.getElementById("log");
    const totalRevenue = document.getElementById("totalRevenue");
    const ordersLastMinute = document.getElementById("ordersLastMinute");
    const revenueLastMinute = document.getElementById("revenueLastMinute");
    const topProducts = document.getElementById("topProducts");
    const wsStatus = document.getElementById("wsStatus");
    const orderBtn = document.getElementById("orderBtn");
    const updateIndicator = document.getElementById("updateIndicator");
    const analyticsFlash = document.getElementById("analyticsFlash");

    let socket;
    let reconnectAttempts = 0;
    let analyticsInterval = null;
    const MAX_RECONNECT_ATTEMPTS = 5;
    const RECONNECT_DELAY = 3000;
    const ANALYTICS_POLL_INTERVAL = 10000;

    function connectWebSocket() {
        const wsUrl = getWebSocketUrl();
        console.log("🔌 Attempting to connect to WebSocket:", wsUrl);

        try {
            socket = new WebSocket(wsUrl);

            socket.onopen = () => {
                console.log("✅ WebSocket connected successfully!");
                reconnectAttempts = 0;
                updateStatus('connected');

                // Fetch analytics immediately on connection
                fetchAndSendAnalytics();
            };

            socket.onmessage = (event) => {
                try {
                    const msg = JSON.parse(event.data);
                    console.log("📨 Received message:", msg);

                    if (msg.event === "new_order") {
                        displayNewOrder(msg.data);
                        setTimeout(() => fetchAndSendAnalytics(), 500);
                    }
                    if (msg.event === "analytics_updated") {
                        updateAnalytics(msg.data);
                    }
                } catch (e) {
                    console.error("❌ Error parsing message:", e);
                }
            };

            socket.onerror = (error) => {
                console.error("❌ WebSocket error:", error);
                console.error("Make sure 'php server.php' is running!");
                updateStatus('error');
            };

            socket.onclose = (event) => {
                console.log("🔴 WebSocket disconnected. Code:", event.code, "Reason:", event.reason);
                updateStatus('disconnected');
                attemptReconnect();
            };
        } catch (e) {
            console.error("❌ Failed to create WebSocket:", e);
            updateStatus('error');
        }
    }

    function attemptReconnect() {
        if (reconnectAttempts < MAX_RECONNECT_ATTEMPTS) {
            reconnectAttempts++;
            console.log(`🔄 Reconnecting... Attempt ${reconnectAttempts}/${MAX_RECONNECT_ATTEMPTS}`);
            setTimeout(connectWebSocket, RECONNECT_DELAY);
        } else {
            console.error("❌ Max reconnection attempts reached. Please check if server.php is running.");
            updateStatus('failed');
        }
    }

    function updateStatus(status) {
        const statusMap = {
            'connected': {text: '🟢 Connected', class: 'bg-green-500 text-white'},
            'disconnected': {text: '🔴 Disconnected', class: 'bg-red-500 text-white'},
            'error': {text: '⚠️ Error', class: 'bg-yellow-500 text-white'},
            'failed': {text: '❌ Connection Failed', class: 'bg-red-700 text-white'}
        };

        const config = statusMap[status] || {text: '⏳ Connecting...', class: 'bg-gray-300 text-gray-700'};
        wsStatus.textContent = config.text;
        wsStatus.className = `px-3 py-1 rounded-full text-xs font-semibold ${config.class}`;

        orderBtn.disabled = status !== 'connected';
    }

    function updateLastUpdateTime() {
        const now = new Date();
        const timeString = now.toLocaleTimeString();
        updateIndicator.textContent = `⏱️ Last update: ${timeString}`;
        updateIndicator.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-green-200 text-green-800 animate-pulse';

        setTimeout(() => {
            updateIndicator.className = 'px-3 py-1 rounded-full text-xs font-semibold bg-gray-200 text-gray-600';
        }, 2000);
    }

    function flashAnalytics() {
        analyticsFlash.style.opacity = '0.3';
        setTimeout(() => {
            analyticsFlash.style.opacity = '0';
        }, 300);
    }

    function displayNewOrder(data) {
        const item = document.createElement("div");
        item.className = "bg-green-100 text-green-800 px-3 py-2 rounded shadow animate-fade-in";
        item.innerHTML = `
            <strong>🆕 New Order</strong><br>
            <span class="text-sm">
                Product: ${data.product_name} |
                Qty: ${data.quantity} |
                Price: ${data.price} LE |
                Time: ${data.date}
            </span>
        `;
        log.prepend(item);

        // Keep only last 20 orders
        while (log.children.length > 20) {
            log.removeChild(log.lastChild);
        }
    }

    function updateAnalytics(data) {
        console.log("📊 Updating Analytics:", data);
        flashAnalytics();
        updateLastUpdateTime();

        totalRevenue.textContent = `EGP ${parseFloat(data.total_revenue || 0).toFixed(2)}`;

        const order = data.orders_last_minute;
        if (order && order.product_name && order.total_sold) {
            ordersLastMinute.textContent = `${order.product_name} - Qty: ${order.total_sold}`;
        } else if (data.count_orders_last_minute > 0) {
            ordersLastMinute.textContent = `${data.count_orders_last_minute} order(s) in last minute`;
        } else {
            ordersLastMinute.textContent = 'No orders in last minute';
        }

        revenueLastMinute.textContent = `EGP ${parseFloat(data.revenue_last_minute || 0).toFixed(2)}`;

        topProducts.innerHTML = '';
        if (data.top_products && data.top_products.length > 0) {
            data.top_products.forEach(prod => {
                const li = document.createElement('li');
                li.textContent = `${prod.product_name} (${prod.total_sold} sold)`;
                topProducts.appendChild(li);
            });
        } else {
            topProducts.innerHTML = '<li class="text-gray-500">No data available</li>';
        }
    }

    async function createRandomOrder() {
        if (!socket || socket.readyState !== WebSocket.OPEN) {
            alert("WebSocket not connected. Please make sure server.php is running!");
            return;
        }

        try {
            orderBtn.disabled = true;
            orderBtn.textContent = '⏳ Creating...';

            const response = await fetch('/products');
            const result = await response.json();

            if (!result.data || !result.data.length) {
                alert("No products found. Run /seed to add demo data.");
                return;
            }

            const random = result.data[Math.floor(Math.random() * result.data.length)];
            const quantity = Math.floor(Math.random() * 5) + 1;

            const orderResponse = await fetch('/create_order', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    product_id: random.id,
                    quantity: quantity,
                    price: random.price,
                    date: new Date().toISOString().slice(0, 19).replace('T', ' ')
                })
            });

            if (!orderResponse.ok) {
                const error = await orderResponse.json();
                alert(`Error: ${error.error || 'Failed to create order'}`);
                return;
            }

            const order = await orderResponse.json();

            if (order.data && socket.readyState === WebSocket.OPEN) {
                socket.send(JSON.stringify({
                    event: 'new_order',
                    data: order.data
                }));
                console.log("✅ Order sent to WebSocket");

                // ⚡ LIVE UPDATE: Fetch analytics immediately after creating order
                setTimeout(() => fetchAndSendAnalytics(), 500);
            }
        } catch (error) {
            console.error("❌ Error creating order:", error);
            alert("Failed to create order. Please try again.");
        } finally {
            orderBtn.disabled = false;
            orderBtn.textContent = '➕ Create Random Order';
        }
    }

    async function fetchAndSendAnalytics() {
        try {
            const response = await fetch('/analytics');
            if (!response.ok) {
                throw new Error('Failed to fetch analytics');
            }

            const data = await response.json();
            console.log("📊 Fetched analytics:", data);

            if (socket && socket.readyState === WebSocket.OPEN) {
                // Send to WebSocket to broadcast to all connected clients
                socket.send(JSON.stringify({
                    event: 'analytics_updated',
                    data: data
                }));
            } else {
                // If WebSocket not ready, update UI directly
                updateAnalytics(data);
            }
        } catch (error) {
            console.error("❌ Error fetching analytics:", error);
        }
    }

    // Initialize
    window.addEventListener('DOMContentLoaded', () => {
        console.log("🚀 Dashboard initializing...");
        connectWebSocket();

        // Initial fetch
        fetchAndSendAnalytics();

        // ⚡ LIVE UPDATE: Poll every 10 seconds (instead of 60)
        analyticsInterval = setInterval(fetchAndSendAnalytics, ANALYTICS_POLL_INTERVAL);

        console.log(`✅ Analytics will update every ${ANALYTICS_POLL_INTERVAL / 1000} seconds`);
    });

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (socket) {
            socket.close();
        }
        if (analyticsInterval) {
            clearInterval(analyticsInterval);
        }
    });

    // Auto-refresh analytics when tab becomes visible
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && socket && socket.readyState === WebSocket.OPEN) {
            console.log("👁️ Tab visible - refreshing analytics");
            fetchAndSendAnalytics();
        }
    });
</script>

<style>
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }
</style>
</body>
</html>