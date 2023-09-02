<?php

namespace Diepxuan\System;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class ConfigServerSecurityFirewall extends Model
{
    public function isInstall(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Str::of(Process::run('command -v csf')->output())->isNotEmpty(),
        );
    }

    public function version(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => trim(preg_replace('/csf: ([\w\d]+)/i', '$1', Process::run("sudo csf -v | grep csf:")->output())),
        );
    }
}
