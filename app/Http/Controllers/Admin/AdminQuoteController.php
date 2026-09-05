<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateQuoteStatusRequest;
use App\Models\QuoteRequest;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminQuoteController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly SettingsService $settings,
    ) {}

    public function index(Request $request)
    {
        $quotes = QuoteRequest::query()
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('search'), fn ($q, $search) => $q->where(fn ($q2) => $q2
                ->where('name', 'like', "%{$search}%")
                ->orWhere('company', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statuses = QuoteStatus::cases();

        return view('admin.quotes.index', compact('quotes', 'statuses'));
    }

    public function show(QuoteRequest $quote)
    {
        return view('admin.quotes.show', compact('quote'));
    }

    public function updateStatus(UpdateQuoteStatusRequest $request, QuoteRequest $quote): RedirectResponse
    {
        $quote->update(['status' => $request->validated('status')]);

        $this->audit->log('quote_status_changed', $quote, oldValues: ['status' => $quote->getOriginal('status')], newValues: ['status' => $quote->status->value]);

        return back()->with('success', __('admin.quote_updated'));
    }
}
