<?php

namespace BlueWhale\WTask;

//基础类函数
use pocketmine\command\CommandSender;
use pocketmine\level\Level;
use pocketmine\nbt\tag\StringTag;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\ProtocolInfo;

//外部函数
use onebone\economyapi\EconomyAPI;

//其他函数
use pocketmine\item\Item;
use pocketmine\entity\Effect;
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use pocketmine\command\ConsoleCommandSender;

//声音类函数
use pocketmine\level\sound\AnvilFallSound;
use pocketmine\level\sound\AnvilUseSound;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\sound\ButtonClickSound;
use pocketmine\level\sound\DoorBumpSound;
use pocketmine\level\sound\DoorCrashSound;
use pocketmine\level\sound\DoorSound;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\level\sound\ExpPickupSound;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\GhastShootSound;
use pocketmine\level\sound\GhastSound;
use pocketmine\level\sound\LaunchSound;
use pocketmine\level\sound\NoteblockSound;
use pocketmine\level\sound\PopSound;
use pocketmine\level\sound\SpellSound;
use pocketmine\level\sound\SplashSound;
use pocketmine\level\sound\TNTPrimeSound;

class NormalTaskAPI
{
    public $player;
    public $plugin;

    public $api;

    public function __construct($p, WTaskAPI $api)//构造函数***********
    {
        $this->player = $p;
        //echo "构建API！\n";
        $this->api = $api;
    }

    /**
     * @param $msg
     * @return bool
     */
    public function sendMessage($msg)//发送消息**
    {
        $msg = $this->api->executeReturnData($msg, $this->player);
        $this->sendMsgPacket($this->player, $this->api->msgs($msg, $this->player), 0);
        unset($msg);
        return true;
    }

    /**
     * @param $msg
     * @return bool
     */
    public function sendTip($msg)//发送提示**
    {
        $msg = $this->api->executeReturnData($msg, $this->player);
        $this->sendMsgPacket($this->player, $this->api->msgs($msg, $this->player), 1);
        return true;
    }

    /**
     * @param $msg
     * @return bool
     */
    public function sendPopup($msg)//发送底部**
    {
        $msg = $this->api->executeReturnData($msg, $this->player);
        $this->sendMsgPacket($this->player, $this->api->msgs($msg, $this->player), 2);
        return true;
    }

    /**
     * @param $it
     * @return bool
     */
    public function setCustomSkin($it)//设置自定义皮肤**
    {
        if (!$this->player instanceof Player)
            return false;
        $player = $this->api->executeReturnData($it, $this->player);
        $real = Server::getInstance()->getPlayerExact($player);
        if ($real === null) {
            return "false:换皮肤的目标玩家不在线！";
        }
        $skin = $real->getSkin();
        $this->player->setSkin($skin);
        return true;
    }

    /**
     * @param $msg
     * @return bool
     */
    public function sendTitle($msg)//发送大字标题**
    {
        if (!$this->player instanceof Player)
            return false;
        $msg = explode("|", $msg);
        $msg[0] = $this->api->executeReturnData($msg[0], $this->player);
        if (isset($msg[1])) {
            $msg[1] = $this->api->executeReturnData($msg[1], $this->player);
        } else {
            $msg[1] = "";
        }
        if (!isset($msg[2])) {
            $msg[2] = 20;
        }
        if (!isset($msg[3])) {
            $msg[3] = 20;
        }
        if (!isset($msg[4])) {
            $msg[4] = 5;
        }
        $protocol = ProtocolInfo::CURRENT_PROTOCOL;
        if ($protocol < 104) {
            return false;
        }
        $this->player->sendTitle($msg[0], $msg[1], $msg[2], $msg[3], $msg[4]);
        return true;
    }

    /**
     * @param $it
     * @return bool
     */
    public function sendMessageTo($it)//发送消息给xxx**
    {
        $it = explode("|", $it);
        $player = $it[0];
        $msg = $it[1];
        $pl = null;
        if (substr($player, 0, 1) == "(") {
            $player = $this->api->executeReturnData($player, $this->player);
            $pl = Server::getInstance()->getPlayerExact($player);
            $all = false;
        } elseif ($player == "*all") {
            $all = true;
        } else {
            $pl = Server::getInstance()->getPlayerExact($player);
            $all = false;
        }
        if ($pl === null && $player != "*all") {
            $this->sendMsgPacket($this->player, "Offline!", 0);
            return "false:目标玩家不在线！";
        } else {
            if (substr($msg, 0, 1) == "(") {
                $msg = $this->api->executeReturnData($msg, $pl);
            }
            if ($all === true) {
                foreach ($this->api->plugin->getServer()->getOnlinePlayers() as $mypl) {
                    $this->sendMsgPacket($mypl, $this->api->msgs($msg, $this->player), 0);
                }
                return true;
            }
            $this->sendMsgPacket($pl, $this->api->msgs($msg, $this->player), 0);
            return true;
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function sendTipTo($it)//发送tip给xxx**
    {
        $it = explode("|", $it);
        $player = $it[0];
        $msg = $it[1];
        $pl = null;
        if (substr($player, 0, 1) == "(") {
            $player = $this->api->executeReturnData($player, $this->player);
            $pl = Server::getInstance()->getPlayerExact($player);
            $all = false;
        } elseif ($player == "*all") {
            $all = true;
        } else {
            $pl = Server::getInstance()->getPlayerExact($player);
            $all = false;
        }
        if ($pl === null && $player != "*all") {
            $this->sendMsgPacket($this->player, "Offline!", 0);
            return "false:目标玩家不在线！";
        } else {
            if (substr($msg, 0, 1) == "(") {
                $msg = $this->api->executeReturnData($msg, $pl);
            }
            if ($all === true) {
                foreach ($this->api->plugin->getServer()->getOnlinePlayers() as $mypl) {
                    $this->sendMsgPacket($mypl, $this->api->msgs($msg, $this->player), 1);
                }
                return true;
            }
            $this->sendMsgPacket($pl, $this->api->msgs($msg, $this->player), 1);
            return true;
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function sendPopupTo($it)//发送popup给xxx**
    {
        $it = explode("|", $it);
        $player = $it[0];
        $msg = $it[1];
        $pl = null;
        if (substr($player, 0, 1) == "(") {
            $player = $this->api->executeReturnData($player, $this->player);
            $pl = Server::getInstance()->getPlayerExact($player);
            $all = false;
        } elseif ($player == "*all") {
            $all = true;
        } else {
            $pl = Server::getInstance()->getPlayerExact($player);
            $all = false;
        }
        if ($pl === null && $player != "*all") {
            $this->sendMsgPacket($this->player, "Offline!", 0);
            return "false:目标玩家不在线！";
        } else {
            if (substr($msg, 0, 1) == "(") {
                $msg = $this->api->executeReturnData($msg, $pl);
            }
            if ($all === true) {
                foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $mypl) {
                    $this->sendMsgPacket($mypl, $this->api->msgs($msg, $this->player), 2);
                }
                return true;
            }
            $this->sendMsgPacket($pl, $this->api->msgs($msg, $this->player), 2);
            return true;
        }
    }

    /**
     * @param $it
     * @param $tn
     * @param $p
     * @return bool
     */
    public function checkFinish($it, $tn, $p)//检查每日模式是否完成**不用修改**
    {
        if (!$p instanceof Player)
            return false;
        $it = explode("|", $it);
        if (sizeof($it) < 2) {
            return "false:检查daily-mode功能的参数错误！应该为2个。";
        }
        $status = false;
        $finish = $this->getPlugin()->getData("daily", "普通任务");
        $finish = (isset($finish[$tn]) ? $finish[$tn] : []);
        $mode = $this->api->mode[$tn];
        $name = strtolower($p->getName());
        $mode = explode(":", $mode);
        switch ($mode[0]) {
            case "false":
                break;
            case "once":
                if (isset($finish[$name])) {
                    $status = true;
                    break;
                } else {
                    break;
                }
            case "multi-day":
                if (isset($finish[strtolower($p->getName())])) {
                    $day = date('d');
                    $pastday = $finish[$name]["date"];
                    if ($day != $pastday) {
                        break;
                    } else {
                        $times = $finish[$name]["times"];
                        if ($times >= $mode[1]) {
                            $status = true;
                            break;
                        } else {
                            break;
                        }
                    }
                } else {
                    break;
                }
            case "single-day":
                if (isset($finish[$name])) {
                    $currentTime = time();
                    $finishTime = $finish[$name]["date"];
                    $upgrade = $finish[$name]["times"] * 86400;
                    if (($upgrade + $finishTime) >= $currentTime) {
                        $status = true;
                    }
                    break;
                } else
                    break;
            case "limit-time":
                if (isset($finish[$name])) {
                    $times = $finish[$name]["times"];
                    if ($times >= $mode[1]) {
                        $status = true;
                        break;
                    } else {
                        break;
                    }
                } else
                    break;
        }
        if ($status === true) {
            return $this->doSubCommand($it[0]);
        } else {
            return $this->doSubCommand($it[1], $tn);
        }
    }

    /**
     * @param $data
     * @return bool
     */
    public function writePrivateData($data)//写私**
    {
        if (!$this->player instanceof Player)
            return "false:玩家不存在！";
        $data = explode("|", $data);
        if (sizeof($data) < 2) {
            return "false:写私功能的参数不足";
        }
        $data[0] = $this->api->executeReturnData($data[0], $this->player);
        $data[1] = $this->api->executeReturnData($data[1], $this->player);
        $datalist = $this->api->plugin->privateTempData;
        if (!isset($datalist[$this->player->getName()])) {
            $datalist[$this->player->getName()] = [];
        }
        $trueList = $datalist[$this->player->getName()];
        $trueList[$data[0]] = $data[1];
        $datalist[$this->player->getName()] = $trueList;
        //echo "检查名字为：".$this->player->getName()."\n";
        $this->api->plugin->privateTempData = $datalist;
        return true;
    }

    /**
     * @param $data
     * @return bool
     */
    public function writePublicData($data)//写公**
    {
        $data = explode("|", $data);
        if (sizeof($data) < 2) {
            return "false:写公功能的参数不足";
        }
        $data[0] = $this->api->executeReturnData($data[0], $this->player);
        $data[1] = $this->api->executeReturnData($data[1], $this->player);
        $datalist = WTask::getInstance()->publicTempData;
        $datalist[$data[0]] = $data[1];
        WTask::getInstance()->publicTempData = $datalist;
        return true;
    }

    /**
     * @param string $data
     * @return bool
     */
    public function deletePrivateData($data = "")//删除私有数据**
    {
        if (!$this->player instanceof Player)
            return "false:玩家不存在！";
        if ($data === "")
            unset(WTask::getInstance()->privateTempData[$this->player->getName()]);
        else {
            unset(WTask::getInstance()->privateTempData[$this->player->getName()][$this->api->executeReturnData($data, $this->player)]);
        }
        return true;
    }

    /**
     * @param $pos
     * @return bool
     */
    public function teleport($pos)//传送**
    {
        $pos = explode("|", $pos);
        if (sizeof($pos) < 2) {
            return "false:传送功能的参数不足";
        }
        $all = false;
        if (substr($pos[0], 0, 1) == "(") {
            $pos[0] = $this->api->executeReturnData($pos[0], $this->player);
            $all = false;
        } elseif ($pos[0] == "*all") {
            $all = true;
        }
        if (substr($pos[1], 0, 1) == "(") {
            $pos[1] = $this->api->executeReturnData($pos[1], $this->player);
        }
        $position = $this->executeLocation($pos[1]);
        if ($all === true) {
            foreach (Server::getInstance()->getOnlinePlayers() as $pl) {
                $pl->teleport($position);
            }
            return true;
        } else {
            $mp = Server::getInstance()->getPlayerExact($pos[0]);
            if ($mp === null) {
                return false;
            }
            $mp->teleport($position);
            return true;
        }
    }

    /**
     * @param $dat
     * @return bool
     */
    public function addMoney($dat)//加钱**
    {
        $dat = $this->api->executeReturnData($dat, $this->player);
        if ($this->api->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI") === null) {
            return "false:未检测到任何经济核心！";
        }
        EconomyAPI::getInstance()->addMoney($this->player, $dat);
        return true;
    }

    /**
     * @param $dat
     * @return bool
     */
    public function reduceMoney($dat)//减钱**
    {
        $dat = $this->api->executeReturnData($dat, $this->player);
        if ($this->api->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI") === null) {
            return "false:未检测到任何经济核心！";
        }
        EconomyAPI::getInstance()->reduceMoney($this->player, $dat);
        return true;
    }

    /**
     * @param $cmd
     * @return bool
     */
    public function runCommand($cmd)//运行命令**
    {
        $cmd = $this->api->executeReturnData($cmd, $this->player);
        Server::getInstance()->dispatchCommand($this->player, $this->api->msgs($cmd, $this->player));
        return true;
    }

    /**
     * @param $cmd
     * @return bool
     */
    public function runConsoleCommand($cmd)//运行控制台命令**
    {
        $cmd = $this->api->executeReturnData($cmd, $this->player);
        Server::getInstance()->dispatchCommand(new ConsoleCommandSender(), $this->api->msgs($cmd, $this->player));
        return true;
    }

    /**
     * @param $it
     * @return bool
     */
    public function addItem($it)//添加物品**
    {
        if (!$this->player instanceof Player)
            return "false:玩家不存在！";
        $it = explode("|", $it);
        if (sizeof($it) < 2) {
            return "false:添加物品功能的参数不足";
        }
        $all = false;
        if ($it[0] == "*当前玩家") {
            $it[0] = $this->player->getName();
        } elseif (substr($it[0], 0, 1) == "(") {
            $it[0] = $this->api->executeReturnData($it[0], $this->player);
            $all = false;
        } elseif ($it[0] == "*all") {
            $all = true;
        }
        $it[1] = $this->api->executeReturnData($it[1], $this->player);
        $item = explode(",", $it[1]);
        $itemList = [];
        foreach ($item as $idd => $exe) {
            $itemList[$idd] = $this->executeItem($exe);
        }
        if ($all === true) {
            foreach (Server::getInstance()->getOnlinePlayers() as $playment) {
                foreach ($itemList as $myitem) {
                    $playment->getInventory()->addItem($myitem);
                }
            }
            return true;
        } else {
            $mp = Server::getInstance()->getPlayerExact($it[0]);
            if ($mp === null) {
                return "false:目标玩家不存在！";
            }
            foreach ($itemList as $iit) {
                $mp->getInventory()->addItem($iit);
            }
            return true;
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function addEffect($it)//添加药水效果**
    {
        if (!$this->player instanceof Player)
            return "false:玩家不存在！";
        $it = explode("|", $it);
        if (sizeof($it) < 4) {
            return "false:添加效果功能中的参数不足！期望参数：4";
        }
        $ID = $this->api->executeReturnData($it[0], $this->player);
        $sec = $this->api->executeReturnData($it[1], $this->player);
        $level = $this->api->executeReturnData($it[2], $this->player);
        $particle = $this->api->executeReturnData($it[3], $this->player);
        if (is_numeric($sec)) {
            $sec = $sec * 20;
        } else {
            return "false:传入了错误的数据！";
        }
        if (!is_numeric($level))
            return "false:传入了错误的数据！";
        $particle = ($particle == "效果开" ? true : false);
        $effect = Effect::getEffect($ID);
        $effect->setVisible($particle);
        $effect->setAmplifier($level);
        $effect->setDuration($sec);
        $this->player->addEffect($effect);
        return true;
    }

    /**
     * @param $it
     * @return bool
     */
    public function makeSound($it)//发送声音**
    {
        if (!$this->player instanceof Player)
            return "false:玩家不存在！";
        $it = explode(",", $it);
        foreach ($it as $i => $ttt) {
            switch ($ttt) {
                case "1":
                    $this->player->getLevel()->addSound(new AnvilFallSound($this->player));
                    break;
                case "2":
                    $this->player->getLevel()->addSound(new AnvilUseSound($this->player));
                    break;
                case "4":
                    $this->player->getLevel()->addSound(new BlazeShootSound($this->player));
                    break;
                case "5":
                    $this->player->getLevel()->addSound(new ButtonClickSound($this->player));
                    break;
                case "6":
                    $this->player->getLevel()->addSound(new DoorBumpSound($this->player));
                    break;
                case "7":
                    $this->player->getLevel()->addSound(new DoorCrashSound($this->player));
                    break;
                case "8":
                    $this->player->getLevel()->addSound(new DoorSound($this->player));
                    break;
                case "9":
                    $this->player->getLevel()->addSound(new EndermanTeleportSound($this->player));
                    break;
                case "10":
                    $this->player->getLevel()->addSound(new ExplodeSound($this->player));
                    break;
                case "11":
                    $this->player->getLevel()->addSound(new ExpPickupSound($this->player));
                    break;
                case "12":
                    $this->player->getLevel()->addSound(new FizzSound($this->player));
                    break;
                case "13":
                    $this->player->getLevel()->addSound(new GhastShootSound($this->player));
                    break;
                case "14":
                    $this->player->getLevel()->addSound(new GhastSound($this->player));
                    break;
                case "15":
                    $this->player->getLevel()->addSound(new LaunchSound($this->player));
                    break;
                case "16":
                    $this->player->getLevel()->addSound(new NoteblockSound($this->player));
                    break;
                case "17":
                    $this->player->getLevel()->addSound(new PopSound($this->player));
                    break;
                case "18":
                    $this->player->getLevel()->addSound(new SpellSound($this->player));
                    break;
                case "19":
                    $this->player->getLevel()->addSound(new SplashSound($this->player));
                    break;
                case "20":
                    $this->player->getLevel()->addSound(new TNTPrimeSound($this->player));
                    break;
                default:
                    break;
            }
        }
        return true;
    }

    public function manageItem($it) {
        $it = explode("|", $it);
        if (sizeof($it) < 5) {
            return "false:管理物品功能传入的参数过少！期望数量：5";
        }
        $it[0] = $this->api->executeReturnData($it[0], $this->player);
        switch ($it[0]) {
            case "玩家手持":
                if (!$this->player instanceof Player) {
                    return "false:玩家不存在！";
                }
                $item = $this->player->getInventory()->getItemInHand();
                if ($item->getId() == 0) {
                    return "false:玩家未手持物品";
                }
                $it[2] = $this->api->executeReturnData($it[2], $this->player);
                switch ($it[1]) {
                    case "是否存在nbt":
                        $nbtName = $it[2];
                        $nbt = $item->getNamedTag();
                        if (isset($nbt[$nbtName])) {
                            return $this->doSubCommand($it[3]);
                        } else {
                            return $this->doSubCommand($it[4]);
                        }
                    case "写入nbt":
                        $nbtName = $it[2];
                        $nbt = $item->getNamedTag();
                        $it[3] = $this->api->executeReturnData($it[3], $this->player);
                        $nbt->$nbtName = new StringTag($nbtName, $it[3]);
                        $item->setNamedTag($nbt);
                        return true;
                    case "删除nbt":
                        $nbtName = $it[2];
                        $nbt = $item->getNamedTag();
                        unset($nbt[$nbtName]);
                        $item->setNamedTag($nbt);
                        return true;
                }
        }
        return false;
    }

    public function makeMusic($it) {
        $it = explode("|", $it);
        if (sizeof($it) < 2) {
            return "false:乐谱功能传入参数少于2个";
        }
        $target = $this->api->executeReturnData($it[0], $this->player);
        $it[1] = $this->api->executeReturnData($it[1], $this->player);
        if ($target == "*all") {
            foreach ($this->getPlugin()->getServer()->getOnlinePlayers() as $p) {
                if ($it[1] != "x") {
                    $lis = explode(",", $it[1]);
                    foreach ($lis as $ms)
                        $p->level->addSound(new NoteblockSound($p, 0, intval($ms)));
                }

            }
        } else {
            $p = $this->getPlugin()->getServer()->getPlayerExact($target);
            if ($p != null) {
                $p->level->addSound(new NoteblockSound($p, 0, $it[1]));
            }
        }
        return true;
    }

    /**
     * @param $tag
     * @return bool
     */
    public function setNameTag($tag)//设置名字标签**
    {
        if (!$this->player instanceof Player)
            return "false:玩家不存在！";
        $tag = $this->api->executeReturnData($tag, $this->player);
        $this->player->setNameTag($this->api->msgs($tag, $this->player));
        return true;
    }

    /**
     * @param $it
     * @return bool
     */
    public function writeTagTemp($it)//写入标签缓存**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 2) {
            return "false:传入参数过少";
        }
        $it[1] = $this->api->executeReturnData($it[1], $this->player);
        $id = $it[0];
        WTask::getInstance()->tagTemp[$id] = $it[1];
        return true;
    }

    /**
     * @param $it
     * @return bool
     */
    public function compareText($it) {
        $it = explode("|", $it);
        if (sizeof($it) < 4) {
            return "false:比较字符串功能传入参数不足，期望数量：4";
        }
        $it[0] = $this->api->executeReturnData($it[0], $this->player);
        echo "参数1：" . $it[0] . "\n";
        $it[1] = $this->api->executeReturnData($it[1], $this->player);
        echo "参数2：" . $it[1] . "\n";
        if ($it[0] == $it[1]) {
            return $this->doSubCommand($it[2]);
        } else {
            return $this->doSubCommand($it[3]);
        }
    }

    public function manageConfig($it)//操作自定义配置文件**
    {
        $it = explode("|", $it);
        switch ($it[0]) {
            case "写入":
                if (sizeof($it) < 4) {
                    return "false:传入参数不足！";
                }
                $filename = $it[1];
                if (file_exists($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml")) {
                    $mingzi = $this->api->executeReturnData($it[2], $this->player);
                    $neirong = $this->api->executeReturnData($it[3], $this->player);
                    $conf = new Config($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                    $conf->set($mingzi, $neirong);
                    $conf->save();
                    return true;
                } else {
                    return "false:写入的自定义配置文件不存在！";
                }
            case "强制写入":
                if (sizeof($it) < 4) {
                    return "false:传入参数不足！";
                }
                $filename = $it[1];
                $mingzi = $this->api->executeReturnData($it[2], $this->player);
                $neirong = $this->api->executeReturnData($it[3], $this->player);
                $conf = new Config($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                $conf->set($mingzi, $neirong);
                $conf->save();
                return true;
            case "写入数组":
                if (sizeof($it) < 4) {
                    return "false:传入参数不足！";
                }
                $filename = $it[1];
                if (file_exists($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml")) {
                    $mingzi = $this->api->executeReturnData($it[2], $this->player);
                    $neirong = explode(",", $it[3]);
                    $conf = new Config($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                    $conf->set($mingzi, $neirong);
                    $conf->save();
                    return true;
                } else {
                    return "false:写入的自定义配置文件不存在！";
                }
            case "检查相等":
            case "equal":
                if (sizeof($it) < 5) {
                    return "false:传入参数不足！";
                }
                $origin = $this->api->executeReturnData($it[1], $this->player);
                $target = $this->api->executeReturnData($it[2], $this->player);
                if ($origin == $target) {
                    return $this->doSubCommand($it[3]);
                } else {
                    return $this->doSubCommand($it[4]);
                }
            case "是否存在":
                if (sizeof($it) < 5) {
                    return "false:传入参数不足！";
                }
                $filename = $it[1];
                if (file_exists($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml")) {
                    $conf = new Config($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                    if ($conf->exists($this->api->executeReturnData($it[2], $this->player)))
                        return $this->doSubCommand($it[3]);
                    else
                        return $this->doSubCommand($it[4]);
                } else
                    return "false:传入的自定义配置文件不存在！";
            case "创建":
                $filename = $this->api->executeReturnData($it[1], $this->player);
                new Config($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                return true;
            case "删除":
                if (sizeof($it) < 3) {
                    return "false:传入参数不足！";
                }
                $filename = $it[1];
                if (file_exists($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml")) {
                    $mingzi = $this->api->executeReturnData($it[2], $this->player);
                    $conf = new Config($this->getPlugin()->getDataFolder() . "CustomConfig/" . $filename . ".yml", Config::YAML, array());
                    if ($conf->exists($mingzi)) {
                        $conf->remove($mingzi);
                        $conf->save();
                        return true;
                    } else {
                        return true;
                    }
                } else {
                    return "false:目标配置文件不存在！";
                }
            default:
                return "false:未知功能";
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function checkItemInHand($it) {
        $it = explode("|", $it);
        if (sizeof($it) < 4) {
            return "false:传入参数不足！";
        }
        if (!($this->player instanceof Player)) {
            return "false:检查玩家手持物品功能中玩家不存在！";
        }
        $item = $this->player->getInventory()->getItemInHand();
        $all = $item->getId() . "-" . $item->getDamage() . "-" . $item->getCount();
        switch ($it[0]) {
            case "检查all":
                $it[1] = $this->api->executeReturnData($it[1], $this->player);
                if ($it[1] == $all)
                    $result = true;
                else
                    $result = false;
                break;
            case "检查id和特殊值":
                $it[1] = $this->api->executeReturnData($it[1], $this->player);
                if ($it[1] == ($item->getId() . "-" . $item->getDamage()))
                    $result = true;
                else
                    $result = false;
                break;
            case "检查id":
                $it[1] = $this->api->executeReturnData($it[1], $this->player);
                if ($it[1] == $item->getId())
                    $result = true;
                else
                    $result = false;
                break;
            case "检查特殊值":
                $it[1] = $this->api->executeReturnData($it[1], $this->player);
                if ($it[1] == $item->getDamage())
                    $result = true;
                else
                    $result = false;
                break;
            case "检查数量":
                $it[1] = $this->api->executeReturnData($it[1], $this->player);
                if ($it[1] >= $item->getCount())
                    $result = true;
                else
                    $result = false;
                break;
            default:
                return false;
        }
        if ($result === true) {
            return $this->doSubCommand($it[2]);
        } else {
            return $this->doSubCommand($it[3]);
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function checkInventory($it)//检查背包**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 3) {
            return "false:传入参数不足！";
        }
        $it[0] = $this->api->executeReturnData($it[0], $this->player);
        $it[0] = explode(",", $it[0]);
        if (!$this->player instanceof Player) return "false:玩家不存在！";
        $res = false;
        foreach ($it[0] as $i) {
            $itemdd = explode("-", $i);
            $cnt = 0;
            foreach ($this->player->getInventory()->getContents() as $item) {
                if ($item->getID() == $itemdd[0] and $item->getDamage() == $itemdd[1]) {
                    $cnt += $item->getCount();
                }
                unset($item);
            }
            if ($cnt >= $itemdd[2]) {
                $res = true;
                unset($cnt);
                continue;
            } else {
                $res = false;
                unset($cnt);
                break;
            }
        }
        if ($res === true) {
            return $this->doSubCommand($it[1], $it[0]);
        } else {
            return $this->doSubCommand($it[2]);
        }
    }

    public function manageTemp($it)//检查缓存**
    {
        $it = explode("|", $it);
        $it[1] = $this->api->executeReturnData($it[1], $this->player);
        switch ($it[0]) {
            case "是否存在私有":
                if (!$this->player instanceof Player)
                    return "false:玩家不存在！";
                if (isset(WTask::getInstance()->privateTempData[$this->player->getName()][$it[1]])) {
                    return $this->doSubCommand($it[2]);
                } else {
                    return $this->doSubCommand($it[3]);
                }
            case "是否存在公有":
                if (isset(WTask::getInstance()->publicTempData[$it[1]])) {
                    return $this->doSubCommand($it[2]);
                } else {
                    return $this->doSubCommand($it[3]);
                }
            default:
                return false;
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function checkMoney($it)//检查金钱**
    {
        $it = explode("|", $it);
        $it[0] = $this->api->executeReturnData($it[0], $this->player);
        if (!$this->player instanceof Player) return false;
        if (Server::getInstance()->getPluginManager()->getPlugin("EconomyAPI") === null) return "false:未检测到任何经济核心！";
        $money = EconomyAPI::getInstance()->myMoney($this->player);
        if ($it[0] < $money) {
            return $this->doSubCommand($it[1], $it[0]);
        } else {
            return $this->doSubCommand($it[2]);
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function checkCount($it)//比较数字大小**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 3) {
            return "false:传入参数不足！";
        }
        $fuhao = $this->api->executeCompare($it[0]);
        $number = explode($fuhao, $it[0]);
        $number[0] = $this->api->executeReturnData($number[0], $this->player);
        $number[1] = $this->api->executeReturnData($number[1], $this->player);
        $result = $this->api->calCompare($number[0], $number[1], $fuhao);
        if ($result == true) {
            return $this->doSubCommand($it[1], null);
        } else {
            return $this->doSubCommand($it[2], null);
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function checkGm($it)//检查游戏模式**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 3) {
            return "false:传入参数不足！";
        }
        $it[0] = $this->api->executeReturnData($it[0], $this->player);
        if (!$this->player instanceof Player) return "false:玩家不存在！";
        if ($this->player->getGamemode() == $it[0]) {
            return $this->doSubCommand($it[1], $it[0]);
        } else {
            return $this->doSubCommand($it[2]);
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function makeExplosion($it)//制造爆炸**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 2) {
            return "false:传入参数不足！";
        }
        $it[0] = $this->api->executeReturnData($it[0], $this->player);
        $it[1] = $this->api->executeReturnData($it[1], $this->player);
        $newdd = $this->executeLocation($it[0]);
        $explodep = $newdd;
        $baozha = new Explosion($explodep, $it[1]);
        if ($baozha->explodeA()) $baozha->explodeB();
        return true;
    }

    /**
     * @param $it
     * @return bool
     */
    public function dropItem($it)//生成掉落物**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 2) {
            return "false:传入参数不足！";
        }
        $it[0] = $this->api->executeReturnData($it[0], $this->player);
        $it[1] = $this->api->executeReturnData($it[1], $this->player);
        $levelss = explode(":", $it[0]);
        $level = $levelss[3];
        $level = Server::getInstance()->getLevelByName($level);
        if ($level == null) return false;
        $it[1] = explode(",", $it[1]);
        $pos = new Vector3($levelss[0], $levelss[1], $levelss[2]);
        return $this->dropItems($pos, $it[1], $level);
    }

    /**
     * @param $it
     * @return bool
     */
    public function calculatePercentTask($it)//概率任务**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 3) {
            return "false:传入参数不足！";
        }
        $gailv = $this->ItemPercent($it[0]);
        if ($gailv == true) {
            return $this->doSubCommand($it[1]);
        } else {
            return $this->doSubCommand($it[2]);
        }
    }

    /**
     * @param $it
     * @return bool
     */
    public function setBlock($it)//task的放置方块函数**
    {
        $it = explode("|", $it);
        if (sizeof($it) < 2) {
            return "false:传入参数不足！";
        }
        $t = new SetBlockAPI($it, $this->player, $this->api);
        switch ($it[0]) {
            case "填充":
                $t->fillBlock($it[1], $it[2]);
                break;
            case "复制":
                $t->copyBlock($it[1]);
                break;
            case "粘贴":
                $t->pasteBlock($it[1]);
                break;
        }
        //未完待续....
        return true;
    }

    public function setItemInHand($it) {
        $it = $this->api->executeReturnData($it, $this->player);
        $item = $this->executeItem($it);
        $this->getPlayer()->getInventory()->setItemInHand($item);
        return true;
    }

    /*

        以下是上面函数调用的隐藏函数！

    */
    /**
     * @param $pos
     * @return Position
     */
    public function executeLocation($pos)//解析坐标
    {
        $poss = explode(":", $pos);
        if (sizeof($poss) < 4) {
            return null;
        }
        $locat = new Position($poss[0], $poss[1], $poss[2], Server::getInstance()->getLevelByName($poss[3]));
        if ($locat->level == null) {
            return null;
        }

        return $locat;
    }

    /**
     * @param $item
     * @return Item
     */
    public function executeItem($item)//解析并返回物品
    {
        $items = explode("-", $item);
        if (sizeof($items) < 3) {
            return null;
        }
        return Item::get($items[0], $items[1], $items[2]);
    }

    /**
     * @param $cmdd
     * @param null $items
     * @return bool
     */
    public function doSubCommand($cmdd, $items = null)//检查系列的子命令
    {
        $multitask = explode(",", $cmdd);
        foreach ($multitask as $multi) {
            $cmd = explode(".", $multi);
            switch ($cmd[0]) {
                case "jump":
                case "跳转":
                    return $cmd[1];
                case "消息":
                case "msg":
                    $this->sendMsgPacket($this->player, $this->api->msgs($cmd[1], $this->player), 0);
                    break;
                case "tip":
                case "提示":
                    $this->sendMsgPacket($this->player, $cmd[1], 1);
                    break;
                case "popup":
                case "底部":
                    $this->sendMsgPacket($this->player, $cmd[1], 2);
                    break;
                case "移除物品":
                case "delitem":
                    if ($items != null) {
                        foreach ($items as $itt) {
                            $itt = explode("-", $itt);
                            $this->removeItem($this->player, new Item($itt[0], $itt[1], $itt[2]));
                        }
                    }
                    break;
                case "结束":
                case "end":
                    return "end";
                case "delmoney":
                case "减少金钱":
                    if ($items != null && !is_array($items)) {
                        EconomyAPI::getInstance()->reduceMoney($this->player, $items);
                        break;
                    } else
                        break;
                case "setfinish":
                    $this->api->setNormalTaskDaily($items, $this->player);
                    break;
                case "pass":
                    return true;
            }
        }
        return true;
    }

    /**
     * @param $sender
     * @param $getitem
     * @return bool
     */
    public function removeItem($sender, $getitem)//移除物品（使用MySignShop函数）
    {
        if (!$getitem instanceof Item)
            return false;
        if (!$sender instanceof Player)
            return false;
        $getcount = $getitem->getCount();
        if ($getcount <= 0)
            return false;
        for ($index = 0; $index < $sender->getInventory()->getSize(); $index++) {
            $setitem = $sender->getInventory()->getItem($index);
            if ($getitem->getID() == $setitem->getID() and $getitem->getDamage() == $setitem->getDamage()) {
                if ($getcount >= $setitem->getCount()) {
                    $getcount -= $setitem->getCount();
                    $sender->getInventory()->setItem($index, Item::get(Item::AIR, 0, 1));
                } else if ($getcount < $setitem->getCount()) {
                    $sender->getInventory()->setItem($index, Item::get($getitem->getID(), $getitem->getDamage(), $setitem->getCount() - $getcount));
                    break;
                }
            }
        }
        return true;
    }

    /**
     * @param $pos
     * @param $item
     * @param $level
     * @return bool
     */
    public function dropItems($pos, $item, $level)//生成掉落物**
    {
        if (!$level instanceof Level)
            return false;
        if ($pos instanceof Vector3) {
            foreach ($item as $b) {
                $itemm = explode("-", $b);
                if (isset($itemm[3])) {
                    $mmm = $this->ItemPercent($itemm[3]);
                    if ($mmm != true) {
                        continue;
                    }
                }
                $level->dropItem($pos, Item::get($itemm[0], $itemm[1], $itemm[2]));
            }
            return true;
        }
        return false;
    }

    /**
     * @param int $rand
     * @return bool
     */
    public function ItemPercent(int $rand)//概率计算
    {
        $randc = mt_rand(1, 100);
        if ($rand >= $randc) {
            return true;
        } else
            return false;
    }

    /**
     * @param $p
     * @param $msg
     * @param $type
     */
    public function sendMsgPacket($p, $msg, $type)//发包消息
    {
        switch ($type) {
            case 0:
                $pk = new TextPacket;
                $pk->message = $msg;
                $pk->type = TextPacket::TYPE_RAW;
                $pk->buffer = "task";
                if ($p instanceof Player)
                    $p->dataPacket($pk);
                elseif ($p instanceof ConsoleCommandSender)
                    $p->sendMessage($msg);
                return;
            case 1:
                $pk = new TextPacket;
                $pk->message = $msg;
                $pk->type = TextPacket::TYPE_TIP;
                $pk->buffer = "task";
                if ($p instanceof Player)
                    $p->dataPacket($pk);
                elseif ($p instanceof ConsoleCommandSender)
                    $p->sendMessage($msg);
                return;
            case 2:
                $pk = new TextPacket;
                $pk->message = $msg;
                $pk->type = TextPacket::TYPE_POPUP;
                $pk->buffer = "task";
                if ($p instanceof Player)
                    $p->dataPacket($pk);
                elseif ($p instanceof ConsoleCommandSender)
                    $p->sendMessage($msg);
                return;
        }
    }

    /**
     * @return WTask
     */
    public function getPlugin() {
        return WTask::getInstance();
    }

    /**
     * @return CommandSender|Player
     */
    public function getPlayer() {
        return $this->player;
    }
}