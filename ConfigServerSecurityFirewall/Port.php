<?php

namespace Diepxuan\System\ConfigServerSecurityFirewall;

use Illuminate\Support\Str;
use Diepxuan\System\Vm;

trait Port
{
    private static $PORTTCP = "tcp";
    private static $PORTUDP = "udp";

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
}
