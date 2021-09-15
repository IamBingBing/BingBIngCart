<?php
namespace bingbing;

use pocketmine\scheduler\Task;

class TimeCheckTask extends Task
{

    private $time;

    public function __construct()
    {}

    public function onRun(int $currentTick)
    {
        if ($this->time == null) {
            $this->time = date("Ymd");
            return;
        } else if ($this->time != date('Ymd')) {
            $ev = new DayChangeEvent();
            $ev->call();
            return;
        } else {
            return;
        }
    }
}