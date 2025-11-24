<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Response extends Model
{
    use HasFactory;

    protected $table = 'survey_responses';

    protected $fillable = [
        'survey_id',
        'user_id',
        'rateable_id',
        'rateable_type',
        'date',
    ];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(ResponseDetail::class, 'survey_response_id');
    }
}
