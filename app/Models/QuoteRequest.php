<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'company',
        'phone',
        'email',
        'message',
        'requested_products',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'requested_products' => 'array',
            'status' => QuoteStatus::class,
        ];
    }
}
