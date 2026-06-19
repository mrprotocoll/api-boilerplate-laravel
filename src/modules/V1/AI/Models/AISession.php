<?php

declare(strict_types=1);

namespace Modules\V1\AI\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\V1\User\Models\User;
use Shared\Models\BaseModel;

final class AISession extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ai_sessions';

    /** @var list<string> */
    protected $fillable = [
        'session_token',
        'user_id',
        'status',
        'source_page',
        'last_activity_at',
        'metadata',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'last_activity_at' => 'integer',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AIMessage::class, 'session_id');
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(AIToolCall::class, 'session_id');
    }
}
