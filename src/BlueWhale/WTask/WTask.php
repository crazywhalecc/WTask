<?php

/*
 *
 * ====================================WTask=====================================
 * Welcome !
 *
 * @author: BlueWhale
 * @Whale's link: http://whalecraft.cn:88/
 * @Plugin Buying link: https://pl.zxda.net/plugins/532.html
 *
*/

namespace BlueWhale\WTask;

//三个最基础的调用
use BlueWhale\WTask\Mods\ModBase;
use BlueWhale\WTask\Mods\ModManager;
use pocketmine\command\CommandSender;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\scheduler\TaskHandler;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;

//WTask自身的API
use BlueWhale\WTask\ScheduleTasks\CallbackTask;
use BlueWhale\WTask\Commands\CustomCommand;
use BlueWhale\WTask\TaskListener\{
    PlayerJoinListener, PlayerMoveListener, PlayerRespawnListener,
    TaskListener, BlockBreakListener, BlockPlaceListener,
    PlayerInteractListener, PlayerDeathListener, PlayerDropItemListener,
    PlayerChatListener, PlayerCommandActivateListener,
    PlayerTeleportListener, EntityDamageListener
};

//外部API
use pocketmine\utils\TextFormat;

/*   §   */

class WTask extends PluginBase
{
    private static $obj = null;

    /** @var */
    public $normalDelayer;

    const CONFIG_VERSION = 6;

    /*
        以下两个是缓存~
    */
    public $privateTempData = array();
    public $publicTempData = array();
    public $tagTemp = array("", "", "", "", "", "", "", "", "");
    public $recoveryTempData = [];
    public $pasteTempData;
    public $allowedDatabase = array(
        "chineseItem", "IPAddress"
    );
    /*
        以下两个好像是循环任务的东西
    */
    public $runningRepeatTask = array();
    public $runningRepeatTaskStatus = array();

    public $normalTaskList = array();//运行任务时候put进来的任务内容
    public $actTaskList = array();//运行任务时候put进来的任务内容
    public $repeatTaskList = array();//运行任务时候put进来的任务内容

    public $taskData = array();//所有任务的所有数据都在这里

    /** @var TaskListener[] */
    public $actTaskListener = array();//监听器存放位置

    private $modManager;

    public $currentVersion = "3.0.0";//当前版本

    /** @var WTaskAPI */
    public $api = null;

    /** @var  ModBase */
    public $myMods;

    public $config = null;
    public $mod;
    public $playerPerm;
    public $commands;
    public $msg;
    public $daily;
    public $customCommand;
    public $database = [];
    public $customFunction;

    public $path = null;
    public $taskPath = null;

    public function onLoad()//加载
    {
        $this->api = new WTaskAPI($this);
        $this->getServer()->getLoader()->addPath("plugins/extMods/");
        self::$obj = $this;
        $this->path = $this->getDataFolder();
        $this->currentVersion = $this->getDescription()->getVersion();
        $this->taskPath = $this->getDataFolder() . "tasks/";
        $this->currentVersion = $this->getDescription()->getVersion();
    }

    /**
     * 启动运行的函数
     */
    public function onEnable() {
        $this->createConfig();
        $this->registerSettings();
        $this->updateData($this->getConfig()->get("Config-Version"));//升级配置文件
        $this->registerCommands();//注册指令
        $this->registerMods();//注册模块

        $this->api->loadTasks();
        $this->enableActTasks();
        $this->enableRepeatTasks();
        $this->enableCustomCommands();
        $this->getServer()->getLogger()->notice("§a成功开启WTask v" . $this->currentVersion . " !");
    }

    /**
     * 启用动作任务的监听
     */
    public function enableActTasks() {
        if (!$this->api instanceof WTaskAPI)
            return;
        foreach ($this->taskData as $taskName => $taskIn) {
            if ($taskIn["type"] != "动作任务")
                continue;
            if ($taskIn["actActive"] === false)
                continue;
            $tn = $this->api->prepareTask($taskName);
            switch ($taskIn["actType"]) {
                case "破坏方块"://
                    $this->actTaskListener[$taskName] = new BlockBreakListener($this->api, $tn, $taskName);
                    break;
                case "放置方块"://
                    $this->actTaskListener[$taskName] = new BlockPlaceListener($this->api, $tn, $taskName);
                    //echo "超级棒@\n";
                    break;
                case "玩家点击":
                    $this->actTaskListener[$taskName] = new PlayerInteractListener($this->api, $tn, $taskName);
                    break;
                case "玩家死亡":
                    $this->actTaskListener[$taskName] = new PlayerDeathListener($this->api, $tn, $taskName);
                    break;
                case "玩家丢弃物品":
                    $this->actTaskListener[$taskName] = new PlayerDropItemListener($this->api, $tn, $taskName);
                    break;
                case "玩家输入指令"://
                    $this->actTaskListener[$taskName] = new PlayerCommandActivateListener($this->api, $tn, $taskName);
                    break;
                case "玩家聊天"://
                    $this->actTaskListener[$taskName] = new PlayerChatListener($this->api, $tn, $taskName);
                    break;
                case "玩家传送":
                    $this->actTaskListener[$taskName] = new PlayerTeleportListener($this->api, $tn, $taskName);
                    break;
                case "玩家攻击玩家"://
                    $this->actTaskListener[$taskName] = new EntityDamageListener($this->api, $tn, $taskName);
                    break;
                case "玩家加入":
                    $this->actTaskListener[$taskName] = new PlayerJoinListener($this->api, $tn, $taskName);
                    break;
                case "玩家移动":
                    $this->actTaskListener[$taskName] = new PlayerMoveListener($this->api, $tn, $taskName);
                    break;
                case "玩家重生":
                    $this->actTaskListener[$taskName] = new PlayerRespawnListener($this->api, $tn, $taskName);
                    break;
                default:
                    $this->getServer()->getLogger()->warning("动作任务 $taskName 错误！ 未知的监听类型！");
                    break;
            }
            $ty = $taskIn["actType"];
            $this->getServer()->getLogger()->notice("成功启动 [ $ty ] 动作任务 $taskName !");
            //unset($taskName, $taskIn, $ty, $tn);
        }
    }

    /**
     * 升级WTask配置文件
     * @param $version
     */
    public function updateData($version) {
        if ($version != self::CONFIG_VERSION) {
            $this->getServer()->getLogger()->notice("正在升级配置文件...");
        }
        switch ($version) {
            case 3:
                $modList = $this->getMod()->getAll();
                unset($modList["SitDown"]);
                $this->getMod()->setAll($modList);
                $this->getMod()->save();
                $this->getConfig()->set("Config-Version", self::CONFIG_VERSION);
                $this->getConfig()->save();
                $this->updateData(4);
                break;
            case 4:
                $dir = scandir($this->getDataFolder() . "normalTasks/");
                $path = $this->getDataFolder() . "normalTasks/";
                unset($dir[0], $dir[1]);
                $line = "";
                foreach ($dir as $dirs) {
                    $taskName = explode(".", $dirs)[0];
                    $taskk = new Config($path . $dirs, Config::JSON, array());
                    $line = $taskk->get("任务线程");
                    if (is_array($line)) {
                        $line = implode(";", $line);
                    }
                    $line = str_replace(">;<", ">\n<", $line);
                    $daily = $taskk->get('daily-mode');
                    $this->api->addNormalTask($taskName, "*daily-mode:" . $daily . "*\n" . $line);
                    //@unlink($path.$dirs);
                    $this->getServer()->getLogger()->info("§6[WTask] 已成功转换普通任务 $taskName ! 部分任务中的设置可能需要重新设置，请根据教程设置即可！");
                }
                $repeatTask = new Config($this->getDataFolder() . "repeatTask.json", Config::JSON, array());
                foreach ($repeatTask->getAll() as $taskName => $dss) {
                    $lines = $dss;
                    if (is_array($lines["任务线程"])) {
                        $line = implode(";", $lines["任务线程"]);
                    }
                    $line = str_replace(">;<", ">\n<", $line);
                    $this->api->addRepeatTask(($lines["开服运行"] === true ? "true" : "false"), $taskName, $lines["循环周期"], $line);
                    $this->getServer()->getLogger()->info("§6[WTask] 已成功转换循环任务 $taskName !");
                }
                unset($taskName, $dss);
                $actTask = new Config($this->getDataFolder() . "actTask.json", Config::JSON, array());
                foreach ($actTask->getAll() as $taskName => $dss) {
                    $lines = $dss;
                    if (is_array($lines["任务线程"])) {
                        $line = implode(";", $lines["任务线程"]);
                    }
                    $line = str_replace(">;<", ">\n<", $line);
                    $this->api->addActTask($lines["动作类型"], $taskName, ($lines["状态"] === true ? "true" : "false"), $line);
                    $this->getServer()->getLogger()->info("§6[WTask] 已成功转换动作任务 $taskName !");
                }
                $this->getCommands()->remove("ResourceCommand");
                $this->getCommands()->remove("RepeatTaskCommand");
                $this->getCommands()->save();
                $this->getMod()->remove("WBanFly");
                $this->getMod()->save();
                $this->getConfig()->set("Config-Version", self::CONFIG_VERSION);
                $this->getConfig()->save();
                $this->api->deldir($this->getDataFolder() . "normalTasks/");
                @unlink($this->getDataFolder() . "actTask.json");
                @unlink($this->getDataFolder() . "repeatTask.json");
                @unlink($this->getDataFolder() . "Resources.json");
                $this->updateData(5);
                break;
            case 5:
                $this->getMod()->remove("BanCommands");
                $this->getMod()->save();
                $this->getConfig()->set("Config-Version", self::CONFIG_VERSION);
                $this->getConfig()->save();
                break;
            default:
                break;
        }

    }

    /**
     * 返回当前实例
     * @return WTask|null
     */
    public static function getInstance() { return self::$obj; }

    /**
     * 返回WTask版本
     * @return string
     */
    public function getWTaskVersion() { return $this->getDescription()->getVersion(); }

    /**
     * 返回玩家的权限
     * @param $p
     * @return bool|int|mixed
     */
    public function getPerm($p) {
        if ($p instanceof ConsoleCommandSender) {
            if ($this->config instanceof Config)
                return $this->config->get("op默认权限");
        } elseif ($p instanceof Player) {
            if ($p->isOp()) {
                if ($this->config instanceof Config)
                    return $this->config->get("op默认权限");
            } else {
                $name = strtolower($p->getName());
                if ($this->playerPerm instanceof Config)
                    if ($this->playerPerm->exists($name)) {
                        return $this->playerPerm->get($name);
                    } else {
                        if ($this->config instanceof Config)
                            return $this->getConfig()->get("玩家默认权限");
                    }
            }
        } else {
            $name = strtolower($p);
            if ($this->getPlayerPerm()->exists($name)) {
                return $this->getPlayerPerm()->get($name);
            } else {
                return $this->getConfig()->get("玩家默认权限");
            }
        }
        return 1;
    }

    /**
     * 创建配置文件
     */
    public function createConfig() {
        @mkdir($this->path);
        @mkdir($this->taskPath);
        @mkdir($this->path . "CustomConfig/");
        @mkdir($this->path . "Mods/");
        $this->config = new Config($this->path . "config.yml", Config::YAML, array(
            "Config-Version" => WTask::CONFIG_VERSION,
            "检查更新" => false,
            "auto-save-time" => 2,
            "玩家默认权限" => 5,
            "op默认权限" => 255,
            "默认任务文件格式" => "cc",
            "普通任务默认权限" => 1,
            "lineTask默认权限" => 200,
            "自定义功能扩展开关" => false,
            "allow-ext-mod" => false
        ));
        if ($this->config->get("自定义功能扩展开关") == true) {
            $this->customFunction = new Config($this->path . "customFunction.yml", Config::YAML, array());
        }
        $this->playerPerm = new Config($this->path . "permissions.yml", Config::YAML, array());
        $this->commands = new Config($this->path . "command.json", Config::JSON, array(
            "MainCommand" => array(
                "command" => "wtask",
                "description" => "§6WTask设置的主命令",
                "permission" => "op"
            ),
            "ActNormalTaskCommand" => array(
                "command" => "运行",
                "description" => "§6WTask的普通型任务激活指令",
                "permission" => "true",
                "multiple" => array("wt")
            ),
            "ModBaseCommand" => array(
                "command" => "模块系统",
                "description" => "§6WTask的模块系统指令",
                "permission" => "op"
            ),
            "SetTempCommand" => array(
                "command" => "settemp",
                "description" => "§6Setting your temp database",
                "permission" => "true",
                "setting" => "private"
            )
        ));
        $this->msg = new Config($this->path . "messages.json", Config::JSON, array(
            "task-not-exist" => "§c[WTask]对不起，这个任务不存在！",
            "once-task-finished" => "对不起，这是个一次性的任务，你已经运行过了！",
            "limit-time-task-finished" => "对不起，今天已经做过了哦！"
        ));
        $this->daily = new Config($this->path . "Finish.json", Config::JSON, array(
            "普通任务" => array()
        ));
        $this->mod = new Config($this->path . "mods.json", Config::JSON, array(
            "Unzip" => array(
                "status" => false,
                "type" => array("Command"),
                "version" => "1.0.0",
                "command" => "解压",
                "permission" => "op",
                "description" => "§bWTask的解压zip模块"
            ),
            "CrazyBlock" => array(
                "status" => false,
                "type" => array("Command", "Listener"),
                "version" => "1.0.0",
                "command" => "cb",
                "permission" => "op",
                "description" => "§bWTask的CrazyBlock模块"
            ),
            "DisableFlow" => array(
                "status" => false,
                "type" => array("Listener"),
                "version" => "1.0.0",
                "description" => "§bWTask的禁止岩浆和水流动的模块"
            ),
            "QueryPos" => array(
                "status" => true,
                "type" => array("Command", "Listener"),
                "version" => "1.0.0",
                "command" => "query",
                "permission" => "true",
                "description" => "§bWTask的查询方块坐标模块"
            ),
            "BossBar" => array(
                "status" => false,
                "type" => array("Command", "Listener"),
                "version" => "1.0.0",
                "command" => "bb",
                "permission" => "op",
                "description" => "§bWTask的Boss血量条显示模块"
            ),
            "WRobot" => array(
                "status" => true,
                "type" => array("Command", "Listener"),
                "version" => "1.0.0",
                "command" => "wrobot",
                "permission" => "op",
                "description" => "§6WRobot智能聊天机器人模块"
            ),
            "WChatCommand" => array(
                "status" => true,
                "type" => array("Command", "Listener"),
                "version" => "1.0.0",
                "command" => "聊天命令",
                "permission" => "op",
                "description" => "§6WChatCommand聊天执行命令模块"
            ),
            "CrazyKey" => array(
                "status" => false,
                "type" => array("Listener"),
                "version" => "1.0.0",
                "description" => "§6卡密模块"
            ),
            "CrazyNPC" => array(
                "status" => false,
                "type" => array("Command", "Listener"),
                "version" => "1.0.0",
                "command" => "crazy",
                "permission" => "op",
                "description" => "§6CrazyNPC模块"
            ),
            "WSimulation" => array(
                "status" => true,
                "type" => array("Command"),
                "version" => "1.0.0",
                "command" => "ws",
                "permission" => "op",
                "description" => "§6WSimulation模拟玩家执行指令模块"
            ),
            "WFloatingText" => array(
                "status" => true,
                "type" => array("Command"),
                "version" => "1.0.0",
                "command" => "wf",
                "permission" => "op",
                "description" => "§6WFloatingText浮空字模块"
            ),
            "WProtect" => array(
                "status" => true,
                "type" => array("Command", "Listener"),
                "version" => "1.0.0",
                "command" => "wp",
                "permission" => "op",
                "description" => "§6WProtect世界保护模块"
            ),
            "WPhar" => array(
                "status" => true,
                "type" => ["Command", "Listener"],
                "version" => "1.0.0",
                "description" => "WPhar解压压缩模块，提供便捷的phar文件解压压缩两种服务"
            )
        ));
        $this->customCommand = new Config($this->getDataFolder() . "customCommands.json", Config::JSON, array());
        if (file_exists($this->getDataFolder() . "iPhone.json")) {
            $cf = new Config($this->getDataFolder() . "iPhone.json", Config::JSON, array());
            foreach ($cf->getAll() as $key => $value) {
                switch ($key) {
                    case "customCommands":
                        foreach ($value as $key2 => $value2) {
                            $this->customCommand->set($key2, $value2);
                            $this->customCommand->save();
                        }
                        break;
                    case "Finish":
                        foreach ($value as $key2 => $value2) {
                            $this->daily->set($key2, $value2);
                            $this->daily->save();
                        }
                        break;
                    case "permissions":
                        foreach ($value as $key2 => $value2) {
                            $this->playerPerm->set($key2, $value2);
                            $this->playerPerm->save();
                        }
                        break;
                    case "package":
                        break;
                }
            }
            @unlink($this->getDataFolder() . "iPhone.json");
            unset($cf);
            $this->getServer()->getLogger()->info(TextFormat::GREEN . "已检测到自动释放文件，已自动释放项目内容～");
        }
    }

    /**
     * 启动自定义指令系统
     */
    public function enableCustomCommands() {
        foreach ($this->getCustomCommand()->getAll() as $cmdData) {
            $this->getServer()->getCommandMap()->register("WTask", new CustomCommand($this, $cmdData));
        }
    }

    /**
     * 检测是否在区域内的神奇函数
     * @param $pos
     * @param $p1
     * @param $p2
     * @return bool
     */
    public function isInArea($pos, $p1, $p2) {
        $pos = explode(":", $pos);
        $p1 = explode(":", $p1);
        $p2 = explode(":", $p2);
        $x1 = ($p1[0] <= $p2[0] ? $p1[0] : $p2[0]);
        $x2 = ($p1[0] > $p2[0] ? $p1[0] : $p2[0]);
        $y1 = ($p1[1] <= $p2[1] ? $p1[1] : $p2[1]);
        $y2 = ($p1[1] > $p2[1] ? $p1[1] : $p2[1]);
        $z1 = ($p1[2] <= $p2[2] ? $p1[2] : $p2[2]);
        $z2 = ($p1[2] > $p2[2] ? $p1[2] : $p2[2]);
        if ($pos[0] >= $x1 && $pos[0] <= $x2 && $pos[1] >= $y1 && $pos[1] <= $y2 && $pos[2] >= $z1 && $pos[2] <= $z2) {
            unset($p1, $p2, $x1, $x2, $y1, $y2, $z1, $z2, $pos);
            return true;
        } else
            unset($p1, $p2, $x1, $x2, $y1, $y2, $z1, $z2, $pos);
        return false;
    }

    /**
     * 获取配置文件数据（修改）
     * @param $type
     * @param $key
     * @return bool|mixed|null
     */
    public function getData($type, $key) {
        switch ($type) {
            case "command":
                return $this->getCommands()->get($key);
            case "config":
                return $this->getConfig()->get($key);
            case "msg":
                return $this->getMsg()->get($key);
            case "daily":
                return $this->getDaily()->get($key);
            case "mod":
                return $this->getMod()->get($key);
            default:
                return null;
        }
    }

    /**
     * 获取插件名字.phar
     * @return bool|null|string
     */
    public function getPharName() {
        $dir = "plugins/";
        $dh = opendir($dir);
        while ($file = readdir($dh)) {
            if ($file != "." && $file != "..") {
                $fullPath = $dir . "/" . $file;
                if (!is_dir($fullPath)) {
                    $pharName = $file;
                    $class = $this->getPluginLoader()->getPluginDescription($dir . $pharName);
                    if ($class->getName() == "WTask") {
                        return $pharName;
                    }
                }
            }
        }
        return null;
    }

    /**
     * 更新模块的配置文件
     * @param $name
     * @param string $version
     */
    public function updateModuleVersion($name, string $version) {
        $mod = $this->getMod()->get($name);
        //$this->getMyMods($name)->updateInfo($mod["version"]);
        $mod["version"] = $version;
        $this->getConfig()->set($name, $mod);
        $this->getConfig()->save(true);
    }

    /**
     * 回调动态函数
     * @param $tn
     * @param Player|ConsoleCommandSender $p
     * @param $delayStep
     * @param $delayTime
     */
    public function WantToDelay($tn, $p, $delayStep, $delayTime) {
        $this->normalDelayer[$p->getName()] = Server::getInstance()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "RemoteRunNormalTask"], [$tn, $p, $delayStep]), $delayTime * 20);
    }

    /**
     * 回调动态函数
     * @param $tn
     * @param CommandSender|Player $p
     * @param $delayStep
     */
    public function RemoteRunNormalTask($tn, $p, $delayStep) {
        $this->api->runNormalTask($tn, $p, $delayStep);
        unset($this->normalDelayer[$p->getName()]);
    }

    /**
     * 获取模块资源信息
     * @param $modname
     * @return bool|mixed
     */
    public function getModule($modname) {
        return $this->getMod()->get($modname);
    }

    /**
     * 设置资源（修改）
     * @param $type
     * @param $key
     * @param $m
     * @return bool
     */
    public function setData($type, $key, $m) {
        switch ($type) {
            case "config":
                $this->getConfig()->set($key, $m);
                $this->getConfig()->save(true);
                return true;
            case "command":
                $this->getCommands()->set($key, $m);
                $this->getCommands()->save();
                return true;
            case "msg":
                $this->getMsg()->set($key, $m);
                $this->getMsg()->save(true);
                return true;
            case "daily":
                $this->getDaily()->set($key, $m);
                $this->getDaily()->save(true);
                return true;
            case "mod":
                $this->getMod()->set($key, $m);
                $this->getMod()->save();
                return true;
            default:
                return false;
        }
    }

    /**
     * 注册所有模块
     * @return bool
     */
    protected function registerMods() {
        $list = $this->getMod()->getAll();
        foreach ($list as $nm => $mb) {
            if ($mb["status"] == false)
                continue;
            $class = "\\BlueWhale\\WTask\\Mods\\" . $nm . "\\" . $nm;
            $this->myMods[$nm] = new $class();
            $this->getModManager()->enableMod($this->myMods[$nm]);
            $this->getServer()->getLogger()->info("§a[WTask] 成功开启模块: $nm v" . $this->myMods[$nm]::VERSION . "!");
        }
        return true;
    }

    /**
     * 注册模块
     * @param string $nm
     * @return bool
     */
    public function registerMod($nm = "") {
        $class = "\\BlueWhale\\WTask\\Mods\\" . $nm . "\\" . $nm;
        $this->myMods[$nm] = new $class();
        $this->getModManager()->enableMod($this->myMods[$nm]);
        $this->getServer()->getLogger()->info("§a[WTask] 成功开启模块: $nm v" . $this->myMods[$nm]::VERSION . "!");
        return true;
    }

    /**
     * 注册外部模块
     */
    public function registerExternalMods() {
        $path = "plugins/extMods/";
        @mkdir($path);
        $dir = scandir($path);
        unset($dir[0], $dir[1]);
        foreach ($dir as $dirs) {
            if (strripos($dirs, ".php") !== false) {
                if (strripos($dirs, "Command") !== false) {
                    continue;
                }
                $name = explode(".php", $dirs)[0];
                $class = "mods\\" . $name;
                if (class_exists($class)) {
                    $this->myMods[$name] = new $class();
                    $this->getModManager()->enableMod($this->myMods[$name]);
                    $this->getServer()->getLogger()->info("§a[WTask] 成功开启外置模块: $name v" . $this->myMods[$name]::VERSION . "!");
                }
            }
        }
    }

    /**
     * 注册WTask的指令函数
     * @return bool
     */
    protected function registerCommands() {
        $data = $this->getCommands()->getAll();
        foreach ($data as $l => $ins) {
            $map = $this->getServer()->getCommandMap();
            $class = "\\BlueWhale\\WTask\\Commands\\" . $l;
            $map->register("WTask", new $class($this));
        }
        return true;
    }

    /**
     * 注册监听器（有待观察）（已修改？？）
     */
    protected function registerSettings() {
        $this->initializeDatabase();
        $this->modManager = new ModManager($this);
        if ($this->getConfig()->get("allow-ext-mod") == true) {
            $this->registerExternalMods();
        }
    }

    /**
     * 加载外置数据库
     */
    protected function initializeDatabase() {
        $path = $this->getDataFolder() . "database/";
        @mkdir($path);
        $dir = scandir($path);
        unset($dir[0], $dir[1]);
        foreach ($dir as $cf) {
            $tyy = explode(".", $cf);
            if ($tyy[1] != "yml") {
                continue;
            }
            if (!in_array($tyy[0], $this->allowedDatabase)) {
                $this->getServer()->getLogger()->notice("数据库 " . $tyy[0] . " 加载失败！未知类型的数据库！");
                continue;
            }
            $this->database[$tyy[0]] = new Config($path . $cf, Config::YAML, array());
            $this->getServer()->getLogger()->notice("成功加载外置数据库: " . $tyy[0] . "!");
        }
    }

    /**
     * 返回数据库
     * @param string $name
     * @return null
     */
    public function getDatabase(string $name) {
        if (isset($this->database[$name])) {
            return $this->database[$name]->getAll();
        }
        return null;
    }

    /**
     * 启动所有循环任务
     */
    public function enableRepeatTasks() {
        foreach ($this->taskData as $taskName => $data) {
            if ($data["type"] != "循环任务")
                continue;
            if ($data["repeatActive"] === false)
                continue;
            $ar = $this->api->prepareTask($taskName);
            $this->repeatTaskList[$taskName] = $ar;
            $this->preRepeatTask($taskName);
        }
    }

    /**
     * 准备循环任务
     * @param $taskName
     */
    public function preRepeatTask($taskName) {
        if ($this->taskData[$taskName]["type"] != "循环任务")
            return;
        $this->runningRepeatTaskStatus[$taskName] = $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "runRepeatTask"], [$taskName]), $this->taskData[$taskName]["repeatTime"] * 20);
    }

    /**
     * 运行循环任务的tick回调函数
     * @param $taskName
     * @param null $delayStep
     * @param null $p
     * @return bool
     */
    public function runRepeatTask($taskName, $delayStep = null, $p = null) {
        $t = new RepeatTaskAPI($this->api, $taskName, $p);
        if ($delayStep == null)
            $ID = 0;
        elseif (is_numeric($delayStep)) {
            $ID = $delayStep;
            $delayStep = null;
        } else
            $ID = 0;
        while (isset($this->repeatTaskList[$taskName][$ID])) {
            $inside = $this->repeatTaskList[$taskName][$ID];
            switch ($inside["type"]) {
                case "广播消息":
                    $t->broadcastMessage($inside["function"]);
                    break;
                case "广播tip":
                case "广播提示":
                    $t->broadcastTip($inside["function"]);
                    break;
                case "广播底部":
                case "广播popup":
                    $t->broadcastPopup($inside["function"]);
                    break;
                case "delay":
                case "延迟":
                    $delayStep = $ID + 1;
                    $delaytime = $inside["function"];
                    $this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this, "runRepeatTask"], [$taskName, $delayStep]), $delaytime * 20);
                    return true;
                default:
                    $result = $t->api->defaultFunction($t, $inside);
                    if ($result === true || $result == "true") {
                        break;
                    } elseif (is_numeric($result)) {
                        $ID = $result - 2;
                    } elseif ($result == "end") {
                        $ID = 10000;
                    } elseif ($result === false) {
                        $this->getServer()->getLogger()->warning("WTask任务：" . $taskName . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                    } else {
                        $ssp = explode(":", $result);
                        if ($ssp[0] == "false") {
                            $this->getServer()->getLogger()->warning("WTask任务：" . $taskName . " 在运行第 " . ($ID + 1) . " 号任务时候出现了错误！");
                            $this->getServer()->getLogger()->warning("错误信息：" . $ssp[1]);
                        }
                        $this->getServer()->getLogger()->notice("WTask任务： " . $taskName . " 在运行第 " . ($ID + 1) . " 号任务时候返回了未知内容！");
                    }
                    break;
            }
            $ID++;
            unset($inside);
        }
        unset($t, $tnn, $ID, $delayStep);
        return true;
    }

    /**
     * 发送自定义列表
     * @param CommandSender $p
     * @return bool
     */
    public function sendCommandList(CommandSender $p)
    {
        $cmds = $this->getCommands()->getAll();
        $p->sendMessage("§6=====WTask指令列表=====");
        foreach ($cmds as $cmd => $dat) {
            $p->sendMessage("§e" . $dat["description"] . ": \n * §b指令: /" . $dat["command"]);
            unset($cmd, $dat);
        }
        unset($p, $cmds);
        return true;
    }

    /**
     * 消息发包
     * @param Player $p
     * @param $msg
     * @param int $type
     */
    public function sendMsgPacket(Player $p, $msg, $type = 0)
    {
        switch ($type) {
            case 0:
                $pk = new TextPacket;
                $pk->message = $this->api->msgs($msg, $p);
                $pk->type = TextPacket::TYPE_RAW;
                $pk->buffer = "task";
                $p->dataPacket($pk);
                return;
            case 1:
                $pk = new TextPacket;
                $pk->message = $this->api->msgs($msg, $p);
                $pk->type = TextPacket::TYPE_TIP;
                $pk->buffer = "task";
                $p->dataPacket($pk);
                return;
            case 2:
                $pk = new TextPacket;
                $pk->message = $this->api->msgs($msg, $p);
                $pk->type = TextPacket::TYPE_POPUP;
                $pk->buffer = "task";
                $p->dataPacket($pk);
                return;
        }
    }

    /**
     * 获取任务路径
     * @return null
     */
    public function getTaskPath() {
        return $this->taskPath;
    }

    /**
     * 获取延迟计时器
     * @return mixed
     */
    public function getNormalDelayer() {
        return $this->normalDelayer;
    }

    /**
     * 获取更新信息（将要遗弃）
     * @return string
     */
    public function getUpdateInfo() {
        $v = $this->getWTaskVersion();
        if (file_exists($this->path . "update.yml"))
            @unlink($this->path . "update.yml");
        $this->saveResource("update.yml");
        $cfg = new Config($this->getDataFolder() . "update.yml", Config::YAML, array());
        if (!$cfg->exists($v)) {
            return "暂无更新日志";
        } else {
            return implode("\n", $cfg->get($v));
        }
    }

    /**
     * 更新任务信息（虽然时间长了我也不知道这个是做什么的了2333）
     * @param $line
     * @param $id
     * @return int|string
     */
    public function subUpdateInfo($line, $id) {
        $finalId = 0;
        foreach ($line as $subId => $cc) {
            if ($subId <= $id)
                continue;
            if (substr($cc, 0, 1) == "[") {
                $finalId = $subId - 1;
                break;
            } elseif ($cc == "&&eof") {
                $finalId = $subId - 1;
                break;
            }
        }
        return $finalId;
    }

    /**
     * 获取config
     * @return null
     */
    public function getConfig2() {
        return $this->config;
    }

    /**
     * 获取玩家权限config
     * @return mixed
     */
    public function getPlayerPerm(): Config {
        return $this->playerPerm;
    }

    /**
     * 获取自定义指令config
     * @return mixed
     */
    public function getCustomCommand(): Config {
        return $this->customCommand;
    }

    /**
     * 获取指令config
     * @return Config
     */
    public function getCommands(): Config {
        return $this->commands;
    }

    /**
     * 获取模块config
     * @return mixed
     */
    public function getMod(): Config {
        return $this->mod;
    }

    /**
     * 获取WTask插件内可自定义文本的消息config
     * @return mixed
     */
    public function getMsg(): Config {
        return $this->msg;
    }

    /**
     * 获取定时任务的config
     * @return mixed
     */
    public function getDaily(): Config {
        return $this->daily;
    }

    /**
     * 获取模块实例
     * @param string $name
     * @return mixed
     */
    public function getMyMods(string $name) {
        return $this->myMods[$name];
    }

    /**
     * 获取循环任务的计时器handler
     * @param string $name
     * @return TaskHandler
     */
    public function getRunningRepeatTaskStatus(string $name): TaskHandler {
        return $this->runningRepeatTaskStatus[$name];
    }

    /**
     * 获取自定义功能的config
     * @return Config
     */
    public function getCustomFunction(): Config {
        return $this->customFunction;
    }

    /**
     * 获取模块管理器实例
     * @return mixed
     */
    public function getModManager() {
        return $this->modManager;
    }
}