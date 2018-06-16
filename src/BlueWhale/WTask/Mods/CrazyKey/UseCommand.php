<?php

namespace BlueWhale\WTask\Mods\CrazyKey;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;

class UseCommand extends Command
{
    private $mod;
    public $cmd;

    public function __construct(CrazyKey $mod) {
        $desc = $mod->getCommand()->get("UseCommand");
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->mod = $mod;
        $this->cmd = $desc["command"];
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)//解析
    {
        if (method_exists($this, "scanPermission")) {
            if (!$this->scanPermission($sender))
                return false;
        } elseif (method_exists($this, "testPermission")) {
            if (!$this->testPermission($sender))
                return false;
        }
        if (isset($args[0])) {
            $code = $args[0];
            $check = $this->mod->checkCode($code);
            if (is_numeric($check)) {
                $d = $this->mod->getCard()->get("card");
                $d[$check]["use-time"]++;
                $c = $d[$check]["cmd"];
                $this->mod->getCard()->set("card", $d);
                $this->mod->getCard()->save();
                $this->mod->getServer()->dispatchCommand(new ConsoleCommandSender(), str_replace("%p", $sender->getName(), $c));
                $sender->sendMessage($this->mod->getCard()->get("use-msg"));
                return true;
            } elseif ($check == "used") {
                $sender->sendMessage("§e 对不起，这个卡密已经使用过了！");
                return true;
            } elseif ($check == false) {
                $sender->sendMessage("§c 对不起，不存在这个卡密！");
                return true;
            }
            return true;
        } else {
            $sender->sendMessage("§6用法: /" . $this->cmd . " [卡密]");
            return true;
        }
    }
}