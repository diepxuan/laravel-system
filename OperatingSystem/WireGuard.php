<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Diepxuan\System\OperatingSystem\Package;
use Diepxuan\System\OperatingSystem as Os;
use Diepxuan\System\OperatingSystem\Wg;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class WireGuard extends Model
{
    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->package = Wg::$package;
        $this->keydir  = Wg::$keydir;
    }

    public function PrivateKey(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Wg::keyPrivate(),
            set: fn (mixed $value, array $attributes) => Wg::keyPrivate($value ?: '')
        );
    }

    public function PublicKey(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Wg::keyPublic(),
            set: fn (mixed $value, array $attributes) => Wg::keyPublic($value ?: '')
        );
    }

    public function enabled(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => Wg::isEnabled()
        );
    }

    public function install()
    {
        return Wg::install();
    }

    public function isInstalled(): bool
    {
        return Wg::isInstalled();
    }

    public function keyGen()
    {
        return Wg::keyGen();
    }
}
