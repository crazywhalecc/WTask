<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/8/31
 * Time: 下午6:23
 */

namespace BlueWhale\WTask\Mods\WPhar\Commands;

use BlueWhale\WTask\Mods\WPhar\WPhar;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as T;

class PackCommand extends Command
{
    private $mod;
    private $cmd;

    public function __construct(WPhar $mod) {
        $this->mod = $mod;
        $desc = $this->mod->getCommands()->get("PackCommand");
        parent::__construct($desc["command"], $desc["description"]);
        $this->setPermission($desc["permission"]);
        $this->cmd = $desc["command"];
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if (!$this->mod->isEnabled()) {
            return false;
        }
        if (isset($args[0]) && isset($args[1])) {
            $dir = $args[0];
            $fileName = $args[1];
            if (!file_exists($dir) && $dir != "&&root") {
                $sender->sendMessage(T::ESCAPE . "c目标文件夹不存在，无法打包！");
                return true;
            }
            $r = $this->mod->packDir($dir, $fileName);
            if (!$r) {
                $sender->sendMessage(T::ESCAPE . "c打包失败！");
            }
            return true;
        } else {
            $sender->sendMessage(T::ESCAPE . "e用法： /" . $this->cmd . " [文件夹路径] [创建的文件名称]（服务器主目录输入 &&root ）");
            return true;
        }
    }
}