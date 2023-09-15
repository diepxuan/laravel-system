<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Diepxuan\System\OperatingSystem\Csf;
use Diepxuan\System\Component\Process;

class ConfigServerSecurityFirewall extends Model
{
    public function isInstall(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Csf::isInstalled(),
        );
    }

    public function version(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Csf::getVersion(),
        );
    }

    /**
     * Fix missing iptables default path for csf cmd
     */
    public function iptables(): void
    {
        $this->_iptables('iptables');
        $this->_iptables('iptables-save');
        $this->_iptables('iptables-restore');
    }

    protected function _iptables($command): void
    {
        $cmdPath    = Process::run("command -v $command")->output();
        $cmdDefault = "/sbin/$command";
        if ($cmdPath == $cmdDefault) return;
        if (!File::isFile($cmdDefault))
            Process::run("sudo ln $(which $command) /sbin/$command");
    }

    public function apply()
    {
        return Csf::apply();
    }
}
