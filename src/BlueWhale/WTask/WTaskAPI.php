<?php

namespace BlueWhale\WTask;

use BlueWhale\extension\IP;
use pocketmine\command\CommandSender;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\entity\Entity;
use BlueWhale\WMailer\PHPMailer\PHPMailer;

use onebone\economyapi\EconomyAPI;

use pocketmine\network\protocol\Info as ProtocolInfo;
use pocketmine\utils\TextFormat;

class WTaskAPI
{
    public $taskDailyId = array();
    public $mode = [];
    public static $obj = null;

    public $plugin;

    public function __construct(WTask $plugin)//构建
    {
        $this->plugin = $plugin;
        self::$obj = $this;
    }

    function mail() {
        PHPMailer::rfcDate();
    }

    public function getWTask() {
        return $this->plugin;
    }

    public static function getInstance() {
        return self::$obj;
    }

    public function isTaskExists($taskName)//任务是否存在
    {
        //echo "正在检查任务！\n";
        $t = $this->getTaskData($taskName);
        //echo "检查完成！\n";
        if ($t == null)
            return false;
        else {
            unset($t);
            return true;
        }
    }

    public function getTaskData($t)//返回任务数据（待修改）
    {
        if (isset($this->plugin->taskData[$t])) {
            return $this->plugin->taskData[$t];
        }
        return null;
    }

    public function loadTasks() {
        $this->plugin->taskData = [];
        $path = $this->plugin->getTaskPath();
        $dir = scandir($path);
        $line = [];
        unset($dir[0], $dir[1]);
        foreach ($dir as $dirs) {
            $taskFileName = explode(".", $dirs);
            if ($taskFileName[1] != "txt" && $taskFileName[1] != "cc")
                continue;
            $file = fopen($path . $dirs, "r");
            while (!feof($file)) {
                $line[] = fgets($file);
            }
            fclose($file);
        }
        $line[] = "&&eof";
        foreach ($line as $id => $cc) {
            if (substr($cc, 0, 1) == "[") {
                $executeMain = substr($cc, 1, (strripos($cc, "]") - 1));
                $executeMain = explode(":", $executeMain);
                switch ($executeMain[0]) {
                    case "普通任务":
                        $taskName = $executeMain[1];
                        $taskIns = $this->subLoadTasks($line, $id);
                        if ($taskIns === false) {
                            $this->plugin->getServer()->getLogger()->critical("任务解析出错！请检查 $cc !");
                            return false;
                        }
                        $ss = $this->executeFunctions($taskIns["function"]);

                        $this->plugin->taskData[$taskName] = $ss;
                        $this->plugin->taskData[$taskName]["type"] = $executeMain[0];
                        $this->plugin->taskData[$taskName]["taskline"] = $taskIns["taskline"];
                        $this->plugin->taskData[$taskName]["function"] = $taskIns["function"];
                        //echo print_r($this->plugin->taskData[$taskName]);
                        break;
                    case "动作任务":
                        $taskName = $executeMain[1];
                        //echo "监测到动作任务！\n";
                        if (!isset($executeMain[2]) or !isset($executeMain[3])) {
                            $this->plugin->getServer()->getLogger()->critical("任务解析出错！请检查动作任务是否指定了类型和开服激活设置的填写！");
                            return false;
                        }
                        $taskIns = $this->subLoadTasks($line, $id);
                        $this->plugin->taskData[$taskName] = array(
                            "type" => $executeMain[0],
                            "taskline" => $taskIns["taskline"],
                            "actType" => $executeMain[2],
                            "actActive" => ($executeMain[3] == "false" ? false : true)
                        );
                        //echo "成功写入taskData！\n";
                        break;
                    case "循环任务":
                        $taskName = $executeMain[1];
                        if (!isset($executeMain[2]) or !isset($executeMain[3])) {
                            $this->plugin->getServer()->getLogger()->critical("任务解析出错！请检查循环任务是否指定了循环周期和开服激活设置的填写！");
                            return false;
                        }
                        $taskIns = $this->subLoadTasks($line, $id);
                        if ($taskIns === false) {
                            $this->plugin->getServer()->getLogger()->critical("任务解析出错！请检查 $cc !");
                            return false;
                        }
                        $this->plugin->taskData[$taskName] = array(
                            "type" => $executeMain[0],
                            "taskline" => $taskIns["taskline"],
                            "repeatTime" => $executeMain[2],
                            "repeatActive" => ($executeMain[3] == "false" ? false : true)
                        );
                        break;
                    default:
                        $this->plugin->getServer()->getLogger()->critical("未知类型的任务解析！");
                        return false;
                }
            } else {
                continue;
            }
        }
        return true;
    }

    public function subLoadTasks($line, $id) {
        $taskline = [];
        $function = [];
        $finalId = 10;
        foreach ($line as $subId => $cc) {
            if ($subId <= $id)
                continue;
            if (substr($cc, 0, 1) == "[") {
                $finalId = $subId - 1;
                break;
            }
            if ($cc == "&&eof") {
                $finalId = $subId - 1;
                break;
            }
        }
        for ($qq = ($id + 1); $qq <= $finalId; $qq++) {
            if (substr($line[$qq], 0, 1) == "<") {
                $fpos = strripos($line[$qq], ">");
                if ($fpos === false) {
                    $this->plugin->getServer()->getLogger()->critical("任务解析出错！");
                    return false;
                }
                $taskline[] = substr($line[$qq], 1, ($fpos - 1));
            } elseif (substr($line[$qq], 0, 1) == "/") {
                continue;
            } elseif (substr($line[$qq], 0, 1) == '*') {
                $fpos = strripos($line[$qq], '*');
                if ($fpos === false)
                    return false;
                $function[] = substr($line[$qq], 1, ($fpos - 1));
            } else
                continue;
        }
        return ["taskline" => $taskline, "function" => $function];
    }

    public function addNormalTask($taskname, $data = "")//创建普通任务***
    {
        $this->loadTasks();
        if ($this->isTaskExists($taskname)) {
            return false;
        }
        file_put_contents($this->plugin->getDataFolder() . "tasks/" . $taskname . "." . $this->plugin->getConfig()->get("默认任务文件格式"), "[普通任务:" . $taskname . "]\n" . ($data == "" ? "<结束>" : $data));

        return true;
    }

    public function addActTask($type, $taskname, $active, $data = "")//创建监听（动作）任务**
    {
        $this->loadTasks();
        if ($this->isTaskExists($taskname)) {
            return false;
        }
        file_put_contents($this->plugin->getDataFolder() . "tasks/" . $taskname . "." . $this->plugin->getConfig()->get("默认任务文件格式"), "[动作任务:" . $taskname . ":" . $type . ":" . $active . "]\n" . ($data == "" ? "<结束>" : $data));
        return true;
    }

    public function addRepeatTask($type, $taskname, $time, $data = "")//创建循环任务**
    {
        $this->loadTasks();
        if ($this->isTaskExists($taskname)) {
            return false;
        }
        file_put_contents($this->plugin->getDataFolder() . "tasks/" . $taskname . "." . $this->plugin->getConfig()->get("默认任务文件格式"), "[循环任务:" . $taskname . ":" . $time . ":" . $type . "]\n" . ($data == "" ? "<结束>" : $data));
        return true;
    }

    public function runNormalTaskDaily($tn, $dat, $p, $keep = false)//每日模式的应用（好像暂时不用改）
    {
        $t = new NormalTaskAPI($p, $this);
        $ar = [];
        if (!isset($dat["daily-mode"])) {
            $this->plugin->getServer()->getLogger()->warning("每日模式设置不存在！");
            return false;
        }
        $line = explode(";", $dat["daily-mode"]);
        $this->mode[$tn] = "false";
        $trueList = $this->plugin->getData("daily", "普通任务");
        if (!isset($trueList[$tn])) {
            $trueList[$tn] = [];
            $this->plugin->setData("daily", "普通任务", $trueList);
        }
        if (substr($line[0], 0, 1) != "<") {
            return true;
        }
        foreach ($line as $taskID => $taskLine) {
            $temp = explode("|", $this->fre1($taskLine));
            $ar[$taskID]["type"] = $temp[0];
            unset($temp[0]);
            $ar[$taskID]["function"] = implode("|", $temp);
        }
        if ($keep !== false) {
            $ID = $keep;
        } else
            $ID = 0;
        while (isset($ar[$ID])) {
            switch ($ar[$ID]["type"]) {
                case "消息":
                case "msg":
                    $t->sendMessage($ar[$ID]["function"]);
                    break;
                case "tip":
                case "提示":
                    $t->sendTip($ar[$ID]["function"]);
                    break;
                case "底部":
                case "popup":
                    $t->sendPopup($ar[$ID]["function"]);
                    break;
                case "setmode":
                    switch (explode("|", $ar[$ID]["function"])[0]) {
                        case "false":
                            $this->mode[$tn] = "false";
                            break;
                        case "一次性":
                            $this->mode[$tn] = "once";
                            break;
                        case "一天多次":
                            $this->mode[$tn] = "multi-day:" . explode("|", $ar[$ID]["function"])[1];
                            break;
                        case "多天一次":
                            $this->mode[$tn] = "single-day:" . explode("|", $ar[$ID]["function"])[1];
                            break;
                        case "限定次":
                            $this->mode[$tn] = "limit-time:" . explode("|", $ar[$ID]["function"])[1];
                            break;
                        default:
                            break;
                    }
                    break;
                case "wait":
                    return $ID + 1;
                default:
                    break;
            }
            $ID++;
        }
        return true;
    }

    public function setNormalTaskDaily($tn, $p)//每日模式的设置（好像也不用改）
    {
        if (!$p instanceof Player)
            return false;
        $finish = $this->plugin->getData("daily", "普通任务");
        $name = strtolower($p->getName());
        $mode = explode(":", $this->mode[$tn]);
        switch ($mode[0]) {
            case "false":
                return false;
            case "once":
                $finmish[$tn][$name] = array(
                    "cid" => $p->getClientId(),
                    "date" => date("d"),
                    "times" => 1
                );
                break;
            case "multi-day":
                if (isset($finish[$tn][$name])) {
                    if ($finish[$tn][$name]["date"] != date("d")) {
                        $finish[$tn][$name] = array(
                            "cid" => $p->getClientId(),
                            "date" => date("d"),
                            "times" => 1
                        );
                    } else
                        $finish[$tn][strtolower($p->getName())]["times"]++;
                    break;
                } else {
                    $finish[$tn][$name] = array(
                        "cid" => $p->getClientId(),
                        "date" => date("d"),
                        "times" => 1
                    );
                    break;
                }
            case "single-day":
                if (isset($finish[$tn][$name])) {
                    $finish[$tn][$name]["date"] = time();
                    break;
                } else {
                    $finish[$tn][$name]["date"] = time();
                    $finish[$tn][$name]["times"] = $mode[1];
                    $finish[$tn][$name]["cid"] = $p->getClientId();
                    break;
                }
            case "limit-time":
                if (isset($finish[$tn][$name])) {
                    $finish[$tn][$name]["times"]++;
                    break;
                } else {
                    $finish[$tn][$name]["cid"] = $p->getClientId();
                    $finish[$tn][$name]["times"] = 1;
                    $finish[$tn][$name]["date"] = time();
                    break;
                }
        }
        $this->plugin->setData("daily", "普通任务", $finish);
        return true;
    }

    public function removeNormalTaskDaily($tn, $p)//移除玩家完成的信息（好像也不用改）
    {
        if (!$p instanceof Player)
            return;
        $finish = $this->plugin->getData("daily", "普通任务");
        if (isset($finish[$tn])) {
            unset($finish[$tn][strtolower($p->getName())]);
        }
        $this->plugin->setData("daily", "普通任务", $finish);
    }

    public function runNormalTask($tn, $p, $delayStep = null)//运行普通任务
    {
        $t = new NormalTaskAPI($p, $this);
        if ($delayStep === null) {
            $ID = 0;
            $data = $this->getTaskData($tn);
            if (isset($data["daily-mode"])) {
                if (!$p instanceof Player) {
                    $this->plugin->getServer()->getLogger()->warning("检测到非玩家运行了每日模式任务！任务强行停止！");
                    return false;
                }
                $result = $this->runNormalTaskDaily($tn, $data, $p, false);
                if (is_numeric($result)) {
                    $this->taskDailyId[$tn] = $result;
                } elseif ($result === false) {
                    $this->plugin->getServer()->getLogger()->critical("未设置每日模式，无法使用daily-mode！已强制停止任务！");
                    return false;
                } else {
                    $this->taskDailyId[$tn] = 0;
                }
            }
        } elseif (is_numeric($delayStep)) {
            $ID = $delayStep;
        } else {
            $ID = 0;
        }
        while (isset($this->plugin->normalTaskList[$tn][$ID])) {
            $inside = $this->plugin->normalTaskList[$tn][$ID];
            switch ($inside["type"]) {
                case "延迟":
                case "delay":
                    $delayStep = $ID + 1;
                    $delaytime = $inside["function"];
                    $this->plugin->WantToDelay($tn, $p, $delayStep, $delaytime);
                    unset($delayStep, $t, $tn);
                    return true;
                case "daily-mode-on":
                    break;
                case "daily-mode-check":
                    $result = $t->checkFinish($inside["function"], $tn, $p);
                    if (is_numeric($result)) {
                        $jumpStep = $result;
                        $ID = $jumpStep - 2;
                    } elseif ($result === true) {
                        break;
                    } elseif ($result == "end") {
                        $ID = 10000;
                        break;
                    }
                    break;
                case "daily-mode-setfinish":
                    $this->setNormalTaskDaily($tn, $p);
                    break;
                case "daily-mode-delete":
                    $this->removeNormalTaskDaily($tn, $p);
                    break;
                default:
                    $result = $this->defaultFunction($t, $inside);
                    if ($result === true || $result == "true") {
                        break;
                    } elseif (is_numeric($result)) {
                        $ID = $result - 2;
                    } elseif ($result == "end") {
                        $ID = 10000;
                    } elseif ($result === false) {
                        $this->plugin->getServer()->getLogger()->warning("WTask任务：" . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                    } else {
                        $ssp = explode(":", $result);
                        if ($ssp[0] == "false") {
                            $this->plugin->getServer()->getLogger()->warning("WTask任务：" . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                            $this->plugin->getServer()->getLogger()->warning("错误信息：" . $ssp[1]);
                        }
                        $this->plugin->getServer()->getLogger()->notice("WTask任务： " . $tn . " 在运行第 " . ($ID + 1) . " 号任务时候返回了未知内容！");
                    }
                    break;
            }
            $ID++;
            unset($inside);
        }
        return true;
    }

    public function defaultFunction(NormalTaskAPI $t, $inside)//默认方式的解析
    {
        switch ($inside["type"]) {
            case "消息":
            case "msg":
                return $t->sendMessage($inside["function"]);
            case "tip":
            case "提示":
                return $t->sendTip($inside["function"]);
            case "底部":
            case "popup":
                return $t->sendPopup($inside["function"]);
            case "标题":
            case "title":
                return $t->sendTitle($inside["function"]);
            case "消息to":
                return $t->sendMessageTo($inside["function"]);
            case "tipto":
            case "提示to":
                return $t->sendTipTo($inside["function"]);
            case "popupto":
            case "底部to":
                return $t->sendPopupTo($inside["function"]);
            case "结束":
            case "end":
                return "end";
            case "跳转":
            case "jump":
                return $this->executeReturnData($inside["function"], $t->player);
            case "写私":
                return $t->writePrivateData($inside["function"]);
            case "写公":
                return $t->writePublicData($inside["function"]);
            case "传送":
            case "tp":
                return $t->teleport($inside["function"]);
            case "addmoney":
            case "加钱":
                return $t->addMoney($inside["function"]);
            case "删私":
                return $t->deletePrivateData($inside["function"]);
            case "减钱":
            case "reducemoney":
                return $t->reduceMoney($inside["function"]);
            case "指令":
            case "cmd":
                return $t->runCommand($inside["function"]);
            case "控制台指令":
            case "scmd":
                return $t->runConsoleCommand($inside["function"]);
            case "给予物品":
            case "添加物品":
            case "additem":
                return $t->addItem($inside["function"]);
            case "玩家动作":
                $curDat = explode("|", $inside["function"]);
                if (!$t->player instanceof Player)
                    return false;
                switch ($curDat[0]) {
                    case "允许飞行":
                        $t->player->setALlowFlight(true);
                        return true;
                    case "取消飞行":
                        $t->player->setFlying(false);
                        $t->player->setAllowFlight(false);
                        return true;
                    case "设置血量":
                    case "sethealth":
                        $t->player->setHealth($this->executeReturnData($curDat[1], $t->player));
                        return true;
                    case "加血":
                        $ori = $t->player->getHealth();
                        $plus = $this->executeReturnData($curDat[1], $t->player);
                        $result = $ori + intval($plus);
                        $t->player->setHealth($result);
                        unset($ori, $plus, $result);
                        return true;
                    case "设置血量上限":
                        $t->player->setMaxHealth($this->executeReturnData($curDat[1], $t->player));
                        return true;
                    case "减血":
                        $ori = $t->player->getHealth();
                        $result = $ori - $this->executeReturnData($curDat[1], $t->player);
                        $t->player->setHealth($result);
                        unset($ori, $result);
                        return true;
                    case "设置饥饿":
                        $t->player->setFood($this->executeReturnData($curDat[1], $t->player));
                        return true;
                    case "加经验等级":
                    case "addexplevel":
                        $t->player->addXpLevel($this->executeReturnData($curDat[1], $t->player));
                        return true;
                    case "加经验":
                    case "addexp":
                        $t->player->addXp($this->executeReturnData($curDat[1], $t->player));
                        return true;
                    case "切换创造":
                        $t->player->setGamemode(1, true);
                        return true;
                    case "切换生存":
                        $t->player->setGamemode(0, true);
                        return true;
                    case "穿鞋":
                        $item = $this->executeReturnData($curDat[1], $t->player);
                        $item = $t->executeItem($item);
                        $t->player->getInventory()->setBoots($item);
                        return true;
                    case "穿裤":
                        $item = $this->executeReturnData($curDat[1], $t->player);
                        $item = $t->executeItem($item);
                        $t->player->getInventory()->setLeggings($item);
                        return true;
                    case "kick":
                        $t->player->kick();
                        return true;
                    case "ban":
                        $t->player->setBanned(true);
                        return true;
                    case "穿衣":
                        $item = $this->executeReturnData($curDat[1], $t->player);
                        $item = $t->executeItem($item);
                        $t->player->getInventory()->setChestplate($item);
                        return true;
                    case "戴头盔":
                        $item = $this->executeReturnData($curDat[1], $t->player);
                        $item = $t->executeItem($item);
                        $t->player->getInventory()->setHelmet($item);
                        return true;
                    case "皮肤伪装":
                        return $t->setCustomSkin($curDat[1]);
                    case "设置大小":
                        $item = $this->executeReturnData($curDat[1], $t->player);
                        if (ProtocolInfo::CURRENT_PROTOCOL >= 91) {
                            $t->player->setDataProperty(Entity::DATA_SCALE, Entity::DATA_TYPE_FLOAT, $item);
                        } else {
                            Server::getInstance()->getLogger()->notice("你的服务器版本不是0.16以上，不能使用0.16以上的新特性！");
                            return false;
                        }
                        return true;
                    case "设置权限":
                        if (!$this->plugin instanceof WTask)
                            return false;
                        if (!$this->plugin->playerPerm instanceof Config)
                            return false;
                        $perm = $this->executeReturnData($curDat[1], $t->player);
                        $this->plugin->getPlayerPerm()->set(strtolower($t->player->getName()), intval($perm));
                        $this->plugin->getPlayerPerm()->save();
                        return true;
                    default:
                        return false;
                }
            case "添加效果":
                return $t->addEffect($inside["function"]);
            case "sound":
            case "声音":
                return $t->makeSound($inside["function"]);
            case "setnametag":
            case "设置名片":
                return $t->setNameTag($inside["function"]);
            case "配置文件":
                return $t->manageConfig($inside["function"]);
            case "爆炸":
            case "explode":
                return $t->makeExplosion($inside["function"]);
            case "检查物品":
            case "checkitem":
                return $t->checkInventory($inside["function"]);
            case "检查手持物品":
            case "checkiteminhand":
                return $t->checkItemInHand($inside["function"]);
            case "检查金钱":
            case "checkmoney":
                return $t->checkMoney($inside["function"]);
            case "比较":
            case "compare":
                return $t->checkCount($inside["function"]);
            case "检查游戏模式":
            case "checkgm":
                return $t->checkGm($inside["function"]);
            case "掉落物品":
            case "dropitem":
                return $t->dropItem($inside["function"]);
            case "概率":
                return $t->calculatePercentTask($inside["function"]);
            case "block":
            case "方块":
                return $t->setBlock($inside["function"]);
            case "写标签":
                return "false:此功能暂时停止提供！";
            case "缓存":
                return $t->manageTemp($inside["function"]);
            case "c":
                $r = $this->executeReturnData($inside["function"], $t->player);
                $result = eval($r);
                if ($result === null)
                    return true;
                else {
                    return $result;
                }
            case "比较字符串":
                return $t->compareText($inside["function"]);
            case "console":
                $this->plugin->getServer()->getLogger()->info($this->executeReturnData($inside["function"], $t->player));
                return true;
            case "乐谱":
                return $t->makeMusic($inside["function"]);
            case "设置手持物品":
                return $t->setItemInHand($inside["function"]);
            case "物品":
                return $t->manageItem($inside["function"]);
            default:
                if ($this->plugin->getConfig()->get("自定义功能扩展开关") == false) {
                    return false;
                }
                foreach ($this->plugin->getCustomFunction()->getAll() as $functionName => $func) {
                    return true; // TODO
                }
                return false;
        }
    }

    public function fre1($str)//翻译<>
    {
        $fpos = strripos($str, ">");
        $str = substr($str, 1, ($fpos - 1));
        return $str;
        /*$result = array();
        preg_match_all("/(?:<)(.*)(?:>)/i",$str, $result);
        return $result[1][0]; */
    }

    public function fre2($str)//翻译()
    {
        $fpos = strripos($str, ")");
        return substr($str, 1, ($fpos - 1));
    }

    public function executePlus($s)//解析符号（无需修改）
    {
        $array = explode("{+}", $s);
        if (!isset($array[1])) {
            $array = explode("{-}", $s);
            if (!isset($array[1])) {
                $array = explode("{*}", $s);
                if (!isset($array[1])) {
                    $array = explode("{/}", $s);
                    if (!isset($array[1])) {
                        return null;
                    } else {
                        return "{/}";
                    }
                } else
                    return "{*}";
            } else
                return "{-}";
        } else
            return "{+}";
    }

    public function executeCompare($s)//解析比较大小
    {
        $array = explode("{大于}", $s);
        if (!isset($array[1])) {
            $array = explode("{小于}", $s);
            if (!isset($array[1])) {
                $array = explode("{等于}", $s);
                if (!isset($array[1])) {
                    $array = explode("{大于等于}", $s);
                    if (!isset($array[1])) {
                        $array = explode("{小于等于}", $s);
                        if (!isset($array[1])) {
                            return null;
                        } else {
                            return "{小于等于}";
                        }
                    } else {
                        return "{大于等于}";
                    }
                } else {
                    return "{等于}";
                }
            } else {
                return "{小于}";
            }
        } else {
            return "{大于}";
        }
    }

    public function msgs($msg, $p = null)//动态消息API接口（无需修改）
    {
        $tps = (string)Server::getInstance()->getTicksPerSecondAverage();
        $minitime = microtime(true) - \pocketmine\START_TIME;
        $uptime = (int)($minitime / 60);
        $load = (string)Server::getInstance()->getTickUsageAverage();
        $load = $load . "%";
        $time = date("H") . ": " . date("i") . ": " . date("s");
        if ($p instanceof Player) {
            $m = EconomyAPI::getInstance()->myMoney($p->getName());
            $beibao = $p->getInventory();
            $item = $beibao->getItemInHand();
            $id = $item->getID();
            $ts = $item->getDamage();
            $lv = $p->getLevel()->getFolderName();
            $food = $p->getFood();
            $x = (int)($p->x);
            $y = (int)($p->y);
            $z = (int)($p->z);
            $msg = str_replace("%p", $p->getName(), $msg);
            $msg = str_replace("{name}", $p->getName(), $msg);
            $msg = str_replace("{hp}", $p->getHealth(), $msg);
            $msg = str_replace("{mhp}", $p->getMaxHealth(), $msg);
            $msg = str_replace("{money}", $m, $msg);
            $msg = str_replace("{itemid}", $id, $msg);
            $msg = str_replace("{itemdamage}", $ts, $msg);
            $msg = str_replace("{level}", $lv, $msg);
            $msg = str_replace("{food}", $food, $msg);
            $msg = str_replace("{ip}", $p->getAddress(), $msg);
            $msg = str_replace("{port}", $p->getPort(), $msg);
            $msg = str_replace("{x}", $x, $msg);
            $msg = str_replace("{y}", $y, $msg);
            $msg = str_replace("{z}", $z, $msg);
            unset($m, $beibao, $item, $id, $ts, $lv, $food, $x, $y, $z);
        }
        $pc = 0;
        foreach (Server::getInstance()->getOnlinePlayers() as $pkk) {
            if ($pkk->isOnline()) {
                ++$pc;
            }
            unset($pkk);
        }
        $tagTemp = WTask::getInstance()->tagTemp;

        foreach ($tagTemp as $idd => $tag) {
            $msg = str_replace("{缓存" . $idd . "}", $tag, $msg);
            unset($idd, $tag);
        }
        $msg = str_replace("{time}", $time, $msg);

        $msg = str_replace("{tps}", $tps, $msg);
        $msg = str_replace("{online}", $pc, $msg);

        $msg = str_replace("{load}", $load, $msg);
        $msg = str_replace("{runtime}", $uptime, $msg);

        $msg = str_replace("%n", "\n", $msg);
        $msg = str_replace("{sp}", " ", $msg);
        unset($tps, $minitime, $uptime, $load, $time, $pc, $tagTemp);
        return $msg;
    }

    /**
     * @param $string
     * @param $p
     * @return bool|string
     */
    public function checkCirculate($string, $p)//循环检测**
    {
        if (substr($string, 0, 1) == "(") {
            return $this->executeReturnData($string, $p);
        } else {
            return true;
        }
    }

    /**
     * @param $m
     * @param null $p
     * @return string
     */
    public function executeReturnData($m, $p = null)//解析嵌套**
    {
        if (substr($m, 0, 1) != "(")
            return $m;
        $m1 = $this->fre2($m);
        //echo $m1."\n";
        $mtype = explode(":", $m1);
        $types = $mtype[0];
        $inv = array_search($types, $mtype);
        array_splice($mtype, $inv, 1);
        $backString = implode(":", $mtype);
        switch ($types) {
            case "玩家":
                $r = $this->checkCirculate($backString, $p);
                $backString = ($r === true ? $backString : $r);
                if (!$p instanceof Player)
                    return "";
                //echo "BackStringChanged:".$backString."\n";
                switch ($backString) {
                    case "手持":
                        $itemheld = $p->getInventory()->getItemInHand();
                        $itemheld = $itemheld->getID() . "-" . $itemheld->getDamage() . "-" . $itemheld->getCount();
                        return $itemheld;
                    case "手持id":
                        $itemheld = $p->getInventory()->getItemInHand()->getId() . "-" . $p->getInventory()->getItemInHand()->getDamage();
                        return $itemheld;
                    case "手持damage":
                        $itemheld = $p->getInventory()->getItemInHand()->getDamage();
                        return $itemheld;
                    case "手持数量":
                        $itemheld = $p->getInventory()->getItemInHand()->getCount();
                        return $itemheld;
                    case "鞋子id":
                        return $p->getInventory()->getBoots()->getId();
                    case "鞋子damage":
                        return $p->getInventory()->getBoots()->getDamage();
                    case "裤子id":
                        return $p->getInventory()->getLeggings()->getId();
                    case "裤子damage":
                        return $p->getInventory()->getLeggings()->getDamage();
                    case "衣服id":
                        return $p->getInventory()->getChestplate()->getId();
                    case "衣服damage":
                        return $p->getInventory()->getChestplate()->getDamage();
                    case "头盔id":
                        return $p->getInventory()->getHelmet()->getId();
                    case "头盔damage":
                        return $p->getInventory()->getHelmet()->getDamage();
                    case "金钱":
                        return EconomyAPI::getInstance()->myMoney($p);
                    case "名字":
                        return $p->getName();
                    case "小写名字":
                        return strtolower($p->getName());
                    case "x":
                        return $p->x;
                    case "y":
                        return $p->y;
                    case "z":
                        return $p->z;
                    case "与最近玩家的距离":
                        $distance = [];
                        foreach (Server::getInstance()->getOnlinePlayers() as $multiplayer) {
                            if ($multiplayer->getName() == $p->getName())
                                continue;
                            if ($multiplayer->level->getFolderName() != $p->level->getFolderName())
                                continue;
                            $distance[] = $p->distance($multiplayer);
                        }
                        if ($distance != []) {
                            rsort($distance);
                            $final = $distance[0];
                        } else {
                            $final = "无其他玩家";
                        }
                        return $final;
                    case "世界名":
                        return $p->level->getFolderName();
                    case "坐标":
                        $sr = $p->x . ":" . $p->y . ":" . $p->z . ":" . $p->getLevel()->getFolderName();
                        return $sr;
                    case "周围":
                        return $p->x . ":" . $p->y . ":" . ($p->z + 5) . ":" . $p->getLevel()->getFolderName();
                    case "饥饿值":
                        return $p->getFood();
                    case "血量":
                        return $p->getHealth();
                    case "最大血量":
                        return $p->getMaxHealth();
                    case "ip":
                        return $p->getAddress();
                    case "port":
                    case "端口":
                        return $p->getPort();
                    case "ip归属地":
                        if (!IP::isDatabaseExists())
                            return "未安装IP归属地数据库，请先安装数据库后再操作！";
                        else {
                            return IP::getFrom($p->getAddress());
                        }
                    case "坐标计算":
                        $bs = explode(":", $backString);
                        $x = $bs[1];
                        $y = $bs[2];
                        $z = $bs[3];
                        $pos = ($p->x + $x) . ":" . ($p->y + $y) . ":" . ($p->z + $z) . ":" . $p->getLevel()->getFolderName();
                        return $pos;
                }
                return "0";
            case "IP归属地":
                if (!IP::isDatabaseExists())
                    return "未安装IP归属地数据库，请先安装数据库后再操作！";
                else {
                    $ip = $this->executeReturnData($backString, $p);
                    return IP::getFrom($ip);
                }
            case "物品解析":
                $dataIns = explode(".", $backString);
                $r = $this->checkCirculate($dataIns[1], $p);
                $dataIns[1] = ($r === true ? $dataIns[1] : $r);
                $itemIns = explode("-", $dataIns[1]);
                if (!isset($dataIns[1]))
                    return "false";
                switch ($dataIns[0]) {
                    case "物品id":
                        return $itemIns[0];
                    case "物品特殊值":
                        return $itemIns[1];
                    case "物品数量":
                        return $itemIns[2];
                    case "物品id和特殊值":
                        return $itemIns[0] . "-" . $itemIns[1];
                    case "物品id和数量":
                        return $itemIns[0] . "-" . $itemIns[2];
                    case "物品id和特殊值和数量":
                        return $dataIns[0];
                    case "物品名称":
                        if (!isset($this->plugin->database["chineseItem"]))
                            return "你没有安装中文名称数据库，无法返回正确的中文名称";
                        $itemNameId = $itemIns[0] . ":" . $itemIns[1];
                        if (isset($this->plugin->getDatabase("chineseItem")[$itemNameId])) {
                            return $this->plugin->getDatabase("chineseItem")[$itemNameId];
                        } else {
                            return "未知名称的物品";
                        }
                    default:
                        return $dataIns[0];
                }
            case "随机数":
                $number = explode(",", $backString);
                $r = $this->checkCirculate($number[0], $p);
                $number[0] = ($r === true ? $number[0] : $r);
                $r = $this->checkCirculate($number[1], $p);
                $number[1] = ($r === true ? $number[1] : $r);
                return mt_rand($number[0], $number[1]);
            case "玩家坐标计算":
                if (!$p instanceof Player)
                    return "(error:null_player)";
                $r = $this->checkCirculate($backString, $p);
                $backString = ($r === true ? $backString : $r);
                if ($p == null)
                    return "";
                $bs = explode(".", $backString);
                $x = $bs[0];
                $y = $bs[1];
                $z = $bs[2];
                $pos = ($p->x + $x) . ":" . ($p->y + $y) . ":" . ($p->z + $z) . ":" . $p->getLevel()->getFolderName();
                return $pos;
            case "计算":
                $fuhao = $this->executePlus($backString);
                $number = explode($fuhao, $backString);
                $r = $this->checkCirculate($number[0], $p);
                $number[0] = ($r === true ? $number[0] : $r);
                $r = $this->checkCirculate($number[1], $p);
                $number[1] = ($r === true ? $number[1] : $r);
                return $this->cal($number[0], $number[1], $fuhao);
            case "返回最大值":
                $data = explode(",", $backString);
                foreach ($data as $key => $ins) {
                    $data[$key] = $this->executeReturnData($ins, $p);
                }
                arsort($data);
                return $data[0];
            case "返回最小值":
                $data = explode(",", $backString);
                foreach ($data as $key => $ins) {
                    $data[$key] = $this->executeReturnData($ins, $p);
                }
                rsort($data);
                return $data[0];
            case "读公":
                $r = $this->checkCirculate($backString, $p);
                $backString = ($r === true ? $backString : $r);
                if (isset($this->plugin->publicTempData[$backString])) {
                    return $this->plugin->publicTempData[$backString];
                } else
                    return "";
            case "读私":
                if (!$p instanceof Player)
                    return "(error:null_player)";
                //echo $backString."\n";
                //echo $this->plugin->privateTempData[$p->getName()][$backString];
                if (isset($this->plugin->privateTempData[$p->getName()][$backString]))
                    return $this->plugin->privateTempData[$p->getName()][$backString];
                else
                    return "(error:none)";
            case "时":
                return date("H");
            case "分":
                return date("i");
            case "秒":
                return date("s");
            case "日":
                return date("d");
            case "时间戳":
                return time();
            case "小时间戳":
                return microtime(true);
            case "月":
                return date("m");
            case "年":
                return date("Y");
            case "自定义配置":
                $backString = explode(".", $backString);
                $r = $this->checkCirculate($backString[1], $p);
                $backString[1] = ($r === true ? $backString[1] : $r);
                $filename = $backString[0];
                if (file_exists($this->plugin->getDataFolder() . "CustomConfig/" . $filename . ".yml")) {
                    $conf = new Config($this->plugin->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                    $dattt = $conf->get($backString[1]);
                    return $dattt;
                } else {
                    return "";
                }
            case "字符串拼接":
            case "implode":
                $backString = explode(",", $backString);
                foreach ($backString as $kissId => $kissLine) {
                    $r = $this->checkCirculate($kissLine, $p);
                    $backString[$kissId] = ($r === true ? $kissLine : $r);
                }
                return implode("", $backString);
            case "读标签":
                $r = $this->checkCirculate($backString, $p);
                $backString = ($r === true ? $backString : $r);
                if (isset($this->plugin->tagTemp[$backString]))
                    return $this->plugin->tagTemp[$backString];
                else
                    return "(error:lost)";
            case "物品nbt":
                $ins = explode(".", $backString);
                switch ($ins[0]) {
                    case "玩家手持":
                        if (!$p instanceof Player) {
                            return "(error:none_player)";
                        }
                        $nbt = $p->getInventory()->getItemInHand()->getNamedTag();
                        $ins2 = $this->checkCirculate($ins[1], $p);
                        $ins[1] = ($ins2 === true ? $ins[1] : $ins2);
                        if (isset($nbt[$ins[1]])) {
                            return $nbt[$ins[1]];
                        } else {
                            return "(error:none_nbt_tag)";
                        }
                }
                return "null";
            case "读取文件":
                $ins3 = $this->checkCirculate($backString, $p);
                $backString = ($ins3 === true ? $backString : $ins3);
                $fileName = $this->plugin->getDataFolder() . $backString;
                if (!file_exists($fileName)) {
                    return "(error:file_not_exist)";
                }
                $str = file_get_contents($fileName);
                $str = str_replace("<?php", "", $str);
                return $str;
            default:
                return "(error:lost)";
        }
    }

    public function calCompare($a, $b, $fuhao)//比较大小**（不用修改）
    {
        switch ($fuhao) {
            case "{大于}":
                if ($a > $b)
                    return true;
                else
                    return false;
            case "{小于}":
                if ($a < $b)
                    return true;
                else
                    return false;
            case "{等于}":
                if ($a == $b)
                    return true;
                else
                    return false;
            case "{大于等于}":
                if ($a >= $b)
                    return true;
                else
                    return false;
            case "{小于等于}":
                if ($a <= $b)
                    return true;
                else
                    return false;
            default:
                return false;
        }
    }

    public function cal($a, $b, $fuhao)//计算（不用修改）
    {
        switch ($fuhao) {
            case "{+}":
                return $a + $b;
            case "{-}":
                return $a - $b;
            case "{*}":
                return $a * $b;
            case "{/}":
                return $a / $b;
            default:
                return $a;
        }
    }

    public function executeFunctions($functions) {
        $functionList = [];
        foreach ($functions as $fc) {
            $fcs = explode(":", $fc);
            $functionList[$fcs[0]] = $fcs[1];
        }
        return $functionList;
    }

    /**
     * @param $taskname
     * @param CommandSender|Player $p
     * @return bool
     */
    public function preNormalTask($taskname, $p) {
        if (isset($this->getTaskData($taskname)["权限"])) {
            if ($this->plugin->getPerm($p) < $this->getTaskData($taskname)["权限"]) {
                $p->sendMessage(TextFormat::RED . "你没有权限运行这个任务！");
                return true;
            }
        }
        $lineData = $this->prepareTask($taskname);
        $this->plugin->normalTaskList[$taskname] = $lineData;
        return $this->runNormalTask($taskname, $p);
    }

    public function prepareTask($taskname)//返回解析后的任务数据
    {
        $data = $this->plugin->taskData[$taskname];
        $task = $data["taskline"];
        $ar = [];
        if ($data["taskline"] == [])
            return array(array("type" => "end", "function" => ""));
        foreach ($task as $taskID => $taskLine) {
            $temp = explode("|", $taskLine);
            $ar[$taskID]["type"] = $temp[0];
            unset($temp[0]);
            $ar[$taskID]["function"] = implode("|", $temp);
        }
        return $ar;
    }

    public function deldir($dir) //删除文件夹和内容的函数
    {
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullpath = $dir . "/" . $file;
                if (!is_dir($fullpath)) {
                    @unlink($fullpath);
                } else {
                    self::deldir($fullpath);
                }
            }
        }
        closedir($dh);
        if (@rmdir($dir)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    function call() {
        $this->call();
    }
}