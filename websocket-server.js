const WebSocket = require('ws');

const PORT = process.env.PORT || 8080;
const wss = new WebSocket.Server({ port: PORT });

wss.on('connection', ws => {
    console.log('🔌 Client connected');
    ws.on('message', message => {
        console.log('📨', message);
        ws.send(`You said: ${message}`);
    });
    ws.send('👋 Welcome to Railway WebSocket');
});

console.log(`✅ WebSocket running on port ${PORT}`);
