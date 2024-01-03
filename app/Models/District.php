<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $primaryKey = 'ID';

    // Custom column names for timestamps
    const CREATED_AT = 'DATE_CREATED';
    const UPDATED_AT = 'DATE_MODIFIED';

    public function city()
    {
        return $this->belongsTo(City::class, 'CPR_MST_CITY_ID'); // Use custom foreign key name
    }
}
