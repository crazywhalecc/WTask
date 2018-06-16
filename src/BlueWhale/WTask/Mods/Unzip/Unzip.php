<?php

namespace BlueWhale\WTask\Mods\Unzip;

use BlueWhale\WTask\Mods\ModBase;

class Unzip extends ModBase
{
    private $plugin;
    const VERSION = "1.0.0";
    const NAME = "Unzip";

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("Unzip");
        $this->getCommandMap()->register("WTask", new UnzipCommand($this, $desc));
        @mkdir($this->getWTask()->getDataFolder() . "Mods/Unzip/");
    }
}