<?php

namespace BlueWhale\WTask\Mods\CrazyBlock;

use BlueWhale\WTask\Mods\ModBase;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use BlueWhale\WTask\Config;
use pocketmine\event\player\PlayerMoveEvent;

class CrazyBlock extends ModBase implements Listener
{
    public $plugin;
    const VERSION = "1.0.0";
    const NAME = "CrazyBlock";
    public $cb;
    public $cmd;
    public $tempData;
    public $tempData2;
    public $moveListener = null;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("CrazyBlock");
        $this->getServer()->getCommandMap()->register("WTask", new CrazyBlockCommand($this, $desc));
        $this->getServer()->getPluginManager()->registerEvents($this, $this->getWTask());
        @mkdir($this->plugin->getDataFolder() . "Mods/CommandBlock/");
        $this->cb = new Config($this->plugin->getDataFolder() . "Mods/CommandBlock/" . "CommandBlock.yml", Config::YAML, array(
            "CB" => array(),
            "cb-protect" => true,
            "CT" => array()
        ));

        if ($this->cb->get("CT") != []) {
            $this->moveListener = new MoveListener($this);
        }
    }

    public function onBreak(BlockBreakEvent $event) {
        $bl = $this->getCb()->get("CB");
        $pig = $event;
        $b = $pig->getBlock();
        $p = $event->getPlayer();
        foreach ($bl as $bname => $link) {
            if ($link["X"] == $b->getX() and $link["Y"] == $b->getY() and $link["Z"] == $b->getZ() and $link["level"] == $b->getLevel()->getFolderName()) {
                if ($p->isOp()) {
                    unset($bl[$bname]);
                    $this->getCb()->set("CB", $bl);
                    $this->getCb()->save();
                    $p->sendMessage("§a成功删除这个点击方块执行命令！");
                    break;
                } else {
                    if ($this->getCb()->get("cb-protect") === true)
                        $event->setCancelled();
                }
                break;
            }
        }
    }

    public function onTouch(PlayerInteractEvent $pig) {
        $bl = $this->getCb()->get("CB");
        $b = $pig->getBlock();
        $p = $pig->getPlayer();
        $pn = $p->getName();
        foreach ($bl as $link) {
            if ($link["X"] == $b->getX() and $link["Y"] == $b->getY() and $link["Z"] == $b->getZ() and $link["level"] == $b->getLevel()->getFolderName()) {
                $cmddd = str_replace("%p", $pn, $link["command"]);
                $this->getServer()->getPluginManager()->callEvent($ev = new PlayerCommandPreprocessEvent($p, "/" . $cmddd));
                if ($ev->isCancelled()) {
                    continue;
                }
                $this->getServer()->dispatchCommand($ev->getPlayer(), substr($ev->getMessage(), 1));
                $time = 1;
            }
        }
        if (isset($time))
            return true;
        if (isset($this->tempData[$pn])) {
            $data = $this->getCb()->get("CB");
            $data[$this->tempData[$pn]["name"]]["command"] = $this->tempData[$pn]["command"];
            $data[$this->tempData[$pn]["name"]]["X"] = $b->x;
            $data[$this->tempData[$pn]["name"]]["Y"] = $b->y;
            $data[$this->tempData[$pn]["name"]]["Z"] = $b->z;
            $data[$this->tempData[$pn]["name"]]["level"] = $b->level->getFolderName();
            $this->getCb()->set("CB", $data);
            $this->getCb()->save();
            $p->sendMessage("§a成功添加点击方块执行命令！");
            $pig->setCancelled(true);
            unset($this->tempData[$pn]);
            return true;
        }
        if (isset($this->tempData2[$pn])) {
            if ($this->moveListener === null) {
                $this->moveListener = new MoveListener($this);
            }
            $data = $this->getCb()->get("CT");
            $data[$this->tempData2[$pn]["name"]]["command"] = $this->tempData2[$pn]["command"];
            $data[$this->tempData2[$pn]["name"]]["X"] = $b->x;
            $data[$this->tempData2[$pn]["name"]]["Y"] = $b->y;
            $data[$this->tempData2[$pn]["name"]]["Z"] = $b->z;
            $data[$this->tempData2[$pn]["name"]]["level"] = $b->level->getFolderName();
            $this->getCb()->set("CT", $data);
            $this->getCb()->save();
            $p->sendMessage("§a成功添加站在方块上执行命令！");
            $pig->setCancelled(true);
            unset($this->tempData2[$pn]);
            return true;
        }
        return false;
    }

    public function onMove(PlayerMoveEvent $event) {
        $p = $event->getPlayer();
        $list = $this->getCb()->get("CT");
        $b = $event->getPlayer()->getLevel()->getBlock($event->getPlayer()->floor()->subtract(0, 1));
        foreach ($list as $data) {
            if ($data["X"] == $b->getX() && $data["Y"] == $b->getY() && $data["Z"] == $b->getZ() && $data["level"] == $b->level->getFolderName()) {
                $this->getServer()->dispatchCommand($p, str_replace("%p", $p->getName(), $data["command"]));
            }
        }
    }

    /**
     * @return mixed
     */
    public function getCb(): Config {
        return $this->cb;
    }
}