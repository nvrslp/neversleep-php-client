<?php

/**
 * Helper function for conversion between binary strings and byte arrays
 */
namespace DataManipulation;

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

    return (int) $i;
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

    return (string) $string;
}

