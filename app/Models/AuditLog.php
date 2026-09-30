<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'details'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
