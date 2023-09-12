<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Package extends Model
{
    public static function isInstalled($package): bool
    {
        $package = is_array($package) ? $package : func_get_args();
        return collect($package)
            ->map(function ($package) {
                return Str::of(Process::run("dpkg -s $package 2>/dev/null | grep 'install ok installed' >/dev/null 2>&1 && echo isInstalled")->output())->trim()->exactly('isInstalled');
            })
            ->filter(function ($flag) {
                return !$flag;
            })
            ->isEmpty();
    }
}
