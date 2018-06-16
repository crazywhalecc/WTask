<?php

namespace BlueWhale\WTask\Mods\WRobot;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\Utils\Utils;
use pocketmine\command\ConsoleCommandSender;

class TuringTask extends AsyncTask
{
    public $result = "";
    const ROBOT_API = 'c6297a8608f54e6ebb9129a26679d6c6';
    private $text;
    private $player;
    private $server;
    private $color;
    private $mod;

    public function __construct($p, $string, $color = "§b", WRobot $mod) {
        $this->text = $string;
        $this->player = $p;
        $this->server = Server::getInstance();
        $this->color = $color;
        $this->mod = $mod;
    }

    public function onRun() {
        $this->result = $this->getTopMessage($this->text);
    }

    private function getTopMessage(string $string) {
        $URL = "www.tuling123.com/openapi/api?key=" . $this->getTuringAPI() . "&info=" . $string;
        $info = json_decode(Utils::getURL($URL), true);
        return $info["text"];
    }

    private function getTuringAPI() {
        if ($this->mod->config->get("key") == "default") {
            return self::ROBOT_API;
        } else {
            return $this->mod->config->get("key");
        }
    }

    public function onCompletion(Server $server) {
        if ($this->player instanceof ConsoleCommandSender) {
            $server->getLogger()->info($this->color . $this->result);
        } elseif ($this->player instanceof Player) {
            $this->player->sendMessage($this->color . $this->result);
        }
    }
}