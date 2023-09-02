<?php

namespace Diepxuan\System\ConfigServerSecurityFirewall;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Diepxuan\System\Vm;

trait Port
{
    private $PORTTCP = "tcp";
    private $PORTUDP = "udp";

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function getPortLst($protocol = '')
    {
        $model = Vm::getCurrent();
        $value = $model->port;

        foreach ($model->clients as $vm) {
            foreach ([$this->PORTTCP, $this->PORTUDP] as $type) {
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
