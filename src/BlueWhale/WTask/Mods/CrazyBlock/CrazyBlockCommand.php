<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 上午11:20
 */

namespace BlueWhale\WTask\Mods\CrazyBlock;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class CrazyBlockCommand extends Command
{
    private $mod;
    private $plugin;
    private $cmd;
    private $mainHelp;

    public function __construct(CrazyBlock $mod, array $desc) {
        $this->mod = $mod;
        $this->plugin = $this->mod->getWTask();
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
        $c = $this->cmd;
        $this->mainHelp = array(
            "§6=====CrazyBlock模块=====",
            "§a/" . $c . " touch: §b添加一个点击方块执行命令",
            "§a/" . $c . " del: §b删除一个点击方块执行命令",
            "§a/" . $c . " step: §b添加一个站在方块上执行命令"
        );
    }

    public function execute(CommandSender $sender, $label, array $args) {
        if (!$this->mod->getWTask()->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0])) {
            switch ($args[0]) {
                case "touch":
                    if (isset($args[1]) && isset($args[2])) {
                        $bname = $args[1];
                        $dat = $this->mod->getCb()->get("CB");
                        if (isset($dat[$bname])) {
                            $sender->sendMessage("§c对不起，此名称的方块已存在！");
                            return true;
                        }
                        $i = 2;
                        $cmd = [];
                        while (isset($args[$i])) {
                            $cmd[] = $args[$i];
                            $i++;
                        }
                        $cmd = implode(" ", $cmd);
                        $this->mod->tempData[$sender->getName()]["command"] = $cmd;
                        $this->mod->tempData[$sender->getName()]["name"] = $bname;
                        $sender->sendMessage("§a成功设置方块指令！请点击一个方块！");
                        return true;
                    } else {
                        $sender->sendMessage("§e用法: /" . $this->cmd . " touch <名称> <执行的指令>");
                        return true;
                    }
                case "step":
                    if (isset($args[1]) && isset($args[2])) {
                        $bname = $args[1];
                        $dat = $this->mod->getCb()->get("CT");
                        if (isset($dat[$bname])) {
                            $sender->sendMessage("§c对不起，此名称的方块已存在！");
                            return true;
                        }
                        $i = 2;
                        $cmd = [];
                        while (isset($args[$i])) {
                            $cmd[] = $args[$i];
                            $i++;
                        }
                        $cmd = implode(" ", $cmd);
                        $this->mod->tempData2[$sender->getName()]["command"] = $cmd;
                        $this->mod->tempData2[$sender->getName()]["name"] = $bname;
                        $sender->sendMessage("§a成功设置方块指令！请点击一个方块！");
                        return true;
                    } else {
                        $sender->sendMessage("§e用法: /" . $this->cmd . " step [名称] [执行的指令]");
                        return true;
                    }
                case "del":
                    if (isset($args[1]) && isset($args[2])) {
                        $name = $args[2];
                        switch ($args[1]) {
                            case "touch":
                                $dat = $this->mod->getCb()->get("CB");
                                if (isset($dat[$name])) {
                                    unset($dat[$name]);
                                    $this->mod->getCb()->set("CB", $dat);
                                    $this->mod->getCb()->save();
                                    $sender->sendMessage("§a成功删除方块执行指令！");
                                    return true;
                                } else {
                                    $sender->sendMessage("§e此名字的点击方块执行指令不存在！");
                                    return true;
                                }
                            case "step":
                                $dat = $this->mod->getCb()->get("CT");
                                if (isset($dat[$name])) {
                                    unset($dat[$name]);
                                    $this->mod->getCb()->set("CT", $dat);
                                    $this->mod->getCb()->save();
                                    $sender->sendMessage("§a成功删除方块执行指令！");
                                    return true;
                                } else {
                                    $sender->sendMessage("§e此名字的站立方块执行指令不存在！");
                                    return true;
                                }
                            default:
                                $sender->sendMessage("§e类型错误！");
                                return true;
                        }
                    } else {
                        $sender->sendMessage("§e[用法]: /" . $this->cmd . " del touch [名称]");
                        $sender->sendMessage("§e[用法]: /" . $this->cmd . " del step [名称]");
                        return true;
                    }
                default:
                    $sender->sendMessage(implode("\n", $this->mainHelp));
                    return true;
            }
        } else {
            $sender->sendMessage(implode("\n", $this->mainHelp));
            return true;
        }
    }
}