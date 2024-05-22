<?php

namespace HopStudios\HopMinifizer\Utility;

class FileUtility
{
    static public function get($file_type, $path)
    {
        return glob($path . '*.' . $file_type);
    }

    static public function getReadableFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $bytes > 1000; $i++) {
            $bytes /= 1000;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}