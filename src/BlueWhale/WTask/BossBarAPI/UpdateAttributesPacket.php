<?php

namespace BlueWhale\WTask\BossBarAPI;

use pocketmine\entity\Attribute;
use pocketmine\network\protocol\DataPacket;
use pocketmine\network\protocol\Info;

class UpdateAttributesPacket extends DataPacket
{

    const NETWORK_ID = Info::UPDATE_ATTRIBUTES_PACKET;

    public $entityId;

    /** @var Attribute[] */
    public $entries = [];

    public function decode() {

    }

    public function encode() {
        $this->reset();
        $this->putEntityId($this->entityId);
        $this->putUnsignedVarInt(count($this->entries));
        foreach ($this->entries as $entry) {
            $this->putLFloat($entry->getMinValue());
            $this->putLFloat($entry->getMaxValue());
            $this->putLFloat($entry->getValue());
            $this->putLFloat($entry->getDefaultValue());
            $this->putString($entry->getName());
        }
    }

    /**
     * @return string
     */
    public function getName() {
        return "UpdateAttributesPacket";
    }

}