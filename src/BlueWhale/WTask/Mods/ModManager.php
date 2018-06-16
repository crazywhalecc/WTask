<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 上午10:58
 */

namespace BlueWhale\WTask\Mods;


use BlueWhale\WTask\WTask;

class ModManager
{
    private $plugin;

    public function __construct(WTask $plugin) {
        $this->plugin = $plugin;
    }

    public function enableMod(ModBase $mod) {
        $mod->onEnable();
    }
}