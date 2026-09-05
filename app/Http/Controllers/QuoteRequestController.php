<?php

namespace App\Http\Controllers;

use App\Http\Requests\QuoteRequestForm;
use App\Models\QuoteRequest;
use Illuminate\Http\RedirectResponse;

class QuoteRequestController extends Controller
{
    public function create()
    {
        return view('store.quote');
    }

    public function store(QuoteRequestForm $request): RedirectResponse
    {
        QuoteRequest::create($request->validated());

        return redirect()
            ->route('quote.create')
            ->with('success', __('store.quote_submitted'));
    }
}
