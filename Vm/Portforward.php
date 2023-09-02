<?php

namespace Diepxuan\System\Vm;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Diepxuan\System\Vm;

class Portforward
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
        foreach ($model->clients as $vm) {
            $value .= "\n";

            $portopen = $vm->portopen;
            foreach ([self::PORTTCP, self::PORTUDP] as $type) {
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
