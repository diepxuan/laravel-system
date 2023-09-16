<?php

namespace Diepxuan\System\Trait\Csf;

use Diepxuan\System\Component\Process;
use Diepxuan\System\OperatingSystem\Vm;
use Diepxuan\System\Trait\Csf\Cluster;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait Dyndns
{
    protected static function rebuildCsfDyndns()
    {
        $csfAllow     = Str::of(Process::run("sudo cat /etc/csf/csf.dyndns")->output())->trim();
        $domainsAllow = Str::of(Process::run("sudo cat /etc/ductn/csf.dyndns")->output())->trim();
        if (!Str::contains($csfAllow, "Include /etc/ductn/csf.dyndns")) {
            Process::run("echo 'Include /etc/ductn/csf.dyndns' | sudo tee -a /etc/csf/csf.dyndns >/dev/null");
        }
        $domains = self::getCsfDyndnsDomains()->implode("\n");
        $flag    = Str::of($domains)->is($domainsAllow);
        if (!$flag)
            Process::run("echo '$domains' | sudo tee /etc/ductn/csf.dyndns >/dev/null");
        return $flag;
    }

    protected static function getCsfDyndnsDomains(): Collection
    {
        return Vm::all()
            ->map(function (Vm $vm) {
                return $vm->vm_id;
            })
            ->filter()->unique()->sort()->values();
    }
}
