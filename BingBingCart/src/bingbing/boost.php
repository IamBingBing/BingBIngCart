<?php
namespace bingbing;

use pocketmine\scheduler\Task;

class boost extends Task
{

    /**
     *
     * @var Cart
     */
    private $cart;

    public function __construct(Cart $cart)
    {
        $this->cart = $cart;
    }

    public function onRun(int $tick)
    {
        $this->cart->isboost = false;
        $this->cart->setSpeed($this->cart->getMaxSpeed());
    }
}
?>