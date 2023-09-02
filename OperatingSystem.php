<?php

namespace Diepxuan\System;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;

class OperatingSystem extends Model
{
    public function hostName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(getHostName() ?: shell_exec('hostname -s')),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function hostDomain(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => shell_exec('hostname -d') ?: "diepxuan.com",
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function hostFullName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => "$this->hostName.$this->hostDomain",
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function ipLocal(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(shell_exec("hostname -I")),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function ipWan(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(
                Process::run("dig -4 @ns1.google.com -t txt o-o.myaddr.l.google.com +short")->output(),
                " \n\r\t\v\x00\""
            ),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function appVersion(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(preg_replace('/Version: ([\w\d]+)/i', '$1', Process::run("dpkg -s ductn | grep Version")->output())),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
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
