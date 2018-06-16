<?php

namespace BlueWhale\WTask;

class RepeatTaskAPI extends NormalTaskAPI
{
    const r_version = array(
        "任务线程" => "",
        "循环周期" => 1,
        "开服运行" => false
    );

    public $plugin;
    public $currentTask;
    public $player;
    public $api;

    public function __construct(WTaskAPI $api, $taskname = "", $p = null) {
        $this->api = $api;
        parent::__construct($p, $api);
        $this->plugin = $api->plugin;
        $this->currentTask = $taskname;
        $this->player = $p;
    }

    public function broadcastMessage($it)//广播消息
    {
        $it = $this->api->executeReturnData($it, null);
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $pl) {
            $this->plugin->sendMsgPacket($pl, $it, 0);
        }
        if (substr($it, 0, 1) == "^")
            $this->plugin->getServer()->getLogger()->info($it);
        return true;
    }

    public function broadcastTip($it)//广播tip
    {
        $it = $this->api->executeReturnData($it, null);
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $pl) {
            $this->plugin->sendMsgPacket($pl, $it, 1);
        }
        return true;
    }

    public function broadcastPopup($it)//广播popup
    {
        $it = $this->api->executeReturnData($it, null);
        foreach ($this->plugin->getServer()->getOnlinePlayers() as $pl) {
            $this->plugin->sendMsgPacket($pl, $it, 2);
        }
        return true;
    }
}