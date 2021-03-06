<?php

namespace BlueWhale\WTask\TaskListener;

use BlueWhale\WTask\WTaskAPI;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use BlueWhale\WTask\ActTaskAPI;

class BlockBreakListener implements Listener, TaskListener
{
    public $plugin;
    public $task;
    public $tn;
    public $api;

    public function __construct(WTaskAPI $api, array $task, string $tn) {
        $this->api = $api;
        $this->plugin = $api->plugin;
        try {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $api->plugin);
        } catch (\Throwable $e) {
        }
        $this->task = $task;
        $this->tn = $tn;
    }

    public function reload() {
        $this->task = $this->api->prepareTask($this->tn);
    }

    public function onBreak(BlockBreakEvent $event) {
        $tn = $this->tn;
        $t = new ActTaskAPI($event, $event->getPlayer(), $this->api);
        $t->writePrivateData("item|" . $event->getBlock()->getId() . "-" . $event->getBlock()->getDamage() . "-1");
        $t->writePrivateData("id|" . $event->getBlock()->getId());
        $t->writePrivateData("damage|" . $event->getBlock()->getDamage());
        $t->writePrivateData("pos|" . $event->getBlock()->x . ":" . $event->getBlock()->y . ":" . $event->getBlock()->z . ":" . $event->getBlock()->level->getFolderName());
        $t->writePrivateData("level|" . $event->getBlock()->level->getFolderName());
        $ID = 0;
        while (isset($this->task[$ID])) {
            $inside = $this->task[$ID];
            switch ($inside["type"]) {
                case "取消":
                case "cancel":
                    $event->setCancelled(true);
                    break;
                case "清空临时缓存":
                    $t->deletePrivateData("id");
                    $t->deletePrivateData("pos");
                    $t->deletePrivateData("level");
                    break;
                case "checkblock":
                case "判断方块":
                    $result = $t->checkBlock($inside["function"]);
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
                case "设置掉落物":
                    $t->setDropItems($inside["function"]);
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