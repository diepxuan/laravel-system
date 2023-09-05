<?php

namespace Diepxuan\System\Trait\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

trait Storage
{
    public static function sysSwapOff(): string
    {
        return Str::of(Process::pipe([
            'sudo swapoff -v /swapfile',
            'sudo rm /swapfile',
        ])->output())->trim();
    }

    public static function sysSwapOn(): string
    {
        self::sysSwapOff();
        return Str::of(Process::pipe([
            'sudo rm -rf /swapfile',
            'sudo fallocate -l 2G /swapfile',
            'sudo chmod 600 /swapfile',
            'sudo mkswap /swapfile',
            'sudo swapon /swapfile',
        ])->output())->trim();
    }
}
