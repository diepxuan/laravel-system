<?php

namespace Diepxuan\System;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConfigServerSecurityFirewall extends Model
{
    public function isInstall(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Str::of(Process::run('command -v csf')->output())->isNotEmpty(),
        );
    }

    public function version(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(preg_replace('/csf: ([\w\d]+)/i', '$1', Process::run("sudo csf -v | grep csf:")->output())),
        );
    }

    /**
     * Fix missing iptables default path for csf cmd
     */
    function iptables(): void
    {
        $this->_iptables('iptables');
        $this->_iptables('iptables-save');
        $this->_iptables('iptables-restore');
    }

    function _iptables($command): void
    {
        $cmdPath    = Process::run("command -v $command")->output();
        $cmdDefault = "/sbin/$command";
        if ($cmdPath == $cmdDefault) return;
        if (!File::isFile($cmdDefault))
            Process::run("sudo ln $(which $command) /sbin/$command");
    }
}
