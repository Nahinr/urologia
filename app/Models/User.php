<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Testing\Fluent\Concerns\Has;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'last_name',
        'specialty',
        'email',
        'password',
        'phone',
        'status',
        'google_calendar_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_calendar_access_token',
        'google_calendar_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'google_calendar_token_expires_at' => 'datetime',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return trim(($this->name ?? '').' '.($this->last_name ?? '')) ?: ($this->email ?? '—');
    }

    public function hasGoogleCalendarLink(): bool
    {
        return filled($this->google_calendar_refresh_token) && filled($this->google_calendar_email);
    }

    public function googleCalendarCalendarId(): string
    {
        return $this->google_calendar_id ?: 'primary';
    }


}
