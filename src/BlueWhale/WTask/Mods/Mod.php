<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 上午10:42
 */

namespace BlueWhale\WTask\Mods;


interface Mod
{

    /**
     * Called when the plugin is enabled
     */
    public function onEnable();

    /**
     * @return \pocketmine\Server
     */
    public function getServer();

    public function getWTask();

}