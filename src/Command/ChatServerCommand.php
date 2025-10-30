<?php
// src/Command/ChatServerCommand.php
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
    private int $port;

    public function __construct()
    {
        parent::__construct();
        $this->clients = new \SplObjectStorage();
    }

    protected function configure()
    {
        $this
            ->addArgument('port', InputArgument::OPTIONAL, 'Port for WebSocket server', 8080)
            ->addArgument('redis', InputArgument::OPTIONAL, 'Redis host:port', '127.0.0.1:6379');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->port = (int)$input->getArgument('port');
        $redisDsn = (string)$input->getArgument('redis');

        $loop = LoopFactory::create();
        $output->writeln("Starting chat server on ws://0.0.0.0:{$this->port}");
        $output->writeln("Connecting to Redis at {$redisDsn}");

        $redisFactory = new RedisFactory($loop);

        // Create publisher client (async)
        $redisFactory->createClient($redisDsn)->then(function ($client) use ($output) {
            $this->redisPub = $client;
            $output->writeln("Redis publisher connected");
        }, function ($e) use ($output) {
            $output->writeln("<error>Failed to create Redis publisher: {$e->getMessage()}</error>");
        });

        // Create subscriber client (async)
        $redisFactory->createClient($redisDsn)->then(function ($client) use ($output) {
            $this->redisSub = $client;
            $output->writeln("Redis subscriber connected");

            // Subscribe once connected
            $client->subscribe($this->channel)->then(function () use ($output) {
                $output->writeln("Subscribed to channel '{$this->channel}'");
            }, function ($e) use ($output) {
                $output->writeln("<error>Subscribe failed: {$e->getMessage()}</error>");
            });

            // When Redis sends messages, broadcast to all connected WebSocket clients
            $client->on('message', function ($channel, $message) use ($output) {
                $output->writeln("[Redis] Broadcast received on {$channel}");
                foreach ($this->clients as $conn) {
                    // send raw JSON string to clients
                    $conn->send($message);
                }
            });
        }, function ($e) use ($output) {
            $output->writeln("<error>Failed to create Redis subscriber: {$e->getMessage()}</error>");
        });

        // Build Ratchet WebSocket server integrated with the React loop
        $wsServer = new WsServer($this);
        $httpServer = new HttpServer($wsServer);
        $socket = new ReactSocketServer("0.0.0.0:{$this->port}", $loop);
        new IoServer($httpServer, $socket, $loop);

        $loop->run();
        return Command::SUCCESS;
    }

    // WebSocket callbacks
    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
        echo "[OPEN] Connection {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        // Expect JSON { user, text }
        $data = json_decode($msg, true);
        if (!is_array($data) || empty($data['text'])) {
            $from->send(json_encode(['error' => 'invalid message']));
            return;
        }

        $payload = json_encode([
            'user'   => $data['user'] ?? 'Anon',
            'text'   => $data['text'],
            'server' => gethostname() . ':' . $this->port,
            'time'   => date('Y-m-d H:i:s'),
        ]);

        // If redis publisher available, publish message (delivered to all servers)
        if ($this->redisPub) {
            $this->redisPub->publish($this->channel, $payload);
        } else {
            // If Redis not ready, broadcast locally as fallback
            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send($payload);
                }
            }
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
