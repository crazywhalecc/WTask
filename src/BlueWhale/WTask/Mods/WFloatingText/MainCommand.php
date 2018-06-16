<?php
/**
 * Created by PhpStorm.
 * User: whale
 * Date: 2017/9/1
 * Time: 下午12:45
 */

namespace BlueWhale\WTask\Mods\WFloatingText;


use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\math\Vector3;
use pocketmine\Player;

class MainCommand extends Command
{
    private $cmd;
    private $mod;

    public function __construct(WFloatingText $mod, array $desc) {
        $this->mod = $mod;
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->cmd = $desc["command"];
    }

    public function execute(CommandSender $sender, $label, array $args) {
        if (isset($args[0])) {
            switch ($args[0]) {
                case "setdynpos":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("§c请在游戏内使用！");
                        return true;
                    }
                    $pos = $sender->x . ":" . $sender->y . ":" . $sender->z . ":" . $sender->level->getFolderName();
                    $th = $this->mod->getText()->get("动态显示");
                    $th["位置"] = $pos;
                    $this->mod->getText()->set("动态显示", $th);
                    $this->mod->getText()->save();
                    $sender->sendMessage("成功调整位置！");
                    return true;
                case "settoppos":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("§c请在游戏内使用！");
                        return true;
                    }
                    $pos = $sender->x . ":" . $sender->y . ":" . $sender->z . ":" . $sender->level->getFolderName();
                    $th = $this->mod->getText()->get("富豪榜");
                    $th["位置"] = $pos;
                    $this->mod->getText()->set("富豪榜", $th);
                    $this->mod->getText()->save();
                    $sender->sendMessage("成功调整位置！");
                    return true;
                case "settopmode":
                    if ($this->mod->text->get("富豪榜")["富豪榜模式"] == 1) {
                        $list = $this->mod->text->get("富豪榜");
                        $list["富豪榜模式"] = 2;
                        $this->mod->text->set("富豪榜", $list);
                        $this->mod->text->save();
                        $sender->sendMessage("§a成功切换到富豪榜模式2！如不能正常显示请再次运行此指令切换到1！");
                        return true;
                    } elseif ($this->mod->text->get("富豪榜")["富豪榜模式"] == 2) {
                        $list = $this->mod->text->get("富豪榜");
                        $list["富豪榜模式"] = 1;
                        $this->mod->text->set("富豪榜", $list);
                        $this->mod->text->save();
                        $sender->sendMessage("§a成功切换到富豪榜模式1！如不能正常显示请再次运行此指令切换到2！");
                        return true;
                    } else {
                        $sender->sendMessage("error!");
                        return true;
                    }
                case "open":
                    if (isset($args[1])) {
                        $mod = $args[1];
                        if (in_array($mod, ["动态显示", "富豪榜"])) {
                            $list = $this->mod->getText()->get($mod);
                            $list["status"] = true;
                            $this->mod->getText()->set($mod, $list);
                            $this->mod->getText()->save();
                            $sender->sendMessage("§a[WFloatingText] 成功开启浮空字模块 $mod !");
                            return true;
                        } else {
                            $sender->sendMessage("§c[WFloatingText] 不存在的浮空字模块！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§e[用法] /" . $this->cmd . " open [动态显示/富豪榜]");
                        return true;
                    }
                case "close":
                    if (isset($args[1])) {
                        $mod = $args[1];
                        if (in_array($mod, ["动态显示", "富豪榜"])) {
                            $list = $this->mod->getText()->get($mod);
                            $list["status"] = false;
                            $this->mod->getText()->set($mod, $list);
                            $this->mod->getText()->save();
                            $sender->sendMessage("§a[WFloatingText] 成功关闭浮空字模块 $mod !");
                            return true;
                        } else {
                            $sender->sendMessage("§c[WFloatingText] 不存在的浮空字模块！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§e[用法] /" . $this->cmd . " close [动态显示/富豪榜]");
                        return true;
                    }
                case "add":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("§c请在游戏内使用！");
                        return true;
                    }
                    if (isset($args[1]) && isset($args[2])) {
                        $id = $args[1];
                        $i = 2;
                        $line = [];
                        while (isset($args[$i])) {
                            $line[] = $args[$i];
                            $i++;
                        }
                        $line = implode(" ", $line);
                        $this->mod->getText()->set($id, array(
                            "text" => $line,
                            "pos" => [$sender->x, $sender->y, $sender->z, $sender->level->getFolderName()]
                        ));
                        $this->mod->getText()->save();
                        $ts = $this->mod->getText()->get($id);
                        $this->mod->getServer()->getLevelByName($ts["pos"][3])->addParticle(new FloatingTextParticle(new Vector3($ts["pos"][0], $ts["pos"][1], $ts["pos"][2]), $this->mod->getWTask()->api->msgs($ts["text"], $sender)));

                        $sender->sendMessage("§a[WFloatingText] 成功添加浮空字！");
                        return true;
                    } else {
                        $sender->sendMessage("§e[用法] /" . $this->cmd . " " . $args[0] . " [浮空字ID] [浮空字内容]");
                        return true;
                    }
                case "setpos":
                    if (!$sender instanceof Player) {
                        $sender->sendMessage("§c请在游戏内使用！");
                        return true;
                    }
                    if (isset($args[1])) {
                        $name = $args[1];
                        if ($this->mod->getText()->exists($name)) {
                            $pos = [
                                $sender->x,
                                $sender->y,
                                $sender->z,
                                $sender->level->getFolderName()
                            ];
                            $list = $this->mod->getText()->get($name);
                            $list["pos"] = $pos;
                            $this->mod->getText()->set($name, $list);
                            $this->mod->getText()->save();
                            $sender->sendMessage("§a[WFloatingText] 成功设置浮空字 $name 的位置！");
                            return true;
                        } else {
                            $sender->sendMessage("§e[WFloatingText] 对不起，浮空字 $name 不存在！");
                            return true;
                        }
                    }
            }
        } else {
            $sender->sendMessage("§e用法：/" . $this->cmd . " setdynpos: §b设置动态浮空字条的位置！");
            $sender->sendMessage("§e用法：/" . $this->cmd . " settoppos: §b设置动态浮空富豪榜的位置！");
            $sender->sendMessage("§e用法：/" . $this->cmd . " open [动态显示/富豪榜]: §b开启动态显示浮空字或富豪榜的开关");
            $sender->sendMessage("§e用法：/" . $this->cmd . " close [动态显示/富豪榜]: §b关闭动态显示浮空字或富豪榜的开关");
            $sender->sendMessage("§e用法：/" . $this->cmd . " add [浮空字id] [内容]: §b添加一个自定义浮空字");
            $sender->sendMessage("§e用法：/" . $this->cmd . " setpos [浮空字id]: §b设置自定义浮空字到你的脚下位置");
            $sender->sendMessage("§e用法：/" . $this->cmd . " settopmode: §b切换经济核心的富豪榜模式，如不能正常显示请切换！");
            return true;
        }
        return false;
    }
}