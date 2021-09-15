<?php
namespace bingbing;

use pocketmine\entity\Vehicle;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\LegacySkinAdapter;
use pocketmine\entity\Skin;
use pocketmine\utils\UUID;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\entity\utils\ExperienceUtils;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\entity\EntityIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use bossbar_system\BossBar;
use bossbar_system\model\BossBarType;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;

class Cart extends Vehicle
{

    public const NETWORK_ID = EntityIds::PLAYER;

    /**
     *
     * @var string
     */
    private $name;

    /**
     *
     * @var float
     */
    private $accel;

    /**
     *
     * @var float
     */
    private $slow;

    /**
     *
     * @var string
     */
    private $rank;

    /**
     *
     * @var float
     */
    private $maxspeed;

    /**
     *
     * @var float
     */
    private $speed = 0;

    /**
     *
     * @var string
     */
    private $skinbyte;

    /**
     *
     * @var array
     *
     */
    private $geometry;

    /**
     *
     * @var Player
     */
    private $owner;

    /**
     *
     * @var float
     */
    private $boost_accel;

    /**
     *
     * @var float
     */
    private $maxboostspeed;

    /**
     *
     * @var bool
     */
    public $isboost = false;

    /**
     *
     * @var UUID
     */
    private $uuid;

    /**
     *
     * @var bool
     */
    private $is_riding = false;

    /**
     *
     * @var float
     */
    private $boostpercent = 0;

    /**
     *
     * @var float
     */
    private $bossid;

    /**
     *
     * @var Skin
     */
    private $skin;

    /**
     *
     * @var string
     */
    private $json;

    /**
     *
     * @var BossBar
     */
    private $pox;

    private $poy;

    private $poz;

    private $boost_second;

    private $bossbar;

    public static $riders = [];

    public static $carts = [];

    /**
     *
     * @param string $name
     * @param float $accel
     * @param float $slow
     * @param float $rank
     * @param float $maxspeed
     * @param Position $pos
     * @param Player $owner
     */
    public function __construct(string $name, float $accel, float $slow, string $rank, float $maxspeed, float $boost_accel, float $maxboostspeed, Position $pos, Player $owner, $boost_second, $pox, $poy, $poz)
    {
        $this->isCollided = true;
        $this->name = $name;
        $this->accel = $accel;
        $this->slow = $slow;
        $this->rank = $rank;
        $this->maxspeed = $maxspeed;
        $this->owner = $owner;
        $this->boost_accel = $boost_accel;
        $this->maxboostspeed = $maxboostspeed;
        $this->bossid = Entity::$entityCount ++;
        $this->pox = $pox;
        $this->poy = $poy;
        $this->poz = $poz;
        $this->boost_second = $boost_second;
        $this->x = $pos->x;
        $this->y = $pos->y;
        $this->z = $pos->z;
        $this->setCanSaveWithChunk(false);
        $json = file_get_contents(BingBingCart::getInstance()->getDataFolder() . $name . ".json");
        $this->geometry = json_decode($json, true);
        $this->height = 1.5;
        $this->width = 1;
        $this->gravity = 1;
        $this->boundingBox = new AxisAlignedBB($this->x, $this->y, $this->z, $this->x + 1, $this->y + 1 - 0.125, $this->z + 1);
        $this->id = Entity::$entityCount ++;
        $url = BingBingCart::getInstance()->getDataFolder() . $name . ".png";
        $img = @imagecreatefrompng($url);
        $size = (int) @getimagesize($url)[1];
        for ($y = 0; $y < $size; $y ++) {
            for ($x = 0; $x < 64; $x ++) {
                $colorat = @imagecolorat($img, $x, $y);
                $a = ((~ ((int) ($colorat >> 24))) << 1) & 0xff;
                $r = ($colorat >> 16) & 0xff;
                $g = ($colorat >> 8) & 0xff;
                $b = $colorat & 0xff;
                $this->skinbyte .= chr($r) . chr($g) . chr($b) . chr($a);
            }
        }
        @imagedestroy($img);
        $this->setPosition($pos);
        $this->id = Entity::$entityCount ++;
        $this->json = $json;
        $this->skin = new Skin($name, $this->skinbyte, "", $this->geometry['geometryName'], $json);
        $nbt = new CompoundTag('', [
            new ListTag("Pos", [
                new DoubleTag("", $this->getX()),
                new DoubleTag("", $this->getY()),
                new DoubleTag("", $this->getZ())
            ]),
            new ListTag("Motion", [
                new DoubleTag("", 0),
                new DoubleTag("", 0),
                new DoubleTag("", 0)
            ]),
            new ListTag("Rotation", [
                new FloatTag(0, 0),
                new FloatTag(0, 0)
            ]),

            new CompoundTag("Skin", [
                new StringTag("Name", $this->skin->getSkinId()),
                new ByteArrayTag("Data", $this->skin->getSkinData()),
                new ByteArrayTag("CapeData", $this->skin->getCapeData()),
                new StringTag("GeometryName", $this->skin->getGeometryName()),
                new ByteArrayTag("GeometryData", $this->skin->getGeometryData())
            ])
        ]);

        parent::__construct($pos->getLevel(), $nbt);
    }

    public function initEntity(): void
    {
        parent::initEntity();

        $this->uuid = UUID::fromRandom();

        $this->propertyManager->setString(Entity::DATA_INTERACTIVE_TAG, "Ride");
    }

    public function getMaxSpeed()
    {
        return $this->maxspeed;
    }

    public function setSpeed(float $speed)
    {
        $this->speed = $speed;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAccel(): float
    {
        return $this->accel;
    }

    public function getSkinByte(): string
    {
        return $this->skinbyte;
    }

    public function getRank(): string
    {
        return $this->rank;
    }

    public function onUpdate(int $currentTick): bool
    {
        $pk = new MovePlayerPacket();
        $pk->entityRuntimeId = $this->id;
        $pk->mode = MovePlayerPacket::MODE_NORMAL;
        $pk->yaw = $this->owner->yaw;
        if ($p instanceof Player) {}
        $pk->position = $this->add(0, 1.62);

        $this->server->broadcastPacket($this->server->getOnlinePlayers(), $pk);

        if ($this->speed != 0 && $this->is_Ride()) {
            $this->moving();
            $this->boost();
            $this->boosting();
            $this->speed = floor($this->speed * 0.97);
            $this->levelbar();
            $this->bossbar();
            $this->addboostbar();
            return true;
        } else {
            return false;
        }
    }

    public function is_Ride()
    {
        return $this->is_riding;
    }

    public function bossbar()
    {
        $x = $this->boostpercent * 0.01;
        $this->bossbar->updatePercentage($x);
    }

    public function addboostbar()
    {
        if (! $this->isboost) {
            if ($this->speed > 50) {
                $this->boostpercent ++;
            }
            if ($this->boostpercent >= 100) {
                $this->owner->getInventory()->setHeldItemIndex(0);
                $this->owner->getInventory()->setItem(1, new Item(375, 0, 1)); // 부스트 아이템
                $this->boostpercent = 0;
            }
        }
    }

    public function boosting()
    {
        if ($this->owner->getInventory()->getItemInHand() !== null) {

            if ($this->owner->getInventory()
                ->getItemInHand()
                ->getId() == Item::SPIDER_EYE) {
                $this->isboost = true;
                BingBingCart::getInstance()->getScheduler()->scheduleDelayedTask(new boost($this), $this->boost_second * 20);
                $this->owner->getInventory()->setItemInHand(new Item(Item::AIR, 0, 1));
            }
        }
    }

    public function input($motionX, $motionY)
    {
        // motionX LEFT = 1, RIGHT = -1
        // motionY UP = 1, DOWN = -1
        //
        // * NOTE
        // when player press a couple of KEY at the same time,
        // motionX and motionY will have a slightly lower value,
        // 0.7 (not exact value, similar to this)

        // you can implement player input override this method
        if ($motionX == 1) {
            $this->owner->yaw = fmod($this->owner->yaw + 350, 360);
            $this->owner->sendPosition($this->owner->asVector3(), $this->owner->yaw, $this->owner->pitch, MovePlayerPacket::MODE_NORMAL);
            if ($this->speed > $this->slow) {
                $this->speed --;
                return;
            } else {
                return;
            }
        } else if ($motionX == - 1) {
            $this->owner->yaw = fmod($this->owner->yaw + 370, 360);
            $this->owner->sendPosition($this->owner->asVector3(), $this->owner->yaw, $this->owner->pitch, MovePlayerPacket::MODE_NORMAL);
            if ($this->speed > $this->slow) {
                $this->speed --;
                return;
            } else {
                return;
            }
        } else if ($motionX > 0) {
            $this->owner->yaw = fmod($this->owner->yaw + 355, 360);
            $this->owner->sendPosition($this->owner->asVector3(), $this->owner->yaw, $this->owner->pitch, MovePlayerPacket::MODE_NORMAL);
            return;
        } else if ($motionX < 0) {
            $this->owner->yaw = fmod($this->owner->yaw + 355, 365);
            $this->owner->sendPosition($this->owner->asVector3(), $this->owner->yaw, $this->owner->pitch, MovePlayerPacket::MODE_NORMAL);
            return;
        }
        if ($motionY > 0) {
            if ($this->speed <= $this->maxspeed - $this->accel) {
                $this->speed = $this->speed + $this->accel;
            } else {
                return;
            }
        } else if ($motionY < 0) {
            if ($this->speed > $this->slow) {

                $this->speed = $this->speed - $this->slow;
                return;
            } else {
                return;
            }
        }
    }

    public function moving()
    {
        $this->motion->x = $this->getOwner()->getDirectionVector()->x * 0.004 * $this->speed;
        $this->motion->z = $this->getOwner()->getDirectionVector()->z * 0.004 * $this->speed;
        $this->motion->y = - $this->gravity;
        /*
         * $pk = new MovePlayerPacket();
         * $pk->entityRuntimeId = $this->id;
         * $pk->mode = MovePlayerPacket::MODE_NORMAL;
         * $pk->position = $this->getOffsetPosition($this->asVector3())->add($this->motion->x ,0, $this->motion->z);
         * $this->server->broadcastPacket($this->server->getOnlinePlayers(), $pk);
         */
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function boost()
    {
        if ($this->isboost) {
            if ($this->speed <= $this->maxboostspeed - $this->boost_accel) {
                $this->speed += $this->boost_accel;
            } else if ($this->maxboostspeed - $this->boost_accel - $this->speed < 0) {
                $this->speed = $this->maxboostspeed;
            }
        }
    }

    public function levelbar()
    {
        $this->owner->setXpLevel($this->speed);
        $this->owner->addXp(ExperienceUtils::getXpToReachLevel($this->speed) * ($this->speed / $this->maxboostspeed), false);
    }

    public function ride(Player $player)
    {
        $this->bossbar = new BossBar($player, new BossBarType('부스트'), '부스트게이지', 0.001);
        $this->bossbar->send();

        $this->owner = $player;

        $player->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, true);
        $player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
        $this->setGenericFlag(Entity::DATA_FLAG_SADDLED, true);

        $this->propertyManager->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3($this->pox, $this->poy, $this->poz));
        $this->propertyManager->setByte(Entity::DATA_CONTROLLING_RIDER_SEAT_NUMBER, 0);
        // $this->propertyManager->setByte(Entity::DATA_RIDER_ROTATION_LOCKED, 1);

        $this->is_riding = true;
        self::$riders[$player->getName()] = $this->owner;
        self::$carts[$player->getName()] = $this;
        foreach ($this->getViewers() as $viewer) {
            $this->sendLink($viewer);
        }
    }

    public function leave()
    {
        $player = $this->owner;
        $this->bossbar->remove();
        if ($this->is_Ride()) {
            $player->setGenericFlag(Entity::DATA_FLAG_WASD_CONTROLLED, false);
            $player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
            $this->setGenericFlag(Entity::DATA_FLAG_SADDLED, false);
            $this->is_riding = false;
            self::$carts[$player->getName()]->kill();
            unset(self::$riders[$player->getName()]);
            unset(self::$carts[$player->getName()]);
            foreach ($this->getViewers() as $viewer) {
                $this->sendLink($viewer, EntityLink::TYPE_REMOVE);
                $pk = new RemoveActorPacket();
                $pk->entityUniqueId = $this->id;
                $viewer->sendDataPacket($pk);
                $pk = new RemoveEntityPacket();
                $pk->putEntityUniqueId($this->id);
                $viewer->sendDataPacket($pk);
            }
        }
    }

    public function sendLink(Player $player, int $type = EntityLink::TYPE_RIDER, bool $immediate = false): void
    {
        if (! $this->owner instanceof Player)
            return;

        if (! isset($player->getViewers()[$this->owner->getLoaderId()])) {
            $this->owner->spawnTo($player);
        }

        $from = $this->getId();
        $to = $this->owner->getId();

        $pk = new SetActorLinkPacket();
        $pk->link = new EntityLink($from, $to, $type, $immediate, true);
        $player->sendDataPacket($pk);
    }

    public function getgeometry(): string
    {
        return $this->geometry;
    }

    protected function sendSpawnPacket(Player $player): void
    {
        $pk = new LegacySkinAdapter();
        $skindata = $pk->toSkinData($this->skin);
        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_ADD;
        $pk->entries = [
            PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->getName(), $skindata)
        ];
        $player->dataPacket($pk);

        $pk = new AddPlayerPacket();
        $pk->uuid = $this->uuid;
        $pk->username = $this->getName();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->item = Item::get(Item::AIR);

        foreach ($this->attributeMap->getAll() as $attri) {
            $pk->putAttributeList($attri);
        }
        $pk->metadata = $this->propertyManager->getAll();
        $player->dataPacket($pk);

        $this->sendLink($player);

        $pk = new PlayerListPacket();
        $pk->type = PlayerListPacket::TYPE_REMOVE;
        $pk->entries = [
            PlayerListEntry::createRemovalEntry($this->uuid)
        ];
        $player->dataPacket($pk);
    }
}