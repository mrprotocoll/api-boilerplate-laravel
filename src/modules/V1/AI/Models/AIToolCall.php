<?php

declare(strict_types=1);

namespace Modules\V1\AI\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\V1\User\Models\User;
use Shared\Models\BaseModel;

final class AIToolCall extends BaseModel
{
    use SoftDeletes;

    protected $table = 'ai_tool_calls';

    /** @var list<string> */
    protected $fillable = [
        'session_id',
        'message_id',
        'user_id',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
