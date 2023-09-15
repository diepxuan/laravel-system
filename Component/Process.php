<?php

namespace Diepxuan\System\Component;

use Illuminate\Support\Facades\Process as BaseProcess;
use Illuminate\Contracts\Process\ProcessResult;

class Process extends BaseProcess
{
    /**
     * @method static \Illuminate\Contracts\Process\ProcessResult run(array|string|null $command = null, callable|null $output = null)
     */

    public static function run(array|string|null $command = null, callable|null $output = null): ProcessResult
    {
        return parent::run($command, $output);
    }
}
