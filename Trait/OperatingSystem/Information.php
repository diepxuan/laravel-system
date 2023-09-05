<?php

namespace Diepxuan\System\Trait\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

trait Information
{
    public static function getIpWan(): string
    {
        return Str::of(Process::run("dig -4 @ns1.google.com -t txt o-o.myaddr.l.google.com +short")->output())->trim(" \n\r\t\v\x00\"");
    }

    public static function getHostName(): string
    {
        return trim(getHostName() ?: Process::run("hostname -s")->output());
    }

    public static function getHostDomain(): string
    {
        return trim(Process::run("hostname -d")->output() ?: "diepxuan.com");
    }

    public static function getHostFullName(): string
    {
        $hostName   = OperatingSystem::getHostName();
        $hostDomain = OperatingSystem::getHostDomain();
        return "$hostName.$hostDomain";
    }

    public static function getOSInformation()
    {
        if (false == function_exists("shell_exec") || false == is_readable("/etc/os-release")) {
            return null;
        }

        $os         = shell_exec('cat /etc/os-release');
        $listIds    = preg_match_all('/.*=/', $os, $matchListIds);
        $listIds    = $matchListIds[0];

        $listVal    = preg_match_all('/=.*/', $os, $matchListVal);
        $listVal    = $matchListVal[0];

        array_walk($listIds, function (&$v, $k) {
            $v = strtolower(str_replace('=', '', $v));
        });

        array_walk($listVal, function (&$v, $k) {
            $v = preg_replace('/=|"/', '', $v);
        });

        return array_combine($listIds, $listVal);
    }
}
