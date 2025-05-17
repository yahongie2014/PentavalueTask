<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Real-Time Sales Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 p-8">
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-md mb-6">
        <h1 class="text-xl font-bold">🚀 Sales Ticket</h1>
        <div class="space-x-2">
            <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">🏠 Dashboard</a>
            <a href="./test-api.php" class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800">🧪 Test API</a>
        </div>
    </div>

    <h1 class="text-3xl font-bold text-center">📊 Sales Dashboard</h1>

    <div class="bg-white shadow-md rounded-xl p-6">
        <h2 class="text-xl font-semibold mb-4">🆕 Live Orders</h2>
        <button onclick="createRandomOrder()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
            ➕ Create Random Order
        </button>
        <div id="log" class="mt-4 h-64 overflow-y-scroll bg-gray-50 border border-gray-300 rounded p-4 space-y-2"></div>
    </div>

    <div class="bg-white shadow-md rounded-xl p-6">
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
        <!-- GitHub Button -->
        <a href="https://github.com/yahongie2014/PentavalueTask" target="_blank" rel="noopener noreferrer"
           class="inline-flex items-center bg-black text-white px-4 py-2 rounded hover:bg-gray-900 space-x-2 transition">
            <svg class="w-5 h-5 fill-current" viewBox="0 0 16 16" aria-hidden="true">
                <path fill-rule="evenodd"
                      d="M8 .198a8 8 0 00-2.53 15.59c.4.074.547-.174.547-.387 0-.19-.007-.693-.01-1.36-2.226.483-2.695-1.073-2.695-1.073-.364-.925-.89-1.17-.89-1.17-.727-.497.055-.487.055-.487.803.057 1.225.825 1.225.825.715 1.223 1.875.87 2.33.666.072-.518.28-.87.508-1.07-1.776-.2-3.644-.888-3.644-3.952 0-.873.312-1.587.823-2.147-.083-.202-.357-1.015.078-2.116 0 0 .672-.215 2.2.82a7.688 7.688 0 012.002-.27c.68.003 1.364.092 2.002.27 1.527-1.035 2.198-.82 2.198-.82.437 1.101.163 1.914.08 2.116.513.56.823 1.274.823 2.147 0 3.072-1.87 3.75-3.65 3.947.288.247.543.735.543 1.48 0 1.068-.01 1.93-.01 2.193 0 .215.144.464.55.385A8.001 8.001 0 008 .198z"></path>
            </svg>
            <span>GitHub</span>
        </a>
        <a href="/RealTime%20Orders%20Revenue.postman_collection.json" download
           class="inline-flex items-center bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 space-x-2 transition">
            <img src="https://www.svgrepo.com/show/354202/postman-icon.svg" alt="Postman" class="w-5 h-5">
            <span>Download Postman</span>
        </a>
    </div>
</footer>
<script>
    window.addEventListener('DOMContentLoaded', () => {
        fetchAndSendAnalytics();
        setInterval(fetchAndSendAnalytics, 60000);
    });


    const isLocalhost = window.location.hostname === "localhost" || window.location.hostname === "127.0.0.1";

    const socket = new WebSocket(
        isLocalhost
            ? "ws://127.0.0.1:8080"
            : "<?php echo $_ENV['URL_SOCKET'] ?? 'ws://pentavaluetask-production.up.railway.app:8080'?>"
    );
    const log = document.getElementById("log");
    const totalRevenue = document.getElementById("totalRevenue");
    const ordersLastMinute = document.getElementById("ordersLastMinute");
    const revenueLastMinute = document.getElementById("revenueLastMinute");
    const topProducts = document.getElementById("topProducts");

    socket.onmessage = (event) => {
        const msg = JSON.parse(event.data);
        if (msg.event === "new_order") {
            const item = document.createElement("div");
            item.className = "bg-green-100 text-green-800 px-3 py-2 rounded shadow";
            item.textContent = `🆕 Order → Product Name: ${msg.data.product_name}, Qty: ${msg.data.quantity}, Price: ${msg.data.price}, Created At: ${msg.data.date}`;
            log.prepend(item);
        }

        if (msg.event === "analytics_updated") {
            updateAnalytics(msg.data);
        }
    };

    socket.onopen = () => {
        console.log("✅ WebSocket connected");
        // socket.send(JSON.stringify({event: "test", data: "Hello!"}));
    }
    socket.onerror = (err) => console.error("WebSocket error", err);

    async function createRandomOrder() {
        const response = await fetch('/products');
        const result = await response.json();

        if (!result.data?.length) return alert("No products found.");

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

        const order = await orderResponse.json();

        if (order.data) {
            socket.send(JSON.stringify({
                event: 'new_order',
                data: order.data
            }));
        }
    }

    function fetchAndSendAnalytics() {
        fetch('/analytics')
            .then(res => res.json())
            .then(data => {
                console.log("📊 Loaded analytics:", data);
                sendAnalyticsToSocket(data);
            });
    }

    function sendAnalyticsToSocket(data) {
        if (socket.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({
                event: 'analytics_updated',
                data: data
            }));
        } else {
            console.warn("WebSocket not open, retrying later...");
        }
    }


    function updateAnalytics(data) {
        console.log("📊 Received Analytics Data:", data);

        totalRevenue.textContent = `EGP ${parseFloat(data.total_revenue).toFixed(2)}`;
        ordersLastMinute.textContent = data.orders_last_minute;
        revenueLastMinute.textContent = `EGP ${parseFloat(data.revenue_last_minute).toFixed(2)}`;

        topProducts.innerHTML = '';
        data.top_products.forEach(prod => {
            const li = document.createElement('li');
            li.textContent = `${prod.product_name} (${prod.total_sold} sold)`;
            topProducts.appendChild(li);
        });
    }
</script>
</body>
</html>
