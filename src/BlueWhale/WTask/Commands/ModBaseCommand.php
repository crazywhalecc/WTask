<?php

namespace BlueWhale\WTask\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use BlueWhale\WTask\WTask;

class ModBaseCommand extends Command
{
    private $plugin;
    public $cmdHelp = array();
    private $mainHelp;
    private $cmd;

    public function __construct(WTask $plugin) {
        $desc = $plugin->getData("command", "ModBaseCommand");
        if (!isset($desc["multiple"]))
            parent::__construct($desc["command"], $desc["description"]);
        else {
            parent::__construct($desc["command"], $desc["description"], null, $desc["multiple"]);
        }
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->plugin = $plugin;
        $this->cmd = $desc["command"];
        $c = $this->cmd;
        $this->mainHelp = "§6======WTask模块系统======
§a/" . $c . " 开启 [模块名称]: §b开启一个模块
§a/" . $c . " 关闭 [模块名称]: §b关闭一个模块
§a/" . $c . " [list/列表]: §b查看已有的内置模块列表";
    }

    public function execute(CommandSender $sender, $label, array $args) {
        if (!$this->plugin->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        $list = $this->plugin->getMod()->getAll();
        if (isset($args[0])) {
            switch ($args[0]) {
                case "开启":
                    if (isset($args[1])) {
                        $modname = $args[1];
                        if (isset($list[$modname])) {
                            $list[$modname]["status"] = true;
                            $this->plugin->getMod()->setAll($list);
                            $this->plugin->getMod()->save();
                            $this->plugin->registerMod($modname);
                            $sender->sendMessage("§a[WTask] 成功启用模块 $modname ! ( 如果开启无效请重启服务器生效！)");
                            return true;
                        } else {
                            $sender->sendMessage("§e[WTask] 对不起，这个名字的模块不存在！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage($this->mainHelp);
                        return true;
                    }
                case "list":
                case "列表":
                    $sender->sendMessage("§6=====WTask模块列表=====");
                    $dat = $this->plugin->getMod()->getAll();
                    foreach ($dat as $mname => $mdata) {
                        $enable = ($mdata["status"] == true) ? "§a" : "§c";
                        if (!isset($mdata["command"]))
                            $command = "无需指令";
                        else
                            $command = $mdata["command"];
                        $desc = $mdata["description"];
                        $sender->sendMessage($enable . "[" . $mname . "]: $desc " . (!isset($mdata["command"]) ? "" : "(指令: $command )"));
                    }
                    return true;
                case "关闭":
                    if (isset($args[1])) {
                        $modname = $args[1];
                        if (isset($list[$modname])) {
                            $list[$modname]["status"] = false;
                            $this->plugin->getMod()->setAll($list);
                            $this->plugin->getMod()->save();
                            $sender->sendMessage("§a[WTask] 成功关闭模块 $modname !\n§b重庆服务器后生效模块！");
                            return true;
                        } else {
                            $sender->sendMessage("§e[WTask] 对不起，这个名字的模块不存在！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage($this->mainHelp);
                        return true;
                    }
                case "eval":
                    $i = 1;
                    $ii = [];
                    while (isset($args[$i])) {
                        $ii[] = $args[$i];
                        $i++;
                    }
                    $line = implode(" ", $ii);
                    eval($line);
                    return true;
            }
        } else {
            $sender->sendMessage($this->mainHelp);
            return true;
        }
        return false;
    }
}