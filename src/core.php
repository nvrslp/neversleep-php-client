<?php

namespace core;
include "util.php";
include "data_manipulation.php";
include "socket.php";
include "request_id_dispenser.php";
include "system_clock.php";

use DataManipulation as Data;
use Socket as S;
use RequestIdDispenser\RequestIdDispenser as RequestIdDispenser;
use SystemClock as SystemClock;
/**
 * Holds the socket connection
 * Class Socket
 * @package core
 */
class Socket {

    public static $socket = null;

    public static function init($ipAddress, $port) {
        self::$socket = S\socketConnect($ipAddress, $port);
    }

    public static function getSocket() {
        self::$socket;
    }
}

/**
 * @param $socket
 * @return string
 * API specific function that receives four bytes to determine the 'body' size in bytes and then receives the actual body
 */
function receiveServerTcpResponse($socket)
{
    //grab the length of the actual message (first 4 bytes)
    $byteArray = S\socketReceive($socket, 4);
    $numOfBytesToReceive = Data\fourBytesToInt($byteArray);

    //grab the actual message
    $byteArray = S\socketReceive($socket, $numOfBytesToReceive);

    return Data\bytesToString($byteArray);
}



function sendByteArray($socket, $byteArray)
{
    $length = count($byteArray);
    if($length > 2147483647) {
        //cannot send bigger request than that
        return false;
    }

    //prepend the length of the message to the message itself
    $data = Data\bytesToString(array_merge(Data\intToFourBytes($length), $byteArray));
    S\socketSend($socket, $data);

    return true;
}

function ioDissocBase($socket, $command, $entityId, $key, $v) {

    if(count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if(count($key) > 127) {
        throw new \Exception("key length greater than 127");
    }

    //1 byte - command
    $commandBytes = [$command];
    //1 byte -v mode
    $verboseMode = [$v];
    //4 bytes - uniqueInt for that request
    $uniqueInt = Data\intToFourBytes(RequestIdDispenser::dispenseId());
    //1 byte - length of entityId
    $entityIdLength = [strlen($entityId)];
    //0-127 bytes - entityId
    $entityIdBytes = Data\stringToBytes($entityId);
    //1 byte - length of key
    $keyLength = [strlen($key)];
    //0-127 bytes - key
    $keyBytes = Data\stringToBytes($key);

    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength, $keyBytes);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);

}

/**
 * @param socket
 * @param command
 * @param $entityId
 * @param $key
 * @param $value
 * @param $v - verbose mode
 * @return string
 * @throws \Exception
 * tcp message structure:
 * |1 byte - command|1 byte - length of entityId|n bytes - entityId|1 byte - length of key|N bytes - key|4 bytes - length of val|N bytes - val|4 bytes - unique integer for that request
 * Base method for ioAssoc, other ioAssoc* methods call this one
 */
function ioAssocBase($socket, $command, $entityId, $key, $value, $v) {

    if(count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if(count($key) > 127) {
        throw new \Exception("key length greater than 127");
    }

    //1 byte - command
    $commandBytes = [$command];
    //1 byte -v mode
    $verboseMode = [$v];
    //4 bytes - uniqueInt for that request
    $uniqueInt = Data\intToFourBytes(RequestIdDispenser::dispenseId());
    //1 byte - length of entityId
    $entityIdLength = [strlen($entityId)];
    //0-127 bytes - entityId
    $entityIdBytes = Data\stringToBytes($entityId);
    //1 byte - length of key
    $keyLength = [strlen($key)];
    //0-127 bytes - key
    $keyBytes = Data\stringToBytes($key);
    //4 bytes - length of val
    $valueLength = Data\intToFourBytes(strlen($value));
    //n bytes - val
    $valueBytes = Data\stringToBytes($value);
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength, $keyBytes, $valueLength, $valueBytes);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);

}

function ioGetKeyAsOfBase($socket, $command, $entityId, $key, $timestamp, $v) {

    if(count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if(count($key) > 127) {
        throw new \Exception("key length greater than 127");
    }

    //1 byte - command
    $commandBytes = [$command];
    //1 byte -v mode
    $verboseMode = [$v];
    //4 bytes - uniqueInt for that request
    $uniqueInt = Data\intToFourBytes(RequestIdDispenser::dispenseId());
    //1 byte - length of entityId
    $entityIdLength = [strlen($entityId)];
    //0-127 bytes - entityId
    $entityIdBytes = Data\stringToBytes($entityId);
    //1 byte - length of key
    $keyLength = [strlen($key)];
    //0-127 bytes - key
    $keyBytes = Data\stringToBytes($key);
    //19 bytes - length of timestamp as a string
    $timestampLength = Data\stringToBytes($timestamp);
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength, $keyBytes, $timestampLength);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);
}


function ioGetEntityAsOfBase($socket, $command, $entityId, $timestamp, $v) {

    if(count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }

    //1 byte - command
    $commandBytes = [$command];
    //1 byte -v mode
    $verboseMode = [$v];
    //4 bytes - uniqueInt for that request
    $uniqueInt = Data\intToFourBytes(RequestIdDispenser::dispenseId());
    //1 byte - length of entityId
    $entityIdLength = [strlen($entityId)];
    //0-127 bytes - entityId
    $entityIdBytes = Data\stringToBytes($entityId);
    //19 bytes - length of timestamp as a string
    $timestampBytes = Data\stringToBytes($timestamp);
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $timestampBytes);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);
}

function ioGetAllVersionsBetweenBase($socket, $command, $entityId, $timestampStart, $timestampEnd, $v) {

    if(count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }

    //1 byte - command
    $commandBytes = [$command];
    //1 byte -v mode
    $verboseMode = [$v];
    //4 bytes - uniqueInt for that request
    $uniqueInt = Data\intToFourBytes(RequestIdDispenser::dispenseId());
    //1 byte - length of entityId
    $entityIdLength = [strlen($entityId)];
    //0-127 bytes - entityId
    $entityIdBytes = Data\stringToBytes($entityId);
    //19 bytes - length of timestamp as a string
    $timestampStartBytes = Data\stringToBytes($timestampStart);
    //19 bytes - length of timestamp as a string
    $timestampEndBytes = Data\stringToBytes($timestampEnd);

    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $timestampStartBytes, $timestampEndBytes);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);
}

//PUBLIC API
//writes
function ioAssocJson($entityId, $key, $value) {
    return ioAssocBase(Socket::$socket, 0, $entityId, $key, json_encode($value), 0);
}

function ioAssocInJson($entityId, array $deepKey, $val) {
    return ioAssocBase(Socket::$socket, 3, $entityId, json_encode($deepKey), $val, 0);
}

function ioAssocString($entityId, $key, $value) {
    return ioAssocBase(Socket::$socket, 1, $entityId, $key, $value, 0);
}

function ioDissocString($entityId, $key) {
    return ioDissocBase(Socket::$socket, 2, $entityId, $key, 0);
}

function ioDissocInJson($entityId, array $deepKey) {
    return ioDissocBase(Socket::$socket, 4, $entityId, json_encode($deepKey), 0);
}

//reads
function ioGetKeyAsOfNow($entityId, $key) {
    return ioGetKeyAsOfBase(Socket::$socket, -128, $entityId, $key, SystemClock\getTime(), 0);
}

function ioGetKeyAsOf($entityId, $key, $timestamp) {
    return ioGetKeyAsOfBase(Socket::$socket, -128, $entityId, $key, SystemClock\convertTimestampToNano($timestamp), 0);
}

function ioGetEntityAsOfNow($entityId) {
    return ioGetEntityAsOfBase(Socket::$socket, -127, $entityId, SystemClock\getTime(), 0);
}

function ioGetEntityAsOf($entityId, $timestamp) {
    return ioGetEntityAsOfBase(Socket::$socket, -127, $entityId, SystemClock\convertTimestampToNano($timestamp), 0);
}

function ioGetAllVersionsBetween($entityId, $timestampStart, $timestampEnd) {
    return ioGetAllVersionsBetweenBase(Socket::$socket, -126, $entityId, SystemClock\convertTimestampToNano($timestampStart), SystemClock\convertTimestampToNano($timestampEnd), 0);
}

function connectToServer($ipAddress, $port) {
    Socket::init($ipAddress, $port);
}

//TODO remove this for production
connectToServer("127.0.0.1", 10000);


var_dump(ioAssocString("users", "k-1", "v-1"));

var_dump(ioDissocString("users", "k-1"));

var_dump(ioAssocJson("users", "k-1", ["result" => []]));

var_dump(ioAssocInJson("users", ["names", 0, "last"], "new-val"));

var_dump(ioDissocInJson("users", ["names", 0, "last"]));

var_dump(ioGetKeyAsOfNow("users", "u-1"));

var_dump(ioGetKeyAsOf("users", "u-1", time() - 3600));

var_dump(ioGetEntityAsOfNow("users"));

var_dump(ioGetEntityAsOf("users", time() - 1));

var_dump(ioGetAllVersionsBetween("users", time(), time() - 3600));