<?php

namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StudentProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'interests',
        'learning_goal',
    ];

    protected $casts = [
        'interests' => 'array', // convierte JSON a array automáticamente
    ];

    // Relación inversa con usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación con respuestas
    public function responses()
    {
        return $this->hasMany(Response::class, 'student_profile_id');
    }
}
