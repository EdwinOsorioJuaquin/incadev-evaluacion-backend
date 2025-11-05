<?php

namespace App\Http\Controllers;

// 1. Import models directly from the package
use IncadevUns\CoreDomain\Models\StudentProfile;
use IncadevUns\CoreDomain\Models\Enrollment;
use Illuminate\Http\Request;

class SomeController extends Controller
{
    /**
     * Display the enrollments for a specific student.
     */
    public function showStudentEnrollments($profileId)
    {
        // 2. Use the package model to find the profile
        $student = StudentProfile::findOrFail($profileId);

        // 3. Access relationships defined in the package
        $enrollments = $student->enrollments()->where('status', 'active')->get();

        return view('some.view', compact('student', 'enrollments'));
    }

    /**
     * Create a new enrollment.
     */
    public function storeEnrollment(Request $request)
    {
        // 4. Use the package models to create new records
        $enrollment = Enrollment::create([
            'student_profile_id' => $request->student_id,
            'course_id' => $request->course_id,
            'status' => 'pending',
            // ... other fields
        ]);

        return redirect()->route('home')->with('success', 'Enrollment created.');
    }
}