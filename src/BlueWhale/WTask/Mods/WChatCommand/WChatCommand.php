<?php

namespace BlueWhale\WTask\Mods\WChatCommand;

use BlueWhale\WTask\Mods\ModBase;
use BlueWhale\WTask\Config;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class WChatCommand extends ModBase implements Listener
{
    private $plugin;
    const VERSION = "1.0.0";
    const NAME = "WChatCommand";

    private $config;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("WChatCommand");
        $this->getCommandMap()->register("WTask", new MainCommand($this, $desc));
        try {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        } catch (\Throwable $e) {
        }
        @mkdir($this->plugin->getDataFolder() . "Mods/WChatCommand/");
        $this->config = new Config($this->plugin->getDataFolder() . "Mods/WChatCommand/" . "config.yml", Config::YAML, array());
    }

    public function onChat(PlayerCommandPreprocessEvent $event) {
        $msg = $event->getMessage();
        foreach ($this->getConfig()->getAll() as $in => $cmd) {
            if ($in == $msg) {
                foreach ($cmd as $cmdr) {
                    $this->getServer()->dispatchCommand($event->getPlayer(), $this->plugin->api->msgs($cmdr, $event->getPlayer()));
                    $event->setCancelled(true);
                }
            }
        }
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }
}