<?php

declare(strict_types=1);

namespace Modules\V1\Admin\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\V1\User\Enums\RoleEnum;

final class AdminRole extends Model
{
    use HasFactory;

    protected $with = ['permissions'];

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }

    // create relationship with users
    public function admins() : BelongsToMany{
        return $this->belongsToMany(Admin::class)->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * get a role
     * @param RoleEnum $role
     * @return AdminRole
     */
    public static function get(RoleEnum $role): AdminRole {
        return self::where('name', $role->name())->first();
    }

}
