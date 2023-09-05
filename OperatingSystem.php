<?php

namespace Diepxuan\System;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class OperatingSystem extends Model
{
    use \Diepxuan\System\Trait\OperatingSystem\Information;
    use \Diepxuan\System\Trait\OperatingSystem\Network;
    use \Diepxuan\System\Trait\OperatingSystem\Storage;

    public function hostName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => self::getHostName(),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function hostDomain(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => self::getHostDomain(),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function hostFullName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => self::getHostFullName(),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function ipLocal(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(shell_exec("hostname -I")),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function ipWan(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => self::getIpWan(),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function appVersion(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(preg_replace('/Version: ([\w\d]+)/i', '$1', Process::run("dpkg -s ductn | grep Version")->output())),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }
}
