<?php

namespace Diepxuan\System\OperatingSystem;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Diepxuan\System\OperatingSystem as Os;
use Diepxuan\System\Service\Ddns;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vm extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'vm_id',
        'parent_id',
        'name',
        'pri_host',
        'pub_host',
        'gateway',
        'version',
        'is_allow',
        'port',
        'wgkey',
    ];

    /**
     * Make attributes available in the json response.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'port'        => \Diepxuan\System\Vm\Port::class,
        'portopen'    => \Diepxuan\System\Vm\Portopen::class,
        // 'tunel'       => \Diepxuan\System\Casts\Vm\Tunel::class,
        'wgkey'       => 'array',
        'gateway'     => 'array',
        // 'sshd_config' => \Diepxuan\System\Casts\Vm\SshdConfig::class,
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'vm_id';

    /**
     * The data type of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    public function getNameAttribute($name)
    {
        $name = $name ?: $this->vm_id;
        return $name;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(
            Vm::class,
            'parent_id',
            'vm_id'
        )->withDefault([
            'name' => 'none',
        ]);
    }

    public function clients(): HasMany
    {
        return $this->hasMany(
            Vm::class,
            "parent_id",
            "vm_id"
        );
    }

    public function getDomainsAttribute($domains)
    {
        $_domains = Domain::all();
        foreach ($_domains as $domain) {
            $domain = $domain->name;
            $domains .= "$domain";
            $domains .= "\n";
        }
        $domains = trim($domains);
        $domains .= "
windows.net
digicert.com
servicebus.windows.net
msappproxy.net
security.ubuntu.com
ppa.launchpad.net
php.launchpad.net
launchpad.net

stats.jpush.cn
ecouser.net
cn.ecouser.net
dc-cn.cn.ecouser.net
api-app.dc-cn.cn.ecouser.net
users-base.dc-cn.cn.ecouser.net

ecovacs.cn
gl-cn-api.ecovacs.cn
sa-datasink.ecovacs.cn
gl-cn-openapi.ecovacs.cn

portal.ecouser.net
msg.ecouser.net
mq.ecouser.net
lb.ecouser.net

portal-as.ecouser.net
msg-as.ecouser.net
mq-as.ecouser.net
lbo.ecouser.net

portal-na.ecouser.net
msg-na.ecouser.net
mq-na.ecouser.net

portal-eu.ecouser.net
msg-eu.ecouser.net
mq-eu.ecouser.net

portal-ww.ecouser.net
msg-ww.ecouser.net
mq-ww.ecouser.net";
        return $domains;
    }

    public function wgPri(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => array_replace(['', ''], $this->wgkey ?: [])[0],
            set: fn (mixed $value, array $attributes) => $this->wgkey[0] = $value
        );
    }

    public function wgPub(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => array_replace(['', ''], $this->wgkey ?: [])[1],
            set: fn (mixed $value, array $attributes) => $this->wgkey[1] = $value
        );
    }

    public function gwIp(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => array_replace(['', ''], $this->gateway ?: [])[0],
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function gwSubnet(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => array_replace(['', ''], $this->gateway ?: [])[1],
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function isRoot(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->parent->name == "none",
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function isWgActive(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->wgkey != null && count($this->wgkey) >= 2 && !empty($this->wg_pri) && !empty($this->wg_pub),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    /**
     * The roles that belong to the ddns.
     */
    public function ddnses(): BelongsToMany
    {
        return $this->belongsToMany(Ddns::class, 'ddns_vm', 'vm', 'ddns');
    }

    public function dnsUpdate()
    {
        $identifier = $this->vm_id;
        $content    = $this->pub_host;
        $this->ddnses->each(function ($item, int $key) use ($identifier, $content) {
            $item = $item->castAs();

            $item->dnsUpdate(null, $identifier, $content);
        });
    }

    public function sshdHost(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->name,
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function sshdHostName(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->is_root ? $this->pub_host : collect(explode(' ', trim($this->pri_host)))->last(),
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public function sshdProxyJump(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value, array $attributes) => $this->is_root ? "" : $this->parent->name,
            // set: fn (mixed $value, array $attributes) => $value ?: Str::sanitizeString($attributes['name'])
        );
    }

    public static function getCurrent($vmId = null): Vm
    {
        return VM::updateOrCreate(["vm_id" => $vmId ?: OS::getHostFullName()]);
    }
}