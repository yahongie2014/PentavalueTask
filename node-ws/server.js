const WebSocket = require('ws');
const wss = new WebSocket.Server({port: process.env.PORT || 3000});

wss.on('connection', ws => {
    console.log('Client connected');
    ws.on('message', message => console.log('Received:', message));
    ws.send('Welcome!');
});
