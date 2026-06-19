<?php

declare(strict_types=1);

namespace Modules\V1\AI\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shared\Models\BaseModel;

final class AIMessage extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ai_messages';

    /** @var list<string> */
    protected $fillable = [
        'session_id',
        'actor_type',
        'actor_id',
        'role',
        'content',
        'attachment',
        'suggestions',
        'provider',
        'model',
        'tokens_prompt',
        'tokens_completion',
        'cost',
        'metadata',
        'is_flagged',
        'flag_reason',
        'flagged_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'attachment' => 'array',
        'suggestions' => 'array',
        'metadata' => 'array',
        'tokens_prompt' => 'integer',
        'tokens_completion' => 'integer',
        'cost' => 'decimal:8',
        'is_flagged' => 'boolean',
        'flagged_at' => 'integer',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AISession::class, 'session_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }

    public function toolCalls(): HasMany
    {
        return $this->hasMany(AIToolCall::class, 'message_id');
    }
}
