<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiteAssign extends Model
{
    use HasFactory;

    protected $table = 'site_assign';

    protected $fillable = [
        'company_id',
        'user_id',
        'supervisor_id',
        'client_id',
        'site_id',
        'client_name',
        'site_name',
        'date_assigned',
    ];

    protected $casts = [
        'date_assigned' => 'date',
    ];

    /**
     * Relationships
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function client()
    {
        return $this->belongsTo(ClientDetail::class);
    }

    public function site()
    {
        return $this->belongsTo(SiteDetail::class);
    }
}
