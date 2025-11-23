<?php
namespace App\Models\Evaluacion\Satisfaccion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResponseDetail extends Model
{
    protected $fillable = ['survey_response_id','survey_question_id','score'];

    public function response(): BelongsTo
    {
        return $this->belongsTo(Response::class, 'survey_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class, 'survey_question_id');
    }
}
