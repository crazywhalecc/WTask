<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 上午11:06
 */

namespace BlueWhale\WTask\Mods\BossBar;


use BlueWhale\WTask\ScheduleTasks\CallbackTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class BossBarCommand extends Command
{
    private $mod;
    private $cmd;
    private $plugin;
    private $helpMsg;

    public function __construct(BossBar $mod, array $desc) {
        $this->mod = $mod;
        $this->plugin = $mod->getWTask();
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
        $this->helpMsg = "§6=====WTask-BossBar帮助=====\n§a/" . $this->cmd . " 动态 [true/false]: §b开启或关闭全局动态血量条文本\n§a/" . $this->cmd . " 动态 内容 [内容]: §b设置顶部动态显示条的内容\n§a/" . $this->cmd . " 动态 百分比 [百分比值]: §b设置显示的动态顶部显示条的百分比\n§a/" . $this->cmd . " 广播: §b广播消息，通过广播条";
    }

    public function execute(CommandSender $sender, $label, array $args) {
        if (!$this->plugin->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0])) {
            switch ($args[0]) {
                case "动态":
                    if (!$sender->isOp()) {
                        $sender->sendMessage("你没有使用此指令的权限！");
                        return true;
                    }
                    if (isset($args[1])) {
                        switch ($args[1]) {
                            case "true":
                                $dat = $this->mod->getConfig()->get("动态bar");
                                $dat["开关"] = true;
                                $this->mod->getConfig()->set("动态bar", $dat);
                                $this->mod->getConfig()->save();
                                $this->mod->repeatSwitch = true;
                                $sender->sendMessage("§a[WTask] 成功开启全局动态顶部条~");
                                return true;
                            case "false":
                                $dat = $this->mod->getConfig()->get("动态bar");
                                $dat["开关"] = false;
                                $this->mod->getConfig()->set("动态bar", $dat);
                                $this->mod->getConfig()->save();
                                $this->mod->repeatSwitch = false;
                                $sender->sendMessage("§a[WTask] 成功开启关闭动态顶部条~如需彻底关闭请重启服务器！");
                                return true;
                            case "内容":
                                if (isset($args[2])) {
                                    $neirong = $args[2];
                                    $dat = $this->mod->getConfig()->get("动态bar");
                                    $dat["内容"] = $neirong;
                                    $this->mod->getConfig()->set("动态bar", $dat);
                                    $this->mod->getConfig()->save();
                                    $sender->sendMessage("§a[WTask] 成功设置顶部显示条内容！");
                                    return true;
                                } else {
                                    $sender->sendMessage("§e[WTask] 请输入内容！");
                                    return true;
                                }
                            case "百分比":
                                if (isset($args[2])) {
                                    $neirong = $args[2];
                                    $dat = $this->mod->getConfig()->get("动态bar");
                                    $dat["百分比"] = $neirong;
                                    $this->mod->getConfig()->set("动态bar", $dat);
                                    $this->mod->getConfig()->save();
                                    $sender->sendMessage("§a[WTask] 成功设置顶部显示条的显示百分比！");
                                    return true;
                                } else {
                                    $sender->sendMessage("§e[WTask] 用法： /" . $this->cmd . " 动态 百分比 [百分比值]");
                                    return true;
                                }
                            default:
                                $sender->sendMessage($this->helpMsg);
                                return true;
                        }
                    } else {
                        $sender->sendMessage($this->helpMsg);
                        return true;
                    }
                case "reload":
                    if (!$sender->isOp()) {
                        $sender->sendMessage("你没有使用此指令的权限！");
                        return true;
                    }
                    $this->mod->getConfig()->reload();
                    $sender->sendMessage("[WTask] 成功重载数据！");
                    return true;
                case "广播":
                    if (isset($args[1])) {
                        $msg = $args[1];
                        $this->mod->broadcastMsg = $msg;
                        $this->mod->currentBroadcastPlayer = $sender->getName();
                        $this->plugin->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "clearBroadcastMessage"]), $this->mod->getConfig()->get("固定bar")["broadcast"]["time"] * 20);
                        $sender->sendMessage("发送广播成功！");
                        return true;
                    } else {
                        $sender->sendMessage("§e用法： /" . $this->cmd . " 广播 <消息>");
                        return true;
                    }
                default:
                    if (!$sender->isOp()) {
                        $sender->sendMessage("§e用法： /" . $this->cmd . " 广播 <消息>");
                        return true;
                    }
                    $sender->sendMessage($this->helpMsg);
                    return true;
            }
        } else {
            if (!$sender->isOp()) {
                $sender->sendMessage("§e用法： /" . $this->cmd . " 广播 <消息>");
                return true;
            }
            $sender->sendMessage($this->helpMsg);
            return true;
        }
    }
}