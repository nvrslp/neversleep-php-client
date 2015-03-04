<?php

namespace core;
include "util.php";
include "data_manipulation.php";
include "socket.php";
include "request_id_dispenser.php";
include "system_clock.php";

ini_set('memory_limit','1024M');

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
    //n bytes - val
    $valueBytes = Data\dispatchToType($value);
    //4 bytes - length of val
    $valueLength = Data\intToFourBytes(count($valueBytes));
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength, $keyBytes, $valueLength, $valueBytes);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);

}


function ioAssocInJsonBase($socket, $command, $entityId, $key, $deepKey, $value, $v) {

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
    //4 bytes - length of deepKey
    $deepKeyLength = Data\intToFourBytes(strlen($deepKey));
    //n bytes - deep_key
    $deepKeyBytes = Data\stringToBytes($deepKey);
    //n bytes - val
    $valueBytes = Data\dispatchToType($value);
    //4 bytes - length of val
    $valueLength = Data\intToFourBytes(count($valueBytes));
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength, $keyBytes, $deepKeyLength, $deepKeyBytes, $valueLength, $valueBytes);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);

}

function ioDissocInJsonBase($socket, $command, $entityId, $key, $deepKey, $v) {

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
    //4 bytes - length of deepKey
    $deepKeyLength = Data\intToFourBytes(strlen($deepKey));
    //n bytes - deep_key
    $deepKeyBytes = Data\stringToBytes($deepKey);
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength, $keyBytes, $deepKeyLength, $deepKeyBytes);

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

function ioGetAllVersionsBetweenBase($socket, $command, $entityId, $timestampStart, $timestampEnd, $limit, $v) {

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
    //4 bytes - limit
    $limitBytes = Data\intToFourBytes($limit);
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $timestampStartBytes, $timestampEndBytes, $limitBytes);

    //send to server
    sendByteArray($socket, $byteArray);
    //wait for response, return it
    return receiveServerTcpResponse($socket);
}

//PUBLIC API
//writes
function ioAssoc($entityId, $key, $value) {
    return ioAssocBase(Socket::$socket, 1, $entityId, $key, $value, 0);
}

function ioAssocInJson($entityId, $key, array $deepKey, $value) {
    return ioAssocInJsonBase(Socket::$socket, 3, $entityId, $key, json_encode($deepKey), $value, 0);
}

function ioDissoc($entityId, $key) {
    return ioDissocBase(Socket::$socket, 2, $entityId, $key, 0);
}

function ioDissocInJson($entityId, $key, array $deepKey) {
    return ioDissocInJsonBase(Socket::$socket, 4, $entityId, $key, json_encode($deepKey), 0);
}

//reads
function ioGetKeyLatest($entityId, $key) {
    return ioGetKeyAsOfBase(Socket::$socket, -128, $entityId, $key, SystemClock\serverLatest(), 0);
}

function ioGetKeyAsOf($entityId, $key, $timestamp) {
    return ioGetKeyAsOfBase(Socket::$socket, -128, $entityId, $key, SystemClock\convertTimestampToNano($timestamp), 0);
}

function ioGetEntityLatest($entityId) {
    return ioGetEntityAsOfBase(Socket::$socket, -127, $entityId, SystemClock\serverLatest(), 0);
}

function ioGetEntityAsOf($entityId, $timestamp) {
    return ioGetEntityAsOfBase(Socket::$socket, -127, $entityId, SystemClock\convertTimestampToNano($timestamp), 0);
}

function ioGetAllVersionsBetween($entityId, $timestampStart, $timestampEnd, $limit) {
    return ioGetAllVersionsBetweenBase(Socket::$socket, -126, $entityId, SystemClock\convertTimestampToNano($timestampStart), SystemClock\convertTimestampToNano($timestampEnd), $limit, 0);
}

function connectToServer($ipAddress, $port) {
    Socket::init($ipAddress, $port);
}

//TODO remove this for production
connectToServer("127.0.0.1", 10000);


//var_dump(ioAssoc("api-users-4", "k-2", 1));

var_dump(ioGetEntityLatest("api-users-4"));

//var_dump(ioAssoc("api-users-4", "k-2", ["a" => 1]));


//var_dump(ioDissoc("api-users-3", "k-2"));
//
//var_dump(ioAssocJson("api-users-3", "k-2", ["names" => [["first" => "will", "last" => "king"], ["first" => "rangel", "last" => "spasov"]]]));
//var_dump(ioAssocJson("api-users", "k-1", ["names" => [["first" => "will", "last" => "king"], ["first" => "rangel", "last" => "spasov"]]]));

//
//var_dump(ioAssocInJson("api-users-4", "k-2", ["b"], "two"));
//
//var_dump(ioDissocInJson("api-users-4", "k-2", ["b"]));
//
//var_dump(ioGetKeyAsOfNow("api-users", "k-1"));
//
//var_dump(ioGetKeyAsOf("api-users", "k-1", time()));
//
//var_dump(ioGetEntityAsOfNow("api-users-3"));
//
//var_dump(ioGetEntityAsOf("api-users-3", time() + 1));

//var_dump(ioGetEntityAsOfNow("api-users-3"));

//
//var_dump(ioGetAllVersionsBetween("api-users-4", time(), time() - 3600, 1000));


//var_dump(SystemClock\convertTimestampToNano(0));