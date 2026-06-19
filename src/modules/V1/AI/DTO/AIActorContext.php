<?php

declare(strict_types=1);

namespace Modules\V1\AI\DTO;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\User\Models\User;

final readonly class AIActorContext
{
    public const SCOPE_USER = 'user';
    public const SCOPE_ADMIN = 'admin';

    public function __construct(
        public Model $model,
        public string $scope,
    ) {
        if (null === $model->getKey()) {
            throw new InvalidArgumentException('AI actor model must be persisted.');
        }

        if (self::SCOPE_USER === $scope && ! $model instanceof User) {
            throw new InvalidArgumentException('User AI actor scope requires a User model.');
        }

        if (self::SCOPE_ADMIN === $scope && ! $model instanceof Admin) {
            throw new InvalidArgumentException('Admin AI actor scope requires an Admin model.');
        }

        if ( ! in_array($scope, [self::SCOPE_USER, self::SCOPE_ADMIN], true)) {
            throw new InvalidArgumentException('Unsupported AI actor scope.');
        }
    }

    public static function forUser(User $user): self
    {
        return new self($user, self::SCOPE_USER);
    }

    public static function forAdmin(Admin $admin): self
    {
        return new self($admin, self::SCOPE_ADMIN);
    }

    public function id(): string
    {
        return (string) $this->model->getKey();
    }

    public function morphClass(): string
    {
        return $this->model->getMorphClass();
    }

}
