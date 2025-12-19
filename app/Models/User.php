<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ForgotPasswordNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $table = "users";

    protected $primaryKey = "User_ID";

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'User_Email',
        'User_Password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'User_Password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verification_token' => 'string',
            'email_verified_at' => 'datetime',
            'User_Password' => 'hashed',
        ];
    }

    protected $dates = ['User_CreatedAt', 'User_UpdatedAt', 'User_DeletedAt',];
    const CREATED_AT = 'User_CreatedAt';
    const UPDATED_AT = 'User_UpdatedAt';
    const DELETED_AT = 'User_DeletedAt';

    public function isAdmin(): bool
    {
        return $this->role === 'ROLE_ADMIN';
    }

    public function getEmailForPasswordReset()
    {
        return $this->User_Email;
    }

    public function getEmailAttribute()
    {
        return $this->User_Email;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ForgotPasswordNotification($token));
    }

    public function routeNotificationForMail($notification = null)
    {
        return $this->User_Email;
    }

    public function  getDeletedAtColumn()
    {
        return 'User_DeletedAt';
    }

    // The database field that should be returned on Eloquent's request
    public function getAuthPassword()
    {
        return $this->User_Password;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
