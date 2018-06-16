<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 下午1:05
 */

namespace BlueWhale\WTask\Mods\WRobot;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class WRobotCommand extends Command
{
    private $mod;
    private $cmd;
    private $mainHelp;

    public function __construct(WRobot $mod, array $desc) {
        $this->mod = $mod;
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
        $c = $this->cmd;
        $this->mainHelp = "§6=====WRobot智能机器人=====\n§a/" . $c . " <true/false>: §b开启或关闭机器人\n§a/" . $c . " chat [文本]: §b直接聊天";
    }

    public function execute(CommandSender $sender, $label, array $args)//解析
    {
        if (!$this->mod->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0])) {
            switch ($args[0]) {
                case "true":
                    $this->mod->status = true;
                    $this->mod->config->set("status", true);
                    $this->mod->config->save();
                    $sender->sendMessage("§6[WRobot] 成功开启智能聊天机器人！");
                    return true;
                case "false":
                    $this->mod->status = false;
                    $this->mod->config->set("status", false);
                    $this->mod->config->save();
                    $sender->sendMessage("§6[WRobot] 成功关闭智能聊天机器人！");
                    return true;
                case "chat":
                    if (isset($args[1])) {
                        $st = $args[1];
                        if ($this->mod->config->get("消息处理方式") == "direct") {
                            $sender->sendMessage($this->mod->config->get("文本前缀") . $this->mod->getTopMessageDirect($st));
                            return true;
                        } elseif ($this->mod->config->get("消息处理方式") == "async") {
                            $this->mod->callAsyncTask($sender, $st, $this->mod->config->get("文本前缀"));
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§6[WRobot] 用法: /" . $this->cmd . " chat <发送的消息>");

                    }
                    return true;
                case "setkey":
                    if (!$sender->isOp()) {
                        $sender->sendMessage("你没有更换key的权限！");
                        return true;
                    }
                    if (isset($args[1])) {
                        $key = $args[1];
                        $this->mod->config->set("key", $key);
                        $this->mod->config->save();
                        $sender->sendMessage("§a[WRobot] 成功更换机器人的key！");
                        return true;
                    } else {
                        $sender->sendMessage("§e[WRobot] 用法： /" . $this->cmd . " setkey [图灵API]");
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