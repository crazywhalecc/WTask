<?php

namespace BlueWhale\WTask\BossBarAPI;

use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\ProtocolInfo as Info;

class SetEntityDataPacket extends DataPacket
{

    const NETWORK_ID = Info::SET_ENTITY_DATA_PACKET;

    public $eid;
    public $metadata;

    public function decode() {

    }

    public function encode() {
        $this->reset();
        $this->putEntityRuntimeId($this->eid);
        $this->putEntityMetadata($this->metadata);
    }

    public function getName(): string {
        return "SetEntityDataPacket";
    }

}

