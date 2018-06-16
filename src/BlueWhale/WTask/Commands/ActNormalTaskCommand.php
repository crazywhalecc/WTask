<?php

namespace BlueWhale\WTask\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use BlueWhale\WTask\WTask;
use BlueWhale\WTask\NormalTaskAPI;

class ActNormalTaskCommand extends Command
{
    private $plugin;
    public $cmdHelp = array();
    private $mainHelp = "";
    public $api;
    public $cmd;

    public function __construct(WTask $plugin)//构造
    {
        $desc = $plugin->getData("command", "ActNormalTaskCommand");
        if (!isset($desc["multiple"]))
            parent::__construct($desc["command"], $desc["description"]);
        else {
            parent::__construct($desc["command"], $desc["description"], null, $desc["multiple"]);
        }
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->plugin = $plugin;
        $this->api = $plugin->api;
        $this->cmd = $desc["command"];
        $c = $this->cmd;
        $this->mainHelp = "§6======WTask======\n§a/" . $c . " [任务名称]: §b运行普通任务";
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)//解析
    {
        if (!$this->plugin->isEnabled())
            return false;
        if (isset($args[0])) {
            $taskname = $args[0];
            if (!$this->api->isTaskExists($taskname)) {
                $sender->sendMessage($this->plugin->getData("msg", "task-not-exist"));
                return true;
            } else {
                $daty = $this->api->getTaskData($taskname);
                if ($daty["type"] == "循环任务") {
                    if ($this->plugin->getPerm($sender) < 200) {
                        $sender->sendMessage("你没有权限激活循环任务！");
                        return true;
                    }
                    $this->plugin->preRepeatTask($taskname);
                    $sender->sendMessage("成功运行循环任务！");
                    return true;
                }
                if (isset($daty["权限"])) {
                    $perm = $daty["权限"];
                    if ($this->plugin->getPerm($sender) < $perm) {
                        $sender->sendMessage("§c你没有权限运行这个任务！");
                        return true;
                    }
                }
                if (isset($daty["ban-cmd-run"])) {
                    if ($daty["ban-cmd-run"] === true) {
                        if (!$sender->isOp()) {
                            $sender->sendMessage("§c[WTask] 对不起，这个任务不能通过指令运行！");
                            return true;
                        }
                    }
                }
                //echo "\n成功，即将预运行任务！";
                $api = new NormalTaskAPI($sender, $this->plugin->api);
                if (isset($args[1])) {
                    $i = 1;
                    while (isset($args[$i])) {
                        $api->writePrivateData($taskname . $i . "|" . $args[$i]);
                        $i++;
                    }
                }
                $result = $this->api->preNormalTask($taskname, $sender);
                //echo "成功运行任务！";
                if ($result !== true) {
                    $sender->sendMessage("§c运行任务失败！");
                    return true;
                }
                return true;
            }
        } else {
            $sender->sendMessage($this->mainHelp);
            return true;
        }
    }
}