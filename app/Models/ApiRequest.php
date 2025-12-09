<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRequest extends Model
{
    /** @use HasFactory<\Database\Factories\ApiRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'method',
        'path',
        'full_url',
        'route_name',
        'ip_address',
        'user_agent',
        'headers',
        'query_params',
        'request_body',
        'status_code',
        'response_body',
        'response_time_ms',
        'device_name',
        'token_id',
    ];

    protected function casts(): array
    {
        return [
            'headers' => 'array',
            'query_params' => 'array',
            'request_body' => 'array',
            'response_body' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
