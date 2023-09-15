<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Diepxuan\System\OperatingSystem\Package;
use Diepxuan\System\OperatingSystem as Os;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Diepxuan\System\Component\Process;
use Diepxuan\System\Trait\Csf\Allow;
use Illuminate\Process\Pipe;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Csf
{
    // use \Diepxuan\System\ConfigServerSecurityFirewall\Cluster;
    // use \Diepxuan\System\ConfigServerSecurityFirewall\Port;

    protected $config = null;
    private static $CONFPATH = '/etc/csf/csf.conf';
    private static $POSTPATH = '/etc/csf/csfpost.sh';
    private static $PORTTCP  = "tcp";
    private static $PORTUDP  = "udp";

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function getPortLst($protocol = '')
    {
        $model = Vm::getCurrent();
        $value = $model->port;

        foreach ($model->clients as $vm) {
            foreach ([self::$PORTTCP, self::$PORTUDP] as $type) {
                $value[$type] = array_merge(
                    explode(',', $vm->portopen[$type]),
                    explode(',', $value[$type])
                );
                $value[$type] = array_unique($value[$type]);
                $value[$type] = array_filter($value[$type]);
                sort($value[$type]);
                $value[$type] = implode(',', $value[$type]);
            }
        }
        if (Str::of($protocol)->isNotEmpty())
            return $value[$protocol];
        return $value;
    }

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

    public static function getClusterLst(): Collection
    {
        $model = Vm::getCurrent();

        // current vm
        $value = collect(explode(' ', trim($model->pri_host)));
        $value = $value->merge(explode(' ', trim($model->pub_host)));

        // same level vms
        foreach ($model->parent->clients as $vm) {
            $value = $value->merge(explode(' ', trim($vm->pri_host)));
            $value = $value->merge(explode(' ', trim($vm->pub_host)));
        }

        // parent vm
        $value = $value->merge(explode(' ', trim($model->parent->pri_host)));
        $value = $value->merge(explode(' ', trim($model->parent->pub_host)));

        // root vms
        $value = $value->merge(Vm::all()->reject(function (Vm $vm) {
            return $vm->parent->name !== "none";
        })->map(function (Vm $vm) {
            $return = collect([]);
            $return = $return->merge(explode(' ', trim($vm->pri_host)));
            $return = $return->merge(explode(' ', trim($vm->pub_host)));
            return $return->all();
        })->flatten());

        $value = $value->filter()->unique()->sort();
        return $value;
    }

    public static function csfConfigLst(): Collection
    {
        $config = collect();

        $config->put('TESTING', "0");
        $config->put('IGNORE_ALLOW', "1");

        $config->put('DYNDNS', "300");

        $config->put('SYNFLOOD', "1");
        $config->put('SYNFLOOD_RATE', "75/s");
        $config->put('SYNFLOOD_BURST', "25");

        $config->put('PACKET_FILTERs', "0");
        $config->put('LF_SELECT', "1");
        $config->put('LF_DAEMON', "1");
        $config->put('LF_DISTATTACK', "0");
        $config->put('ICMP_IN', "0");

        $config->put('TCP_IN', self::getPortLst('tcp'));
        $config->put('TCP_OUT', "1:65535");
        $config->put('UDP_IN', self::getPortLst('udp'));
        $config->put('UDP_OUT', "1:65535");
        $config->put('CC_DENY', "");

        $config->put('DENY_IP_LIMIT', "500");
        $config->put('CLUSTER_BLOCK', "1");
        $config->put('CLUSTER_SENDTO', self::getClusterLst()->implode(','));
        $config->put('CLUSTER_RECVFROM', self::getClusterLst()->implode(','));
        $config->put('CUSTOM1_LOG', "/var/log/syslog");

        return $config;
    }

    public function csfConfig(): string
    {
        $config = $this->csfConfigLst()->map(function ($value, $key) {
            return "$key = \"$value\"";
        })->implode("\n");
        return Str::of($config)->trim();
    }

    public static function csfLocalConfig(string $key = null, string $val = null): string
    {
        if (!is_null($key)) {
            $key = Str::of($key)->trim();
            if (!is_null($val)) {
                $val = Str::of($val)->trim();
                return Process::run(
                    sprintf(
                        "sudo sed -i 's|$key = .*|$key = \"$val\"|' %s",
                        self::$CONFPATH
                    )
                )->output();
            }
            return Str::of(Process::run(
                sprintf("sudo cat %s | grep '$key = '", self::$CONFPATH)
            )->output())
                ->replace("$key = ", '')->trim()->trim('"');
        }
        return Str::of(Process::run(
            sprintf("sudo cat %s", self::$CONFPATH)
        )->output());
    }

    public static function rebuildConfiguration(): bool
    {
        $flag = false;
        self::csfConfigLst()->map(function ($val, $key) use ($flag) {
            $orgVal = self::csfLocalConfig($key);
            $flag = $flag ?: $key != $orgVal;
            if ($flag)
                self::csfLocalConfig($key, $val);
        });

        return $flag;
    }

    public static function apply()
    {
        $flag = false;
        $flag = $flag ?: self::rebuildConfiguration();
        $flag = $flag ?: self::rebuildIptablesRules();
        if ($flag) return Process::run("sudo csf -ra")->output();
    }
}
