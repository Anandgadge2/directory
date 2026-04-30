<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'logo',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function clients()
    {
        return $this->hasMany(ClientDetail::class);
    }

    public function sites()
    {
        return $this->hasMany(SiteDetail::class);
    }
}
