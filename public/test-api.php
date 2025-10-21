<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Universal API Tester</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 p-8">
<div class="max-w-4xl mx-auto space-y-6">

    <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-md mb-6">
        <h1 class="text-xl font-bold">🧪 Universal API Tester</h1>
        <a href="index.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">🏠 Dashboard</a>
    </div>

    <div class="bg-white p-6 rounded shadow space-y-4">
        <label class="block text-sm font-medium">Choose API Endpoint:</label>
        <select id="apiSelect" onchange="renderForm()" class="w-full p-2 border rounded">
            <option value="">-- Select API --</option>
            <option value="GET:/products">GET /products</option>
            <option value="POST:/create_order">POST /create_order</option>
            <option value="GET:/recommendations">GET /recommendations</option>
            <option value="GET:/analytics">GET /analytics</option>
        </select>

        <form id="apiForm" onsubmit="submitAPIRequest(event)" class="space-y-4"></form>

        <pre id="apiResult" class="bg-gray-100 p-3 rounded text-sm overflow-x-auto h-64"></pre>
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
        <a href="../docs/RealTime%20Orders%20Revenue.postman_collection.json" download
           class="inline-flex items-center bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 space-x-2 transition">
            <img src="https://www.svgrepo.com/show/354202/postman-icon.svg" alt="Postman" class="w-5 h-5">
            <span>Download Postman</span>
        </a>
    </div>
</footer>

<script>
    function renderForm() {
        const select = document.getElementById('apiSelect').value;
        const [method, endpoint] = select.split(':');
        const form = document.getElementById('apiForm');
        form.innerHTML = '';

        const title = document.createElement('h2');
        title.className = 'text-lg font-bold';
        title.textContent = `${method} ${endpoint}`;
        form.appendChild(title);

        form.dataset.method = method;
        form.dataset.endpoint = endpoint;

        if (method === 'POST' && endpoint === '/create_order') {
            ['product_id', 'quantity', 'price', 'date'].forEach(field => {
                const input = document.createElement('input');
                input.className = 'w-full p-2 border rounded';
                input.name = field;
                input.placeholder = field.replace('_', ' ');
                input.type = (field === 'price') ? 'number' :
                    (field === 'date') ? 'datetime-local' : 'number';
                form.appendChild(input);
            });
        } else if (method === 'POST' && endpoint === '/feedback') {
            ['user', 'message', 'rating'].forEach(field => {
                const input = document.createElement('input');
                input.className = 'w-full p-2 border rounded';
                input.name = field;
                input.placeholder = field.charAt(0).toUpperCase() + field.slice(1);
                input.type = field === 'rating' ? 'number' : 'text';
                form.appendChild(input);
            });
        }


        const submit = document.createElement('button');
        submit.type = 'submit';
        submit.className = 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700';
        submit.textContent = 'Submit Request';
        form.appendChild(submit);
    }

    async function submitAPIRequest(e) {
        e.preventDefault();
        const form = e.target;
        const method = form.dataset.method;
        const endpoint = form.dataset.endpoint;

        let response;
        if (method === 'GET') {
            response = await fetch(endpoint);
        } else if (method === 'POST') {
            const payload = {};
            for (const input of form.querySelectorAll('input')) {
                payload[input.name] = input.name === 'price'
                    ? parseFloat(input.value)
                    : input.name === 'quantity' || input.name === 'product_id'
                        ? parseInt(input.value)
                        : input.name === 'date'
                            ? input.value.replace('T', ' ')
                            : input.value;
            }

            response = await fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            });
        }

        const result = await response.json();
        document.getElementById('apiResult').textContent = JSON.stringify(result, null, 2);
    }
</script>
</body>
</html>
