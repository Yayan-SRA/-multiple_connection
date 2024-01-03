<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Latlong extends Model
{
    use HasFactory;

    protected $guarded = [];
    
    public $timestamps = false;
}
