<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/chat.php';

use MyApp\Chat;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Chat()
        )
    )
  , 8080
);

$server->run();