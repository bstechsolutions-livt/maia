<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiRequest>
 */
class ApiRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
        $paths = ['/api/user', '/api/login', '/api/logout', '/api/products', '/api/orders'];
        $statusCodes = [200, 201, 400, 401, 403, 404, 500];

        return [
            'user_id' => User::factory(),
            'method' => fake()->randomElement($methods),
            'path' => fake()->randomElement($paths),
            'full_url' => fn (array $attrs) => 'http://localhost:8000'.$attrs['path'],
            'route_name' => fn (array $attrs) => 'api.'.str_replace(['/', '-'], ['.', '_'], trim($attrs['path'], '/api/')),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'headers' => ['Accept' => 'application/json', 'Content-Type' => 'application/json'],
            'query_params' => [],
            'request_body' => [],
            'status_code' => fake()->randomElement($statusCodes),
            'response_body' => ['message' => 'OK'],
            'response_time_ms' => fake()->numberBetween(10, 500),
            'device_name' => fake()->randomElement(['Postman', 'Mobile App', 'Web Browser', null]),
            'token_id' => fake()->optional()->randomNumber(3),
        ];
    }

    /**
     * Indicate a successful request.
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => fake()->randomElement([200, 201]),
        ]);
    }

    /**
     * Indicate a failed request.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => fake()->randomElement([400, 401, 403, 404, 500]),
        ]);
    }
}
