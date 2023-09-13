<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Package extends Model
{
    public static function isInstalled($package, $output = true): bool
    {
        return Collection::wrap($package)
            ->map(function ($package) {
                return Str::of(Process::run("dpkg -s $package 2>/dev/null | grep 'install ok installed' >/dev/null 2>&1 && echo isInstalled")->output())->trim()->exactly('isInstalled');
            })
            ->filter(function ($flag) {
                return !$flag;
            })
            ->isEmpty();
    }

    public static function install($package, $output = true)
    {
        Collection::wrap($package)
            ->map(function ($package) {
                return Process::run("sudo apt install -y $package");
            });
    }
}
