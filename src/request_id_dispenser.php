<?php

namespace RequestIdDispenser;
/**
 * Dispenses request UUIDs; needs to be implemented if the library ever grows to support non-blocking IO
 *
 * Class RequestIdDispenser
 * @package RequestIdDispenser
 */
class RequestIdDispenser {

    //TODO
    public static function dispenseUUIDBytes() {
        return [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0];
    }

}