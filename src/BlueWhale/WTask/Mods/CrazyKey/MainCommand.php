<?php

namespace BlueWhale\WTask\Mods\CrazyKey;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class MainCommand extends Command
{
    private $mod;
    public $Usage;

    public function __construct(CrazyKey $mod) {
        $desc = $mod->getCommand()->get("MainCommand");
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->mod = $mod;
        $this->Usage = "§e==========\n§a/" . $desc["command"] . " [卡密使用次数] [运行的控制台指令]: §b添加一个卡密，按照格式会自动生成\n§7*  ps：指令中玩家名字可用%p代替哦";
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
        if (!$sender->isOp()) {
            $sender->sendMessage("§c[CrazyKey] 对不起，你不是op，不能设置卡密！");
            return true;
        }
        if (isset($args[0])) {
            switch ($args[0]) {
                case "添加卡密":
                    if (isset($args[1])) {
                        $useTime = $args[1];
                        if (isset($args[2])) {
                            $i = 2;
                            $scmd = [];
                            while (isset($args[$i])) {
                                $scmd[] = $args[$i];
                                $i++;
                            }
                            unset($i);
                            $rcmd = implode(" ", $scmd);
                            $ID = $this->mod->getCard()->get("ID");
                            $ID++;
                            $code = $this->mod->generateCode();
                            $cardlist = $this->mod->getCard()->get("card");
                            $cardlist[$ID] = array(
                                "key" => $code,
                                "use-time" => 0,
                                "all-time" => $useTime,
                                "cmd" => $rcmd
                            );
                            $this->mod->getCard()->set("ID", $ID);
                            $this->mod->getCard()->set("card", $cardlist);
                            $this->mod->getCard()->save();
                            $sender->sendMessage("§a 成功添加卡密！卡密使用次数为 $args[0] ,卡密在下一行, 或打开配置文件查看！");
                            $sender->sendMessage("§b* 你的卡密: §6" . $code);
                            return true;
                        }
                    }
            }
            $useTime = $args[0];
            if (isset($args[1])) {
                $i = 1;
                $scmd = [];
                while (isset($args[$i])) {
                    $scmd[] = $args[$i];
                    $i++;
                }
                unset($i);
                $rcmd = implode(" ", $scmd);
                $ID = $this->mod->getCard()->get("ID");
                $ID++;
                $code = $this->mod->generateCode();
                $cardlist = $this->mod->getCard()->get("card");
                $cardlist[$ID] = array(
                    "key" => $code,
                    "use-time" => 0,
                    "all-time" => $useTime,
                    "cmd" => $rcmd
                );
                $this->mod->getCard()->set("ID", $ID);
                $this->mod->getCard()->set("card", $cardlist);
                $this->mod->getCard()->save();
                $sender->sendMessage("§a 成功添加卡密！卡密使用次数为 $args[0] ,卡密在下一行, 或打开配置文件查看！");
                $sender->sendMessage("§b* 你的卡密: §6" . $code);
                return true;
            } else {
                $sender->sendMessage($this->Usage);
                return true;
            }
        } else {
            $sender->sendMessage($this->Usage);
            return true;
        }
    }
}