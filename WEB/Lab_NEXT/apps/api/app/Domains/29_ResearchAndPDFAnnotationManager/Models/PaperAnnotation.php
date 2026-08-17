<?php

namespace App\Domains\ResearchAndPDFAnnotationManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperAnnotation extends Model
{
    protected $table = 'paper_annotations';

    protected $fillable = [
        'paper_id',
        'page',
        'text',
        'note',
        'color',
    ];

    protected $casts = [
        'page' => 'integer',
    ];

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class, 'paper_id');
    }
}