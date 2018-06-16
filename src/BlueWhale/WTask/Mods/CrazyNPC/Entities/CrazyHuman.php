<?php

namespace BlueWhale\WTask\Mods\CrazyNPC\Entities;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\Player;
use pocketmine\network\protocol\ProtocolInfo as PInfo;
use pocketmine\utils\TextFormat;

class CrazyHuman extends Human
{

    public function __construct($chunk, CompoundTag $nbt) {
        parent::__construct($chunk, $nbt);
        if (!isset($this->namedtag->NameVisibility)) {
            $this->namedtag->NameVisibility = new IntTag("NameVisibility", 2);
        }
        switch ($this->namedtag->NameVisibility->getValue()) {
            case 0:
                $this->setNameTagVisible(false);
                if (PInfo::CURRENT_PROTOCOL > 90)
                    $this->setNameTagAlwaysVisible(false);
                break;
            case 1:
                $this->setNameTagVisible(true);
                if (PInfo::CURRENT_PROTOCOL > 90)
                    $this->setNameTagAlwaysVisible(false);
                break;
            case 2:
                $this->setNameTagVisible(true);
                if (PInfo::CURRENT_PROTOCOL > 90)
                    $this->setNameTagAlwaysVisible(true);
                break;
            default:
                $this->setNameTagVisible(true);
                if (PInfo::CURRENT_PROTOCOL > 90)
                    $this->setNameTagAlwaysVisible(true);
                break;
        }
        if (PInfo::CURRENT_PROTOCOL > 90) {
            if (!isset($this->namedtag->Scale)) {
                $this->namedtag->Scale = new FloatTag("Scale", 1.0);
            }
            $this->setScale($this->namedtag->Scale->getValue());
        }
    }

    public function saveNBT() {
        parent::saveNBT();
        $visibility = 0;
        if ($this->isNameTagVisible()) {
            $visibility = 1;
            if (PInfo::CURRENT_PROTOCOL > 90) {
                if ($this->isNameTagAlwaysVisible()) $visibility = 2;
            }

        }
        if (PInfo::CURRENT_PROTOCOL > 90) {
            $scale = $this->getDataPropertyManager()->getPropertyType(Entity::DATA_SCALE);
            $this->namedtag->Scale = new FloatTag("Scale", $scale);
        }

        $this->namedtag->NameVisibility = new IntTag("NameVisibility", $visibility);

    }

    public function spawnTo(Player $player) {
        if (!isset($this->hasSpawned[$player->getLoaderId()])) {
            $this->hasSpawned[$player->getLoaderId()] = $player;

            $uuid = $this->getUniqueId();
            $entityId = $this->getId();

            $pk = new AddPlayerPacket();
            $pk->uuid = $uuid;
            $pk->username = "";
            $pk->eid = $entityId;
            $pk->x = $this->x;
            $pk->y = $this->y;
            $pk->z = $this->z;
            $pk->yaw = $this->yaw;
            $pk->pitch = $this->pitch;
            $pk->item = $this->getInventory()->getItemInHand();
            $pk->metadata[self::DATA_NAMETAG] = [self::DATA_TYPE_STRING, $this->getDisplayName($player)];
            $player->dataPacket($pk);
            $this->armorInventory->setContents([]);
            $player->server->updatePlayerListData($uuid, $entityId, $this->namedtag["MenuName"] ?? "", $this->skin, $this->skin, [$player]);
            if ($this->namedtag["MenuName"] === "") {
                $player->server->removePlayerListData($uuid, [$player]);
            }
        }
    }

    public function getDisplayName(Player $player) {
        return str_ireplace(["{name}", "{display_name}", "{nametag}"], [$player->getName(), $player->getDisplayName(), $player->getNametag()], $player->isOp() ? $this->getNameTag() . "\n" . TextFormat::GREEN . "NPC ID: " . $this->getId() : $this->getNameTag());
    }
}
