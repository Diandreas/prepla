<?php

use App\Models\Classroom;
use App\Models\LanguageCenter;
use App\Models\User;

function centerStaffUser(LanguageCenter $center, string $role): User
{
    $user = User::factory()->create();
    $user->centers()->attach($center->id, ['role' => $role, 'joined_at' => now()]);

    return $user;
}

beforeEach(function () {
    $this->center = LanguageCenter::create([
        'name' => 'Centre Test', 'slug' => 'centre-test', 'seats_limit' => 100, 'is_active' => true,
    ]);

    $this->teacherA = centerStaffUser($this->center, 'teacher');
    $this->teacherB = centerStaffUser($this->center, 'teacher');

    $this->classroomA = Classroom::create(['center_id' => $this->center->id, 'name' => 'Classe A', 'invite_code' => 'AAAA1111']);
    $this->classroomA->members()->attach($this->teacherA->id, ['role_in_class' => 'teacher']);

    $this->classroomB = Classroom::create(['center_id' => $this->center->id, 'name' => 'Classe B', 'invite_code' => 'BBBB2222']);
    $this->classroomB->members()->attach($this->teacherB->id, ['role_in_class' => 'teacher']);
});

test('a teacher cannot view a classroom they do not teach', function () {
    $this->actingAs($this->teacherA)
        ->get(route('center.classes.show', $this->classroomB))
        ->assertForbidden();
});

test('a teacher can view their own classroom', function () {
    $this->actingAs($this->teacherA)
        ->get(route('center.classes.show', $this->classroomA))
        ->assertOk();
});

test('a teacher only sees their own classrooms in the classroom list', function () {
    $response = $this->actingAs($this->teacherA)->get(route('center.classes.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('classrooms', 1)
        ->where('classrooms.0.name', 'Classe A')
    );
});

test('a center_admin sees every classroom of the center', function () {
    $admin = centerStaffUser($this->center, 'center_admin');

    $response = $this->actingAs($admin)->get(route('center.classes.index'));

    $response->assertInertia(fn ($page) => $page->has('classrooms', 2));
});

test('a teacher cannot rename a classroom they do not teach', function () {
    $this->actingAs($this->teacherA)
        ->patch(route('center.classes.update', $this->classroomB), ['name' => 'Renommée'])
        ->assertForbidden();
});

test('a teacher cannot remove a student from a classroom they do not teach', function () {
    $student = centerStaffUser($this->center, 'student');
    $this->classroomB->members()->attach($student->id, ['role_in_class' => 'student']);

    $this->actingAs($this->teacherA)
        ->delete(route('center.classes.students.remove', ['classroom' => $this->classroomB, 'user' => $student]))
        ->assertForbidden();
});
