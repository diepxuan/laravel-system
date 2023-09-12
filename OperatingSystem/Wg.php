<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Diepxuan\System\OperatingSystem\Package;
use Diepxuan\System\OperatingSystem as Os;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Wg
{
    public static $package = ['wireguard', 'resolvconf'];
    public static $keydir  = '/etc/wireguard/keys';

    public static function isEnabled()
    {
        return Str::of(Process::run("[[ -f /usr/bin/wg ]] && echo isEnabled")->output())->trim()->exactly('isEnabled');
    }

    public static function install()
    {
        $package = collect(self::$package)->implode(' ');
        if (Package::isInstalled($package)) return;
        Process::run("sudo apt install -y $package");
    }

    public static function isInstalled(): bool
    {
        return Package::isInstalled(self::$package);
    }

    public static function keyPublic($value = null)
    {
        $keydir = self::$keydir;
        if ($value)
            return Str::of(Process::run("echo $value | sudo tee $keydir/server_public.key")->output())->trim();
        return Str::of(Process::run("sudo cat $keydir/server_public.key")->output())->trim();
    }

    public static function keyPrivate($value = null)
    {
        $keydir = self::$keydir;
        if ($value)
            return Str::of(Process::run("echo $value | sudo tee $keydir/server_private.key")->output())->trim();
        return Str::of(Process::run("sudo cat $keydir/server_private.key")->output())->trim();
    }

    public static function keyReNew()
    {
        $keydir = self::$keydir;
        return Str::of(Process::run("sudo cat $keydir/server_private.key")->output())->trim();
    }

    public static function keyGen()
    {
        $keydir = self::$keydir;
        return Str::of(Process::run("sudo cat $keydir/server_private.key")->output())->trim();
    }
}
