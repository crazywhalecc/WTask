<?php

namespace BlueWhale\WTask\Commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use BlueWhale\WTask\Config;

use BlueWhale\WTask\WTask;
use BlueWhale\WTask\SetBlockAPI;

class MainCommand extends Command
{
    private $plugin;
    public $cmdHelp = array();
    public $mmd = 0;
    public $nttype = array("消息", "msg", "tip", "提示", "popup", "底部");
    public $api;
    public $cmd;
    public $ids;

    public function __construct(WTask $plugin)//构造
    {
        $desc = $plugin->getData("command", "MainCommand");
        parent::__construct($desc["command"], $desc["description"]);
        $permission = ($desc["permission"] == "true") ? "wtask.command.wt" : ($desc["permission"] == "op" ? "wtask.command.wtask" : "wtask.command.wtask");
        $this->setPermission($permission);
        $this->plugin = $plugin;
        $this->api = $plugin->api;
        $this->cmd = $desc["command"];
        $c = $this->cmd;
        $this->ids = 0;
        $this->cmdHelp[0] = "§6======WTask主菜单(§a1 §6/ §e3§6)======§7*  当前版本: " . $this->plugin->getWTaskVersion() .
            "§7*  翻页: /" . $this->cmd . " help [页数]
§e*  输入/wtask info 来查看当前版本的更新日志哦～
§a/" . $c . " 添加任务: §b添加一个普通型任务
§a/" . $c . " reload: §b重新载入所有内容
§a/" . $c . " 创建配置文件: §b创建一个自定义的空白配置文件
§a/" . $c . " reload [自定义配置文件名称]: §b重载自定义配置文件
§a/" . $c . " info: §b关于WTask插件及版权";
        $this->cmdHelp[1] = "§6======WTask主菜单(§a2 §6/ §e3§6)======
§a/" . $c . " 权限: §b设置玩家权限
§a/" . $c . " 添加循环任务: §b添加一个循环型任务
§a/" . $c . " 停止循环任务: §b停止一个正在运行的循环任务
§a/" . $c . " cmdlist: §b查看其他WTask所有相关指令列表
§a/" . $c . " 删除所有配置文件: §b清空所有WTask数据";
        $this->cmdHelp[2] = "§6======WTask主菜单(§a3 §6/ §e3§6)======
§a/" . $c . " 添加动作任务: §b添加一个监听（动作）执行的普通任务
§a/" . $c . " 任务列表: §b查看任务的列表
§a/" . $c . " 创建指令: §b创建一个自定义指令
§a/" . $c . " 设置自定义指令: §b设置一个自定义指令";
    }

    public function selectColor($t) {
        switch ($t) {
            case "普通任务":
                return "a";
            case "循环任务":
                return "e";
            case "动作任务":
                return "d";
            default:
                return "r";
        }
    }

    public function execute(CommandSender $sender, $label, array $args)//解析
    {
        if (!$this->plugin->isEnabled())
            return false;
        if (!$this->testPermission($sender))
            return false;
        if (isset($args[0])) {
            switch ($args[0]) {
                case "添加任务":
                    if (isset($args[1])) {
                        $taskname = $args[1];
                        if ($this->api->isTaskExists($taskname)) {
                            $sender->sendMessage("§c[WTask] 对不起，这个名称的任务已经存在了！");
                            return true;
                        } else {
                            $result = $this->api->addNormalTask($taskname);
                            if ($result)
                                $sender->sendMessage("§a[WTask] 成功添加任务！	请到tasks文件夹下找到对应任务名称的配置文件进行编辑任务！\n如有不会请查阅帮助教程！");/*\n§b*  如需编辑任务请使用/".$this->cmd ." 编辑普通任务 <任务名称>");*/
                            else
                                $sender->sendMessage("§e[WTask] 添加普通任务失败！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§c[WTask] 用法： /" . $this->cmd . " 添加任务 <任务名称>");
                        return true;
                    }
                case "help":
                    if (isset($args[1])) {
                        $page = $args[1] - 1;
                        if (!isset($this->cmdHelp[$page])) {
                            $sender->sendMessage("§c[WTask] 对不起，你输入的页码有误！");
                            return true;
                        }
                        $sender->sendMessage($this->cmdHelp[$page]);
                        return true;
                    } else {
                        $sender->sendMessage($this->cmdHelp[0]);
                        return true;
                    }
                case "cmdlist":
                    $this->plugin->sendCommandList($sender);
                    return true;
                case "reload":
                    if (isset($args[1])) {
                        $filename = $args[1];
                        if ($args[1] == "all") {
                            $this->plugin->onEnable();
                        } else {
                            if (file_exists($this->plugin->getDataFolder() . "CustomConfig/" . $filename . ".yml")) {
                                $conf = new Config($this->plugin->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                                $conf->reload();
                                $sender->sendMessage("§a[WTask] 成功重新载入自定义配置文件: $filename !");
                                return true;
                            } else {
                                $sender->sendMessage("§c[WTask] 对不起，这个名字的自定义配置文件不存在！\n§e用法: /" . $this->cmd . " reload: §b重载系统指定的配置文件\n§e/" . $this->cmd . " reload [自定义配置文件名称]: §b重载自定义配置文件");
                                return true;
                            }
                        }
                    } else {
                        $this->plugin->getConfig()->reload();
                        $this->plugin->getPlayerPerm()->reload();
                        $this->plugin->getCommands()->reload();
                        $this->plugin->getMsg()->reload();
                        $this->plugin->getMod()->reload();
                        $this->plugin->getDaily()->reload();
                        $this->api->loadTasks();
                        foreach ($this->plugin->actTaskListener as $pn => $value) {
                            $value->reload();
                        }
                        foreach ($this->plugin->taskData as $taskName => $data) {
                            if ($data["type"] != "循环任务")
                                continue;
                            $this->plugin->repeatTaskList[$taskName] = $this->api->prepareTask($taskName);
                            //echo "蛤？";
                        }
                        $sender->sendMessage("§a[WTask] 重新读取加载WTask完成！");
                        return true;
                    }
                    return true;
                case "任务列表":
                    $sender->sendMessage("§6=====任务列表=====");
                    foreach ($this->plugin->taskData as $taskname => $data) {
                        $sender->sendMessage("§" . $this->selectColor($data["type"]) . "[" . $data["type"] . "] §b" . $taskname);
                    }
                    return true;
                case "创建配置文件":
                    if (isset($args[1])) {
                        $filename = $args[1];
                        if (file_exists($this->plugin->getDataFolder() . "CustomConfig/" . $filename . ".yml")) {
                            $sender->sendMessage("§c[WTask] 对不起，这个名字的配置文件已经存在了！");
                            return true;
                        }
                        new Config($this->plugin->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                        $sender->sendMessage("§a[WTask] 成功创建自定义配置文件！");
                        return true;
                    } else {
                        $sender->sendMessage("用法 /" . $this->cmd . " 创建配置文件 [文件名]");
                        return true;
                    }
                case "添加循环任务":
                    if (isset($args[1])) {
                        $name = $args[1];
                        if ($this->api->isTaskExists($name)) {
                            $sender->sendMessage("§c[WTask] 对不起，这个名字的任务已经存在了！");
                            return true;
                        }
                        $this->api->addRepeatTask("true", $name, 1);
                        $sender->sendMessage("§a[WTask] 成功创建循环任务 $name ! 请到tasks文件夹找到任务配置任务！");
                        return true;
                    } else {
                        $sender->sendMessage("§e[WTask] 用法: /" . $this->cmd . " 添加循环任务 <任务名称>");
                        return true;
                    }
                case "停止循环任务":
                case "stoprepeattask":
                    if (isset($args[1])) {
                        $taskname = $args[1];
                        if (isset($this->plugin->runningRepeatTaskStatus[$taskname])) {
                            $this->plugin->getRunningRepeatTaskStatus($taskname)->remove();
                            unset($this->plugin->runningRepeatTaskStatus[$taskname]);
                            $sender->sendMessage("§a[WTask] 成功停止运行循环任务 $taskname !");
                            return true;
                        } else {
                            $sender->sendMessage("Stop Error!");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§e[WTask] 用法： /" . $this->cmd . " 停止循环任务 <任务名称>");
                        return true;
                    }
                case "returnblock":
                    if ($this->plugin->recoveryTempData == []) {
                        $sender->sendMessage("§c[WTask] 对不起，你还没有粘贴方块！");
                        return true;
                    } else {
                        $ttt = new SetBlockAPI(null, $sender, $this->plugin->api);
                        $ttt->returnPasteBlock();
                        $sender->sendMessage("§a[WTask] 成功恢复方块！");
                        return true;
                    }
                case "添加动作任务":
                    if (isset($args[1])) {
                        $type = $args[1];
                        switch ($type) {
                            case "破坏方块":
                            case "放置方块":
                            case "玩家点击":
                            case "玩家死亡":
                            case "玩家丢弃物品":
                            case "玩家输入指令":
                            case "玩家聊天":
                            case "玩家传送":
                            case "玩家攻击玩家":
                            case "玩家加入":
                                if (isset($args[2])) {
                                    $name = $args[2];
                                    $result = $this->api->addActTask($type, $name, "true");
                                    if (!$result) {
                                        $sender->sendMessage("§c[WTask] 对不起，这个名字的动作任务已经存在了！");
                                        return true;
                                    }
                                    $sender->sendMessage("§a[WTask] 成功添加动作任务 $name , 类型是 $type !");
                                    return true;
                                } else {
                                    $sender->sendMessage("§e[WTask] 用法: /" . $this->cmd . " 添加动作任务 $type [任务名称]");
                                    return true;
                                }
                            default:
                                $sender->sendMessage("§c[WTask] 类型出错！不支持的动作类型！");
                                return true;
                        }
                    } else {
                        $sender->sendMessage("§6=====动作任务=====\n§a/" . $this->cmd . " 添加动作任务 <动作类型> <任务名称>: §b添加一个动作任务\n§d当前支持的动作类型有: \n§b破坏方块, 放置方块, 玩家点击, 玩家死亡, 玩家丢弃物品, 玩家输入指令, 玩家聊天, 玩家传送, 玩家攻击玩家, 玩家加入");
                        return true;
                    }
                case "权限":
                    if (isset($args[1]) && isset($args[2])) {
                        $name = strtolower($args[1]);
                        $this->plugin->getPlayerPerm()->set($name, intval($args[2]));
                        $this->plugin->getPlayerPerm()->save();
                        $sender->sendMessage("§a成功设置玩家 $name 的权限为 " . $args[2] . " ！");
                        return true;
                    } else {
                        $sender->sendMessage("§e[用法] /" . $this->cmd . " 权限 [玩家ID] [权限值]");
                        return true;
                    }
                case "删除所有配置文件":
                    if (!isset($args[1])) {
                        $sender->sendMessage("§e***你确定要删除所有WTask配置文件吗？此操作不可恢复！\n§6***确认删除请输入/" . $this->cmd . " 删除所有配置文件 yes");
                        return true;
                    } elseif ($args[1] == "yes") {
                        if ($sender instanceof Player) {
                            $sender->sendMessage("§c[WTask] 对不起，为了保证安全，请到控制台执行此操作！");
                            return true;
                        }
                        $this->api->deldir($this->plugin->getDataFolder());
                        $this->plugin->CreateConfig();
                        $sender->sendMessage("§a成功重置所有配置文件！");
                        return true;
                    } else {
                        $sender->sendMessage("确认指令错误，已取消！");
                        return true;
                    }
                case "创建指令":
                    if (isset($args[1]) && isset($args[4]) && isset($args[2]) && isset($args[3])) {
                        $mainCommand = $args[1];
                        $data["command"] = $mainCommand;
                        $data["description"] = $args[2];
                        $data["permission"] = $args[3];
                        $data["default"] = $args[4];
                        $data["cover"] = true;
                        $data["setting"] = [];
                        $this->plugin->getCustomCommand()->set($mainCommand, $data);
                        $this->plugin->getCustomCommand()->save();
                        $sender->sendMessage("§a[WTask] 成功创建自定义指令 $mainCommand !");
                        return true;
                    } else {
                        $sender->sendMessage("§e用法： /" . $this->cmd . " 创建指令 [主指令] [指令描述] [指令权限] [默认提示] （这里指令均无需斜杠）");
                        $sender->sendMessage("§e权限： true为全体玩家，op为仅op使用，默认提示可以直接写要运行的普通任务例如： *礼包，就是前面加个*就可以了");
                        return true;
                    }
                case "设置自定义指令":
                    if (isset($args[1])) {
                        $command = $args[1];
                        if ($this->plugin->getCustomCommand()->exists($command)) {
                            if (isset($args[2])) {
                                switch ($args[2]) {
                                    case "添加副指令":
                                        if (isset($args[3]) && isset($args[4])) {
                                            $subName = $args[3];
                                            $inside = $args[4];
                                            $d = $this->plugin->getCustomCommand()->get($command);
                                            $d["setting"][$subName] = $inside;
                                            $this->plugin->getCustomCommand()->set($command, $d);
                                            $this->plugin->getCustomCommand()->save();
                                            $sender->sendMessage("§a[WTask] 成功添加自定义副指令！");
                                            return true;
                                        } else {
                                            $sender->sendMessage("§e[用法] /" . $this->cmd . " " . $args[0] . " " . $args[1] . " " . $args[2] . " [副指令(无需斜杠)] [内容]\n§b*小提示：如果内容中最前面的是斜杠/，那么在运行这个副指令时候就会被识别为指令，通过这个副指令可以运行其他指令哦\n§b*小提示2：如果输入*xxx，那么就会被识别为是运行WTask任务\n§b*小提示3：如果直接输入文字，那么就会直接显示信息哦\n§b*小提示4：这里所有的人的名字均可用 %p 来代替");
                                            return true;
                                        }
                                    case "删除副指令":
                                        if (isset($args[3])) {
                                            $d = $this->plugin->getCustomCommand()->get($command);
                                            if (isset($d["setting"][$args[3]])) {
                                                unset($d["setting"][$args[3]]);
                                                $this->plugin->getCustomCommand()->set($command, $d);
                                                $this->plugin->getCustomCommand()->save();
                                                $sender->sendMessage("§a[WTask] 成功删除副指令 " . $args[3]);
                                                return true;
                                            } else {
                                                $sender->sendMessage("§c对不起。该副指令不存在！");
                                                return true;
                                            }
                                        } else {
                                            $sender->sendMessage("用法 /" . $this->cmd . " " . $args[0] . " " . $args[1] . " " . $args[2] . " [副指令(无需斜杠)]");
                                            return true;
                                        }
                                    case "设置默认提示":
                                        if (isset($args[3])) {
                                            $tip = $args[3];
                                            $d = $this->plugin->getCustomCommand()->get($command);
                                            $d["default"] = $tip;
                                            $this->plugin->getCustomCommand()->set($command, $d);
                                            $this->plugin->getCustomCommand()->save();
                                            $sender->sendMessage("§a[WTask] 成功设置默认提示！");
                                            return true;
                                        } else {
                                            $sender->sendMessage("§e[用法] /" . $this->cmd . " " . $args[0] . " " . $args[1] . " " . $args[2] . " [提示内容]");
                                            return true;
                                        }
                                    default:
                                        $sender->sendMessage("用法错误！");
                                        return true;
                                }
                            } else {
                                $sender->sendMessage("§6=====自定义指令设置======\n§a/" . $this->cmd . " 设置自定义指令 " . $args[1] . " 添加副指令: §b添加一个副指令");
                                $sender->sendMessage("§a/" . $this->cmd . " 设置自定义指令 " . $args[1] . " 删除副指令: §b删除一个副指令");
                                $sender->sendMessage("§a/" . $this->cmd . " 设置自定义指令 " . $args[1] . " 设置默认提示: §b设置这个指令的默认提示");
                                return true;
                            }
                        } else {
                            $sender->sendMessage("§c对不起，这个指令不存在！");
                            return true;
                        }
                    } else {
                        $sender->sendMessage("§e[用法] /" . $this->cmd . " 设置自定义指令 [主指令]");
                        return true;
                    }
                case "info":
                    $sender->sendMessage("§6===============\n§b*    WTask    *\n§eTwitter: @BlockForWhale\n§a鲸鱼QQ: 627577391\n§d插件页面: pl.zxda.net/plugins/532.html\n§6===============");
                    $sender->sendMessage("§e当前版本更新日志：" . $this->plugin->getWTaskVersion());
                    $sender->sendMessage("§b" . $this->plugin->getUpdateInfo());
                    return true;
                default:
                    $sender->sendMessage("§c[WTask] 输入指令错误！请输入 /" . $this->cmd . " help [页数]");
                    return true;
            }
        } else {
            $sender->sendMessage("§c[WTask] 请输入 /" . $this->cmd . " help [页数]");
            return true;
        }
    }
}