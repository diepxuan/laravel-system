<?php

namespace Diepxuan\System\Trait\Csf;

use Diepxuan\System\Component\Process;
use Diepxuan\System\OperatingSystem\Vm;
use Diepxuan\System\Trait\Csf\Cluster;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait Allow
{
    protected static function rebuildCsfAllow()
    {
        $csfAllow = Str::of(Process::run("sudo cat /etc/csf/csf.allow")->output())->trim();
        $ipsAllow = Str::of(Process::run("sudo cat /etc/ductn/csf.allow")->output())->trim();
        if (!Str::contains($csfAllow, "Include /etc/ductn/csf.allow")) {
            Process::run("echo 'Include /etc/ductn/csf.allow' | sudo tee -a /etc/csf/csf.allow >/dev/null");
        }
        $ips = self::getCsfAllowIpLst()->implode("\n");
        $flag = Str::of($ips)->is($ipsAllow);
        if (!$flag)
            Process::run("echo '$ips' | sudo tee /etc/ductn/csf.allow >/dev/null");
        return $flag;
    }

    protected static function getCsfAllowIpLst(): Collection
    {
        return Vm::all()
            ->map(function (Vm $vm) {
                return collect([])
                    ->merge(explode(' ', trim($vm->pri_host)))
                    ->merge(explode(' ', trim($vm->pub_host)))
                    ->filter()
                    ->all();
            })->flatten()
            ->filter(fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4))
            ->filter()->unique()->sort()->values();
    }
}
