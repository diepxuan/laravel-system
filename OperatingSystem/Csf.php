<?php

namespace Diepxuan\System\OperatingSystem;

use Diepxuan\System\Component\Process;
use Diepxuan\System\Trait\Csf\Allow;
use Diepxuan\System\Trait\Csf\Dyndns;
use Diepxuan\System\Trait\Csf\Config;
use Diepxuan\System\Trait\Csf\CsfPost;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Csf
{
    use Allow, Config, CsfPost, Dyndns;

    protected $config = null;
    private static $CONFPATH = '/etc/csf/csf.conf';

    public static function isInstalled(): bool
    {
        return Str::of(Process::run('[ -d "/etc/csf" ] && command -v csf >/dev/null && sudo csf -e 2>&1 | grep -q "are not disabled!" && echo isInstalled')->output())->trim()->is('isInstalled');
    }

    public static function apply()
    {
        $flag = false;
        $flag = $flag ?: self::rebuildCsfDyndns();
        $flag = $flag ?: self::rebuildCsfAllow();
        $flag = $flag ?: self::rebuildConfiguration();
        $flag = $flag ?: self::rebuildIptablesRules();
        if ($flag) return Process::run("sudo csf -ra")->output();
    }

    public static function getVersion()
    {
        return Str::of(preg_replace('/csf: ([\w\d]+)/i', '$1', Process::run("sudo csf -v | grep csf:")->output()))->trim();
    }
}
