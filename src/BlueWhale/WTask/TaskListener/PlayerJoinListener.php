<?php

namespace BlueWhale\WTask\TaskListener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use BlueWhale\WTask\ActTaskAPI;
use BlueWhale\WTask\WTaskAPI;

class PlayerJoinListener implements Listener, TaskListener
{
    public $plugin;
    public $tn;
    public $task;
    public $api;

    public function __construct(WTaskAPI $api, array $task, string $tn) {
        $this->api = $api;
        $this->plugin = $api->plugin;
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $api->plugin);
        $this->task = $task;
        $this->tn = $tn;
    }

    public function reload() {
        $this->task = $this->api->prepareTask($this->tn);
    }

    public function onBreak(PlayerJoinEvent $event) {
        $tn = $this->tn;
        $t = new ActTaskAPI($event, $event->getPlayer(), $this->api);
        $ID = 0;
        while (isset($this->task[$ID])) {
            $inside = $this->task[$ID];
            switch ($inside["type"]) {
                case "取消":
                case "cancel":
                    $event->setCancelled(true);
                    break;
                case "c":
                    $result = eval($t->api->executeReturnData($inside["function"], $event->getPlayer()));
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