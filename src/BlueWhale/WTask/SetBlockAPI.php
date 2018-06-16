<?php

namespace BlueWhale\WTask;

use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\math\Vector3;

class SetBlockAPI
{
    public $player;
    public $buffer;
    public $api;
    public $plugin;

    public function __construct($buffer = null, $p = null, WTaskAPI $api) {
        $this->api = $api;
        $this->plugin = $api->plugin;
        $this->player = $p;
        $this->buffer = $buffer;
    }

    public function fillBlock($blockPos, $Block)//填充方块
    {
        $blockPos = explode(",", $blockPos);
        $blockPos[0] = $this->api->executeReturnData($blockPos[0], $this->player);
        $blockPos[1] = $this->api->executeReturnData($blockPos[1], $this->player);
        $Block = $this->api->executeReturnData($Block, $this->player);
        $Block = explode("-", $Block);
        $ff = explode(":", $blockPos[0]);
        $level = Server::getInstance()->getLevelByName($ff[3]);
        unset($ff);
        $pos = $this->calculatePosition($blockPos[0], $blockPos[1]);
        for ($x = $pos[0][0]; $x <= $pos[1][0]; $x++) {
            for ($y = $pos[0][1]; $y <= $pos[1][1]; $y++) {
                for ($z = $pos[0][2]; $z <= $pos[1][2]; $z++) {
                    $level->setBlock(new Vector3($x, $y, $z), Block::get($Block[0], $Block[1]));
                }
            }
        }
        return true;
    }

    public function copyBlock($blockPos)//复制方块
    {
        $blockPos = explode(",", $blockPos);
        $blockPos[0] = $this->api->executeReturnData($blockPos[0], $this->player);
        $blockPos[1] = $this->api->executeReturnData($blockPos[1], $this->player);
        $ff = explode(":", $blockPos[0]);
        $level = Server::getInstance()->getLevelByName($ff[3]);
        $this->plugin->pasteTempData = [];
        unset($ff);
        $pos = $this->calculatePosition($blockPos[0], $blockPos[1]);
        for ($x = $pos[0][0]; $x <= $pos[1][0]; $x++) {
            for ($y = $pos[0][1]; $y <= $pos[1][1]; $y++) {
                for ($z = $pos[0][2]; $z <= $pos[1][2]; $z++) {
                    $bl = $level->getBlock(new Vector3($x, $y, $z));
                    $this->plugin->pasteTempData[] = array($x - $pos[0][0], $y - $pos[0][1], $z - $pos[0][2], $bl->getId(), $bl->getDamage());
                }
            }
        }
        return true;
    }

    public function pasteBlock($blockPos)//粘贴方块
    {
        $blockPos = explode(":", $this->api->executeReturnData($blockPos, $this->player));
        $ID = 0;
        while (isset(WTask::getInstance()->pasteTempData[$ID]))
            $ID++;
        $finalBlock = WTask::getInstance()->pasteTempData[$ID - 1];
        $level = Server::getInstance()->getLevelByName($blockPos[3]);
        $this->plugin->recoveryTempData = [];
        for ($px = 0; $px <= $finalBlock[0]; $px++) {
            for ($py = 0; $py <= $finalBlock[1]; $py++) {
                for ($pz = 0; $pz <= $finalBlock[2]; $pz++) {
                    $bl = $level->getBlock(new Vector3($blockPos[0] + $px, $blockPos[1] + $py, $blockPos[2] + $pz));
                    $this->plugin->recoveryTempData[] = array($bl->x, $bl->y, $bl->z, $bl->level->getFolderName(), $bl->getId(), $bl->getDamage());
                }
            }
        }
        Server::getInstance()->getLogger()->info("成功保存原来的数据！");
        unset($px, $py, $pz);

        for ($px = 0; $px <= $finalBlock[0]; $px++) {
            for ($py = 0; $py <= $finalBlock[1]; $py++) {
                for ($pz = 0; $pz <= $finalBlock[2]; $pz++) {
                    $block = $this->getBlockIds($px, $py, $pz);
                    $level->setBlock(new Vector3($blockPos[0] + $px, $blockPos[1] + $py, $blockPos[2] + $pz), Block::get($block[0], $block[1]));
                    //$bl=$level->getBlock(new Vector3($blockPos[0]+$x,$blockPos[1]+$y,$blockPos[2]+$z));
                    //WTask::getInstance()->recoveryTempData[]=array($bl->x,$bl->y,$bl->z,$bl->level->getFolderName(),$bl->getId(),$bl->getDamage());
                }
            }
        }
    }

    public function getBlockIds($x, $y, $z)//获取对应复制的方块类型
    {
        foreach (WTask::getInstance()->pasteTempData as $blockID => $data) {
            if ($data[0] == $x && $data[1] == $y && $data[2] == $z) {
                return array($data[3], $data[4]);
            }
        }
        return array(0, 0);
    }

    public function returnPasteBlock() {
        if ($this->plugin->recoveryTempData == [])
            return false;
        $level = $this->plugin->recoveryTempData[0][3];
        $level = Server::getInstance()->getLevelByName($level);
        foreach ($this->plugin->recoveryTempData as $tempID => $data) {
            $level->setBlock(new Vector3($data[0], $data[1], $data[2]), Block::get($data[4], $data[5]));
        }
        $this->plugin->recoveryTempData = [];
        return true;
    }

    public function calculatePosition($p1, $p2)//重列坐标
    {
        $p1 = explode(":", $p1);
        $p2 = explode(":", $p2);
        $x1 = ($p1[0] >= $p2[0] ? $p2[0] : $p1[0]);
        $x2 = ($p1[0] >= $p2[0] ? $p1[0] : $p2[0]);
        $y1 = ($p1[1] >= $p2[1] ? $p2[1] : $p1[1]);
        $y2 = ($p1[1] >= $p2[1] ? $p1[1] : $p2[1]);
        $z1 = ($p1[2] >= $p2[2] ? $p2[2] : $p1[2]);
        $z2 = ($p1[2] >= $p2[2] ? $p1[2] : $p2[2]);
        $pos[0] = array($x1, $y1, $z1);
        $pos[1] = array($x2, $y2, $z2);
        return $pos;
    }
}