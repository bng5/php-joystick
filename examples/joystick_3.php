<?php

require(dirname(__FILE__).'/../Joystick.php');

$disponibles = Joystick::getIds();
echo "Controles disponibles: ".count($disponibles)." (IDs: ".implode(',', $disponibles).")\n";

$joystick = new Joystick(current($disponibles));

$botones = $joystick->getButtons();
echo "Botones (".count($botones)."): ".print_r($botones, true)."
    
Ejes: ".print_r($joystick->getAxes(), true)."

";

while(true) {
    if($event = $joystick->getEvent()) {
        
        $bytes = array();
        $bytes[] = (255 & ($event->data >> 24));
        $bytes[] = (255 & ($event->data >> 16));
//        $bytes[] = (255 & ($event->data >> 8));
        $bytes[] = decbin(65535 & $event->data);
        $bytes[] = sprintf('%d', 65535 & $event->data);
        
        echo "data  ".chunk_split(sprintf("%032b", $event->data), 8, ' ')."\n";
        switch($event->type) {
            case Joystick::BUTTONDOWN:
            case Joystick::BUTTONUP:
                printf("      %'.8s <-button\n", $event->button);
                break;
            case Joystick::AXISMOTION:
                printf("      %8d <-  axis %16d\n", $event->axis, $event->value);
                break;
        }
        print_r($bytes);
        echo "\n";
        continue;
        
        
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
