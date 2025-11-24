<?php
namespace App\Models\Evaluation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $table = 'students_profiles';

    protected $fillable = [
        'student_id',
        'user_id',
        'document_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'status',
    ];

    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
