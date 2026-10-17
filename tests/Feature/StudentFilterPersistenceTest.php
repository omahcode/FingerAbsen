<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentFilterPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private SchoolClass $class;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $major = Major::create(['name' => 'Rekayasa Perangkat Lunak']);
        $this->class = SchoolClass::create([
            'major_id' => $major->id,
            'name' => 'XII RPL 1',
        ]);

        $this->student = Student::create([
            'name' => 'Budi Santoso',
            'school_class_id' => $this->class->id,
            'parent_phone' => '081234567890',
            'device_user_id' => '102',
            'privilege' => 0,
        ]);
    }

    public function test_student_index_preserves_query_params_in_edit_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('students.index', [
            'class_id' => $this->class->id,
            'search' => 'Budi',
            'fingerprint' => 'unregistered',
        ]));

        $response->assertOk();

        $expectedEditUrl = route('students.edit', [
            'student' => $this->student->id,
            'class_id' => $this->class->id,
            'search' => 'Budi',
            'fingerprint' => 'unregistered',
        ]);

        $response->assertSee(e($expectedEditUrl), false);
    }

    public function test_student_update_redirects_with_filter_query_params(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('students.update', [
                'student' => $this->student->id,
                'class_id' => $this->class->id,
                'search' => 'Budi',
                'fingerprint' => 'unregistered',
            ]),
            [
                'name' => 'Budi Santoso Updated',
                'school_class_id' => $this->class->id,
                'parent_phone' => '081234567890',
            ]
        );

        $expectedRedirectUrl = route('students.index', [
            'class_id' => $this->class->id,
            'search' => 'Budi',
            'fingerprint' => 'unregistered',
        ]);

        $response->assertRedirect($expectedRedirectUrl);
        $this->assertDatabaseHas('students', [
            'id' => $this->student->id,
            'name' => 'Budi Santoso Updated',
        ]);
    }

    public function test_student_update_without_filters_redirects_to_plain_students_index(): void
    {
        $response = $this->actingAs($this->admin)->put(
            route('students.update', $this->student->id),
            [
                'name' => 'Budi Santoso Plain',
                'school_class_id' => $this->class->id,
                'parent_phone' => '081234567890',
            ]
        );

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'id' => $this->student->id,
            'name' => 'Budi Santoso Plain',
        ]);
    }
}
