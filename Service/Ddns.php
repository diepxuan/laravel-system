<?php

/**
 * Copyright © DiepXuan, Ltd. All rights reserved.
 */

namespace Diepxuan\System\Service;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;
use Diepxuan\System\Service\Cloudflare;

class Ddns extends Model
{
    use HasFactory;
    use HasApiTokens;

    const SERVICE_CF = 'CloudFlare';

    protected $serviceMappings = [
        // Add your other mappings here
        self::SERVICE_CF => Cloudflare::class
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ddns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'api',
        'apiUrl',
        'service',
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
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function castAs(mixed $className = null)
    {
        if ($className == null) {
            $className = $this->serviceMappings[$this->service];
        }

        if (class_exists($className)) {
            // return new $className($this->toArray());

            $model = (new $className())
                ->newInstance([], true)
                ->setRawAttributes($this->getAttributes());

            // foreach ($this->getRelations() as $relation => $items) {
            //     foreach ($items as $item) {
            //         unset($item->id);
            //         $model->{$relation}()->create($item->toArray());
            //     }
            // }
            return $model;



            return $className::findOr($this->id, function () {
                return $this;
            });
        }
        return $this;
    }

    public function dnsUpdate($zone_identifier = null, $identifier = null, $content = null)
    {
        //
    }

    /**
     * The vms that belong to the ddns.
     */
    public function vms(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Sys\Vm::class, 'ddns_vm', 'ddns', 'vm');
    }
}