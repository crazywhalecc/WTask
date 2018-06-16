<?php

namespace BlueWhale\WTask;

use pocketmine\event\entity\EntityDamageByEntityEvent;

class EntityDamageAPI extends NormalTaskAPI
{
    public $player;
    public $entityapi;
    public $event;
    public $plugin;
    public $api;

    public function __construct($p, EntityDamageByEntityEvent $event, WTaskAPI $api) {
        parent::__construct($p, $api);
        $this->player = $p;

        $this->entityapi = new NormalTaskAPI($event->getEntity(), $api);
        $this->event = $event;
        $this->api = $api;
        $this->plugin = $api->plugin;

    }

    public function getPlugin() {
        return $this->plugin;
    }

    public function getVictimAPI() {
        return $this->entityapi;
    }

    public function getDamagerAPI() {
        return $this;
    }

    public function sendMessageToEntity($it) {
        return $this->entityapi->sendMessage($it);
    }

    public function sendTipToEntity($it) {
        return $this->entityapi->sendTip($it);
    }

    public function sendPopupToEntity($it) {
        return $this->entityapi->sendPopup($it);
    }

    public function doSubCommand2($cmdd) {
        $multitask = explode(",", $cmdd);
        foreach ($multitask as $task) {
            $cmd = explode(".", $task);
            switch ($cmd[0]) {
                case "jump":
                case "跳转":
                    return $cmd[1];
                case "cancel":
                case "取消":
                    $this->event->setCancelled(true);
                    return true;
                case "消息":
                case "msg":
                    $this->sendMsgPacket($this->event->getDamager(), $cmd[1], 0);
                    return true;
                case "tip":
                case "提示":
                    $this->sendMsgPacket($this->event->getDamager(), $cmd[1], 1);
                    return true;
                case "popup":
                case "底部":
                    $this->sendMsgPacket($this->event->getDamager(), $cmd[1], 2);
                    return true;
                case "结束":
                case "end":
                    return "end";
                case "pass":
                    return true;
            }
        }
        return false;
    }
}