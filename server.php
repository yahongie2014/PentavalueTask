<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables if .env exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

class Server implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        echo "🚀 WebSocket Server Initialized\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "🟢 New connection: {$conn->resourceId} (Total: {$this->clients->count()})\n";

        // Send welcome message
        $conn->send(json_encode([
            'event' => 'connected',
            'data' => [
                'message' => 'Connected to WebSocket server',
                'connection_id' => $conn->resourceId,
                'server_time' => date('Y-m-d H:i:s')
            ]
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "📨 Message from {$from->resourceId}: $msg\n";

        // Broadcast to all clients
        foreach ($this->clients as $client) {
            // Send to everyone including sender
            $client->send($msg);
        }

        echo "📤 Broadcasted to {$this->clients->count()} client(s)\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "🔴 Connection {$conn->resourceId} disconnected (Remaining: {$this->clients->count()})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "❌ Error on connection {$conn->resourceId}: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Detect environment and configure accordingly
$isRailway = !empty(getenv('RAILWAY_ENVIRONMENT')) || !empty(getenv('RAILWAY_STATIC_URL'));
$isProduction = !empty(getenv('RAILWAY_ENVIRONMENT')) || (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'production');

// Railway automatically provides PORT, use it first
// Then check custom WS_PORT, finally default to 8000
$port = getenv('PORT') ?: ($_ENV['PORT'] ?? ($_ENV['WS_PORT'] ?? 8000));

// Host configuration
// Railway requires 0.0.0.0, but allow override for local development
$host = $_ENV['WS_HOST'] ?? '0.0.0.0';

// Display startup information
echo "\n";
echo "========================================\n";
echo "🚀 WebSocket Server Starting...\n";
echo "========================================\n";
echo "Environment: " . ($isRailway ? '🚂 Railway' : '💻 Local') . "\n";
echo "Mode: " . ($isProduction ? 'Production' : 'Development') . "\n";
echo "Host: $host\n";
echo "Port: $port\n";

if ($isRailway) {
    $railwayUrl = getenv('RAILWAY_STATIC_URL') ?: getenv('RAILWAY_PUBLIC_DOMAIN');
    if ($railwayUrl) {
        echo "Railway URL: wss://$railwayUrl\n";
    }
    echo "⚠️  Use WSS (WebSocket Secure) for Railway connections\n";
} else {
    echo "Local URL: ws://localhost:$port\n";
}

echo "========================================\n";
echo "Press Ctrl+C to stop\n";
echo "========================================\n\n";

try {
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new Server()
            )
        ),
        $port,
        $host
    );

    echo "✅ Server is running and waiting for connections...\n";
    
    if ($isRailway) {
        echo "💡 Railway Tip: Check deployment logs for connection issues\n";
    }
    
    echo "\n";
    
    // Keep the process alive and running
    $server->run();
    
} catch (\Exception $e) {
    echo "\n❌ Failed to start server: {$e->getMessage()}\n";
    echo "\nError Details:\n";
    echo "- Message: " . $e->getMessage() . "\n";
    echo "- File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    echo "\nPossible solutions:\n";
    
    if ($isRailway) {
        echo "1. Check Railway logs for detailed error messages\n";
        echo "2. Verify all Composer dependencies are installed\n";
        echo "3. Ensure PORT environment variable is set\n";
        echo "4. Check Railway service status\n";
    } else {
        echo "1. Check if port $port is already in use:\n";
        echo "   - Windows: netstat -ano | findstr :$port\n";
        echo "   - Linux/Mac: lsof -i :$port\n";
        echo "2. Try running with administrator/sudo privileges\n";
        echo "3. Change the port in .env file (WS_PORT=8001)\n";
        echo "4. Ensure Composer dependencies are installed\n";
    }
    
    // Log error for Railway/production monitoring
    error_log("WebSocket server failed to start: " . $e->getMessage());
    
    exit(1);
}