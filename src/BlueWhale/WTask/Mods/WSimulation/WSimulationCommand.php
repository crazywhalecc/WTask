<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 下午1:10
 */

namespace BlueWhale\WTask\Mods\WSimulation;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class WSimulationCommand extends Command
{
    private $mod;
    private $cmd;

    public function __construct(WSimulation $mod, array $desc) {
        $this->mod = $mod;
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)//解析
    {
        if (!$this->mod->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0]) && isset($args[1])) {
            if (substr($args[0], 0, 3) == "op:") {
                $playermode = 1;
                $args[0] = substr($args[0], 3);
            } else
                $playermode = 0;
            $player = $this->mod->getServer()->getPlayerExact($args[0]);
            array_shift($args);
            $cmd = implode(" ", $args);
            if ($player === null) {
                $sender->sendMessage("§e[WSimulation] 对不起，玩家不在线！");
                return true;
            }
            if ($playermode == 1) {
                $player->setOp(true);
                $this->mod->getServer()->dispatchCommand($player, $cmd);
                $player->setOp(false);
            } else {
                $this->mod->getServer()->dispatchCommand($player, $cmd);
            }
            $sender->sendMessage("§a[WSimulation] 成功模拟该玩家执行指令！");
            return true;
        } else {
            $sender->sendMessage("§e[WSimulation] 用法: /" . $this->cmd . " <玩家ID> <指令>\n§b如果想让目标玩家以op身份执行命令，请以 §dop:玩家ID §b的格式写玩家ID即可~");
            return true;
        }
    }
}