<?php

namespace Diepxuan\System\Component;

use Illuminate\Support\Facades\Process as BaseProcess;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class Process extends BaseProcess
{
    /**
     * @method static \Illuminate\Contracts\Process\ProcessResult run(array|string|null $command = null, callable|null $output = null)
     */
    public static function run(array|string|null $command = null, callable|null $output = null): ProcessResult
    {

        if (is_string($command) && Str::of($command)->startsWith("sudo ")) {
            $command = Str::of($command)
                ->toString();
        }
        return parent::run($command, $output);
    }
}
