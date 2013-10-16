<?php

require('../Joystick.php');

ini_set('display_errors', 'on');
error_reporting(E_ALL | E_STRICT);

$disponibles = Joystick::getIds();


$area = array(
    '                                                             ',
    '                                                             ',
    '                                                             ',
    '                                                             ',
    '                                                             ',
);

$h_len = strlen($area[0]);
$v_len = count($area);

$pos = array(
    3,
    10,
);


$joystick = new Joystick(current($disponibles));
var_dump($joystick);

echo "\n\n\n\n\n\n\n\n\n\n\n\n\n\n";

$char = '@';
$botones = array();
do {
//    echo "\033[2J";
    echo "\033[5A";
    
    $area[$pos[0]][$pos[1]] = $char;
    echo implode("\n", $area)."\n";

    if($event = $joystick->getEvent()) {
        if($event->type & Joystick::BUTTON) {
            if($event->type == Joystick::BUTTONDOWN) {
                array_push($botones, $event->button);
                $char = $event->button;
            }
            else {
//                $char = ($event->type == Joystick::BUTTONDOWN) ? $event->button : '@';
                unset($botones[array_search($event->button, $botones)]);
                $char = count($botones) ? end($botones) : '@';
            }
        }
        elseif($event->type & Joystick::AXISMOTION) {
            $area[$pos[0]][$pos[1]] = '-';
            if($event->type & Joystick::AXIS_V) {
                switch($event->value) {
                    case Joystick::AXISPOS:
                        $pos[0]++;
                        if($pos[0] >= $v_len) {
                            $pos[0] = 0;
                        }
                        break;
                    case Joystick::AXISNEG:
                        $pos[0]--;
                        if($pos[0] < 0) {
                            $pos[0] = ($v_len - 1);
                        }
                        break;
                }
            }
            else {
                switch($event->value) {
                    case Joystick::AXISPOS:
                        $pos[1]++;
                        if($pos[1] >= $h_len) {
                            $pos[1] = 0;
                        }
                        break;
                    case Joystick::AXISNEG:
                        $pos[1]--;
                        if($pos[1] < 0) {
                            $pos[1] = ($h_len -1);
                        }
                }
            }
        }
//        else {
//            switch($event->type) {
//                case Joystick::BUTTONDOWN:
//                    break;
//                case Joystick::BUTTONUP:
//                    $char = '@';
//                    break;
//                case Joystick::AXISMOTION:
//                    echo "AXISMOTION\n";
//                    echo "Value: {$event->value}\n";
//                    echo "Axis: {$event->axis}\n";
//                    break;
//                default:
//                    var_dump($event->type);
//            }
//        }
    }
} while(true);

?>
