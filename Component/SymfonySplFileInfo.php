<?php

namespace Diepxuan\System\Component;

use Symfony\Component\Finder\SplFileInfo;

class SymfonySplFileInfo extends SplFileInfo
{
    public function getRealPath(): string|false
    {
        $realPath = parent::getRealPath();
        $path = $this->getPathname();
        if ($realPath == false && file_exists($path)) {
            if (str_starts_with($path, 'phar://'))
                $realPath = $path;
            if (strpos($path, 'phar://') === 0)
                $realPath = $path;
        }
        return $realPath;
    }
}
