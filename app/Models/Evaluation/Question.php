<?php
namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $table = 'survey_questions'; // tabla correcta
    protected $fillable = ['survey_id','question','order'];

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class);
    }
     // 🔹 Relación con las respuestas individuales
    public function details()
    {
        return $this->hasMany(ResponseDetail::class, 'survey_question_id');
    }
}
