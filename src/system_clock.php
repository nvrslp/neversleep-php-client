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

    //check if we're running on 64 bit machine
    if(PHP_INT_SIZE == 8) {
        return strval($timestamp * 1000 * 1000 * 1000);
    }
    else {
        //prevent integer overflow on 32 bit machines
        return strval($timestamp) . "000000000";
    }

}
