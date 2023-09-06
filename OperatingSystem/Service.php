<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;

class Service extends Model
{
    /**
     * Create a new model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->name = "ductnd";
    }

    public function actived(): bool
    {
        return self::isActive($this->name);
    }

    public static function valid(): string
    {
        return 'test';
    }

    public static function isActive($serviceName): bool
    {
        return Str::of(Process::run("sudo systemctl is-active $serviceName")->output())->exactly('active');
    }
}
