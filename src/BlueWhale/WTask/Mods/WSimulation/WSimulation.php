<?php

namespace BlueWhale\WTask\Mods\WSimulation;

use BlueWhale\WTask\Mods\ModBase;

class WSimulation extends ModBase
{
    private $plugin;
    const VERSION = "1.0.0";
    const NAME = "WSimulation";

    public function onEnable()//构造
    {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("WSimulation");
        $this->getCommandMap()->register("WTask", new WSimulationCommand($this, $desc));
    }
}