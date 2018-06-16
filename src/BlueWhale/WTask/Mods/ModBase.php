<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 上午10:43
 */

namespace BlueWhale\WTask\Mods;


use BlueWhale\WTask\WTask;
use pocketmine\Server;

abstract class ModBase implements Mod
{
    public function onEnable() {

    }

    /**
     * @return Server
     */
    public final function getServer() {
        return Server::getInstance();
    }

    /**
     * @return WTask
     */
    public final function getWTask() {
        return WTask::getInstance();
    }

    public final function getCommandMap() {
        return Server::getInstance()->getCommandMap();
    }

    public final function isEnabled() {
        return true;
    }
}