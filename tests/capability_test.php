<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace report_forumtriage;

use advanced_testcase;
use context_course;

/**
 * Tests report access capability.
 *
 * @coversNothing
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class capability_test extends advanced_testcase {
    /**
     * Student archetype has no report access by default while teachers do.
     *
     * @return void
     */
    public function test_report_is_teacher_only_by_default(): void {
        global $CFG;
        require_once($CFG->dirroot . '/report/forumtriage/lib.php');

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $context = context_course::instance($course->id);

        $this->setUser($student);
        $this->assertFalse(report_forumtriage_can_access($context));

        $this->setUser($teacher);
        $this->assertTrue(report_forumtriage_can_access($context));
    }
}
