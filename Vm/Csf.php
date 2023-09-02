<?php

namespace Diepxuan\System\Casts\Vm;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Sys\Vm;
use App\Helpers\Str;

class Csf
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Vm $model, string $key, mixed $value, array $attributes)
    {
        $csf = 'TESTING = "0"
IGNORE_ALLOW = "1"
SYNFLOOD = "1"
SYNFLOOD_RATE = "75/s"
SYNFLOOD_BURST = "25"

PACKET_FILTERs = "0"
LF_SELECT = "1"
LF_DAEMON = "1"
LF_DISTATTACK = "0"
ICMP_IN = "0"

TCP_IN = "???"
TCP_OUT = "1:65535"
UDP_IN = "???"
UDP_OUT = "1:65535"
CC_DENY = ""

DENY_IP_LIMIT = "500"
CLUSTER_BLOCK = "1"
CLUSTER_SENDTO = "???"
CLUSTER_RECVFROM = "???"
CUSTOM1_LOG = "/var/log/syslog"
DYNDNS = "300"';
        $csf = Str::replaceArray('???', [
            $model->portopen['tcp'],
            $model->portopen['udp'],
            $model->csf_cluster,
            $model->csf_cluster,
        ], $csf);
        return $csf;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Vm $model, string $key, mixed $value, array $attributes)
    {
        return $value;
    }
}
