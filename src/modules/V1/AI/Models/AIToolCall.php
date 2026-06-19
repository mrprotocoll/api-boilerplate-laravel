<?php

declare(strict_types=1);

namespace Modules\V1\AI\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Shared\Models\BaseModel;

final class AIToolCall extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ai_tool_calls';

    /** @var list<string> */
    protected $fillable = [
        'session_id',
        'message_id',
        'actor_type',
        'actor_id',
        'tool',
        'arguments',
        'status',
        'authorized',
        'duration_ms',
        'error_message',
        'result_meta',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'arguments' => 'array',
        'authorized' => 'boolean',
        'duration_ms' => 'integer',
        'result_meta' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(AISession::class, 'session_id');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(AIMessage::class, 'message_id');
    }

    public function actor(): MorphTo
    {
        return $this->morphTo();
    }
}
