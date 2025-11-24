<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Model;

class Survey extends Model
{
    protected $fillable = ['title', 'description'];

    public function questions()
    {
        return $this->hasMany(Question::class, 'survey_id');
    }

    public function responses()
    {
        return $this->hasMany(Response::class, 'survey_id');
    }

    public function mapping()
    {
        return $this->hasOne(SurveyMapping::class);
    }
}
