<?php

/**
 * Helper function for conversion between binary strings and byte arrays
 */
namespace DataManipulation;

function prefixByte($byte, $byteArray) {
    if($byte < -128 || $byte > 127){
        throw new \Exception("Byte out of range: $byte");
    }
    else {
        //prefix byte
        return array_merge([$byte], $byteArray);
    }
}

/**
 * @param $long
 * @return array
 * @throws \Exception
 */
function longToEightBytes($long)
{
    if (PHP_VERSION_ID >= 50630) {
        return unpack("C*", pack("J", $long));
    } else {
        throw new \Exception("longs only supported in PHP 5.6.3 and above");
    }
}

function shortToTwoBytes($short)
{
    return unpack("C*", pack("n", $short));
}

/**
 * @param $integer
 * @return array
 */
function intToFourBytes($integer)
{
    return unpack("C*", pack("N", $integer));
}

/**
 * @param array $ar
 * @return int
 */
function fourBytesToInt(array $ar)
{
    $i = ($ar[1] << 24) + ($ar[2] << 16) + ($ar[3] << 8) + ($ar[4]);

    return (int)$i;
}


/**
 * @param $string
 * @return array
 */
function stringToBytes($string)
{
    return unpack("C*", $string);
}


/**
 * @param array $ar
 * @return string
 */
function bytesToString(array $ar)
{
    $string = "";
    foreach ($ar as $chr) {
        $string .= chr($chr);
    }

    return (string)$string;
}

function boolToOneByte($bool) {
    if ($bool == true) {
        return [1];
    }
    else {
        return [0];
    }
}

function dispatchToType($value)
{
    if(is_array($value)) {
        $json = json_encode($value);
        $jsonLastError = json_last_error();
        if($jsonLastError == 0) {
            //prefix 0
            return prefixByte(0, stringToBytes($json));
        }
        else {
            throw new \Exception("Unable to convert array to JSON, json_last_error() code $jsonLastError");
        }
    }
    //string
    else if (is_string($value)) {
        //prefix 1
        return prefixByte(1, stringToBytes($value));
    //int <= 2147483647
    } else if (is_int($value) && $value <= 2147483647) {
        //prefix 4
        return prefixByte(4, intToFourBytes($value));
    }
    //long
    else if (is_int($value) && $value > 2147483647) {
        return prefixByte(5, longToEightBytes($value));
    }
    //bool
    else if (is_bool($value)) {
        return prefixByte(6, boolToOneByte($value));
    }
    //null
    else if (is_null($value)) {
        return prefixByte(7, []);
    }
    //in other cases try to cast to string
    else {
        //prefix 1
        return prefixByte(1, stringToBytes(strval($value)));
    }
}