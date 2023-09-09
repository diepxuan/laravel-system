<?php

namespace Diepxuan\System\Trait\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

trait Storage
{
    private static $swapPath = "/swap.img";

    public static function sysSwapCheck(): bool
    {
        return Str::of(Process::pipe([
            sprintf("swapon -s | grep %s | awk '{print $1}'", self::$swapPath),
        ])->output())->trim()->is(self::$swapPath);
    }
    public static function sysSwapOff(): string
    {
        return Str::of(Process::pipe([
            sprintf('sudo swapoff -v %s', self::$swapPath),
            sprintf('sudo rm %s', self::$swapPath),
        ])->output())->trim();
    }

    public static function sysSwapOn(): string
    {
        if (self::sysSwapCheck()) return 'swap is enabled';
        return Str::of(Process::pipe([
            sprintf('sudo rm -rf %s', self::$swapPath),
            sprintf('sudo fallocate -l 2G %s', self::$swapPath),
            sprintf('sudo chmod 600 %s', self::$swapPath),
            sprintf('sudo mkswap %s', self::$swapPath),
            sprintf('sudo swapon %s', self::$swapPath),
        ])->output())->trim();
    }
}
