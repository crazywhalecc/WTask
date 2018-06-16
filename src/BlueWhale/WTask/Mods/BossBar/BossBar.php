<?php

namespace BlueWhale\WTask\Mods\BossBar;

use BlueWhale\WTask\Mods\ModBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use BlueWhale\WTask\Config;
use pocketmine\level\Location;
use BlueWhale\WTask\BossBarAPI\BossBarAPI;
use BlueWhale\WTask\ScheduleTasks\CallbackTask;
use pocketmine\network\mcpe\protocol\MoveEntityPacket;

class BossBar extends ModBase implements Listener
{
    private $plugin;
    private $eid = [];
    private $repeateid = [];
    private $currentVersion = 1;
    private $broadcasteid = [];
    public $broadcastMsg = "§b";
    public $repeatSwitch = false;
    public $currentBroadcastPlayer = "";
    const VERSION = "1.0.0";
    const NAME = "BossBar";

    public $path;
    public $cmd;
    public $config;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("BossBar");
        $this->getServer()->getCommandMap()->register("WTask", new BossBarCommand($this, $desc));
        @mkdir($this->plugin->getDataFolder() . "Mods/BossBar/");
        $this->path = $this->plugin->getDataFolder() . "Mods/BossBar/";
        try {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        } catch (\Throwable $e) {
        }
        $this->config = new Config($this->path . "config.yml", Config::YAML, array(
            "Config-Version" => $this->currentVersion,
            "动态bar" => array(
                "开关" => true,
                "内容" => '§a♂在线<{online}>  §b$金币<{money}>  §7※现在时间<{time}>%n  %n   §e▲手持<{itemid}:{itemdamage}>  §d■当前地图<{level}>  §9♪流畅度<{tps}>',
                "百分比" => 50,
                "更新频率(秒)" => 1
            ),
            "固定bar" => array(
                "broadcast" => array(
                    "开关" => true,
                    "description" => "const-broadcast",
                    "time" => 10,
                    "repeat" => false,
                    "百分比" => 100,
                    "广播格式" => "§e玩家[@player] 说：§7{msg}",
                    "默认消息" => "§c%n§c%n§7无广播消息"
                )
            )
        ));
        $dat = $this->config->get("动态bar");
        if ($dat["开关"] == true) {
            $this->repeatSwitch = true;

            $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "sendRepeatBar"]), $dat["更新频率(秒)"] * 20);
        }
        foreach ($this->config->get("固定bar") as $bar => $data) {
            if ($bar == "broadcast") {
                if ($data["开关"] == true) {
                    $this->broadcastMsg = $data["默认消息"];
                    $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "sendBroadcastBarFirst"]), 20);
                }
            }
        }
    }

    public function sendBroadcastBarFirst() {
        if ($this->broadcasteid == [])
            return;
        $dat = $this->getConfig()->get("固定bar")["broadcast"];
        foreach ($this->broadcasteid as $eidd) {
            if ($this->getServer()->getPlayerExact($eidd["name"]) === null) {
            } else {
                $p = $this->getServer()->getPlayerExact($eidd["name"]);
                $p->dataPacket($this->playerMove($p, $eidd["eid"]));
            }
            if ($this->broadcastMsg == $dat["默认消息"])
                BossBarAPI::setText($this->broadcastMsg, $eidd["eid"]);
            else
                BossBarAPI::setText(str_replace("@player", $this->currentBroadcastPlayer, str_replace("{msg}", $this->broadcastMsg, $dat["广播格式"])), $eidd["eid"]);
        }
    }

    public function updateInfo($oldVersion)//更新信息(通用的方法)
    {
        switch ($oldVersion) {
            case "0.0.1":
                return null;
            default:
                return null;
        }
    }

    public function searchEid($p) {
        foreach ($this->repeateid as $id => $eid) {
            if ($eid["name"] == $p) {
                return $eid["eid"];
            }
        }
        return null;
    }

    public function sendRepeatBar() {
        if ($this->repeateid == [])
            return;
        $dat = $this->getConfig()->get("动态bar");
        foreach ($this->repeateid as $eidd) {
            if ($this->getServer()->getPlayerExact($eidd["name"]) === null) {
            } else {
                $p = $this->getServer()->getPlayerExact($eidd["name"]);
                $p->dataPacket($this->playerMove($p, $eidd["eid"]));
            }
            BossBarAPI::setText($dat["内容"], $eidd["eid"]);
        }
    }

    public function playerMove(Location $pos, $eid) {
        $pk = new MoveEntityPacket();
        $pk->x = $pos->x;
        $pk->y = $pos->y - 28;
        $pk->z = $pos->z;
        $pk->eid = $eid;
        $pk->yaw = $pk->pitch = $pk->headYaw = 0;
        return clone $pk;
    }

    public function onJoin(PlayerJoinEvent $event) {
        if ($this->repeatSwitch == true) {
            $dat = $this->getConfig()->get("动态bar");
            $this->repeateid[] = array(
                "eid" => BossBarAPI::addBossBar($event->getPlayer(), $dat["内容"], $dat["百分比"]),
                "name" => $event->getPlayer()->getName()
            );
        }
        if ($this->getConfig()->get("固定bar")["broadcast"]["开关"] == true) {
            $dat = $this->getConfig()->get("固定bar")["broadcast"];
            $this->broadcasteid[] = array(
                "eid" => BossBarAPI::addBossBar($event->getPlayer(), BossBarAPI::msgs($event->getPlayer(), $this->broadcastMsg), $dat["百分比"]),
                "name" => $event->getPlayer()->getName()
            );
        }
    }

    public function onQuit(PlayerQuitEvent $event) {
        if ($this->repeatSwitch == true) {
            foreach ($this->repeateid as $id => $eid) {
                if ($eid["name"] == $event->getPlayer()->getName()) {
                    unset($this->repeateid[$id]);
                    return;
                }
            }
        }
    }

    public function clearBroadcastMessage() {
        $this->broadcastMsg = $this->getConfig()->get("固定bar")["broadcast"]["默认消息"];
        $this->currentBroadcastPlayer = "";
    }

    /**
     * @return mixed
     */
    public function getConfig(): Config {
        return $this->config;
    }
}