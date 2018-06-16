<?php

namespace BlueWhale\WTask\Mods\CrazyNPC;

use BlueWhale\WTask\Mods\CrazyNPC\Entities\{
    CrazyBat, CrazyBlaze, CrazyCaveSpider,
    CrazyChicken, CrazyCow, CrazyCreeper,
    CrazyDonkey, CrazyEnderman, CrazyGhast,
    CrazyHorse, CrazyHusk, CrazyIronGolem,
    CrazyLavaSlime, CrazyMule, CrazyMushroomCow,
    CrazyOcelot, CrazyPig, CrazyPigZombie,
    CrazyRabbit, CrazySheep, CrazyEntity,
    CrazyHuman, CrazySilverfish, CrazySkeleton,
    CrazySkeletonHorse, CrazySlime, CrazySnowman,
    CrazySpider, CrazySquid, CrazyStray,
    CrazyVillager, CrazyWitch, CrazyWitherSkeleton,
    CrazyWolf, CrazyZombie, CrazyZombieHorse,
    CrazyZombieVillager
};
use BlueWhale\WTask\Mods\ModBase;
use pocketmine\Player;
use pocketmine\event\Listener;
use pocketmine\entity\Entity;
//NBT Tag type
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\StringTag;
//Events
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;

class CrazyNPC extends ModBase implements Listener
{
    private $plugin;
    const VERSION = "1.0.0";
    const NAME = "CrazyNPC";
    const TYPES = [
        "Chicken", "Pig", "Sheep", "Cow",
        "MushroomCow", "Wolf", "Enderman", "Spider",
        "Skeleton", "PigZombie", "Creeper", "Slime",
        "Silverfish", "Villager", "Zombie", "Human",
        "Bat", "CaveSpider", "LavaSlime", "Ghast",
        "Ocelot", "Blaze", "ZombieVillager", "Snowman",
        "Horse", "Donkey", "Mule", "SkeletonHorse",
        "ZombieHorse", "Witch", "Rabbit", "Stray",
        "Husk", "WitherSkeleton", "IronGolem", "Snowman",
        "MagmaCube", "Squid"
    ];

    public $mainHelp;
    public $cmd;
    public $editMode;

    public function onEnable() {
        $this->plugin = $this->getWTask();
        $desc = $this->plugin->getModule("CrazyNPC");
        $this->getServer()->getCommandMap()->register("WTask", new NPCCommand($this, $desc));
        $this->registerEntities();
        try {
            $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
        } catch (\Throwable $e) {
        }
    }

    public function registerEntities() {
        Entity::registerEntity(CrazyCreeper::class, true);
        Entity::registerEntity(CrazyBat::class, true);
        Entity::registerEntity(CrazySheep::class, true);
        Entity::registerEntity(CrazyPigZombie::class, true);
        Entity::registerEntity(CrazyGhast::class, true);
        Entity::registerEntity(CrazyBlaze::class, true);
        Entity::registerEntity(CrazyIronGolem::class, true);
        Entity::registerEntity(CrazySnowman::class, true);
        Entity::registerEntity(CrazyOcelot::class, true);
        Entity::registerEntity(CrazyZombieVillager::class, true);
        Entity::registerEntity(CrazyHuman::class, true);
        Entity::registerEntity(CrazyVillager::class, true);
        Entity::registerEntity(CrazyZombie::class, true);
        Entity::registerEntity(CrazySquid::class, true);
        Entity::registerEntity(CrazyCow::class, true);
        Entity::registerEntity(CrazySpider::class, true);
        Entity::registerEntity(CrazyPig::class, true);
        Entity::registerEntity(CrazyMushroomCow::class, true);
        Entity::registerEntity(CrazyWolf::class, true);
        Entity::registerEntity(CrazyLavaSlime::class, true);
        Entity::registerEntity(CrazySilverfish::class, true);
        Entity::registerEntity(CrazySkeleton::class, true);
        Entity::registerEntity(CrazySlime::class, true);
        Entity::registerEntity(CrazyChicken::class, true);
        Entity::registerEntity(CrazyEnderman::class, true);
        Entity::registerEntity(CrazyCaveSpider::class, true);
        Entity::registerEntity(CrazyHorse::class, true);
        Entity::registerEntity(CrazyDonkey::class, true);
        Entity::registerEntity(CrazyMule::class, true);
        Entity::registerEntity(CrazySkeletonHorse::class, true);
        Entity::registerEntity(CrazyZombieHorse::class, true);
        Entity::registerEntity(CrazyRabbit::class, true);
        Entity::registerEntity(CrazyWitch::class, true);
        Entity::registerEntity(CrazyStray::class, true);
        Entity::registerEntity(CrazyHusk::class, true);
        Entity::registerEntity(CrazyWitherSkeleton::class, true);
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
     * @param $type
     * @param $name
     * @param Player $player
     * @return CompoundTag
     */
    public function createNBT($type, $name, $player) {
        $nbt = new CompoundTag;
        $nbt->Pos = new ListTag("Pos", [
            new DoubleTag(0, $player->getX()),
            new DoubleTag(1, $player->getY()),
            new DoubleTag(2, $player->getZ())
        ]);
        $nbt->Motion = new ListTag("Motion", [
            new DoubleTag(0, 0),
            new DoubleTag(1, 0),
            new DoubleTag(2, 0)
        ]);
        $nbt->Rotation = new ListTag("Rotation", [
            new FloatTag(0, $player->getYaw()),
            new FloatTag(1, $player->getPitch())
        ]);
        $nbt->Health = new ShortTag("Health", 1);
        $nbt->Commands = new CompoundTag("Commands", []);
        $nbt->MenuName = new StringTag("MenuName", $name);
        //$nbt->NPCVersion = new StringTag("SlapperVersion", "1.3.2");
        if ($type === "Human") {
            $nbt->Inventory = new ListTag("Inventory", $player->getInventory());
            $nbt->Skin = new CompoundTag("Skin", [
                "Data" => new StringTag("Data", $player->getSkin()->getSkinData()),
                "Name" => new StringTag("Name", $player->getSkin()->getSkinId())
            ]);
        }
        return $nbt;
    }

    public function onEntityDamage(EntityDamageEvent $event) {
        $entity = $event->getEntity();
        if ($entity instanceof CrazyEntity or $entity instanceof CrazyHuman) {
            $event->setCancelled(true);
            if (!$event instanceof EntityDamageByEntityEvent)
                return;
            $damager = $event->getDamager();
            if (!$damager instanceof Player)
                return;
            if (isset($this->editMode[$damager->getName()])) {
                switch ($this->editMode[$damager->getName()]) {
                    case 1:
                        $id = $entity->getId();
                        $damager->sendMessage("§b实体NPC的ID: " . $id);
                        unset($this->editMode[$damager->getName()]);
                        return;
                }
            }
            if (!(empty($entity->namedtag->Commands))) {
                foreach ($entity->namedtag->Commands as $cmd) {
                    $this->getServer()->dispatchCommand($damager, str_replace("%p", $damager->getName(), $cmd));
                }
            }
        }
    }
}