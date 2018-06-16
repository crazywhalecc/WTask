<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/8/16
 * Time: 下午9:13
 */

namespace BlueWhale\WTask\Mods\CrazyBlock;


use pocketmine\event\Listener;

class MoveListener implements Listener
{
    public $mod;
    public $plugin;

    public function __construct(CrazyBlock $mod) {
        $this->mod = $mod;
        $this->plugin = $mod->getWTask();
        try {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        } catch (\Throwable $e) {
        }
        $this->plugin->getServer()->getLogger()->info("成功创建移动监听器！");
    }


}