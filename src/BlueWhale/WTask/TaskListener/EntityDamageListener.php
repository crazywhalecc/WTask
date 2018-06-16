<?php

namespace BlueWhale\WTask\TaskListener;

use pocketmine\event\Listener;
use BlueWhale\WTask\EntityDamageAPI;
use BlueWhale\WTask\WTaskAPI;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;

class EntityDamageListener implements Listener, TaskListener
{
    public $plugin;
    public $task;
    public $tn;
    public $api;

    public function __construct(WTaskAPI $api, array $task, string $tn) {
        $this->api = $api;
        $this->plugin = $api->plugin;
        try {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        } catch (\Throwable $e) {
        }
        $this->task = $task;
        $this->tn = $tn;
    }

    public function reload() {
        $this->task = $this->api->prepareTask($this->tn);
    }

    public function onBreak(EntityDamageEvent $event) {
        $tn = $this->tn;
        if (!$event instanceof EntityDamageByEntityEvent) {
            return false;
        }
        $player = $event->getDamager();
        if (!$player instanceof Player)
            return false;
        $entity = $event->getEntity();
        if (!$entity instanceof Player)
            return false;
        $p = $player;
        $t = new EntityDamageAPI($player, $event, $this->api);
        $at = $t;
        $t->writePrivateData("damager|" . $p->getName());
        $t->writePrivateData("victim|" . $entity->getName());
        $t->getVictimAPI()->writePrivateData("damager|" . $p->getName());
        $t->getVictimAPI()->writePrivateData("victim|" . $entity->getName());
        $ID = 0;
        while (isset($this->task[$ID])) {
            $inside = $this->task[$ID];
            switch ($inside["type"]) {
                case "取消":
                case "cancel":
                    $event->setCancelled(true);
                    break;
                case "设置伤害":
                    $event->setDamage($inside["function"]);
                    break;
                case "增加伤害":
                    $event->setDamage($inside["function"] + $event->getDamage());
                    break;
                case "设置击退":
                    $event->setKnockBack($inside["function"]);
                    break;
                case "转换对象":
                    if ($inside["function"] == 0) {
                        $t = $t->getVictimAPI();
                        break;
                    } elseif ($inside["function"] == 1) {
                        $t = $at;
                        break;
                    }
                    break;
                case "c":
                    $result = eval($t->api->executeReturnData($inside["function"], $t->player));
                    if ($result === null)
                        break;
                    if ($result === true || $result == "true") {
                        break;
                    } elseif (is_numeric($result)) {
                        $ID = $result - 2;
                    } elseif ($result == "end") {
                        $ID = 10000;
                    } elseif ($result === false) {
                        $this->plugin->getServer()->getLogger()->warning("WTask任务：" . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                    } else {
                        $ssp = explode(":", $result);
                        if ($ssp[0] == "false") {
                            $this->plugin->getServer()->getLogger()->warning("WTask任务：" . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                            $this->plugin->getServer()->getLogger()->warning("错误信息：" . $ssp[1]);
                        }
                        $this->plugin->getServer()->getLogger()->notice("WTask任务： " . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候返回了未知内容！");
                    }
                    break;
                default:
                    $result = $this->api->defaultFunction($t, $inside);
                    if ($result === true || $result == "true") {
                        break;
                    } elseif (is_numeric($result)) {
                        $ID = $result - 2;
                    } elseif ($result == "end") {
                        $ID = 10000;
                    } elseif ($result === false) {
                        $this->plugin->getServer()->getLogger()->warning("WTask任务：" . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                    } else {
                        $ssp = explode(":", $result);
                        if ($ssp[0] == "false") {
                            $this->plugin->getServer()->getLogger()->warning("WTask任务：" . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                            $this->plugin->getServer()->getLogger()->warning("错误信息：" . $ssp[1]);
                        }
                        $this->plugin->getServer()->getLogger()->notice("WTask任务： " . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候返回了未知内容！");
                    }
                    break;
            }
            $ID++;
        }
        return true;
    }
}