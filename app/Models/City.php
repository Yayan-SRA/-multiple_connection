<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $primaryKey = 'ID';
    
    // Custom column names for timestamps
    const CREATED_AT = 'DATE_CREATED';
    const UPDATED_AT = 'DATE_MODIFIED';

    public function province()
    {
        return $this->belongsTo(Province::class, 'CPR_MST_PROVINCE_ID'); // Use custom foreign key name
    }
}
