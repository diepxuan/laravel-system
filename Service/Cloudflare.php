<?php

/**
 * @copyright © 2023 DiepXuan. All rights reserved.
 * @author Tran Ngoc Duc <caothu91@gmail.com>
 *
 * Created on Thu May 25 2023 21:01:52
 */


namespace Diepxuan\System\Service;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Diepxuan\System\Service\Ddns as Model;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\HasApiTokens;

class Cloudflare extends Model
{
    use HasFactory;
    use HasApiTokens;


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
    protected $attributes = [
        'service' => 'CloudFlare'
    ];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function dnsUpdate($zone_identifier = null, $identifier = null, $content = null)
    {
        $this->zone_identifier = $zone_identifier ?: ($this->zone_identifier ?: '');
        $this->identifier      = $identifier ?: ($this->identifier ?: '');
        $content               = trim($content);

        $key            = new \Cloudflare\API\Auth\APIKey($this->email, $this->api);
        $adapter        = new \Cloudflare\API\Adapter\Guzzle($key);
        $zoneRepository = new \Cloudflare\API\Endpoints\Zones($adapter);
        $dnsRepository  = new \Cloudflare\API\Endpoints\DNS($adapter);

        $listZones = collect($zoneRepository->listZones($this->zone_identifier)->result);
        // Log::info($listZones);
        $listZones->each(function ($zone) use ($dnsRepository, $content) {
            // Log::info($zone->name);
            // Log::info($zone->id);

            $listDns = collect($dnsRepository->listRecords($zone->id, 'A', $this->identifier)->result);
            // Log::info($listDns);
            $listDns->each(function ($dns) use ($dnsRepository, $content) {
                if ($dns->content == $content)
                    return;

                $dns->content = $content;
                // Log::info((array) $dns);
                $dnsRepository->updateRecordDetails($dns->zone_id, $dns->id, (array) $dns);
            });
        });
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope("ddns.cloudflare", function (Builder $builder) {
            // $builder->whereNotEmpty('port');
            $builder->where('service', 'CloudFlare');
        });
    }
}