<?php

namespace BlueWhale\WTask\BossBarAPI;

use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\ProtocolInfo as Info;

class BossEventPacket extends DataPacket
{

    const NETWORK_ID = Info::BOSS_EVENT_PACKET;

    public $eid;
    public $type;

    public function decode() {

    }

    public function encode() {
        $this->reset();
        $this->putEntityRuntimeId($this->eid);
        $this->putUnsignedVarInt($this->type);
    }

    public function getName(): string {
        return "BossEventPacket";
    }

}