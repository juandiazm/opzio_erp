<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class service extends Model
{
    use HasFactory;
    /*relationships*/
    public function tax()
    {
        return $this->belongsTo(tax::class, 'tax_id');
    }
}
