<?php

namespace BlueWhale\WTask\Mods\WRobot;

use BlueWhale\WTask\Mods\ModBase;
use pocketmine\event\Listener;
use BlueWhale\WTask\Config;
use pocketmine\Utils\Utils;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

class WRobot extends ModBase implements Listener
{
    private $plugin;
    public $status;
    const ROBOT_API = 'c6297a8608f54e6ebb9129a26679d6c6';
    const VERSION = "1.0.0";
    const NAME = "WRobot";

    private $sign;
    /** @var  Config */
    public $config;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("WRobot");
        $this->getCommandMap()->register("WTask", new WRobotCommand($this, $desc));
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        @mkdir($this->plugin->getDataFolder() . "Mods/WRobot/");
        $this->config = new Config($this->plugin->getDataFolder() . "Mods/WRobot/" . "config.yml", Config::YAML, array(
            "status" => true,
            "sign" => '#',
            "消息处理方式" => "direct",
            "文本前缀" => "§b",
            "key" => "default"
        ));
        $this->status = $this->config->get("status");
        $this->sign = $this->config->get("sign");
    }


    public function getTopMessageDirect(string $string) {
        try {
            $URL = "www.tuling123.com/openapi/api?key=" . $this->getTuringAPI() . "&info=" . $string;
            $info = json_decode(Utils::getURL($URL), true);
            return $info["text"];
        } catch (\Throwable $e) {
            $this->getServer()->getLogger()->logException($e);
        }
        return null;
    }

    public function callAsyncTask($p, $string, $color) {
        $class = new TuringTask($p, $string, $color);
        $this->getServer()->getScheduler()->scheduleAsyncTask($class);
        return true;
    }

    public function onChat(PlayerCommandPreprocessEvent $event) {
        if ($this->status === false)
            return;
        else {
            $msg = $event->getMessage();
            $key = substr($msg, 0, 1);
            if ($key == $this->sign) {
                $msg = substr($msg, 1);
                if ($this->config->get("消息处理方式") == "direct") {
                    $event->getPlayer()->sendMessage($this->config->get("文本前缀") . $this->getTopMessageDirect($msg));
                    $event->setCancelled(true);
                } elseif ($this->config->get("消息处理方式") == "async") {
                    $this->callAsyncTask($event->getPlayer(), $msg, $this->config->get("文本前缀"));
                    $event->setCancelled(true);
                }
            }
        }
    }

    protected function getTuringAPI() {
        if ($this->config->get("key") == "default") {
            return self::ROBOT_API;
        } else {
            return $this->config->get("key");
        }
    }
}