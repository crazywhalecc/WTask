<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 下午12:59
 */

namespace BlueWhale\WTask\Mods\WProtect;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class WProtectCommand extends Command
{
    private $mod;
    private $cmd;
    private $mainHelp;

    public function __construct(WProtect $mod, array $desc) {
        $this->mod = $mod;
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
        $c = $this->cmd;
        $this->mainHelp = "§6==WProtect==\n§a/" . $c . " protect: §b添加或删除一个保护的地图\n§a/" . $c . " pvp: §b添加或删除一个禁止pvp的世界\n§a/" . $c . " 禁止流动: §b开启或关闭禁止液体流动\n§a/" . $c . "更改op权限: §b开启或关闭op的破坏世界权限";
    }

    public function execute(CommandSender $sender, $label, array $args) {
        if (!$this->mod->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0])) {
            switch ($args[0]) {
                case "protect":
                    if (isset($args[1])) {
                        $world = $args[1];
                        $list = $this->mod->getConfig()->get("worlds");
                        if (in_array($world, $this->mod->getConfig()->get("worlds"))) {
                            $inv = array_search($world, $list);
                            array_splice($list, $inv, 1);
                            $this->mod->getConfig()->set("worlds", $list);
                            $this->mod->getConfig()->save();
                            $sender->sendMessage("§a成功删除地图 $world 的保护！");
                            return true;
                        } else {
                            $list[] = $world;
                            $this->mod->getConfig()->set("worlds", $list);
                            $this->mod->getConfig()->save();
                            $sender->sendMessage("§a成功添加地图 $world 的保护！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§e[WProtect] 用法: /" . $this->cmd . " protect [地图名]");
                        return true;
                    }
                case "pvp":
                    if (isset($args[1])) {
                        $world = $args[1];
                        $list = $this->mod->getConfig()->get("pvp");
                        if (in_array($world, $this->mod->getConfig()->get("pvp"))) {
                            $inv = array_search($world, $list);
                            array_splice($list, $inv, 1);
                            $this->mod->config->set("pvp", $list);
                            $this->mod->config->save();
                            $sender->sendMessage("§a成功删除地图 $world 的禁止pvp！");
                            return true;
                        } else {
                            $list[] = $world;
                            $this->mod->config->set("pvp", $list);
                            $this->mod->config->save();
                            $sender->sendMessage("§a成功添加地图 $world 的禁止pvp！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§e[WProtect] 用法: /" . $this->cmd . " pvp [地图名]");
                        return true;
                    }
                case "禁止流动":
                    if ($this->mod->config->get("ban-floating") === true) {
                        $this->mod->config->set("ban-floating", false);
                        $this->mod->config->save();
                        $sender->sendMessage("§a成功开启流动！");
                        return true;
                    } else {
                        $this->mod->config->set("ban-floating", true);
                        $this->mod->config->save();
                        $sender->sendMessage("§a成功禁止流动！");
                        return true;
                    }
                case "更改op权限":
                    if ($this->mod->config->get("op-master") === true) {
                        $this->mod->config->set("op-master", false);
                        $this->mod->config->save();
                        $sender->sendMessage("§a成功关闭op的权限！");
                        return true;
                    } else {
                        $this->mod->config->set("op-master", true);
                        $this->mod->config->save();
                        $sender->sendMessage("§a成功开启op的权限！");
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