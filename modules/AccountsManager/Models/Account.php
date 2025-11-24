<?php

declare(strict_types=1);

namespace UniGaleModules\AccountsManager\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class Account extends Model implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract, FilamentUser
{
    use Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail;

    protected $table = 'accounts';

    public $timestamps = false;

    protected static $unguarded = true; // <- empty $guarded prevent _{field} assignation

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public static ?string $runtimeConnection = null;

    public function getConnectionName(): ?string
    {
        return self::$runtimeConnection ?? parent::getConnectionName();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
