<?php

namespace Diepxuan\System\Casts\Vm;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use App\Models\Sys\Vm;
use App\Helpers\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use File;

class SshdConfig
{
    const WIREGUARD_PORT = 17691;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Vm $model, string $key, mixed $value, array $attributes)
    {
        return Vm::all()->sortBy('id')
            ->filter(function (Vm $vm) use ($model) {
                if (empty($vm->sshd_host_name)) return false;
                return true;
            })
            ->map(function (Vm $vm, int $key) {
                $sshd_proxy_jump = $vm->sshd_proxy_jump ? "ProxyJump $vm->sshd_proxy_jump\n" : "";
                return <<<EOL
Host $vm->sshd_host
    User ductn
    HostName $vm->sshd_host_name
    $sshd_proxy_jump
EOL;
            })->implode("\n");
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
}
