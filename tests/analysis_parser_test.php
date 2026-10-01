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
use report_forumtriage\service\analysis_parser;

/**
 * Tests for structured AI response validation.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_forumtriage\service\analysis_parser
 */
final class analysis_parser_test extends advanced_testcase {
    /**
     * Invalid JSON must fail closed and keep empty semantic results.
     *
     * @return void
     */
    public function test_invalid_json_fails_closed(): void {
        $result = analysis_parser::parse('{not-json', [10 => 'high']);

        $this->assertFalse($result['ok']);
        $this->assertSame('invalidjson', $result['error']);
        $this->assertSame([], $result['threads']);
        $this->assertSame([], $result['clusters']);
    }

    /**
     * IDs not sent to the model are discarded from both thread results and clusters.
     *
     * @return void
     */
    public function test_invented_ids_are_discarded(): void {
        $json = json_encode([
            'threads' => [
                [
                    'discussionid' => 10,
                    'status' => 'possibly_unresolved',
                    'topic' => 'Valid',
                    'confidence' => 'medium',
                    'priority' => 'normal',
                    'reason' => 'Still open',
                    'summary' => 'Valid discussion',
                ],
                [
                    'discussionid' => 999,
                    'status' => 'needs_attention',
                    'topic' => 'Invented',
                    'confidence' => 'high',
                    'priority' => 'high',
                    'reason' => 'Invented',
                    'summary' => 'Invented',
                ],
            ],
            'clusters' => [
                [
                    'topic' => 'Mixed cluster',
                    'summary' => 'Contains one invented id.',
                    'discussionids' => [10, 20, 999],
                ],
                [
                    'topic' => 'Invalid cluster',
                    'summary' => 'Only invented ids.',
                    'discussionids' => [998, 999],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $result = analysis_parser::parse($json, [10 => 'high', 20 => 'medium']);

        $this->assertTrue($result['ok']);
        $this->assertArrayHasKey(10, $result['threads']);
        $this->assertArrayNotHasKey(999, $result['threads']);
        $this->assertSame('high', $result['threads'][10]['priority']);
        $this->assertCount(1, $result['clusters']);
        $this->assertSame([10, 20], $result['clusters'][0]['discussionids']);
        $this->assertGreaterThanOrEqual(3, $result['discardedids']);
    }
}
