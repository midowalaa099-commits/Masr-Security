<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use Illuminate\Http\Response;

class ProductImageController extends Controller
{
    public function show(ProductImage $image): Response
    {
        abort_unless($image->path === 'database', 404);

        $content = $image->content;

        abort_if($content === null, 404);

        $bytes = base64_decode($content->contents, true);

        abort_if($bytes === false, 404);

        return response($bytes)
            ->header('Content-Type', $content->mime_type)
            ->header('Cache-Control', 'public, max-age=86400')
            ->header('X-Content-Type-Options', 'nosniff');
    }
}
