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
use report_forumtriage\service\priority_calculator;

/**
 * Tests deterministic priority rules.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_forumtriage\service\priority_calculator
 */
final class priority_calculator_test extends advanced_testcase {
    /**
     * Priority is based on configured objective facts, not semantic output.
     *
     * @return void
     */
    public function test_priority_is_deterministic(): void {
        $now = 2_000_000;
        $criteria = [
            'unansweredhours' => 24,
            'noteacherhours' => 48,
            'longthreadreplies' => 10,
        ];
        $threads = [[
            'created' => $now - (30 * HOURSECS),
            'replycount' => 0,
            'noresponse' => true,
            'teacherresponded' => false,
            'starterisstaff' => false,
        ]];

        $decorated = priority_calculator::decorate($threads, $criteria, $now);
        $this->assertSame('high', $decorated[0]['priority']);
        $this->assertFalse($decorated[0]['islong']);
    }

    /**
     * A teacher-created announcement is not high priority merely because nobody replied.
     *
     * @return void
     */
    public function test_teacher_started_no_reply_is_not_treated_as_unanswered_question(): void {
        $now = 2_000_000;
        $criteria = [
            'unansweredhours' => 24,
            'noteacherhours' => 48,
            'longthreadreplies' => 10,
        ];
        $threads = [[
            'created' => $now - (100 * HOURSECS),
            'replycount' => 0,
            'noresponse' => true,
            'teacherresponded' => false,
            'starterisstaff' => true,
        ]];

        $decorated = priority_calculator::decorate($threads, $criteria, $now);
        $this->assertSame('normal', $decorated[0]['priority']);
    }
}
