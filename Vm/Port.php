<?php

namespace Diepxuan\System\Vm;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Diepxuan\System\OperatingSystem\Vm;

class Port
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
        $value = $value ?: '';
        $value = explode(':', $value);
        $value = array_replace(['', ''], $value);
        $tcp = $value[0];
        $udp = $value[1];
        return [
            'tcp' => $tcp,
            'udp' => $udp,
        ];
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Vm $model, string $key, mixed $value, array $attributes)
    {
        $value = array_filter($value);
        $value = array_replace($model->port, $value);
        $value = array_replace(['tcp' => '', 'udp' => ''], $value);

        foreach ([self::PORTTCP, self::PORTUDP] as $type) {
            $value[$type] = str_replace(" ", ",", $value[$type]);
            $value[$type] = explode(",", $value[$type]);
            foreach ($value[$type] as $k => $v) {
                $value[$type][$k] = trim($value[$type][$k]);
            }
            $value[$type] = array_unique($value[$type]);
            $value[$type] = array_filter($value[$type]);
            $value[$type] = array_filter($value[$type]);
            sort($value[$type]);
            $value[$type] = implode(',', $value[$type]);
        }

        $value = implode(':', $value);
        return $value;
    }
}
