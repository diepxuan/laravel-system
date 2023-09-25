<?php

namespace Diepxuan\System\Trait\Csf;

use Diepxuan\System\OperatingSystem as OS;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Diepxuan\System\OperatingSystem\Vm;

trait CsfPost
{
    private static $PORTTCP  = "tcp";
    private static $PORTUDP  = "udp";
    private static $POSTPATH = '/etc/csf/csfpost.sh';

    public static function getPortForward()
    {
        $model = Vm::getCurrent();
        return $model->clients->keyBy('pri_host')->map(function (Vm $vm) use ($model) {
            return Arr::map(Arr::keyBy([self::$PORTTCP, self::$PORTUDP], fn ($v) => Str::lower($v)), function ($type) use ($model, $vm) {
                $_portForward = Str::of($vm->portopen[$type])->explode(',')->where(function ($port, $key) use ($model, $type) {
                    return !Str::of($model->port[$type])->explode(',')->contains($port);
                })->implode(',');
                return $_portForward;
            });
        });
    }

    public static function getIptablesPortForward($route)
    {
        return self::getPortForward()->map(function ($port, $vm) use ($route) {
            return Arr::map($port, function ($port, $type) use ($vm, $route) {
                $type = Str::upper($type);
                return "iptables -t nat -A PREROUTING -i $route -p $type -m multiport --dport $port -j DNAT --to-destination $vm";
            });
        });
    }

    public static function rebuildIptablesRules(): bool
    {
        $netIp      = OS::getIpWan();
        $netRoute   = OS::getRouteDefault();
        $lanRoute   = OS::getRoutes('vmbr1');
        $lanSubnet  = OS::getSubnet($lanRoute);
        $lanIp      = OS::getIpLocal();
        $localRoute = "lo";
        $localIp    = "127.0.0.1";
        $wgRoute    = OS::getRoutes("wg0");
        $tsRoute    = OS::getRoutes("tailscale0");

        $rules = collect(['#!/bin/bash']);
        $rules->push('iptables -t raw -I PREROUTING -i fwbr+ -j CT --zone 1');
        $rules->push("iptables -t nat -A POSTROUTING -o $netRoute -j MASQUERADE");

        if (Str::of($lanRoute)->isNotEmpty()) {
            $rules->push("# iptables -t nat -A POSTROUTING -o $lanRoute -j MASQUERADE");

            $rules->push("iptables -A INPUT -i $lanRoute -j ACCEPT");
            $rules->push("iptables -A FORWARD -i $lanRoute -j ACCEPT");
            $rules->push("iptables -A FORWARD -o $lanRoute -j ACCEPT");

            # allow traffic from internal to DMZ
            $rules->push("iptables -A FORWARD -i $netRoute -o $lanRoute -m state --state NEW,RELATED,ESTABLISHED -j ACCEPT");
            $rules->push("iptables -A FORWARD -i $lanRoute -o $netRoute -m state --state RELATED,ESTABLISHED -j ACCEPT");

            $rules = $rules->concat(self::getIptablesPortForward($lanRoute)->flatten());
        }

        if (Str::of($wgRoute)->isNotEmpty()) {
            $rules->push("iptables -A INPUT -i $wgRoute -j ACCEPT");
            $rules->push("iptables -A FORWARD -i $wgRoute -j ACCEPT");
            $rules->push("iptables -A FORWARD -o $wgRoute -j ACCEPT");
        }
        if (Str::of($tsRoute)->isNotEmpty()) {
            $rules->push("iptables -A INPUT -i $tsRoute -j ACCEPT");
            $rules->push("iptables -A FORWARD -i $tsRoute -j ACCEPT");
            $rules->push("iptables -A FORWARD -o $tsRoute -j ACCEPT");
        }

        $rules = $rules->implode("\n");
        $rules = "$rules\n";

        $oldRules = Process::run(sprintf("sudo cat %s", self::$POSTPATH))->output();

        $flag = $rules != $oldRules;
        if ($flag)
            Process::run(sprintf("echo '$rules' | sudo tee %s", self::$POSTPATH))->output();

        return $flag;
    }
}