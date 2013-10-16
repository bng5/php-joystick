<?php

/*
1 1 1 1 1 1 1 1
            | |_ Botón
            |___ Axis
*/

// ~$ cat /proc/bus/input/devices | less

class Joystick {

    const BUTTON     = 65536;
    const AXISMOTION = 131072;
    
    const UP         = 2;
    const DOWN       = 1;
    const BUTTONUP   = 65536;
    const BUTTONDOWN = 65537;

    const AXIS_H = 0;//33554432;
    const AXIS_V = 16777216;

//    const AXISCENTER = 0;
    const AXISNEG = 32769;
    const AXISPOS = 32767;
    const AXISLEFT = 32769;
    const AXISRIGHT = 32767;
    const AXISUP = 32769;
    const AXISDOWN = 32767;
    const AXISCENTER = 0;




    const AXISMOTIONUP = 1;     // AXISMOTION + AXIS_V + AXIS_UP
    const AXISMOTIONDOWN = 1;   // AXISMOTION + AXIS_V + AXIS_DOWN
    const AXISMOTIONMIDDLE = 1; // AXISMOTION + AXIS_V + AXIS_CENTER
    const AXISMOTIONLEFT = 1;   // AXISMOTION + AXIS_H + AXIS_LEFT
    const AXISMOTIONRIGHT = 1;  // AXISMOTION + AXIS_H + AXIS_RIGHT
    const AXISMOTIONCENTER = 1; // AXISMOTION + AXIS_H + AXIS_CENTER


    public static $prefix = '/dev/input/js';

    private $_js;

    private $_buttonsCount = 0;

    public static function getIds() {
        $a = glob(self::$prefix.'*');
        if(!count($a)) {
            return false;
        }
        return preg_replace('/^'.preg_quote(self::$prefix, '/').'(\d+)$/', '\1', $a);
    }

    public function __construct($id) {
        $this->_js = fopen(self::$prefix.$id, 'rb');
        stream_set_blocking($this->_js, false);
        $this->_init();
        stream_set_blocking($this->_js, true);
    }

    public function __destruct() {
        fclose($this->_js);
    }
    
    private function _init() {

        /*
         *                                                         ---- Tipo (\x81: Botón, \x82: Axis)
         *                                                        |   --- Número
         *                                                        |  |
         * {timestamp} {timestamp} {timestamp} {timestamp} 01 80 82 01
         */
//        $data = fgets($this->_js, 8);
        $data = fgets($this->_js);
        foreach(str_split($data, 8) AS $read) {

            /*
echo "
---- ".strlen($read)."
";
             */
//var_dump(
//    array_values(unpack('n*', $read)),
//    array_values(unpack('L*', $read)),
//    array_values(unpack('H*', $read))
//);

            $value = array_values(unpack('L*', $read));
//$short = array_values(unpack('n*', $read));
            $timestamp = $value[0];//($value[0] >> 8);

//echo sprintf("time %032b", $value[0])."\n";
echo "data  ".chunk_split(sprintf("%032b", $value[1]), 8, ' ')."\n";
//var_dump($short);

/*
 *
int(16874824)
int(1378440863)
* 
int(16973144)       98320
int(1378440962)     99
*
* 
 * */


            /*
            "\n";
            chunk_split(base_convert($value[1], 16, 2), 8, ' ');
            .
            "\n".
            base_convert($value[1], 16, 2).
            "\n".
            .
            "\n";

var_dump(ord($read[6]));
             */
            if(self::BUTTON & $value[1]) {
//                case "\x81": // button
                    echo "botón\n";
                    $this->_buttonsCount++;
//                    break;
            }
        }

    }

    /**
     * 
     * @return \JoystickEvent|boolean
     */
    public function getEvent() {

        if($read = fread($this->_js, 8)) {
            $ev = new JoystickEvent($read);
            return $ev;
        }
        return false;
    }

    public function getNumButtons() {
        return $this->_buttonsCount;
    }
    
    private function _dump($word) {
        for($i = 0; $i < strlen($word); $i++) {
            if($i%8 == 0) {
                echo PHP_EOL;
            }
            echo str_pad(dechex(ord($word[$i])), 2, '0', STR_PAD_LEFT).' ';
        }
        echo PHP_EOL;
    }

}

class JoystickEvent {
    public $type;
    public $button;
    public $axis;
    public $value;
    public $data;
    
    public function __construct($read) {
        //var_dump(ord($read[7]), dechex(ord($read[7])), decoct(ord($read[7])));
            
        $value = array_values(unpack('L*', $read));

        if(Joystick::BUTTON & $value[1]) {
//                $ev->type = self::BUTTON;
//                $ev->type |= ($value[1] & 65537);
            $this->type = ($value[1] & 65537);
            $this->button = ($value[1] >> 24);
        }
        elseif(Joystick::AXISMOTION & $value[1]) {
            $this->type = Joystick::AXISMOTION;
            $this->type |= ($value[1] & Joystick::AXIS_V);
            $this->value = ($value[1] & 65535);
        }
        else {
            switch($read[6]) {
//                case "\x01": // button
//                    $ev->button = ord($read[7]);
//                    switch($read[4]) {
//                        case "\x01":
//                            $ev->type = self::BUTTONDOWN;
//                            break;
//                        case "\x00":
//                            $ev->type = self::BUTTONUP;
//                            break;
//                    }
//                    break;
                case "\x02": // axis
                    $this->type = Joystick::AXISMOTION;
                    $this->axis = ord($read[7]);
                    $this->value = ord($read[5]);
                    switch ($read[4]) {
                        case "\x01":
                            $this->type = Joystick::AXISLEFT;
                            break;
                        case "\xFF":
                            $this->type = Joystick::AXISRIGHT;
                            break;
                    }
                    break;
                default:
                    $this->type = 0;
                    break;
            }
        }

        $this->timestamp = $value[0];
        $this->data = $value[1];
    }
}