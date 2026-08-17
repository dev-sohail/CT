<?php

namespace App\Domains\MedicationAndPrescriptionTracker\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicationLog extends Model
{
    protected $fillable = ['medication_id', 'user_id', 'taken_at', 'status', 'note'];
    protected $casts = ['taken_at' => 'datetime'];
    public function medication(): BelongsTo { return $this->belongsTo(Medication::class); }
}
