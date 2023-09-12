<?php

namespace Diepxuan\System\Vm;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Sys\Vm;
use App\Helpers\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use File;

class Tunel
{
    const WIREGUARD_PORT = 17691;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Vm $model, string $key, mixed $value, array $attributes)
    {
        if (!$model->is_wg_active) return;

        if ($model->is_root)
            return $this->siteConfig($model, $key, $value, $attributes);
        else
            return $this->clientConfig($model, $key, $value, $attributes);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Vm $model, string $key, mixed $value, array $attributes)
    {
        return $value;
    }

    protected function siteConfig(Vm $model, string $key, mixed $value, array $attributes)
    {
        $listenPort = self::WIREGUARD_PORT;

        $addr1 = floor($model->id / 255);
        $addr2 = $model->id % 255;
        $wg0 = <<<EOL
[Interface]
# [$model->id] $model->vm_id
PrivateKey = $model->wg_pri
Address = 10.10.$addr1.$addr2/31
ListenPort = $listenPort
EOL;

        $vms = Vm::all()->sortBy('id');

        $wg0 = $vms->filter(function (Vm $vm) use ($model) {
            if (!$vm->is_root) return false;
            if (!$vm->is_wg_active) return false;
            if ($vm->vm_id == $model->vm_id) return false;
            return true;
        })->map(function (Vm $vm, int $key) use ($listenPort) {
            $addr1 = floor($vm->id / 255);
            $addr2 = $vm->id % 255;

            $allowIp = collect(["10.10.$addr1.$addr2/31"])
                ->push($vm->gw_subnet)
                ->filter()->unique()->implode(",");
            return <<<EOL
[Peer]
# [$vm->id] $vm->vm_id
PublicKey = $vm->wg_pub
AllowedIPs = $allowIp
Endpoint = $vm->pub_host:$listenPort
EOL;
        })->prepend($wg0);

        $wgclients = $vms->filter(function (Vm $vm) use ($model) {
            if ($vm->is_root) return false;
            if (!$vm->is_wg_active) return false;
            if ($vm->vm_id == $model->vm_id) return false;
            // if ($vm->parent->vm_id != $model->vm_id) return false;
            return true;
        })->map(function (Vm $vm, int $key) {
            $addr1 = floor($vm->id / 255);
            $addr2 = $vm->id % 255;

            $allowIp = collect(["10.10.$addr1.$addr2/31"])
                ->push($vm->gw_subnet)
                ->filter()->unique()->implode(",");
            return <<<EOL
[Peer]
# [$vm->id] $vm->vm_id
PublicKey = $vm->wg_pub
AllowedIPs = $allowIp
EOL;
        });

        $wg0 = $wg0->merge($wgclients);

        $wg0 = $wg0->implode("\n\n");

        return $wg0;
    }

    protected function clientConfig(Vm $model, string $key, mixed $value, array $attributes)
    {
        $listenPort = self::WIREGUARD_PORT;

        $addr1 = floor($model->id / 255);
        $addr2 = $model->id % 255;
        $wg0 = <<<EOL
[Interface]
# [$model->id] $model->vm_id
PrivateKey = $model->wg_pri
Address = 10.10.$addr1.$addr2/31
EOL;
        $vms = Vm::all()->sortBy('id');
        $wg0 = $vms->filter(function (Vm $vm) use ($model) {
            if (!$vm->is_root) return false;
            if (!$vm->is_wg_active) return false;
            if ($vm->vm_id == $model->vm_id) return false;
            // if ($vm->vm_id != $model->parent->vm_id) return false;
            return true;
        })
            ->map(function (Vm $vm, int $key) use ($listenPort) {
                $addr1 = floor($vm->id / 255);
                $addr2 = $vm->id % 255;

                $allowIp = collect(["10.10.$addr1.$addr2/31"])
                    ->push($vm->gw_subnet)
                    ->filter()->unique()->implode(",");
                return <<<EOL
[Peer]
# [$vm->id] $vm->vm_id
PublicKey = $vm->wg_pub
AllowedIPs = $allowIp
Endpoint = $vm->pub_host:$listenPort
EOL;
            })->prepend($wg0);

        // $wg0 = $wg0->implode("\n\n");
        $wg0 = $wg0->implode("\n\n");
        return $wg0;
    }
}
