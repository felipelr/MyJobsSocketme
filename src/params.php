<?php

namespace MyApp;

class Params
{
    public $type = '';
    public $from = 0;
    public $to = 0;
    public $token = '';

    function __construct($conn)
    {
        $strQuery = $conn->httpRequest->getUri()->getQuery();
        $array = explode("&", $strQuery);

        foreach ($array as $item) {
            $arrayElements = explode("=", $item);
            if ($arrayElements[0] == 'type')
                $this->type = $arrayElements[1];
            else if ($arrayElements[0] == 'from')
                $this->from = (int) $arrayElements[1];
            else if ($arrayElements[0] == 'to')
                $this->to = (int) $arrayElements[1];
            else if ($arrayElements[0] == 'token')
                $this->token = $arrayElements[1];
        }
    }
}
