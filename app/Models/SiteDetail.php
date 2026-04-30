<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'client_name',
        'location',
        'lat',
        'lng',
        'isActive',
    ];

    protected $casts = [
        'isActive' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function client()
    {
        return $this->belongsTo(ClientDetail::class);
    }
}
