<?php

namespace App\Support;

class SearchPattern
{
    public static function contains(string $search): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
    }
}
