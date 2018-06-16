<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 下午12:31
 */

namespace BlueWhale\WTask\Mods\WChatCommand;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class MainCommand extends Command
{
    private $mod;
    private $cmd;
    private $mainHelp;

    public function __construct(WChatCommand $mod, array $desc) {
        $this->mod = $mod;
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
        $c = $this->cmd;
        $this->mainHelp = "§6==WChatCommand聊天执行命令==\n§a/" . $c . " add: §b添加一个聊天执行命令项\n§a/" . $c . " del: §b删除一个聊天执行命令项";
    }

    public function execute(CommandSender $sender, $label, array $args) {
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0])) {
            switch ($args[0]) {
                case "add":
                    if (isset($args[1])) {
                        $name = $args[1];
                        if (!$this->mod->getConfig()->exists($name)) {
                            $this->mod->getConfig()->set($name, array());
                            $this->mod->getConfig()->save(true);
                        }
                        if (isset($args[2])) {
                            $i = 3;
                            $cmd = $args[2];
                            while (isset($args[$i])) {
                                $cmd = $cmd . " " . $args[$i];
                                $i++;
                            }
                            $cfg = $this->mod->getConfig()->get($name);
                            if (in_array($cmd, $cfg)) {
                            } else {
                                $cfg[] = $cmd;
                                $this->mod->getConfig()->set($name, $cfg);
                                $this->mod->getConfig()->save();
                            }
                            $sender->sendMessage("§a[WChatCommand] 成功添加指令到 $name 的聊天文本中！你可以输入 $name 来激活一系列指令！");
                            return true;
                        } else {
                            $sender->sendMessage("§e[WChatCommand] 用法: /" . $this->cmd . " add <聊天内容> <执行的命令( 多条命令请添加多次)>");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§e[WChatCommand] 用法: /" . $this->cmd . " add <聊天内容> <执行的命令( 多条命令请添加多次)>");
                        return true;
                    }
                case "del":
                    if (isset($args[1])) {
                        $name = $args[1];
                        if ($this->mod->getConfig()->exists($name)) {
                            $this->mod->getConfig()->remove($name);
                            $this->mod->getConfig()->save(true);
                            $sender->sendMessage("§a[WChatCommand] 成功删除聊天执行命令系列 $name !");
                            return true;
                        } else {
                            $sender->sendMessage("§e[WChatCommand] 对不起，这个聊天文本的执行命令不存在！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§c[WChatCommand] 用法: /" . $this->cmd . " del <聊天内容>");
                        return true;
                    }
                default:
                    $sender->sendMessage($this->mainHelp);
                    return true;
            }
        } else {
            $sender->sendMessage($this->mainHelp);
            return true;
        }
    }
}