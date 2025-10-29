<?php
namespace App\Command;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory as LoopFactory;
use React\Socket\Server as ReactSocketServer;
use Clue\React\Redis\Factory as RedisFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChatServerCommand extends Command implements MessageComponentInterface
{
    protected static $defaultName = 'app:chat-server';
    private \SplObjectStorage $clients;
    private $redisPub;
    private $redisSub;
    private string $channel = 'chat_channel';

    public function __construct()
    {
        parent::__construct();
        $this->clients = new \SplObjectStorage();
    }

    protected function configure()
    {
        $this
            ->addArgument('port', InputArgument::OPTIONAL, 'Port for WebSocket', 8080)
            ->addArgument('redis', InputArgument::OPTIONAL, 'Redis address', '127.0.0.1:6379');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = (int)$input->getArgument('port');
        $redisDsn = (string)$input->getArgument('redis');

        $loop = LoopFactory::create();

        $output->writeln("🚀 Starting chat server on ws://localhost:{$port}");
        $output->writeln("🔌 Connecting to Redis at {$redisDsn}");

        $redisFactory = new RedisFactory($loop);

        // Create publisher
        $redisFactory->createClient($redisDsn)->then(function ($client) use ($output) {
            $this->redisPub = $client;
            $output->writeln("✅ Redis publisher connected");
        });

        // Create subscriber
        $redisFactory->createClient($redisDsn)->then(function ($client) use ($output) {
            $this->redisSub = $client;
            $output->writeln("✅ Redis subscriber connected");

            // Subscribe AFTER the client object exists
            $client->subscribe($this->channel)->then(function () use ($output) {
                $output->writeln("📡 Subscribed to Redis channel '{$this->channel}'");
            });

            // Handle incoming messages from Redis
            $client->on('message', function ($channel, $message) use ($output) {
                $output->writeln("Redis broadcast: $message");
                foreach ($this->clients as $clientConn) {
                    $clientConn->send($message);
                }
            });
        });

        // Build Ratchet WebSocket server
        $wsServer = new WsServer($this);
        $httpServer = new HttpServer($wsServer);
        $socket = new ReactSocketServer("0.0.0.0:{$port}", $loop);
        new IoServer($httpServer, $socket, $loop);

        $loop->run();
        return Command::SUCCESS;
    }

    // WebSocket callbacks
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "[OPEN] New connection {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo "[MSG] {$msg}\n";
        $data = json_decode($msg, true);
        if (!$data) return;

        $payload = json_encode([
            'user' => $data['user'] ?? 'Anon',
            'text' => $data['text'] ?? '',
            'server' => gethostname(),
            'time' => date('H:i:s')
        ]);

        // Publish to Redis if connected
        if ($this->redisPub) {
            $this->redisPub->publish($this->channel, $payload);
        } else {
            echo "⚠️ Redis not yet connected, skipping publish\n";
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "[CLOSE] Connection {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "[ERROR] {$e->getMessage()}\n";
        $conn->close();
    }
}
