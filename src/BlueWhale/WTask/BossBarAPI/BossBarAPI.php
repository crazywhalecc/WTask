<?php

namespace BlueWhale\WTask\BossBarAPI;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\Server;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\entity\Entity;
use onebone\economyapi\EconomyAPI;

class BossBarAPI
{
    public static function addBossBar($players, string $title = "", int $percentage = 100) {
        if (is_array($players)) {
            if (empty($players))
                return null;
        }
        $eid = Entity::$entityCount++;
        $packet = new AddEntityPacket();
        $packet->eid = $eid;
        $packet->type = 52;
        $packet->yaw = 0;
        $packet->pitch = 0;
        $packet->metadata = [Entity::DATA_LEAD_HOLDER_EID => [Entity::DATA_TYPE_LONG, -1], Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, 0 ^ 1 << Entity::DATA_FLAG_SILENT ^ 1 << Entity::DATA_FLAG_INVISIBLE ^ 1 << Entity::DATA_FLAG_NO_AI], Entity::DATA_SCALE => [Entity::DATA_TYPE_FLOAT, 0],
            Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $title], Entity::DATA_BOUNDING_BOX_WIDTH => [Entity::DATA_TYPE_FLOAT, 0], Entity::DATA_BOUNDING_BOX_HEIGHT => [Entity::DATA_TYPE_FLOAT, 0]];
        if (is_array($players)) {
            foreach ($players as $player) {
                $pk = clone $packet;
                $pk->x = $player->x;
                $pk->y = $player->y - 28;
                $pk->z = $player->z;
                $player->dataPacket($pk);
            }
            $bpk = new BossEventPacket(); // This updates the bar
            $bpk->eid = $eid;
            $bpk->state = 0;
            Server::getInstance()->broadcastPacket($players, $bpk);
            self::setPercent($percentage, $eid);
            return $eid;
        } elseif ($players instanceof Player) {
            $pk = clone $packet;
            $pk->x = $players->x;
            $pk->y = $players->y - 28;
            $pk->z = $players->z;
            $players->dataPacket($pk);
            $bpk = new BossEventPacket(); // This updates the bar
            $bpk->eid = $eid;
            $bpk->state = 0;
            $players->dataPacket($bpk);
            self::setPercent($percentage, $eid);
            return $eid;
        }
        return false;
    }

    public static function setPercent(int $percentage, int $eid) {
        if (!count(Server::getInstance()->getOnlinePlayers()) > 0)
            return;
        $upk = new UpdateAttributesPacket(); // Change health of fake wither -> bar progress
        $upk->entries[] = new BossBarValues(0, 600, max(0.5, min([$percentage, 100])) / 100 * 600, 'minecraft:health'); // Ensures that the number is between 0 and 100;
        $upk->entityId = $eid;
        Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $upk);

        $bpk = new BossEventPacket(); // This updates the bar
        $bpk->eid = $eid;
        $bpk->state = 0;
        Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $bpk);
    }

    public static function setText(string $title, int $eid) {
        if (!count(Server::getInstance()->getOnlinePlayers()) > 0)
            return;
        $npk = new SetEntityDataPacket(); // change name of fake wither -> bar text
        $npk->eid = $eid;
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            $npk->metadata = [Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, self::msgs($player, $title)]];
            $player->dataPacket($npk);
        }
        $bpk = new BossEventPacket(); // This updates the bar
        $bpk->eid = $eid;
        $bpk->state = 0;
        Server::getInstance()->broadcastPacket(Server::getInstance()->getOnlinePlayers(), $bpk);
    }

    /**
     * @param Player $p
     * @param $msg
     * @return mixed
     */
    public static function msgs($p, $msg) {
        $tps = (string)Server::getInstance()->getTicksPerSecondAverage();
        $minitime = microtime(true) - \pocketmine\START_TIME;
        $uptime = (int)($minitime / 60);
        $load = (string)Server::getInstance()->getTickUsageAverage();
        $load = $load . "%";
        $time = date("H") . ": " . date("i") . ": " . date("s");
        $m = EconomyAPI::getInstance()->myMoney($p->getName());
        $beibao = $p->getInventory();
        $item = $beibao->getItemInHand();
        $id = $item->getID();
        $ts = $item->getDamage();
        $pc = 0;
        foreach (Server::getInstance()->getOnlinePlayers() as $pkk) {
            if ($pkk->isOnline()) {
                ++$pc;
            }
            unset($pkk);
        }
        $lv = $p->getLevel()->getFolderName();
        $food = $p->getFood();
        $x = (int)($p->x);
        $y = (int)($p->y);
        $z = (int)($p->z);
        $msg = str_replace("%n", "\n", $msg);
        $msg = str_replace("+", " ", $msg);
        $msg = str_replace("%p", $p->getName(), $msg);
        $msg = str_replace("{name}", $p->getName(), $msg);
        $msg = str_replace("{time}", $time, $msg);
        $msg = str_replace("{hp}", $p->getHealth(), $msg);
        $msg = str_replace("{mhp}", $p->getMaxHealth(), $msg);
        $msg = str_replace("{tps}", $tps, $msg);
        $msg = str_replace("{online}", $pc, $msg);
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
        $msg = str_replace("{load}", $load, $msg);
        $msg = str_replace("{runtime}", $uptime, $msg);
        unset($tps, $time, $m, $beibao, $item, $id, $ts, $pc, $load);
        return $msg;
    }

    public static function removeBossBar($players, int $eid) {
        if (is_array($players)) {
            if (empty($players))
                return null;
        }
        $pk = new RemoveEntityPacket();
        $pk->eid = $eid;
        if (is_array($players)) {
            Server::getInstance()->broadcastPacket($players, $pk);
            return true;
        } elseif ($players instanceof Player) {
            $players->dataPacket($pk);
            return true;
        }
        return false;
    }
}