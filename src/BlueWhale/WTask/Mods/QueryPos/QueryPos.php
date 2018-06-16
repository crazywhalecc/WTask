<?php

namespace BlueWhale\WTask\Mods\QueryPos;

use BlueWhale\WTask\Mods\ModBase;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;

class QueryPos extends ModBase implements Listener
{
    private $plugin;
    public $locations = [];
    const VERSION = "1.0.0";
    const NAME = "QueryPos";

    /** @var Vector3[] $pos */
    private $pos;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("QueryPos");
        $this->getCommandMap()->register("WTask", new QueryPosCommand($this, $desc));
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
    }

    public function onTouch(PlayerInteractEvent $pig) {
        $log = $pig->getPlayer()->getName();
        if (in_array($log, $this->locations)) {
            $ru = $pig->getBlock();
            $zoo = strtolower($pig->getPlayer()->getName());
            $this->pos[$zoo] = new Vector3($ru->getX(), $ru->getY(), $ru->getZ());
            $pig->getPlayer()->sendMessage("§b查询的坐标§c:\n[x->" . $this->pos[$zoo]->getX() . "]\n[y->" . $this->pos[$zoo]->getY() . "]\n[z->" . $this->pos[$zoo]->getZ() . "]\n§b方块ID: [" . $ru->getID() . ":" . $ru->getDamage() . "]");
            $fr = array_search($log, $this->locations);
            unset($this->locations[$fr]);
            return;
        }
    }
}