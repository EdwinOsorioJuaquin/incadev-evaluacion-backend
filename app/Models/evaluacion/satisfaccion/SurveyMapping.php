<?php
namespace App\Models\Evaluacion\Satisfaccion;

use Illuminate\Database\Eloquent\Model;

class SurveyMapping extends Model
{
    protected $fillable = ['event','survey_id','description'];

    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }
}
