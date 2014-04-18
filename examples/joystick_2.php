<?php

require(dirname(__FILE__).'/../Joystick.php');

function seleccionarControl($disponibles, $id) {
    $opciones = $disponibles;
    $opciones_ultimo = array_pop($opciones);
    do {
        foreach($disponibles as $key => $value) {
            printf("%-40s -> %d\n", $value, $key);
        }
        $sel = readline("Seleccione el control a utilizar: ");
        if(!empty($sel)) {
            if(!array_key_exists($sel, $disponibles)) {
                printf("'%s' no es una opción válida.\n", $sel);
                continue;
            }
            $id = (int) $sel;
        }
        break;
    } while(true);
    return $id;
}

class Barra {
    private $minVal = 1;
    private $maxVal = 27;
    private $dVal;
    
    private $txt;
    
    public function __construct($val) {
        $this->dVal = ($this->maxVal - $this->minVal);
        $this->update($val);
    }
    
    public function update($val) {
//        $pos = round(((($val - Joystick::AXISMIN) * ($this->maxVal - $this->minVal)) / (Joystick::AXISMAX - Joystick::AXISMIN)) + $this->minVal);
        $pos = round(((($val - Joystick::AXISMIN) * $this->dVal) / 65534) + $this->minVal);
        $this->txt = '|---------------------------|';
        $this->txt = substr_replace('|---------------------------|', "\033[32;7m|\033[0m", $pos, 1);
    }
    
    public function __toString() {
        return $this->txt;
    }
}

$disponibles = Joystick::getIds();
print_r($disponibles);
$disponibles = Joystick::getJoysticks();
print_r($disponibles);

$id = current($disponibles);
if(!count($disponibles)) {
    fwrite(STDERR, sprintf("No se encontraron controles disponibles en %s \n", Joystick::$prefix));
    exit(1);
}
elseif(count($disponibles) > 1) {
    $id = seleccionarControl($disponibles, $id);
}

try {
    $joystick = new Joystick($id);
} catch (Exception $exc) {
    fwrite(STDERR, 'Error: '.$exc->getMessage()."\n");
    exit(1);
}

$botones = $joystick->getButtons();
print_r($botones);
var_dump($joystick->getName());
exit;
$lineas = max(ceil(count($joystick->getButtons()) / 10), count($joystick->getAxes()));


$bars = array();
foreach($joystick->getAxes() as $key => $value) {
    $bars[$key] = new Barra($value);
}

while(true) {
    echo "\033[2J\033[0;0H\n";
    $pad = ' ';
    for($i = 0; $i < $lineas; $i++) {
        $linea = array_key_exists($i, $bars) ? sprintf(" %2s %s", $i, $bars[$i]) : '                                 ';
        $linea .= '    ';
        $g = $i * 10;
        $h = ($g + 10);
        if(count($botones) > $g) {
            for($g; $g < count($botones), $g < $h; $g++) {
                if(!array_key_exists($g, $botones)) {
                    break;
                }
                $linea .= $pad.($botones[$g] ? "\033[32;7m{$g}\033[0m" : $g);
            }
        }
        echo "{$linea}\n";
        $pad = '';
    }
    if($event = $joystick->getEvent()) {
        switch($event->type) {
            case Joystick::BUTTONDOWN:
                $botones[$event->button] = 1;
                break;
            case Joystick::BUTTONUP:
                $botones[$event->button] = 0;
                break;
            case Joystick::AXISMOTION:
                $bars[$event->axis]->update($event->value);
                break;
        }
    }
}

?>
