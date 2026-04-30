<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'contact', // Phone number column
        'password',
        'company_id',
        'role_id',
        'isActive',
        'profile_pic',
    ];

    /**
     * Get the name of the unique identifier for the user.
     * This tells Laravel to use 'contact' instead of 'email' for authentication
     *
     * @return string
     */
    public function username()
    {
        return 'contact';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
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
            'isActive' => 'boolean',
        ];
    }

    /**
     * Relationships
     * Note: company() and role() are commented out because those tables don't exist yet
     */
    
    // public function company()
    // {
    //     return $this->belongsTo(Company::class);
    // }

    // public function role()
    // {
    // return $this->belongsTo(Role::class);
    // }

    public function siteAssignments()
    {
        return $this->hasMany(SiteAssign::class);
    }

    /**
     * Role checking methods
     */
    public function isSuperAdmin()
    {
        return $this->role_id === 1;
    }

    public function isSupervisor()
    {
        return $this->role_id === 2;
    }

    public function isGuard()
    {
        return $this->role_id === 3;
    }

    public function isAdmin()
    {
        return $this->role_id === 7;
    }

    /**
     * Get guards under this supervisor
     */
    public function guards()
    {
        if (!$this->isSupervisor()) {
            return collect([]);
        }

        return $this->hasMany(SiteAssign::class, 'supervisor_id');
    }

    /**
     * Get supervisor for this guard
     */
    public function supervisor()
    {
        if (!$this->isGuard()) {
            return null;
        }

        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
