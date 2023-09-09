<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Diepxuan\System\OperatingSystem\Package;
use Diepxuan\System\OperatingSystem as Os;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class Service extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected  $fillable = [
        'name',
        'service',
        'package',
    ];

    /**
     * List services for valid and automation
     */
    protected static $rows = [
        [
            'name'    => 'test',
            'service' => 'ductnd',
            'package' => 'ductn',
        ],
        [
            'name'    => 'httpd',
            'service' => 'apache2',
            'package' => 'apache2',
        ],
        [
            'name'    => 'mysql',
            'service' => 'mariadb',
            'package' => 'mariadb-server',
        ],
        [
            'name'    => 'mysql',
            'service' => 'mysqld',
            'package' => 'mysql-server',
        ],
        [
            'name'    => 'mssql',
            'service' => 'mssql-server',
            'package' => 'mssql-server',
        ],
    ];

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $value ?: "ductnd",
            set: fn (mixed $value, array $attributes) => $value
        );
    }

    public function actived(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => self::isActive($this->service),
            // set: fn (mixed $value, array $attributes) => $value
        );
    }

    public function installed(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => self::isInstalled($this->package),
            // set: fn (mixed $value, array $attributes) => $value
        );
    }

    public static function valid(): string
    {
        return collect(self::$rows)->map(function ($service) {
            return (new self($service));
        })->where('installed', true)
            ->where('actived', false)
            ->map(function ($service) {
                $service_name = $service->service;
                $result = "Service $service_name is installed but not start!\n";
                OS::sysSwapOn();
                $result .= "  Extend swap storage\n";
                $flag = self::restart($service->service);
                $result .= $flag ? "  Service $service_name restarted\n" : "  Service $service_name restart failure\n";

                return $result;
            })->implode("\n");
    }

    public static function isActive($service): bool
    {
        $status = Str::of(Process::run("sudo systemctl is-active $service.service")->output())->trim();
        return $status->is('active') || $status->is('activating');
    }

    public static function restart($service): bool
    {
        $status = Process::run("sudo systemctl restart $service.service");
        return $status->exitCode() == 0;
    }

    public static function isInstalled($package): bool
    {
        return Package::isInstalled($package);
    }
}
