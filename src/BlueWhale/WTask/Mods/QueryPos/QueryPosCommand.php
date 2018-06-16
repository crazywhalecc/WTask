<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 上午11:42
 */

namespace BlueWhale\WTask\Mods\QueryPos;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;

class QueryPosCommand extends Command
{
    private $mod;
    private $cmd;

    public function __construct(QueryPos $mod, array $desc) {
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
        $this->mod = $mod;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) {
        if (method_exists($this, "scanPermission")) {
            if (!$this->scanPermission($sender))
                return false;
        } elseif (method_exists($this, "testPermission")) {
            if (!$this->testPermission($sender))
                return false;
        }
        if (!$sender instanceof Player) {
            $sender->sendMessage("请在游戏内查询坐标！");
            return true;
        }
        $pos = $sender->getName();
        if (!(in_array($pos, $this->mod->locations))) {
            $this->mod->locations[] = $pos;
            $sender->sendMessage("§a开启坐标查询模式。 再次输入/" . $this->cmd . "退出查询模式");
            return true;
        } else {
            $fr = array_search($pos, $this->mod->locations);
            unset($this->mod->locations[$fr]);
            $sender->sendMessage("§b关闭坐标查询模式");
            return true;
        }
    }
}