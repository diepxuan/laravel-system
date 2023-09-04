<?php

namespace Diepxuan\System\Trait;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

trait OperatingSystemNetwork
{
    public static function getRouteDefault(): string
    {
        return Str::of(Process::run("ip r | grep ^default | head -n 1 | grep -oP '(?<=dev )[^ ]*'")->output())->trim();
    }
    public static function getRoutes($name = null): string|array
    {
        if (Str::of($name)->isNotEmpty())
            // return Str::of(Process::run("ip r | grep ^$name | head -n 1 | grep -oP '(?<=dev )[^ ]*'")->output())->trim();
            // return Str::of(Process::run("ip -j a show tailscale0 | jq -r 'map(.ifname) | .[]'")->output())->trim();
            return Str::of(Process::run("ip --brief address show | grep ^$name | awk '{print $1}'")->output())->trim();
        return Str::of(Process::run("ip r | grep -oP '(?<=dev )[^ ]*'")->output())->trim()->split('/[\s,\n]+/')->unique();
    }

    public static function getIpLocal(): string
    {
        return Str::of(Process::run("hostname -I | awk '{print $1}'")->output())->trim();
    }

    public static function getSubnet($route = 'vmbr1'): string
    {
        return Str::of(Process::run("ip r | grep vmbr1 | awk '{print $1}'")->output())->trim();
    }
}
