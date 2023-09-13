<?php

namespace Diepxuan\System\Component;

/**
 * Extends \SplFileInfo to support realpath paths in Phar://.
 */
class SplFileInfo extends \SplFileInfo
{
    public function getRealPath(): string|false
    {
        return parent::getRealPath() ?: $this->phar_realpath(parent::getPathname());
    }

    function phar_realpath($path)
    {
        $realPath = realpath($path);
        if ($realPath == false && file_exists($path)) {
            if (str_starts_with($path, 'phar:///'))
                $strStart = 'phar:///';
            $realPath = str_replace($strStart, '', $path);
            $realPath = $this->phar_absolutepath($realPath);
            $realPath = "$strStart$realPath";
        }
        return $realPath;
    }

    function phar_absolutepath($path)
    {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
    }
}
