<?php

declare(strict_types=1);

namespace Modules\V1\AI\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shared\Models\BaseModel;

final class AISession extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ai_sessions';

    /** @var list<string> */
    protected $fillable = [
        'session_token',
        'actor_type',
        'actor_id',
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

    public function actor(): MorphTo
    {
        return $this->morphTo();
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
