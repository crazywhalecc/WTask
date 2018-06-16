<?php

namespace BlueWhale\WTask\Commands;

use BlueWhale\WTask\NormalTaskAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use BlueWhale\WTask\WTask;

class SetTempCommand extends Command
{
    private $plugin;
    private $cmd;
    private $mytype;

    public function __construct(WTask $plugin)//构造
    {
        $desc = $plugin->getData("command", "SetTempCommand");
        if (!isset($desc["multiple"]))
            parent::__construct($desc["command"], $desc["description"]);
        else {
            parent::__construct($desc["command"], $desc["description"], null, $desc["multiple"]);
        }
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->plugin = $plugin;
        $this->cmd = $desc["command"];
        $this->mytype = $desc["setting"];
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)//解析
    {
        if (!$this->plugin->isEnabled())
            return false;
        if (method_exists($this, "scanPermission")) {
            if (!$this->scanPermission($sender))
                return false;
        } elseif (method_exists($this, "testPermission")) {
            if (!$this->testPermission($sender))
                return false;
        }
        if (isset($args[0]) && isset($args[1])) {
            $tempname = $args[0];
            $i = 1;
            $msg = [];
            while (isset($args[$i])) {
                $msg[] = $args[$i];
                $i++;
            }
            $msg = implode(" ", $msg);
            switch ($this->mytype) {
                case "private":
                    $this->plugin->privateTempData[$sender->getName()][$tempname] = $this->plugin->api->executeReturnData($msg, $sender);
                    break;
                case "public":
                    $ts = new NormalTaskAPI($sender, $this->plugin->api);
                    $ts->writePublicData($tempname . "|" . $msg);
                    break;
            }
            $sender->sendMessage("Successfully added data!");
            return true;
        }
        return false;
    }
}