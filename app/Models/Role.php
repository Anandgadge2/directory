<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Role constants
     */
    const SUPERADMIN = 1;
    const SUPERVISOR = 2;
    const GUARD = 3;
    const ADMIN = 7;

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
