<?php

namespace RequestIdDispenser;


/**
 * Dispenses request ids and handles the state of those ids, adding and removing them as they come back
 *
 * Class RequestIdDispenser
 * @package RequestIdDispenser
 */
class RequestIdDispenser {

    private static $currentId = -2147483648;
    private static $dispensedIntegers = array();

    public static function dispenseId() {

        while(array_key_exists(self::$currentId, self::$dispensedIntegers) == true)
        {
            $newCurrentId = self::$currentId + 1;
            //keep the id within 4 bytes
            if($newCurrentId > 2147483647){
                //start over (realistically possible only in very long lived connections)
                self::$currentId = -2147483648;
            }
            else {
                //try next integer
                self::$currentId = self::$currentId + 1;;
            }
        }

        //store the dispensed id
        self::$dispensedIntegers[self::$currentId] = null;

        return self::$currentId;
    }

    public static function returnId($id) {
        unset(self::$dispensedIntegers[$id]);
    }

}