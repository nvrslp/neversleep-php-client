<?php

/**
 * Nicer wrappers over php's C-like socket functions
 */
namespace Socket;

/**
 * @return resource
 */
function socketCreate()
{
    if (!($socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
        //error check
        $errorCode = socket_last_error($socket);
        $errorMsg = socket_strerror($errorCode);
        die("Couldn't create socket: [$errorCode] $errorMsg \n");
    }
    echo "Socket created  \n";

    return $socket;
}

/**
 * @param $ip
 * @param $port
 * @return resource
 */
function socketConnect($ip, $port)
{
    $socket = socketCreate();

    if (!socket_connect($socket, $ip, $port)) {
        //error check
        $errorCode = socket_last_error($socket);
        $errorMsg = socket_strerror($errorCode);
        die("Couldn't connect: [$errorCode] $errorMsg \n");
    }

    echo "Socket connected  \n";

    return $socket;
}

/**
 * @param $socket
 * @param $string
 * @return bool
 */
function socketSend($socket, $string)
{

    if (!socket_send($socket, $string, strlen($string), 0)) {
        //error check
        $errorCode = socket_last_error($socket);
        $errorMsg = socket_strerror($errorCode);
        die("Couldn't send data: [$errorCode] $errorMsg \n");
    }
    echo "Message send successfully \n";

    return true;

}
/**
 * @param $socket
 * @param $numOfBytesToReceive
 * @return array - byte array
 */
function socketReceive($socket, $numOfBytesToReceive)
{

    if (socket_recv($socket, $buf, $numOfBytesToReceive, MSG_WAITALL) === false) {
        $errorCode = socket_last_error();
        $errorMsg = socket_strerror($errorCode);
        die("Could not receive data: [$errorCode] $errorMsg \n");
    }

    //convert the string to a byte array
    return unpack("C*", $buf);

}
