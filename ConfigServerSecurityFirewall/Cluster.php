<?php

namespace Diepxuan\System\ConfigServerSecurityFirewall;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Diepxuan\System\Vm;
use App\Helpers\Str;

trait Cluster
{
    public static function getClusterLst(): Collection
    {
        $model = Vm::getCurrent();

        // current vm
        $value = collect(explode(' ', trim($model->pri_host)));
        $value = $value->merge(explode(' ', trim($model->pub_host)));

        // same level vms
        foreach ($model->parent->clients as $vm) {
            $value = $value->merge(explode(' ', trim($vm->pri_host)));
            $value = $value->merge(explode(' ', trim($vm->pub_host)));
        }

        // parent vm
        $value = $value->merge(explode(' ', trim($model->parent->pri_host)));
        $value = $value->merge(explode(' ', trim($model->parent->pub_host)));

        // root vms
        $value = $value->merge(Vm::all()->reject(function (Vm $vm) {
            return $vm->parent->name !== "none";
        })->map(function (Vm $vm) {
            $return = collect([]);
            $return = $return->merge(explode(' ', trim($vm->pri_host)));
            $return = $return->merge(explode(' ', trim($vm->pub_host)));
            return $return->all();
        })->flatten());

        $value = $value->filter()->unique()->sort();
        return $value;
    }
}
