<?php

declare(strict_types=1);

namespace Modules\V1\Logging\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\V1\User\Models\Admin;
use Shared\Models\BaseModel;

final class AdminActivityLog extends BaseModel
{
    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        'admin_id',
        'action',
        'model_type',
        'model_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

}
