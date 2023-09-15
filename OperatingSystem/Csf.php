<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Diepxuan\System\OperatingSystem\Package;
use Diepxuan\System\OperatingSystem as Os;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Diepxuan\System\Component\Process;
use Illuminate\Process\Pipe;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Csf
{
    public static function apply()
    {
        $flag = false;
        $flag = $flag ?: self::rebuildConfiguration();
        $flag = $flag ?: self::rebuildIptablesRules();
        if ($flag) return Process::run("sudo csf -ra")->output();
    }
}
