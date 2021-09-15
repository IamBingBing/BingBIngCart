<?php
namespace bingbing;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use racing\RacingStartEvent;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use onebone\economyapi\EconomyAPI;
use pocketmine\OfflinePlayer;
use racing\BingBingracing;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\level\Location;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;

class BingBingCart extends PluginBase implements Listener
{

    private static $instance;

    public function onEnable()
    {
        $this->getServer()
            ->getPluginManager()
            ->registerEvents($this, $this);
        self::$instance = $this;
        @mkdir($this->getDataFolder());
        $this->data = new Config($this->getDataFolder() . "data.json", Config::JSON, []); // 플레이어
        $this->d = $this->data->getAll();
        $this->cart = new Config($this->getDataFolder() . "cart.json", Config::JSON, [
            'carts' => []
        ]); // 카트들 데이터
        $this->c = $this->cart->getAll();
        $this->event = new Config($this->getDataFolder() . 'box.json', Config::JSON, []); // 박스
        $this->e = $this->event->getAll();
        Entity::registerEntity(Cart::class);
    }

    static public function getInstance(): BingBingCart
    {
        return self::$instance;
    }

    public function join(PlayerJoinEvent $event)
    {
        $p = $event->getPlayer();
        $this->d[$p->getName()] = [
            'carts' => [
                '보급카트' => '9999999'
            ],
            'choice' => '보급카트',
            'usually' => '보급카트'
        ];
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if ($command->getName() == "카트") {
            if (isset($args[0])) {
                if ($args[0] == '추가') {
                    if (isset($args[1]) && isset($args[2])) {
                        foreach ($this->getServer()->getOnlinePlayers() as $p) {
                            if (strtolower($p->getName()) == strtolower($args[1])) {
                                if (array_search($args[2], $this->c['carts']) !== false) {
                                    $this->d[$p->getName()]['carts'][$args[2]] = 9999999;
                                }
                            }
                        }
                    }
                } else if ($args[0] == '등록' && $sender->isOp()) {
                    if (isset($args[1]) && isset($args[2]) && isset($args[3]) && isset($args[4]) && isset($args[5]) && isset($args[6]) && isset($args[7]) && isset($args[8]) && isset($args[9]) && isset($args[10]) && isset($args[11]) && isset($args[12]) && isset($args[13]) && isset($args[14]) && isset($args[15]) && isset($args[16]) && isset($args[17]) && isset($args[18]) && isset($args[19])) {
                        if ($args[8] == 'true') {
                            $this->makecart($args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], true, $args[9], $args[10], $args[11], $args[12], $args[13], $args[14], $args[15], $args[16], $args[17], $args[18], $args[19]);
                            $sender->sendMessage('등록완료');
                            return true;
                        } else if ($args[8] == 'false') {
                            $this->makecart($args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7], false, $args[9], $args[10], $args[11], $args[12], $args[13], $args[14], $args[15], $args[16], $args[17], $args[18], $args[19]);
                            $sender->sendMessage('등록완료');

                            return true;
                        } else {
                            $sender->sendMessage('/카트 등록 [카트이름 ] [가속] [감속] [최대속도] [랭크이름] [부스트가속] [부스트최대속도] [상점허용여부 true / false ] [1일기간제 가격] [7일기간제 가격] [30일기간제 가격] [90일기간제 가격] [영구 가격] [부스트 시간] [만든사람] [수수료] [자리좌표x] [자리좌표y] [자리좌표z] ');
                            return true;
                        }
                    } else {
                        $sender->sendMessage('/카트 등록 [카트이름 ] [가속] [감속] [최대속도] [랭크이름] [부스트가속] [부스트최대속도] [상점허용여부 true / false ] [1일기간제 가격] [7일기간제 가격] [30일기간제 가격] [90일기간제 가격] [영구 가격] [부스트 시간] [만든사람] [수수료] [자리좌표x] [자리좌표y] [자리좌표z] ');
                        return true;
                    }
                }
            } else {
                $pk = new ModalFormRequestPacket();
                $pk->formData = $this->mainUI();
                $pk->formId = 20200818;
                $sender->sendDataPacket($pk);
                return true;
            }
        }
        return true;
    }

    public function gamestart(RacingStartEvent $event)
    {
        $ps = $event->getPlayers();
        foreach ($ps as $p) {
            if ($p instanceof Player) {
                if (! $this->is_ride($p)) {
                    $this->riding($p, $this->d[$p->getName()]['usually']);
                }
            }
        }
    }

    public function onInter(PlayerInteractEvent $event)
    {
        $p = $event->getPlayer();
        $block = $p->getInventory()->getItemInHand();
        if ($block->getId() == Item::HEART_OF_THE_SEA) {
            $cartn = $this->randbox()['cartn'];
            $time = $this->randbox()['time'];
            $this->d[$p->getName()]['carts'][$cartn] = $time;
            ;
        }
    }

    public function giveCartMoney($cartn, $money)
    {
        $player = $this->c['carts'][$cartn]['maker_pay'];
        $tax = $this->c['carts'][$cartn]['maker_tax'];
        $amount = $money * (100 - $tax);

        EconomyAPI::getInstance()->addMoney($player, $amount);
    }

    public function randbox(Player $p)
    {
        $e = $this->e;
        $cartn = array_keys($e);
        $pos = 0;
        foreach ($cartn as $n) {
            $pos += $e[$n][0];
        }
        foreach ($cartn as $n) {
            $k = mt_rand(1, $pos);
            if ($k <= $e[$n][0]) {
                if (isset($this->d[$p->getName()][$n])) {
                    $this->d[$p->getName()][$n] += $e[$n][1];

                    $p->sendMessage($n . $e[$n][1] . '기간연장');
                } else {
                    $this->d[$p->getName()][$n] = $e[$n][1];
                    $p->sendMessage($n . $e[$n][1] . '획득');
                }
                return true;
            }

            $pos -= $e[$n][0];
        }
    }

    public function rand(int $int, $max)
    {
        $k = mt_rand(1, 100);
        if ($k <= $int) {
            return true;
        }
    }

    /**
     *
     * @param Player $p
     * @return Cart
     */
    public function getCart(Player $p): Cart
    {
        if ($this->is_ride($p)) {
            $riding = Cart::$carts[$p->getName()];
            return $riding;
        }
    }

    public function is_ride(Player $p): bool
    {
        if (! empty(Cart::$riders[$p->getName()])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param Player $p
     * @param string $cartn
     */
    public function riding(Player $p, string $cartn)
    {
        $c = $this->c['carts'][$cartn];
        $cart = new Cart($cartn, (float) $c['accel'], (float) $c['slow'], $c['rank'], (float) $c['maxspeed'], (float) $c['boost_accel'], (float) $c['maxboostspeed'], $p->asPosition(), $p, $c['boost_second'], $c['positionx'], $c['positiony'], $c['positionz']);
        $cart->spawnToAll();
        $cart->ride($p);
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event)
    {
        $pk = $event->getPacket();
        $p = $event->getPlayer();
        if ($pk->pid() == PlayerInputPacket::NETWORK_ID) {
            if ($pk->motionX == 0 && $pk->motionY == 0)
                return; // ignore non-input
            if ($this->is_ride($p)) {
                $riding = $this->getCart($p) instanceof Cart ? $this->getCart($p) : null;
                $riding->input($pk->motionX, $pk->motionY);
                $event->setCancelled();
            }
        }

        if ($pk->pid() == InteractPacket::NETWORK_ID) {
            if ($pk->action === InteractPacket::ACTION_LEAVE_VEHICLE) {
                $target = $event->getPlayer()
                    ->getLevel()
                    ->getEntity($pk->target);
                if ($target instanceof Cart and $target->getOwner() === $event->getPlayer()) {
                    $target->leave();
                    $event->setCancelled();
                }
            }
        }
        if ($pk->pid() == InventoryTransactionPacket::NETWORK_ID) {
            if ($pk->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) {
                $target = $event->getPlayer()
                    ->getLevel()
                    ->getEntity($pk->trData->entityRuntimeId);
                if ($target instanceof Cart and $pk->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT) {
                    $riding = $this->getCart($p) instanceof Cart ? $this->getCart($p) : null;
                    $riding->input($pk->motionX, $pk->motionY);
                    $event->setCancelled();
                }
            }
        }
    }

    public function swipe(PlayerMoveEvent $event)
    {
        $p = $event->getPlayer();
        if ($this->is_ride($p)) {
            $p->lastYaw = $p->lastYaw;
            $p->yaw = $p->lastYaw;
            $p->lastPitch = $p->lastPitch;
            $p->pitch = $p->lastPitch;
            $p->sendPosition($p->asVector3(), $p->lastYaw, $p->lastPitch, MovePlayerPacket::MODE_RESET);
        }
    }

    public function UIrecieve(DataPacketReceiveEvent $event)
    {
        $pk = $event->getPacket();
        $p = $event->getPlayer();
        if ($pk instanceof ModalFormResponsePacket) {
            $id = $pk->formId;
            $result = json_decode($pk->formData, true);
            if ($result !== null) {

                if ($pk->formId == 20200818) { // 메인 ui
                    $result = json_decode($pk->formData, true);
                    if ($result === 0) {
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202008181; // shop ui
                        $pk->formData = $this->shopMainUI();
                        $p->sendDataPacket($pk);
                        return;
                    } else if ($result === 1) {
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202008182; // my carts ui
                        $pk->formData = $this->MyCartsUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else if ($result === 2) {
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202008183; // usually ui
                        $pk->formData = $this->usuallyUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else if ($result === 3) {
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202008184; // cartexplain ui
                        $pk->formData = $this->cartexplainMainUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else {
                        return;
                    }
                } else if ($id == 202008181) { // shop mainui
                    if ($result === 0) { // 1
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 2020081811; // shop ui
                        $pk->formData = $this->shopUI();
                        $p->sendDataPacket($pk);
                        return;
                    } else if ($result === 1) { // 7
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 2020081817; // my carts ui
                        $pk->formData = $this->shopUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else if ($result === 2) { // 30
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 20200818130; // my carts ui
                        $pk->formData = $this->shopUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else if ($result === 3) { // 90
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 20200818190; // usually ui
                        $pk->formData = $this->shopUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else if ($result === 4) { // 영구
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 202008181100; // usually ui
                        $pk->formData = $this->shopUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else {
                        return;
                    }
                } else if ($id == 2020081811) { // 1일
                    if (count(array_keys($this->c['carts'])) === $result) {
                        return;
                    } else if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['store']) {
                        if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['price1'] <= EconomyAPI::getInstance()->myMoney($p)) {
                            if (array_search(array_keys($this->c['carts'])[$result], array_keys($this->d[$p->getName()]['carts'])) === false) {
                                EconomyAPI::getInstance()->reduceMoney($p, $this->c['carts'][array_keys($this->c['carts'])[$result]]['price1']);
                                $this->d[$p->getName()]['carts'][array_keys($this->c['carts'])[$result]] = 1;
                                $this->giveCartMoney(array_keys($this->c['carts'])[$result], $this->c['carts'][array_keys($this->c['carts'])[$result]]['price1']);
                                return;
                            } else {
                                $p->sendMessage('카트를 이미 가지고 있습니다. ');
                            }
                        } else {
                            $p->sendMessage('카트를 구입할 돈이 부족합니다. ');
                            return;
                        }
                    }
                } 
                else if ($id == 2020081817) { // 7일
                    if (count(array_keys($this->c['carts'])) === $result) {
                        return;
                    } else if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['store']) {
                        if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['price7'] <= EconomyAPI::getInstance()->myMoney($p)) {
                            if (array_search(array_keys($this->c['carts'])[$result], array_keys($this->d[$p->getName()]['carts'])) === false) {
                                EconomyAPI::getInstance()->reduceMoney($p, $this->c['carts'][array_keys($this->c['carts'])[$result]]['price7']);
                                $this->d[$p->getName()]['carts'][array_keys($this->c['carts'])[$result]] = 7;
                                $this->giveCartMoney(array_keys($this->c['carts'])[$result], $this->c['carts'][array_keys($this->c['carts'])[$result]]['price7']);

                                return;
                            } else {
                                $p->sendMessage('카트를 이미 가지고 있습니다. ');
                            }
                        } else {
                            $p->sendMessage('카트를 구입할 돈이 부족합니다. ');
                            return;
                        }
                    }
                } else if ($id == 20200818130) { // 30일
                    if (count(array_keys($this->c['carts'])) === $result) {
                        return;
                    } else if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['store']) {
                        if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['price30'] <= EconomyAPI::getInstance()->myMoney($p)) {
                            if (array_search(array_keys($this->c['carts'])[$result], array_keys($this->d[$p->getName()]['carts'])) === false) {
                                EconomyAPI::getInstance()->reduceMoney($p, $this->c['carts'][array_keys($this->c['carts'])[$result]]['price30']);
                                $this->d[$p->getName()]['carts'][array_keys($this->c['carts'])[$result]] = 30;
                                $this->giveCartMoney(array_keys($this->c['carts'])[$result], $this->c['carts'][array_keys($this->c['carts'])[$result]]['price30']);

                                return;
                            } else {
                                $p->sendMessage('카트를 이미 가지고 있습니다. ');
                            }
                        } else {
                            $p->sendMessage('카트를 구입할 돈이 부족합니다. ');
                            return;
                        }
                    }
                } else if ($id == 20200818190) { // 90일
                    if (count(array_keys($this->c['carts'])) === $result) {
                        return;
                    } else if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['store']) {
                        if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['price90'] <= EconomyAPI::getInstance()->myMoney($p)) {
                            if (array_search(array_keys($this->c['carts'])[$result], array_keys($this->d[$p->getName()]['carts'])) === false) {
                                EconomyAPI::getInstance()->reduceMoney($p, $this->c['carts'][array_keys($this->c['carts'])[$result]]['price90']);
                                $this->d[$p->getName()]['carts'][array_keys($this->c['carts'])[$result]] = 90;
                                $this->giveCartMoney(array_keys($this->c['carts'])[$result], $this->c['carts'][array_keys($this->c['carts'])[$result]]['price90']);

                                return;
                            } else {
                                $p->sendMessage('카트를 이미 가지고 있습니다. ');
                            }
                        } else {
                            $p->sendMessage('카트를 구입할 돈이 부족합니다. ');
                            return;
                        }
                    }
                } else if ($id == 202008181100) { // 영구
                    if (count(array_keys($this->c['carts'])) === $result) {
                        return;
                    } else if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['store']) {
                        if ($this->c['carts'][array_keys($this->c['carts'])[$result]]['pricemax'] <= EconomyAPI::getInstance()->myMoney($p)) {
                            if (array_search(array_keys($this->c['carts'])[$result], array_keys($this->d[$p->getName()]['carts'])) === false) {
                                EconomyAPI::getInstance()->reduceMoney($p, $this->c['carts'][array_keys($this->c['carts'])[$result]]['pricemax']);
                                $this->d[$p->getName()]['carts'][array_keys($this->c['carts'])[$result]] = 9999999;
                                $this->giveCartMoney(array_keys($this->c['carts'])[$result], $this->c['carts'][array_keys($this->c['carts'])[$result]]['pricemax']);

                                return;
                            } else {
                                $p->sendMessage('카트를 이미 가지고 있습니다. ');
                            }
                        } else {
                            $p->sendMessage('카트를 구입할 돈이 부족합니다. ');
                            return;
                        }
                    }
                } else if ($id == 202008182) { // mycart

                    $this->d[$p->getName()]['choice'] = array_keys($this->d[$p->getName()]['carts'])[$result];
                    $this->riding($p, array_keys($this->d[$p->getName()]['carts'])[$result]);
                    $p->sendMessage('탑승완료 점프를 통해 탈출하차');
                    return;
                } else if ($id == 202008183) { // usually
                    if ($result === 0) {
                        $pk = new ModalFormRequestPacket();
                        $pk->formId = 2020081831; // my carts ui
                        $pk->formData = $this->MyCartsUI($p);
                        $p->sendDataPacket($pk);
                        return;
                    } else {
                        return;
                    }
                } else if ($id == 2020081831) {
                    if (count(array_keys($this->d[$p->getName()]['carts'])) === $result) {
                        return;
                    }
                    $this->d[$p->getName()]['usually'] = array_keys($this->d[$p->getName()]['carts'])[$result];
                    return;
                } 
                else if ($id == 202008184) { // cart explain
                    if (count(array_keys($this->c['carts'])) === $result) {
                        return;
                    }
                    $pk = new ModalFormRequestPacket();
                    $pk->formId = rand(1, 100000000);
                    $pk->formData = $this->cartexplainUI(array_keys($this->c['carts'])[$result]);
                    $p->sendDataPacket($pk);
                    return;
                }
            } else {
                return;
            }
        }
    }

    public function daychange(DayChangeEvent $event)
    {
        $all = [];
        $dir = opendir($this->getServer()->getDataPath() . "/players/");
        while (($filename = readdir($dir)) !== false) {
            array_push($all, $filename);
        }
        foreach ($all as $n) {
            $of = $this->getServer()->getOfflinePlayer($n);
            if ($of instanceof OfflinePlayer) {
                if (isset($this->d[$of->getName()])) {
                    foreach ($this->d[$of->getName()]['carts'] as $c) {
                        $c --;
                        if ($c <= 0) {
                            unset($this->d[$of->getName()]['carts']);
                            foreach ($this->d[$of->getName()]['carts'] as $k => $v) {
                                if (is_numeric($v) or is_int($v)) {
                                    $this->d[$of->getName()]['carts'][$k] = $v;
                                }
                            }
                        } else {
                            continue;
                        }
                    }
                } else {
                    continue;
                }
            }
        }
    }

    public function makecart($cartn, $accel, $slow, $maxspeed, $rank, $boost_accel, $maxboostspeed, bool $store, $price1, $price7, $price30, $price90, $pricemax, $boost_second, $maker_pay, $maker_tax, $positionx, $positiony, $positionz)
    {
        $this->c['carts'][$cartn] = [
            'name' => $cartn,
            'accel' => $accel,
            'slow' => $slow,
            'maxspeed' => $maxspeed,
            'rank' => $rank,
            'boost_accel' => $boost_accel,
            'maxboostspeed' => $maxboostspeed,
            'store' => $store,
            'price1' => $price1,
            'price7' => $price7,
            'price30' => $price30,
            'price90' => $price90,
            'pricemax' => $pricemax,
            'boost_second' => $boost_second,
            'maker_pay' => $maker_pay,
            'maker_tax' => $maker_tax,
            'positionx' => $positionx,
            'positiony' => $positiony,
            'positionz' => $positionz
        ];
    }

    public function shopMainUI()
    {
        $value = [
            "type" => "form",
            "title" => "빙빙카트",
            "content" => "카트 구매",
            "buttons" => [
                [

                    'text' => '1일 기간제'
                ],
                [

                    'text' => '7일 기간제'
                ],
                [

                    'text' => '30일 기간제'
                ],
                [

                    'text' => '90일 기간제'
                ],
                [

                    'text' => '영구 소유'
                ],
                [

                    'text' => '창닫기'
                ]
            ]
        ];
        return json_encode($value);
    }

    public function cartexplainUI($cn)
    {
        $value = [
            "type" => "custom_form",
            "title" => "빙빙카트",
            "content" => [
                "type" => "label",
                'text' => $this->c['carts'][$cn]['explain']
            ]
        ];
        return json_encode($value);
    }

    public function cartexplainMainUI()
    {
        $carts = [];
        foreach (array_keys($this->c['carts']) as $cn) {
            array_push($carts, [
                'text' => $cn
            ]);
        }
        array_push($carts, [
            'text' => '창닫기'
        ]);
        $value = [
            "type" => "form",
            "title" => "빙빙카트",
            "content" => "카트 설명",
            "buttons" => $carts
        ];
        return json_encode($value);
    }

    public function shopUI()
    {
        $carts = [];
        foreach (array_keys($this->c['carts']) as $cn) {
            array_push($carts, [
                'text' => $cn
            ]);
        }
        array_push($carts, [
            'text' => '창닫기'
        ]);
        $value = [
            "type" => "form",
            "title" => "빙빙카트",
            "content" => "카트 구매",
            "buttons" => $carts
        ];
        return json_encode($value);
    }

    public function MyCartsUI(Player $p)
    {
        $carts = [];
        foreach (array_keys($this->d[$p->getName()]['carts']) as $cn) {
            array_push($carts, [
                'text' => $cn
            ]);
        }
        array_push($carts, [
            'text' => '창닫기'
        ]);
        $value = [
            "type" => "form",
            "title" => "빙빙카트",
            "content" => "카트 목록 누르면 탑승",
            "buttons" => $carts
        ];
        return json_encode($value);
    }

    public function usuallyUI(Player $p)
    {
        $value = [
            "type" => "form",
            "title" => "빙빙카트",
            "content" => "현재 즐겨찾기 카트 : " . $this->d[$p->getName()]['usually'],
            "buttons" => [
                [
                    'text' => '변경'
                ],
                [
                    'text' => '창닫기'
                ]
            ]
        ];
        return json_encode($value);
    }

    public function mainUI()
    {
        $value = [
            "type" => "form",
            "title" => "빙빙카트",
            "content" => "카트 구매 / 선택 / 조회",
            "buttons" => [
                [

                    'text' => '카트상점 '
                ],
                [

                    'text' => '자신의 카트 조회'
                ],
                [

                    'text' => '즐겨찾기'
                ],
                [

                    'text' => '카트설명'
                ],
                [

                    'text' => '창닫기'
                ]
            ]
        ];
        return json_encode($value);
    }

    public function quit(PlayerQuitEvent $event)
    {
        $p = $event->getPlayer();
        $c = $this->getCart($p);
        $c->leave();
    }

    public function onDisable()
    {
        $this->saveConfig();
        $this->save();
        foreach (Cart::$carts as $c) {
            if ($c instanceof Cart) {
                $c->kill();
            }
        }
    }

    public function save()
    {
        $this->data->setAll($this->d);
        $this->data->save();
        $this->cart->setAll($this->c);
        $this->cart->save();
        $this->event->setAll($this->e);
        $this->event->save();
    }
}