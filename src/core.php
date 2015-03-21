<?php

namespace core;
include "util.php";
include "data_manipulation.php";
include "socket.php";
include "request_id_dispenser.php";
include "system_clock.php";

ini_set('memory_limit', '1024M');

use DataManipulation as Data;
use Socket as S;
use RequestIdDispenser\RequestIdDispenser as RequestIdDispenser;
use SystemClock as SystemClock;

/**
 * Holds the socket connection
 * Class Socket
 * @package core
 */
class Socket
{

    public static $socket = NULL;

    public static function init($ipAddress, $port)
    {
        try {
            self::$socket = S\socketConnect($ipAddress, $port);
        } catch (\Exception $e) {
            error_log($e->getMessage() . ' ' . $e->getTraceAsString());
        }

        //success?
        if(self::$socket === NULL) {
            return false;
        }
        else {
            return true;
        }

    }

    public static function getSocket()
    {
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
    try {
        $byteArray = S\socketReceive($socket, $numOfBytesToReceive);
        return json_decode(Data\bytesToString($byteArray), true);
    }
    catch (\Exception $e) {
        error_log($e->getMessage() . ' ' . $e->getTraceAsString());
        return false;
    }
}


function sendByteArray($socket, $byteArray)
{

    //prepend the api version
    $byteArray = array_merge([1], $byteArray);

    $length = count($byteArray);
    if ($length > 2147483647) {
        //cannot send bigger request than that
        return false;
    }

    //prepend the length of the message to the message itself
    $data = Data\bytesToString(array_merge(Data\intToFourBytes($length), $byteArray));
    try {
        S\socketSend($socket, $data);
        return true;
    }
    catch (\Exception $e) {
        error_log($e->getMessage() . ' ' . $e->getTraceAsString());
        return false;
    }
}

function tcpRequestResponce($socket, $byteArray) {

    //send to server
    $result = sendByteArray($socket, $byteArray);

    if($result === false) {
        return false;
    }
    else {
        //wait for response, return it
        return receiveServerTcpResponse($socket);
    }

}

function ioDissocBase($socket, $command, $entityId, $key, $v)
{

    if (count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if (count($key) > 127) {
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

    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength,
        $keyBytes);

    return tcpRequestResponce($socket, $byteArray);

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
function ioAssocBase($socket, $command, $entityId, $key, $value, $v)
{

    if (count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if (count($key) > 127) {
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
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength,
        $keyBytes, $valueLength, $valueBytes);

    return tcpRequestResponce($socket, $byteArray);

}


function ioAssocInJsonBase($socket, $command, $entityId, $key, $deepKey, $value, $v)
{

    if (count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if (count($key) > 127) {
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
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength,
        $keyBytes, $deepKeyLength, $deepKeyBytes, $valueLength, $valueBytes);

    return tcpRequestResponce($socket, $byteArray);

}

function ioDissocInJsonBase($socket, $command, $entityId, $key, $deepKey, $v)
{

    if (count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if (count($key) > 127) {
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
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength,
        $keyBytes, $deepKeyLength, $deepKeyBytes);

    return tcpRequestResponce($socket, $byteArray);

}


function ioGetKeyAsOfBase($socket, $command, $entityId, $key, $timestamp, $v)
{

    if (count($entityId) > 127) {
        throw new \Exception("entityId length greater than 127");
    }
    if (count($key) > 127) {
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
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes, $keyLength,
        $keyBytes, $timestampLength);

    return tcpRequestResponce($socket, $byteArray);

}


function ioGetEntityAsOfBase($socket, $command, $entityId, $timestamp, $v)
{

    if (count($entityId) > 127) {
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

    return tcpRequestResponce($socket, $byteArray);

}

function ioGetAllVersionsBetweenBase($socket, $command, $entityId, $timestampStart, $timestampEnd, $limit, $v)
{

    if (count($entityId) > 127) {
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
    $byteArray = array_merge($commandBytes, $verboseMode, $uniqueInt, $entityIdLength, $entityIdBytes,
        $timestampStartBytes, $timestampEndBytes, $limitBytes);

    return tcpRequestResponce($socket, $byteArray);

}

//PUBLIC API
//writes
function ioAssoc($entityId, $key, $value)
{
    return ioAssocBase(Socket::$socket, 1, $entityId, $key, $value, 1);
}

function ioAssocInJson($entityId, $key, array $deepKey, $value)
{
    return ioAssocInJsonBase(Socket::$socket, 3, $entityId, $key, json_encode($deepKey), $value, 0);
}

function ioDissoc($entityId, $key)
{
    return ioDissocBase(Socket::$socket, 2, $entityId, $key, 0);
}

function ioDissocInJson($entityId, $key, array $deepKey)
{
    return ioDissocInJsonBase(Socket::$socket, 4, $entityId, $key, json_encode($deepKey), 0);
}

//reads
function ioGetKeyLatest($entityId, $key)
{
    return ioGetKeyAsOfBase(Socket::$socket, -128, $entityId, $key, SystemClock\serverLatest(), 0);
}

function ioGetKeyAsOf($entityId, $key, $timestamp)
{
    return ioGetKeyAsOfBase(Socket::$socket, -128, $entityId, $key, SystemClock\convertTimestampToNano($timestamp), 0);
}

function ioGetEntityLatest($entityId)
{
    return ioGetEntityAsOfBase(Socket::$socket, -127, $entityId, SystemClock\serverLatest(), 0);
}

function ioGetEntityAsOf($entityId, $timestamp)
{
    return ioGetEntityAsOfBase(Socket::$socket, -127, $entityId, SystemClock\convertTimestampToNano($timestamp), 0);
}

function ioGetAllVersionsBetween($entityId, $timestampStart, $timestampEnd, $limit)
{
    return ioGetAllVersionsBetweenBase(Socket::$socket, -126, $entityId,
        SystemClock\convertTimestampToNano($timestampStart), SystemClock\convertTimestampToNano($timestampEnd), $limit,
        0);
}

function ioGetAllVersionsBetweenNowAnd($entityId, $timestampEnd, $limit)
{
    return ioGetAllVersionsBetweenBase(Socket::$socket, -126, $entityId, SystemClock\serverLatest(),
        SystemClock\convertTimestampToNano($timestampEnd), $limit, 0);
}

function connectToServer($ipAddress, $port)
{
    Socket::init($ipAddress, $port);
}

//TODO remove this for production
connectToServer("localhost", 10000);


//var_dump(ioAssoc("api-users", "k-2", ["names" => [["first" => "will", "last" => "king"], ["first" => "rangel", "last" => "spasov"]]]));

//var_dump(ioGetEntityLatest("api-users"));
//
//var_dump(ioAssoc("api-users-99", "k-2", "new-val"));
//var_dump(ioAssoc("api-users", "k-1", 1));
//var_dump(ioAssoc("api-users", "k-1", true));
//var_dump(ioAssoc("api-users", "k-1", "v-1"));

//var_dump(ioDissoc("api-users", "k-1"));
//

//var_dump(ioAssoc("rangel", "addresses", NULL));

//
//var_dump(ioAssocInJson("ryan", "addresses", ["address-1", "state"], ["short" => "CA", "full" => "California"]));
//
//var_dump(ioDissocInJson("ryan", "addresses", ["address-1", "state", "full"]));
//
//var_dump(ioGetKeyLatest("api-users", "k-1"));
//
//var_dump(ioGetKeyAsOf("api-users", "k-1", time()));
//
//var_dump(ioGetEntityLatest("api-users-99"));
//
//var_dump(ioGetEntityAsOf("api-users-99", time() - 60));

//var_dump(ioGetEntityLatest("ryan"));

//
//var_dump(json_decode(ioGetAllVersionsBetweenNowAnd("api-users-99", time() - 3600, 99)), true);

//var_dump(ioGetAllVersionsBetweenNowAnd("api-users-99", time() - 3600, 99));;

function test_1() {
    for($i = 1; $i < 2500; $i++) {
        ioAssoc("users-test-3", "k-".$i, "v-".$i);
    }
}
//test_1();
