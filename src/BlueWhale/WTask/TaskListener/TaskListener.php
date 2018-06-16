<?php

namespace BlueWhale\WTask\TaskListener;

//Basic Use
use BlueWhale\WTask\WTaskAPI;

interface TaskListener
{
    public function reload();

    public function __construct(WTaskAPI $api, array $task, string $tn);
}