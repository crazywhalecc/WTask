<?php

namespace BlueWhale\WTask\ScheduleTasks;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\Utils\Utils;
use pocketmine\command\ConsoleCommandSender;

class DownloadTask extends AsyncTask
{
    private $pluginName, $player, $result, $secondResult, $dat, $finalResult = " ss ", $plm;

    /**
     * DownloadTask constructor.
     * @param string $pname
     * @param $p
     * @param $sw
     */
    public function __construct(string $pname, $p, $sw) {
        $this->pluginName = $pname;
        $this->player = $p;
        $this->plm = $sw;
    }

    public function onRun() {
        $this->result = $this->findPlugin($this->pluginName);
        //echo "成功查找插件！";
        $this->secondResult = $this->executeResult($this->result, $this->pluginName);
        //echo "成功匹配插件！";
        $this->dat = $this->downloadPlugin($this->secondResult, $this->pluginName);
        //echo "成功下载插件！";
    }

    private function findPlugin($plugin) {
        $info = json_decode(Utils::getURL("s3.whalecraft.cn:88/wtask/plugins/PluginList.json"), true);
        if (!isset($info["status"])) {
            return false;
        }
        $ar = $info["list"];
        if (in_array($plugin, $ar)) {
            return true;
        } else {
            return false;
        }
    }

    private function executeResult($r, $pname) {
        if ($r === false) {
            $this->finalResult = "未找到这个插件，请检查你的插件名字！";
            return false;
        }
        $dd = Server::getInstance()->getPluginManager()->getPlugin($pname);
        if ($dd !== null) {
            $this->finalResult = "这个插件已经安装了，无法下载！";
            return false;
        }
        return true;
    }

    private function downloadPlugin($r, $pname) {
        if ($r == false) {
            $this->finalResult = "[WTask] " . $this->finalResult;
            return;
        }
        $dd = file_put_contents('plugins/' . '[WTask-only]' . $pname . '.phar', file_get_contents("http://s3.whalecraft.cn:88/wtask/plugins/" . $pname . ".phar"));
        if ($dd == false) {
            $this->finalResult = "下载插件失败！请联系蓝鲸服务器维护人员！";
            return;
        }
        $this->finalResult = "成功下载插件 !请重启服务器运行！";
        return;
    }

    public function onCompletion(Server $server) {
        //echo "成功运行主线程返回！";
        if ($this->player instanceof ConsoleCommandSender) {
            $server->getLogger()->info($this->finalResult);

        } elseif ($this->player instanceof Player) {
            $this->player->sendMessage($this->finalResult);
        }
    }
}
