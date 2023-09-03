<?php

namespace Diepxuan\System\ConfigServerSecurityFirewall;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Diepxuan\System\Vm;
use App\Helpers\Str;

trait Cluster
{
    private static $PORTTCP = "tcp";
    private static $PORTUDP = "udp";

    public static function getPortForward()
    {
        $model = Vm::getCurrent();
        $value = '';

        foreach ($model->clients as $vm) {
            $value .= "\n";

            $portopen = $vm->portopen;
            foreach ([self::$PORTTCP, self::$PORTUDP] as $type) {
                $ports = $model->port[$type];
                $ports = explode(',', $ports);

                $portopen[$type] = explode(',', $portopen[$type]);

                $portopen[$type] = Arr::where($portopen[$type], function (string|int $v, int $k) use ($ports) {
                    return !in_array($v, $ports);
                });

                $portopen[$type] = implode(',', $portopen[$type]);
            }

            $value .= implode(':', [collect(explode(' ', trim($vm->pri_host)))->last(), implode(':', $portopen)]);
            $value = trim($value);
        }
        $value = trim($value);

        return $value;
    }
}
