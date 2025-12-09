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

it('requires authentication to access dashboard', function () {
    get('/dashboard')
        ->assertRedirect('/login');
});

it('displays dashboard for authenticated user', function () {
    actingAs(test()->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('stats')
            ->has('requestsPerDay')
            ->has('requestsByMethod')
            ->has('requestsByStatus')
            ->has('recentRequests')
            ->where('canViewAllLogs', false)
        );
});

it('shows only own requests for regular users', function () {
    $otherUser = User::factory()->create();

    ApiRequest::factory()->count(3)->create(['user_id' => test()->user->id]);
    ApiRequest::factory()->count(5)->create(['user_id' => $otherUser->id]);

    actingAs(test()->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('stats.total', 3)
            ->where('canViewAllLogs', false)
        );
});

it('shows all requests for admin users', function () {
    $otherUser = User::factory()->create();

    ApiRequest::factory()->count(3)->create(['user_id' => test()->admin->id]);
    ApiRequest::factory()->count(5)->create(['user_id' => $otherUser->id]);

    actingAs(test()->admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('stats.total', 8)
            ->where('canViewAllLogs', true)
            ->has('users')
        );
});

it('admin can filter by specific user', function () {
    $otherUser = User::factory()->create();

    ApiRequest::factory()->count(3)->create(['user_id' => test()->admin->id]);
    ApiRequest::factory()->count(5)->create(['user_id' => $otherUser->id]);

    actingAs(test()->admin)
        ->get('/dashboard?user_id='.$otherUser->id)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('stats.total', 5)
            ->where('filterUserId', (string) $otherUser->id)
        );
});

it('regular user cannot filter by other user', function () {
    $otherUser = User::factory()->create();

    ApiRequest::factory()->count(3)->create(['user_id' => test()->user->id]);
    ApiRequest::factory()->count(5)->create(['user_id' => $otherUser->id]);

    actingAs(test()->user)
        ->get('/dashboard?user_id='.$otherUser->id)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('stats.total', 3)
        );
});

it('calculates correct stats', function () {
    ApiRequest::factory()->count(7)->successful()->create(['user_id' => test()->user->id]);
    ApiRequest::factory()->count(3)->failed()->create(['user_id' => test()->user->id]);

    actingAs(test()->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('stats.total', 10)
            ->where('stats.successful', 7)
            ->where('stats.failed', 3)
            ->where('stats.successRate', 70)
        );
});

it('returns recent requests ordered by latest', function () {
    $first = ApiRequest::factory()->create([
        'user_id' => test()->user->id,
        'created_at' => now()->subHours(2),
    ]);
    $second = ApiRequest::factory()->create([
        'user_id' => test()->user->id,
        'created_at' => now()->subHour(),
    ]);
    $third = ApiRequest::factory()->create([
        'user_id' => test()->user->id,
        'created_at' => now(),
    ]);

    actingAs(test()->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->has('recentRequests', 3)
            ->where('recentRequests.0.id', $third->id)
            ->where('recentRequests.1.id', $second->id)
            ->where('recentRequests.2.id', $first->id)
        );
});
