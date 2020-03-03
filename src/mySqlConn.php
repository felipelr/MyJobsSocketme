<?php

namespace MyApp;

use PDO;
use PDOException;

class MySqlConn
{
    private $servername = "myjobs.mysql.dbaas.com.br";
    private $username = "myjobs";
    private $password = "myjobs@123";
    private $database = "myjobs";

    public function saveMessage($type, $clienteId, $professionalId, $message)
    {
        try {
            $conn = new PDO("mysql:host={$this->servername};dbname={$this->database}", $this->username, $this->password, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "INSERT INTO chat_messages (professional_id, client_id, date_time, message, msg_from) VALUES ($professionalId, $clienteId, NOW(), '$message', '$type')";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $id = $conn->lastInsertId();
            $conn = null;

            return $id;
        } catch (PDOException $e) {
            echo "Erro => " . $e->getMessage();
            return -1;
        }
    }

    public function saveClientResourceId($clientId, $resourceId)
    {
        try {
            $conn = new PDO("mysql:host={$this->servername};dbname={$this->database}", $this->username, $this->password, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "UPDATE clients SET websocket={$resourceId} WHERE id={$clientId}";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $conn = null;

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function saveProfessionalResourceId($professionalId, $resourceId)
    {
        try {
            $conn = new PDO("mysql:host={$this->servername};dbname={$this->database}", $this->username, $this->password, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "UPDATE professionals SET websocket={$resourceId} WHERE id={$professionalId}";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $conn = null;

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function getClientResourceId($clientId)
    {
        try {
            $conn = new PDO("mysql:host={$this->servername};dbname={$this->database}", $this->username, $this->password, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT clients.name, clients.websocket, users.fcm_token FROM clients inner join users on clients.user_id = users.id WHERE clients.id={$clientId}";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $client = [];
            if ($row = $stmt->fetch()) {
                $client['name'] = $row['name'];
                $client['websocket'] = (int) $row['websocket'];
                $client['fcm_token'] = $row['fcm_token'];
            }
            $conn = null;

            return $client;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function getProfessionalResourceId($professionalId)
    {
        try {
            $conn = new PDO("mysql:host={$this->servername};dbname={$this->database}", $this->username, $this->password, [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"]);

            // set the PDO error mode to exception
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $sql = "SELECT professionals.name, professionals.websocket, users.fcm_token FROM professionals inner join users on professionals.user_id = users.id WHERE professionals.id={$professionalId}";
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $professional = [];
            if ($row = $stmt->fetch()) {
                $professional['name'] = $row['name'];
                $professional['websocket'] = (int) $row['websocket'];
                $professional['fcm_token'] = $row['fcm_token'];
            }
            $conn = null;

            return $professional;
        } catch (PDOException $e) {
            return null;
        }
    }
}
