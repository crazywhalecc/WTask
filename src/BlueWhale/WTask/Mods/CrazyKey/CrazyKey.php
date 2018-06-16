<?php

namespace BlueWhale\WTask\Mods\CrazyKey;

use BlueWhale\WTask\Mods\ModBase;
use BlueWhale\WTask\Config;

class CrazyKey extends ModBase
{
    private $randomCode = ['1' => '1', '2' => '2', '3' => '3', '4' => '4', '5' => '5', '6' => '6', '7' => '7', '8' => '8', '9' => '9', '0' => '0', 'Q' => 'Q', 'W' => 'W', 'E' => 'E', 'R' => 'R', 'T' => 'T', 'Y' => 'Y', 'U' => 'U', 'P' => 'P', 'A' => 'A', 'S' => 'S', 'D' => 'D', 'F' => 'F', 'G' => 'G', 'H' => 'H', 'J' => 'J', 'K' => 'K', 'L' => 'L', 'Z' => 'Z', 'X' => 'X', 'C' => 'C', 'V' => 'V', 'B' => 'B', 'N' => 'N', 'M' => 'M'];
    const VERSION = "1.0.0";
    const NAME = "CrazyKey";

    public $plugin;
    public $command;
    public $card;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("CrazyKey");
        @mkdir($this->getWTask()->getDataFolder() . "Mods/CrazyKey/");
        if (self::VERSION != $desc["version"]) {
            $this->plugin->updateModuleVersion(self::NAME, self::VERSION);
        }
        $this->command = new Config($this->plugin->getDataFolder() . "Mods/CrazyKey/" . "command.yml", Config::YAML, array(
            "MainCommand" => array(
                "command" => "ck",
                "permission" => "op",
                "description" => "§2CrazyKey卡密模块主指令"
            ),
            "UseCommand" => array(
                "command" => "卡密",
                "permission" => "true",
                "description" => "§2CrazyKey卡密使用指令"
            )
        ));
        $this->card = new Config($this->plugin->getDataFolder() . "Mods/CrazyKey/" . "card.yml", Config::YAML, array(
            "ID" => 0,
            "use-msg" => "成功使用卡密！",
            "card" => array()
        ));
        $this->registerCommands();
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

    private function registerCommands()//注册子命令
    {
        foreach ($this->getCommand()->getAll() as $cmdName => $data) {
            $map = $this->getServer()->getCommandMap();
            $class = "\\BlueWhale\\WTask\\Mods\\CrazyKey\\" . $cmdName;
            $map->register("WTask", new $class($this));
        }
    }

    public function generateCode()//生成序列号
    {
        $result = [];
        $r1 = array_rand($this->randomCode, 5);
        $result[] = implode("", $r1);
        $r2 = array_rand($this->randomCode, 5);
        $result[] = implode("", $r2);
        $r3 = array_rand($this->randomCode, 5);
        $result[] = implode("", $r3);
        $r4 = array_rand($this->randomCode, 5);
        $result[] = implode("", $r4);
        //$r5=array_rand($this->randomCode,5);
        //$result[]=implode("",$r5);
        $results = implode("", $result);
        return $results;
    }

    public function checkCode($code)//检查序列号使用情况
    {
        $arrays = $this->getCard()->get("card");
        foreach ($arrays as $key => $my) {
            if ($my["key"] == $code) {
                if ($my["use-time"] >= $my["all-time"]) {
                    return "used";
                } else {
                    return $key;
                }
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getCommand() {
        return $this->command;
    }

    /**
     * @return mixed
     */
    public function getCard() {
        return $this->card;
    }
}