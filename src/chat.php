<?php

namespace MyApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Kreait\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Factory;
use Exception;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/src/mySqlConn.php';
require dirname(__DIR__) . '/src/params.php';

use MyApp\MySqlConn;
use MyApp\Params;

class Chat implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $params = new Params($conn);
        $context = stream_context_create([
            'http' => [
                'method' => "GET",
                'header' => "Authorization: Bearer " . $params->token
            ]
        ]);
        $json = file_get_contents("http://localhost/ws/api/users/validate.json", false, $context);
        $deconde = json_decode($json, true);

        if (isset($deconde['success'])) {
            // Store the new connection to send messages to later
            $this->clients->attach($conn);
            if ($params->type === 'client') {
                $mySql = new MySqlConn();
                $mySql->saveClientResourceId($params->from, $conn->resourceId);
            } else if ($params->type === 'professional') {
                $mySql = new MySqlConn();
                $mySql->saveProfessionalResourceId($params->from, $conn->resourceId);
            }
        } else {
            echo "Connection denied! ({$conn->resourceId})\n";
            $conn->close();
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $params = new Params($from);
        $mySql = new MySqlConn();
        $msgJson = json_decode($msg, true);

        //salva nova mensagem
        if ($params->type === 'client') {
            $id = $mySql->saveMessage($params->type, $params->from, $params->to, $msgJson['message']);
            if ($id !== -1) {
                $msgJson['id'] = $id;
            }
        } else if ($params->type === 'professional') {
            $id = $mySql->saveMessage($params->type, $params->to, $params->from, $msgJson['message']);
            if ($id !== -1) {
                $msgJson['id'] = $id;
            }
        }

        //retorna a msg salva e com o id
        $from->send(json_encode($msgJson));

        //recupera o codigo da conexao do destino
        $userTo = null;
        $userFrom = null;
        if ($params->type === 'client') {
            $userTo = $mySql->getProfessionalResourceId($params->to);
            $userFrom = $mySql->getClientResourceId($params->from);
        } else if ($params->type === 'professional') {
            $userTo = $mySql->getClientResourceId($params->to);
            $userFrom = $mySql->getProfessionalResourceId($params->from);
        }

        //dispara a mensagem para o destino
        if ($userTo != null) {
            $userFounded = false;
            foreach ($this->clients as $client) {
                if ($client->resourceId === $userTo['websocket']) {
                    if ($params->type === 'client') {
                        $client->send(json_encode($msgJson));
                    } else if ($params->type === 'professional') {
                        $client->send(json_encode($msgJson));
                    }
                    $userFounded = true;
                    break;
                }
            }

            //se o destino não estiver conectado, envia uma notificação
            if (!$userFounded) {
                $title = $userFrom != null ? $userFrom['name'] : 'MyJobs';
                if ($userTo['fcm_token'] != '') {
                    try {
                        $path = dirname(__DIR__) . '/myjobstest-719a9-firebase-adminsdk-bjq4h-db0fea2767.json';
                        $factory = (new Factory())
                            ->withServiceAccount($path);
                        $messaging = $factory->createMessaging();

                        $messageFCM = CloudMessage::withTarget('token', $userTo['fcm_token'])
                            ->withNotification([
                                'title' => $title,
                                'body' => $msgJson['message'],
                                'icon' => 'ic_launcher'
                            ])
                            ->withData([
                                'message' => json_encode($msgJson)
                            ]);

                        $messaging->send($messageFCM);
                    } catch (Exception $ex) {
                        echo "Notification error => " . $ex->getMessage() . "\n";
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        $params = new Params($conn);

        if ($params->type === 'client') {
            $mySql = new MySqlConn();
            $mySql->saveClientResourceId($params->from, 0);
        } else if ($params->type === 'professional') {
            $mySql = new MySqlConn();
            $mySql->saveProfessionalResourceId($params->from, 0);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}
