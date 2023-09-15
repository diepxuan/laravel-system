<?php

namespace Diepxuan\System;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Diepxuan\System\OperatingSystem\ConfigServerSecurityFirewall as CSF;

class ConfigServerSecurityFirewall extends CSF
{
}
