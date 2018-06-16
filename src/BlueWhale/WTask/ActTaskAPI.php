<?php

namespace BlueWhale\WTask;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Event;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;

class ActTaskAPI extends NormalTaskAPI
{
    public $event;
    public $plugin;
    public $api;
    public $player;

    public function __construct(Event $event, Player $p, WTaskAPI $api) {
        parent::__construct($p, $api);
        $this->event = $event;
        $this->api = $api;
        $this->plugin = $api->plugin;
        $this->player = $p;
    }

    /**
     * @return bool
     */
    public function isBlockActionEvent() {
        if (!$this->event instanceof BlockBreakEvent && !$this->event instanceof BlockPlaceEvent && !$this->event instanceof PlayerInteractEvent)
            return false;
        else
            return true;
    }

    public function setRespawnPosition($it) {
        if (!($this->event instanceof PlayerRespawnEvent)) {
            return false;
        }
        $it = $this->api->executeReturnData($it);
        $pos = $this->executeLocation($it);
        $this->event->setRespawnPosition($pos);
        return true;
    }

    public function checkBlock($data) {
        if (!$this->event instanceof BlockBreakEvent && !$this->event instanceof BlockPlaceEvent && !$this->event instanceof PlayerInteractEvent)
            return "false:动作任务严禁使用不匹配类型";
        $data = explode("|", $data);
        if (sizeof($data) < 4) {
            return "false:检查方块功能中分参数未完整填写！";
        }
        $player = $this->event->getPlayer();
        $type = $this->api->executeReturnData($data[0], $player);
        $scale = $this->api->executeReturnData($data[1], $player);
        switch ($type) {
            case "ID对比":
                $block = $this->event->getBlock()->getId() . "-" . $this->event->getBlock()->getDamage();
                if ($block == $scale) {
                    return $this->doSubCommand2($data[2]);
                } else {
                    return $this->doSubCommand2($data[3]);
                }
        }
        return false;
    }

    public function checkCommand($data) {
        if (!$this->event instanceof PlayerCommandPreprocessEvent)
            return false;
        $data = explode("|", $data);
        if (sizeof($data) < 4) {
            return "false:检查指令功能中分参数未完整填写！";
        }
        $type = $data[0];
        switch ($type) {
            case "比较主指令":
                $msg = substr($this->event->getMessage(), 1);
                $msg = explode(" ", $msg);
                if ($msg[0] == $data[1]) {
                    return $this->doSubCommand2($data[2]);
                } else {
                    return $this->doSubCommand2($data[3]);
                }
            case "比较全指令":
                $msg = substr($this->event->getMessage(), 1);
                if ($msg == $data[1]) {
                    return $this->doSubCommand2($data[2]);
                } else {
                    return $this->doSubCommand2($data[3]);
                }
        }
        return false;
    }

    public function checkMessage($data) {
        if (!$this->event instanceof PlayerCommandPreprocessEvent)
            return false;
        $data = explode("|", $data);
        if (sizeof($data) < 4) {
            return "false:检查消息功能中分参数未完整填写！";
        }
        $player = $this->event->getPlayer();
        $type = $data[0];
        switch ($type) {
            case "比较消息":
                $msg = $this->event->getMessage();
                if ($msg == $this->api->executeReturnData($data[1], $player)) {
                    return $this->doSubCommand2($data[2]);
                } else {
                    return $this->doSubCommand2($data[3]);
                }
            case "存在关键词":
                $msg = $this->event->getMessage();
                if (strpos($msg, $this->api->executeReturnData($data[1], $player)) !== false) {
                    return $this->doSubCommand2($data[2]);
                } else {
                    return $this->doSubCommand2($data[3]);
                }
        }
        return false;
    }

    public function checkTarget($data) {
        if (!$this->event instanceof EntityTeleportEvent)
            return false;
        $data = explode("|", $data);
        if (sizeof($data) < 4) {
            return "false:检查传送位置功能中分参数未完整填写！";
        }
        $type = $data[0];
        switch ($type) {
            case "检查地图":
                $map = $this->event->getTo()->level->getFolderName();
                if ($map == $data[1]) {
                    return $this->doSubCommand2($data[2]);
                } else {
                    return $this->doSubCommand2($data[3]);
                }
            case "检查范围":
                $map = $this->event->getTo()->level->getFolderName();
                $pos = $this->event->getTo()->x . ":" . $this->event->getTo()->y . ":" . $this->event->getTo()->z . ":" . $this->event->getTo()->level->getFolderName();
                $mypos = explode(",", $data[1]);
                $pos1 = $this->api->executeReturnData($mypos[0], $this->event->getEntity());
                $pos2 = $this->api->executeReturnData($mypos[1], $this->event->getEntity());
                if ($map != explode(":", $pos1)[3]) {
                    return $this->doSubCommand2($data[3]);
                }
                if ($this->plugin->isInArea($pos, $pos1, $pos2)) {
                    return $this->doSubCommand2($data[2]);
                } else {
                    return $this->doSubCommand2($data[3]);
                }
        }
        return false;
    }

    public function setKeepInv($data) {
        if (!$this->event instanceof PlayerDeathEvent)
            return false;
        $data = intval($data);
        $type = ($data == 0 ? false : true);
        $this->event->setKeepInventory($type);
        return true;
    }

    public function setKeepExp($data) {
        if (!$this->event instanceof PlayerDeathEvent)
            return false;
        $data = intval($data);
        $type = ($data == 0 ? false : true);
        $this->event->setKeepExperience($type);
        return true;
    }

    public function checkDropItem($data) {
        if (!$this->event instanceof PlayerDropItemEvent) return false;
        $data = explode("|", $data);
        if (sizeof($data) < 3) {
            return "false:检查凋落物功能中分参数未完整填写！";
        }
        $player = $this->event->getPlayer();
        $type = $this->api->executeReturnData($data[0], $player);
        $block = $this->event->getItem()->getId() . "-" . $this->event->getItem()->getDamage();
        if ($block == $type) {
            return $this->doSubCommand2($data[1]);
        } else {
            return $this->doSubCommand2($data[2]);
        }
    }

    public function setDropItems($data) {
        if (!$this->event instanceof BlockBreakEvent)
            return false;
        $data = explode(",", $data);
        $player = $this->event->getPlayer();
        $itemq = [];
        foreach ($data as $item) {
            $its = $this->executeItem($this->api->executeReturnData($item, $player));
            if ($its === null) {
                return false;
            }
            $itemq[] = $its;
        }
        $this->event->setDrops($itemq);
        return true;
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
                    $this->sendMsgPacket($this->player, $cmd[1], 0);
                    return true;
                case "tip":
                case "提示":
                    $this->sendMsgPacket($this->player, $cmd[1], 1);
                    return true;
                case "popup":
                case "底部":
                    $this->sendMsgPacket($this->player, $cmd[1], 2);
                    return true;
                case "结束":
                case "end":
                    return "end";
                case "pass":
                    return true;
                default:
                    return false;
            }
        }
        return false;
    }
}