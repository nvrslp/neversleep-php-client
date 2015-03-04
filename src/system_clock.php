<?php

namespace SystemClock;

/**
 * Returns current timestamp extended to nanoseconds in a string format
 * @return string
 */
function getTime() {
    //check if we're running on 64 bit machine
    if(PHP_INT_SIZE == 8) {
        return strval((int) microtime(true) * 1000 * 1000 * 1000);
    }
    else {
        //prevent integer overflow on 32 bit machines
        return strval(time()) . "000000000";
    }
}

function convertTimestampToNano($timestamp) {

    if(strlen(strval($timestamp)) > 10) {
        throw new \Exception("Timestamp too big: $timestamp");
    }

    //check if we're running on 64 bit machine
    if(PHP_INT_SIZE == 8) {
        $return = strval($timestamp * 1000 * 1000 * 1000);
    }
    else {
        //prevent integer overflow on 32 bit machines
        $return = strval($timestamp) . "000000000";
    }

    //normalize output to always be 19 characters
    if(strlen($return) < 19) {
        $prefixZeros = 19 - strlen($return);
        return str_repeat("0", $prefixZeros) . $return;
    }
    else {
        return $return;
    }

}

function serverLatest() {
    //19 underscores
    return "___________________";
}
