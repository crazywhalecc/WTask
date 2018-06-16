<?php

namespace BlueWhale\WTask\Mods\WProtect;

use BlueWhale\WTask\Mods\ModBase;
use pocketmine\event\block\BlockEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\Player;
use BlueWhale\WTask\Config;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

class WProtect extends ModBase implements Listener
{
    private $plugin;
    const VERSION = "1.0.0";
    const NAME = "WProtect";

    /** @var  Config */
    public $config;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->getWTask()->getModule("WProtect");
        $this->getCommandMap()->register("WTask", new WProtectCommand($this, $desc));
        $this->getWTask()->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        @mkdir($this->plugin->getDataFolder() . "Mods/WProtect/");
        $this->config = new Config($this->plugin->getDataFolder() . "Mods/WProtect/" . "config.yml", Config::YAML, array(
            "worlds" => array(),
            "pvp" => array(),
            "ban-floating" => true,
            "world-msg" => "tip:§c对不起，这个世界被保护了",
            "pvp-msg" => "tip:§c这个世界禁止pvp",
            "op-master" => true
        ));

    }

    public function onPlace(BlockPlaceEvent $event) {
        $this->checkPermission($event);
    }

    public function onBreak(BlockBreakEvent $event) {
        $this->checkPermission($event);
    }

    /**
     * @param PlayerEvent|BlockEvent $event
     */
    protected function checkPermission($event) {
        $p = $event->getPlayer();
        $block = $event->getBlock();
        if (in_array($block->level->getFolderName(), $this->getConfig()->get("worlds"))) {
            if ($this->getConfig()->get("op-master") === true && $p->isOp())
                return;
            else {
                $event->setCancelled(true);
                $msg = explode(":", $this->getConfig()->get("world-msg"));
                switch ($msg[0]) {
                    case "tip":
                        $p->sendTip($msg[1]);
                        break;
                    case "msg":
                        $p->sendMessage($msg[1]);
                        break;
                    case "popup":
                        $p->sendPopup($msg[1]);
                        break;
                    case "title":
                        $p->sendTitle($msg[1]);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * @param EntityDamageByEntityEvent $event
     */
    protected function checkPermissionOnPvp($event) {
        $damager = $event->getDamager();
        if ($damager instanceof Player) {
            if (in_array($damager->level->getFolderName(), $this->getConfig()->get("pvp"))) {
                if ($this->getConfig()->get("op-master") === true && $damager->isOp()) {
                    return;
                } else {
                    $event->setCancelled(true);
                    $msg = explode(":", $this->getConfig()->get("pvp-msg"));
                    switch ($msg[0]) {
                        case "tip":
                            $damager->sendTip($msg[1]);
                            break;
                        case "msg":
                            $damager->sendMessage($msg[1]);
                            break;
                        case "popup":
                            $damager->sendPopup($msg[1]);
                            break;
                        case "title":
                            $damager->sendTitle($msg[1]);
                            break;
                        default:
                            break;
                    }
                }
            }
        }
    }

    public function onDamage(EntityDamageEvent $event) {
        if ($event instanceof EntityDamageByEntityEvent) {
            $this->checkPermissionOnPvp($event);
        }
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }
}