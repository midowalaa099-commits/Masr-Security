<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;

class EscapedSQLiteGrammar extends SQLiteGrammar
{
    /** @param array<string, mixed> $where */
    protected function whereLike(Builder $query, $where): string
    {
        $sql = parent::whereLike($query, $where);

        return $where['caseSensitive'] ? $sql : $sql." ESCAPE '\\'";
    }
}
