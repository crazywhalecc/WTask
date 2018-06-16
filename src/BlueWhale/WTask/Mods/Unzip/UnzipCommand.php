<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 上午11:46
 */

namespace BlueWhale\WTask\Mods\Unzip;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class UnzipCommand extends Command
{
    private $mod;
    private $cmd;

    public function __construct(Unzip $mod, array $desc) {
        $this->mod = $mod;
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
    }

    public function execute(CommandSender $sender, $label, array $args)//解析
    {
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0]) && isset($args[1])) {
            $path = $args[1];
            $filename = $args[0];
            if (file_exists($this->mod->getWTask()->getDataFolder() . "Mods/Unzip/" . $filename)) {
                $zip = new Unzipper();
                $test = $zip->extract("plugins/WTask/Mods/Unzip/" . $filename, $path);
                if ($test !== false) {
                    //$zip->extractTo($path);
                    $zip->close();
                    $sender->sendMessage("§a成功解压压缩包 $filename 到路径 $path 中！");
                    return true;
                } else {
                    $sender->sendMessage("解包错误! 错误代码: $test ");
                    return true;
                }
            } else {
                $sender->sendMessage("§c[WTools] 对不起，文件不存在！");
                return true;
            }
        } else {
            $sender->sendMessage("§e[WTools] 用法: /" . $this->cmd . " [文件名] [路径]");
            return true;
        }
    }
}