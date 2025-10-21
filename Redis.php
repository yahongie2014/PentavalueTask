<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Predis\Client;

require __DIR__ . '/vendor/autoload.php';

class Redis implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;
    protected Client $redis;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->redis = new Client();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "🟢 Client {$conn->resourceId} connected\n";
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "🔴 Client {$conn->resourceId} disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "❌ Error: {$e->getMessage()}\n";
        $conn->close();
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Optional: echo or handle messages from clients if needed
        echo "🔄 Message from Client {$from->resourceId}: $msg\n";
    }

    private function listenToRedis(): void
    {
        $pubsub = $this->redis->pubSubLoop();
        $pubsub->subscribe('sales_channel');

        foreach ($pubsub as $message) {
            if ($message->kind === 'message') {
                foreach ($this->clients as $client) {
                    $client->send($message->payload);
                }
                echo "📣 Broadcasted: {$message->payload}\n";
            }
        }
    }
}


$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Redis()
        )
    ),
    $_ENV['REDIS_PORT']
);

$server->run();
