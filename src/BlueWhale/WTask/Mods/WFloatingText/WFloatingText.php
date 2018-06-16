<?php

namespace BlueWhale\WTask\Mods\WFloatingText;

use BlueWhale\WTask\Mods\ModBase;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\event\Listener;
use BlueWhale\WTask\Config;
use pocketmine\math\Vector3;
use pocketmine\level\particle\FloatingTextParticle;
use onebone\economyapi\EconomyAPI;
use BlueWhale\WTask\ScheduleTasks\CallbackTask;

class WFloatingText extends ModBase implements Listener
{
    private $plugin;
    const VERSION = "1.0.0";
    const NAME = "WFloatingText";
    /** @var  Config */
    public $text;
    public $cmd;

    /** @var  FloatingTextParticle */
    public $dynamicText;
    /** @var  FloatingTextParticle */
    public $dynamicTopMoney;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("WFloatingText");
        $this->getCommandMap()->register("WTask", new MainCommand($this, $desc));
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        @mkdir($this->plugin->getDataFolder() . "Mods/WFloatingText/");
        $this->text = new Config($this->plugin->getDataFolder() . "Mods/WFloatingText/" . "text.yml", Config::YAML, array(
            "动态显示" => array(
                "status" => false,
                "显示文本" => "",
                "位置" => "0:0:0:default",
                "刷新时间" => 5
            ),
            "富豪榜" => array(
                "status" => false,
                "位置" => "0:0:0:default",
                "刷新时间" => 5,
                "显示数量" => 8,
                "格式" => "§e==========富豪榜==========%n{list}",
                "list-color" => "§a",
                "富豪榜模式" => 1
            )
        ));
        if ($this->text->get("动态显示")["status"] === true) {
            $this->dynamicText = new FloatingTextParticle($this->executeVector3($this->text->get("动态显示")["位置"]), $this->plugin->api->msgs($this->text->get("动态显示")["显示文本"]));
            $this->plugin->getServer()->getLevelByName(explode(":", $this->text->get("动态显示")["位置"])[3])->addParticle($this->dynamicText);
            $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "onTick"]), $this->text->get("动态显示")["刷新时间"] * 20);
        }
        if ($this->text->get("富豪榜")["status"] === true) {
            $this->dynamicTopMoney = new FloatingTextParticle($this->executeVector3($this->text->get("富豪榜")["位置"]), $this->executeTopMsg($this->text->get("富豪榜")["格式"]));
            $this->plugin->getServer()->getLevelByName(explode(":", $this->text->get("富豪榜")["位置"])[3])->addParticle($this->dynamicTopMoney);
            $this->plugin->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "onTopTick"]), $this->text->get("富豪榜")["刷新时间"] * 20);
        }
    }

    /**
     * @param $msg
     * @return mixed
     */
    public function executeTopMsg($msg) {
        $msg = str_replace("{list}", $this->getTopList(), $msg);
        $msg = str_replace("%n", "\n", $msg);
        $msg = str_replace("++", "  ", $msg);
        return $msg;
    }

    /**
     * @return array|string
     */
    public function getTopList() {
        $datasetting = $this->getText()->get("富豪榜")["显示数量"];
        if ($this->text->get("富豪榜")["富豪榜模式"] == 2)
            $list = EconomyAPI::getInstance()->getAllMoney();
        else {
            $list = EconomyAPI::getInstance()->getAllMoney()["money"];
        }
        if ($list)
            arsort($list);
        $datasetting--;
        $ss = 0;
        $array[0] = 1;
        foreach ($list as $mystyle => $money) {
            $array[] = $this->getText()->get("富豪榜")["list-color"] . "[" . ($ss + 1) . "]" . $mystyle . ": " . $money;
            $ss++;
            if ($ss > $datasetting)
                break;
        }
        array_shift($array);
        $array = implode("%n", $array);
        return $array;
    }

    public function onTopTick() {
        $dat = $this->getText()->get("富豪榜")["格式"];
        $msg = $this->executeTopMsg($dat);
        $this->dynamicTopMoney->setText($msg);
        $this->getServer()->getLevelByName(explode(":", $this->getText()->get("富豪榜")["位置"])[3])->addParticle($this->dynamicTopMoney, $this->getServer()->getOnlinePlayers());
    }

    public function onTick() {
        $dat = $this->getText()->get("动态显示")["显示文本"];
        $this->dynamicText->setText($this->plugin->api->msgs($dat));
        $this->getServer()->getLevelByName(explode(":", $this->getText()->get("动态显示")["位置"])[3])->addParticle($this->dynamicText, $this->getServer()->getOnlinePlayers());

    }

    public function executeVector3($pos) {
        $pos = explode(":", $pos);
        return new Vector3($pos[0], $pos[1], $pos[2]);
    }

    public function onJoin(PlayerJoinEvent $event) {
        $list = $this->getText()->getAll();
        unset($list["动态显示"], $list["富豪榜"]);
        foreach ($list as $tsId => $ts) {
            $this->getServer()->getLevelByName($ts["pos"][3])->addParticle(new FloatingTextParticle(new Vector3($ts["pos"][0], $ts["pos"][1], $ts["pos"][2]), $this->plugin->api->msgs($ts["text"], $event->getPlayer())));
        }
    }

    public function msgs($msg, $p = null)//动态消息API接口
    {
        $tps = (string)$this->getServer()->getInstance()->getTicksPerSecondAverage();
        $minitime = microtime(true) - \pocketmine\START_TIME;
        $uptime = (int)($minitime / 60);
        $load = (string)$this->getServer()->getInstance()->getTickUsageAverage();
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
        }
        $pc = 0;
        foreach ($this->getServer()->getInstance()->getOnlinePlayers() as $pkk) {
            if ($pkk->isOnline()) {
                ++$pc;
            }
            unset($pkk);
        }

        $msg = str_replace("%n", "\n", $msg);
        $msg = str_replace("+", " ", $msg);
        $msg = str_replace("{time}", $time, $msg);

        $msg = str_replace("{tps}", $tps, $msg);
        $msg = str_replace("{online}", $pc, $msg);

        $msg = str_replace("{load}", $load, $msg);
        $msg = str_replace("{runtime}", $uptime, $msg);
        unset($tps, $time, $m, $beibao, $item, $id, $ts, $pc, $load);
        return $msg;
    }

    public function updateInfo($oldVersion)//更新信息(通用的方法)
    {
        switch ($oldVersion) {
            case "0.0.1":
                return null;
            default:
                return null;
        }
    }

    /**
     * @return Config
     */
    public function getText(): Config {
        return $this->text;
    }

    /**
     * @return mixed
     */
    public function getCmd() {
        return $this->cmd;
    }
}

?>