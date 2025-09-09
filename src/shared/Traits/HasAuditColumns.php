<?php

namespace Shared\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\V1\Admin\Models\Admin;

trait HasAuditColumns
{
    /**
     * Boot the audit columns trait.
     */
    protected static function bootHasAuditColumns(): void
    {
        // Set created_by on creating
        static::creating(function (Model $model) {
            if (auth()->check() && !$model->created_by) {
                $model->created_by = auth()->id();
            }
        });

        // Set updated_by on updating
        static::updating(function (Model $model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });

        // Set deleted_by on deleting (if soft deletes is used)
        if (method_exists(static::class, 'bootSoftDeletes')) {
            static::deleting(function (Model $model) {
                if (auth()->check() && $model->isForceDeleting() === false) {
                    $model->deleted_by = auth()->id();
                    $model->saveQuietly(); // Save without triggering events
                }
            });
        }
    }

    protected static function actionUserModel(): string
    {
        // default to User if not overridden
        return Admin::class;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(static::actionUserModel(), 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(static::actionUserModel(), 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(static::actionUserModel(), 'deleted_by');
    }

    /**
     * Get audit trail information.
     */
    public function getAuditTrail(): array
    {
        return [
            'created' => [
                'at' => $this->created_at,
                'by' => $this->creator,
            ],
            'updated' => [
                'at' => $this->updated_at,
                'by' => $this->updater,
            ],
            'deleted' => [
                'at' => $this->deleted_at ?? null,
                'by' => $this->deleter,
            ],
        ];
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function auditCasts(): array
    {
        return [
            'created_by' => 'string',
            'updated_by' => 'string',
            'deleted_by' => 'string',
        ];
    }
}
