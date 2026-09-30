<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminOrderDateFilterTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['from', 'not-a-date'])]
    #[TestWith(['to', '2026-02-30'])]
    #[TestWith(['from', '2026-9-1'])]
    #[TestWith(['to', '2026-09-30 12:00:00'])]
    #[TestWith(['from', ['2026-09-30']])]
    public function test_malformed_dates_return_validation_errors(string $field, mixed $date): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->getJson(route('admin.orders.index', [$field => $date]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field])
            ->assertJsonPath("errors.$field.0", "The $field field must match the format Y-m-d.");
    }

    public function test_html_requests_redirect_with_a_date_validation_error(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->from(route('admin.orders.index'))
            ->get(route('admin.orders.index', ['from' => 'yesterday']))
            ->assertRedirect(route('admin.orders.index'))
            ->assertSessionHasErrors(['from' => 'The from field must match the format Y-m-d.']);
    }

    public function test_valid_dates_include_boundary_days_and_exclude_outside_orders(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        Order::factory()->create(['created_at' => '2026-09-01 23:59:59']);
        $first = Order::factory()->create(['created_at' => '2026-09-02 00:00:00']);
        $last = Order::factory()->create(['created_at' => '2026-09-03 23:59:59']);
        Order::factory()->create(['created_at' => '2026-09-04 00:00:00']);

        $this->get(route('admin.orders.index', ['from' => '2026-09-02', 'to' => '2026-09-03']))
            ->assertOk()
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders): bool => $orders->getCollection()->modelKeys() === [$last->id, $first->id]);
    }

    public function test_empty_date_filters_leave_orders_unfiltered(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $order = Order::factory()->create();

        $this->get(route('admin.orders.index', ['from' => '', 'to' => '']))
            ->assertOk()
            ->assertViewHas('orders', fn (LengthAwarePaginator $orders): bool => $orders->getCollection()->modelKeys() === [$order->id]);
    }
}
