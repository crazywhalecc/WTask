<?php

namespace BlueWhale\WTask\Mods\DisableFlow;

use BlueWhale\WTask\Mods\ModBase;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\block\Lava;
use pocketmine\block\Water;

class DisableFlow extends ModBase implements Listener
{
    const VERSION = "1.0.0";
    const NAME = "DisableFlow";
    private $plugin;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $this->getWTask()->getServer()->getPluginManager()->registerEvents($this, $this->getWTask());
    }

    public function onBlockUpdate(BlockUpdateEvent $event) {
        $Block = $event->getBlock();
        if (($Block instanceof Water) OR ($Block instanceof Lava)) {
            $event->setCancelled(true);
        }
    }
}
