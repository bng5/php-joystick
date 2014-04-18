<?php

/*
1 1 1 1 1 1 1 1
            | |_ Botón
            |___ Axis
*/

// ~$ cat /proc/bus/input/devices | less

class Joystick {

    const INIT       = 8388608;
    
    const BUTTON     = 65536;
    const AXISMOTION = 131072;
    
    const UP         = 2;
    const DOWN       = 1;
    const BUTTONUP   = 65536;
    const BUTTONDOWN = 65537;

    const AXISMIN = -32767;
    const AXISMAX = 32767;

    public static $prefix = '/dev/input/js';

    private $_id;
    private $_js;

    private $_buttons = array();
    private $_axes = array();
    
    private $_name = null;
    
    /**
     * 
     * @return array
     */
    public static function getJoysticks() {
        $devices = array();
        $fp = fopen('/proc/bus/input/devices', 'r');
        if($fp) {
            $name = null;
            $handler = null;
            while($read = fgets($fp)) {
                $read = trim($read);
                if(!$read) {
                    $name = null;
                    $handler = null;
                    continue;
                }
                if(preg_match('/^N: Name="([^"]+)"/', $read, $matches)) {
                    $name = $matches[1];
                }
                elseif(preg_match('/^H: Handlers=.*\bjs(\d)\b/', $read, $matches)) {
                    $handler = $matches[1];
                }
                if(!is_null($name) && !is_null($handler)) {
                    $devices[$handler] = $name;
                }
            }
            fclose($fp);
        }
        return $devices;
    }
    
    public static function getIds() {
        $a = glob(self::$prefix.'*');
        if(!count($a)) {
            return false;
        }
        return preg_replace('/^'.preg_quote(self::$prefix, DIRECTORY_SEPARATOR).'(\d+)$/', '\1', $a);
    }

    public function __construct($id) {
//        filetype('/dev/input/js0'));
//string(4) "char"
        $this->_id = $id;
        $cfile = self::$prefix.$id;
        if(!is_readable($cfile) || filetype($cfile) != 'char') {
            throw new Exception(sprintf("%s is not a Character special file or it's not readable.", self::$prefix.$id), 2);
        }

        $this->_js = fopen($cfile, 'rb');
        if(!$this->_js) {
            throw new Exception(sprintf("Cannot open %s", self::$prefix.$id), 1);
        }
        stream_set_blocking($this->_js, false);
        $this->_init();
        stream_set_blocking($this->_js, true);
    }

    public function __destruct() {
        fclose($this->_js);
    }
    
//    public function getName() {
//        
//        if(!is_null($this->_name)) {
//            return $this->_name;
//        }
//        
//        $this->_name = false;
//        $fp = fopen('/proc/bus/input/devices', 'r');
//        if($fp) {
//            $name = null;
//            $handler = null;
//            while($read = fgets($fp)) {
//                if($name && $handler) {
//                    $this->_name = $name;
//                    break;
//                }
//                $read = trim($read);
//                if(!$read) {
//                    $name = null;
//                    continue;
//                }
//                if(preg_match('/^N: Name="([^"]+)"/', $read, $matches)) {
//                    $name = $matches[1];
//                }
//                elseif(preg_match('/^H: Handlers=([a-z0-9 ]+)/', $read, $matches)) {
//                    if(preg_match("/\\bjs{$this->_id}\\b/", $matches[1])) {
//                        $handler = $name;
//                    }
//                }
//            }
//            fclose($fp);
//        }
//        return $this->_name;
//    }
    
    private function _init() {
        /*
         *                                                         ---- Tipo (\x81: Botón, \x82: Axis)
         *                                                        |   --- Número
         *                                                        |  |
         * {timestamp} {timestamp} {timestamp} {timestamp} 01 80 82 01
         */
        
        
        while($read = fread($this->_js, 8)) {
//            $value = array_values(unpack('L*', $read));
//            $timestamp = $value[0];
//            echo "data  {$timestamp} ".chunk_split(sprintf("%032b", $value[1]), 8, ' ')."\n";
            if(empty($read)) {
                return;
            }
            $ev = new JoystickEvent($read);
            if(!$ev->init) {
                return;
            }
            
            switch($ev->type) {
                case self::BUTTON:
//                    array_push($this->_buttons, $ev->button);
                    $this->_buttons[$ev->button] = ($ev->type == self::BUTTONDOWN) ? 1 : 0;
                    break;
                case self::AXISMOTION:
//                    array_push($this->_axes, $ev->axis);
                    $this->_axes[$ev->axis] = $ev->value;
                    break;
                default:
                    throw new Exception("Event type not implemented");
                    break;
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

    /**
     * 
     * @return array
     */
    public function getButtons() {
        return $this->_buttons;
    }
    
    /**
     * 
     * @return array
     */
    public function getAxes() {
        return $this->_axes;
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
    public $init;
    
    public function __construct($read) {
            
        $value = array_values(unpack('L*', $read));
        $this->timestamp = $value[0];
        $this->data = $value[1];
        
        $this->init = (bool) (Joystick::INIT & $this->data);
        if(Joystick::BUTTON & $value[1]) {
//                $ev->type = self::BUTTON;
//                $ev->type |= ($value[1] & 65537);
            $this->type = ($value[1] & 65537);
            $this->button = ($value[1] >> 24);
        }
        elseif(Joystick::AXISMOTION & $value[1]) {
            $this->type = Joystick::AXISMOTION;
//            $this->type |= ($value[1] & Joystick::AXIS_V);
            $this->axis = ($value[1] >> 24);
            $this->value = ($value[1] & 65535);
//          −32,768 to 32,767
            if($this->value > 32767) {
                $this->value -= 65536;
            }
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

    }
}