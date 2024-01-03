<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GridMap extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $primaryKey = 'ID';

    // Custom column names for timestamps
    const CREATED_AT = 'DATE_CREATED';
    const UPDATED_AT = 'DATE_MODIFIED';

    public function district()
    {
        return $this->belongsTo(District::class, 'CPR_MST_DISTRICT_ID'); // Use custom foreign key name
    }
    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class, 'CPR_MST_SUBDISTRICT_ID'); // Use custom foreign key name
    }
}
