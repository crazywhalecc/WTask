<?php

namespace BlueWhale\WTask\Commands;

use BlueWhale\WTask\NormalTaskAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use BlueWhale\WTask\WTask;

class CustomCommand extends Command
{
    private $plugin;
    public $setting;
    public $defaultHelp;
    public $api;
    public $cmd;
    public $desc;

    public function __construct(WTask $plugin, array $data)//构造
    {
        $desc = $data;
        if (!isset($desc["multiple"]))
            parent::__construct($desc["command"], $desc["description"]);
        else {
            parent::__construct($desc["command"], $desc["description"], null, $desc["multiple"]);
        }
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->plugin = $plugin;
        $this->api = $this->plugin->api;
        $this->cmd = $desc["command"];
        $this->setting = $desc["setting"];
        $this->defaultHelp = $desc["default"];
        $this->desc = $desc;
    }

    public function execute(CommandSender $sender, $label, array $args)//解析
    {
        if (!$this->plugin->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0])) {
            if (isset($this->setting[$args[0]])) {
                $i = 1;
                $data = [];
                while (isset($args[$i])) {
                    $data[] = $args[$i];
                    $i++;
                }
                if ($data != []) {
                    if (isset($this->desc["cover"])) {
                        if ($this->desc["cover"] === true) {
                            $t = new NormalTaskAPI($sender, $this->plugin->api);
                            foreach ($data as $q => $m) {
                                $t->writePrivateData($this->cmd . $q . "|" . $m);
                            }
                        }
                    }
                }
                if (substr($this->setting[$args[0]], 0, 1) == "/")
                    $this->plugin->getServer()->dispatchCommand($sender, str_replace("%p", $sender->getName(), substr($this->setting[$args[0]], 1)));
                elseif (substr($this->setting[$args[0]], 0, 1) == "*") {
                    $this->api->preNormalTask(substr($this->setting[$args[0]], 1), $sender);
                    return true;
                } else
                    $sender->sendMessage($this->api->msgs($this->setting[$args[0]], $sender));
                return true;
            } else {
                $sender->sendMessage($this->api->msgs($this->defaultHelp, $sender));
                return true;
            }
        } else {
            if (substr($this->defaultHelp, 0, 1) == "/") {
                $this->plugin->getServer()->dispatchCommand($sender, str_replace("%p", $sender->getName(), substr($this->defaultHelp, 1)));
            } elseif (substr($this->defaultHelp, 0, 1) == "*") {
                $this->api->preNormalTask(substr($this->defaultHelp, 1), $sender);
            } else
                $sender->sendMessage($this->api->msgs($this->defaultHelp, $sender));
            return true;
        }
    }
}