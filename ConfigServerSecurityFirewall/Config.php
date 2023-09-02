<?php

namespace Diepxuan\System\ConfigServerSecurityFirewall;

use Diepxuan\System\ConfigServerSecurityFirewall as Model;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

trait Config
{
    use \Diepxuan\System\ConfigServerSecurityFirewall\Cluster;

    protected $config = null;

    public function getConfigLst(): Collection
    {
        if ($this->config == null)
            $this->config = collect();

        if ($this->config->count() > 0) return $this->config;

        $this->config->put('TESTING', "0");
        $this->config->put('IGNORE_ALLOW', "1");
        $this->config->put('SYNFLOOD', "1");
        $this->config->put('SYNFLOOD_RATE', "75/s");
        $this->config->put('SYNFLOOD_BURST', "25");

        $this->config->put('PACKET_FILTERs', "0");
        $this->config->put('LF_SELECT', "1");
        $this->config->put('LF_DAEMON', "1");
        $this->config->put('LF_DISTATTACK', "0");
        $this->config->put('ICMP_IN', "0");

        $this->config->put('TCP_IN', "???");
        $this->config->put('TCP_OUT', "1:65535");
        $this->config->put('UDP_IN', "???");
        $this->config->put('UDP_OUT', "1:65535");
        $this->config->put('CC_DENY', "");

        $this->config->put('DENY_IP_LIMIT', "500");
        $this->config->put('CLUSTER_BLOCK', "1");
        $this->config->put('CLUSTER_SENDTO', $this->getClusterLst()->implode(','));
        $this->config->put('CLUSTER_RECVFROM', $this->getClusterLst()->implode(','));
        $this->config->put('CUSTOM1_LOG', "/var/log/syslog");

        return $this->config;
        // [DYNDNS = "300"';]
        //         $csf = Str::replaceArray('???', [
        //             $model->portopen['tcp'],
        //             $model->portopen['udp'],
        //         ], $csf);
        //         return $csf;
    }

    public function getConfig(): string
    {
        $config = $this->getConfigLst()->map(function ($value, $key) {
            return "$key = \"$value\"";
        })->implode("\n");
        return Str::of($config)->trim();
    }
}
