<?php

namespace Diepxuan\System\Cast;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Diepxuan\System\Vm;

class VmPortOpen
{
    const PORTTCP = "tcp";
    const PORTUDP = "udp";

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Vm $model, string $key, mixed $value, array $attributes)
    {
        $value = $model->port;

        foreach ($model->clients as $vm) {
            foreach ([self::PORTTCP, self::PORTUDP] as $type) {
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
        return $value;
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
