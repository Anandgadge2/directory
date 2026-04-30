<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeatKmlFeature extends Model
{
    use HasFactory;

    protected $table = 'beat_features';

    public $timestamps = false;

    protected $fillable = [
        'layer_type',
        'name',
        'geometry_type',
        'coordinates',
        'attributes',
        'company_id',
        'site_id',
        'geofence_id',
        'range_id',
        'year'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'attributes' => 'array',
    ];
}
