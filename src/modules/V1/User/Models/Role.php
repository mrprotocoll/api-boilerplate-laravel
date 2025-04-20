<?php

declare(strict_types=1);

namespace Modules\V1\User\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Role extends Model
{
    use HasFactory;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        'name',
        'description',
    ];

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    /**
     * Create relationships with users
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


}
