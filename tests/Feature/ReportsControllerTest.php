<?php

use App\Models\ApiRequest;
use App\Models\Permission;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    /** @var User $user */
    $user = User::factory()->create();
    test()->user = $user;

    /** @var User $admin */
    $admin = User::factory()->create();
    test()->admin = $admin;

    $permission = Permission::factory()->withSlug('admin.logs.view')->create();
    $admin->permissions()->attach($permission);
});

it('requires authentication to access reports', function () {
    get('/reports')
        ->assertRedirect('/login');
});

it('displays reports page for authenticated user', function () {
    actingAs(test()->user)
        ->get('/reports')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->has('requests')
            ->has('methods')
            ->where('canViewAllLogs', false)
        );
});

it('shows only own requests for regular users', function () {
    $otherUser = User::factory()->create();

    ApiRequest::factory()->count(3)->create(['user_id' => test()->user->id]);
    ApiRequest::factory()->count(5)->create(['user_id' => $otherUser->id]);

    actingAs(test()->user)
        ->get('/reports')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 3)
            ->where('canViewAllLogs', false)
        );
});

it('shows all requests for admin users', function () {
    $otherUser = User::factory()->create();

    ApiRequest::factory()->count(3)->create(['user_id' => test()->admin->id]);
    ApiRequest::factory()->count(5)->create(['user_id' => $otherUser->id]);

    actingAs(test()->admin)
        ->get('/reports')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 8)
            ->where('canViewAllLogs', true)
            ->has('users')
        );
});

it('admin can filter by specific user', function () {
    $otherUser = User::factory()->create();

    ApiRequest::factory()->count(3)->create(['user_id' => test()->admin->id]);
    ApiRequest::factory()->count(5)->create(['user_id' => $otherUser->id]);

    actingAs(test()->admin)
        ->get('/reports?user_id='.$otherUser->id)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 5)
            ->where('filters.user_id', (string) $otherUser->id)
        );
});

it('can filter by method', function () {
    ApiRequest::factory()->count(3)->create([
        'user_id' => test()->user->id,
        'method' => 'GET',
    ]);
    ApiRequest::factory()->count(2)->create([
        'user_id' => test()->user->id,
        'method' => 'POST',
    ]);

    actingAs(test()->user)
        ->get('/reports?method=GET')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 3)
            ->where('filters.method', 'GET')
        );
});

it('can filter by status success', function () {
    ApiRequest::factory()->count(4)->successful()->create(['user_id' => test()->user->id]);
    ApiRequest::factory()->count(2)->failed()->create(['user_id' => test()->user->id]);

    actingAs(test()->user)
        ->get('/reports?status=success')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 4)
            ->where('filters.status', 'success')
        );
});

it('can filter by date range', function () {
    ApiRequest::factory()->count(2)->create([
        'user_id' => test()->user->id,
        'created_at' => now()->subDays(10),
    ]);
    ApiRequest::factory()->count(3)->create([
        'user_id' => test()->user->id,
        'created_at' => now(),
    ]);

    $today = now()->format('Y-m-d');

    actingAs(test()->user)
        ->get("/reports?date_from={$today}&date_to={$today}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 3)
        );
});

it('can filter by path', function () {
    ApiRequest::factory()->count(2)->create([
        'user_id' => test()->user->id,
        'path' => '/api/users',
    ]);
    ApiRequest::factory()->count(3)->create([
        'user_id' => test()->user->id,
        'path' => '/api/products',
    ]);

    actingAs(test()->user)
        ->get('/reports?path=users')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 2)
        );
});

it('paginates results', function () {
    ApiRequest::factory()->count(25)->create(['user_id' => test()->user->id]);

    actingAs(test()->user)
        ->get('/reports')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Reports')
            ->where('requests.total', 25)
            ->where('requests.per_page', 20)
            ->where('requests.current_page', 1)
            ->where('requests.last_page', 2)
        );
});
