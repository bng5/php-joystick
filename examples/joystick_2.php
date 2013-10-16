<?php

require('../Joystick.php');

$disponibles = Joystick::getIds();
echo "Disponibles: ".implode(',', $disponibles)."\n";

$joystick = new Joystick(current($disponibles));

echo "
Botones: ".$joystick->getNumButtons()."
    
";

while(true) {
    if($event = $joystick->getEvent()) {
//        echo "Type: {$event->type}\n";
        echo "{$event->timestamp} ";
        switch($event->type) {
            case Joystick::BUTTONDOWN:
                echo "BUTTONDOWN {$event->button}\n";
                break;
            case Joystick::BUTTONUP:
                echo "BUTTONUP   {$event->button}\n";
                break;
            case Joystick::AXISMOTION:
                echo "AXISMOTION\n";
                echo "Value: {$event->value}\n";
                echo "Axis: {$event->axis}\n";
                break;
            default:
                var_dump($event->type);
        }
    }
}

?>
