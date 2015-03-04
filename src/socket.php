<?php

/**
 * Nicer wrappers over php's C-like socket functions
 */
namespace Socket;

/**
 * @throws \Exception
 * @return resource
 */
function socketCreate()
{
    if (!($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
        //error check
        $errorCode = socket_last_error($socket);
        $errorMsg = socket_strerror($errorCode);
        throw new \Exception ("Couldn't create socket: [$errorCode] $errorMsg");
    }
    //socket created
    return $socket;
}

/**
 * @param $ip
 * @param $port
 * @throws \Exception
 * @return resource
 */
function socketConnect($ip, $port)
{
    $socket = socketCreate();

    if (!socket_connect($socket, $ip, $port)) {
        //error check
        $errorCode = socket_last_error($socket);
        $errorMsg = socket_strerror($errorCode);
        throw new \Exception ("Couldn't connect: [$errorCode] $errorMsg");
    }
    //socket connected
    return $socket;
}

/**
 * @param $socket
 * @param $string
 * @throws \Exception
 * @return bool
 */
function socketSend($socket, $string)
{

    if (!socket_send($socket, $string, strlen($string), 0)) {
        //error check
        $errorCode = socket_last_error($socket);
        $errorMsg = socket_strerror($errorCode);
        throw new \Exception ("Couldn't send data: [$errorCode] $errorMsg");
    }
    //message sent successfully
    return true;

}
/**
 * @param $socket
 * @param $numOfBytesToReceive
 * @throws \Exception
 * @return array - byte array
 */
function socketReceive($socket, $numOfBytesToReceive)
{

    if (socket_recv($socket, $buf, $numOfBytesToReceive, MSG_WAITALL) === false) {
        $errorCode = socket_last_error();
        $errorMsg = socket_strerror($errorCode);
        throw new \Exception ("Could not receive data: [$errorCode] $errorMsg");
    }
    //convert the string to a byte array
    return unpack("C*", $buf);

}
