<?php

namespace Diepxuan\System\ConfigServerSecurityFirewall;

use Diepxuan\System\ConfigServerSecurityFirewall as Model;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

trait Config
{
    use \Diepxuan\System\ConfigServerSecurityFirewall\Cluster;
    use \Diepxuan\System\ConfigServerSecurityFirewall\Port;

    protected $config = null;

    public static function getConfigLst(): Collection
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

    public function getConfig(): string
    {
        $config = $this->getConfigLst()->map(function ($value, $key) {
            return "$key = \"$value\"";
        })->implode("\n");
        return Str::of($config)->trim();
    }
}
