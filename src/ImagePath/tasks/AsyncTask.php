<?php

namespace ImagePath\tasks;

class AsyncTask extends \pocketmine\scheduler\AsyncTask
{
    private ?\Closure $call1;
    private ?\Closure $call2;

    public function __construct(callable $call1, callable $call2)
    {
        $this->call1 = \Closure::bind($call1, $this);
        $this->call2 = \Closure::bind($call2, $this);
    }

    public function onRun(): void
    {
        call_user_func($this->call1, $this);
    }

    public function onCompletion(): void
    {
        call_user_func($this->call2, $this);
    }
}