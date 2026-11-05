<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueId;
use App\Models\Concerns\HasModelCache;
use App\Models\Concerns\PostedDocument;
use Illuminate\Database\Eloquent\Model;

class Damage extends Model
{
    use HasModelCache, PostedDocument, HasUniqueId;

    public const REF_PREFIX = 'DMG';

    protected $fillable = ['damage_date', 'total_quantity', 'notes', 'status', 'cancel_reason', 'cancelled_by', 'cancelled_at'];

    protected $casts = ['damage_date' => 'date', 'cancelled_at' => 'datetime'];

    public function details()
    {
        return $this->hasMany(DamageDetail::class);
    }
}
