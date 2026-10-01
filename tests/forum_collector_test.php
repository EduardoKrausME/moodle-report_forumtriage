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
use report_forumtriage\service\forum_collector;
use report_forumtriage\service\priority_calculator;
use stdClass;

/**
 * Integration tests for forum visibility and deterministic facts.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \report_forumtriage\service\forum_collector
 */
final class forum_collector_test extends advanced_testcase {
    /**
     * Separate groups must never expose discussions from another group.
     *
     * @return void
     */
    public function test_separate_groups_do_not_leak(): void {
        [$course, $forum, $studenta, $studentb, $groupa, $groupb] = $this->create_group_fixture(SEPARATEGROUPS);
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $discussiona = $forumgenerator->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $studenta->id,
            'groupid' => $groupa->id,
        ]);
        $discussionb = $forumgenerator->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $studentb->id,
            'groupid' => $groupb->id,
        ]);

        $this->setUser($studenta);
        $threads = $this->collect($course, $studenta->id);
        $ids = array_column($threads, 'discussionid');

        $this->assertContains((int)$discussiona->id, $ids);
        $this->assertNotContains((int)$discussionb->id, $ids);
    }

    /**
     * Visible groups allow the viewer to see discussions in other groups.
     *
     * @return void
     */
    public function test_visible_groups_are_visible(): void {
        [$course, $forum, $studenta, $studentb, $groupa, $groupb] = $this->create_group_fixture(VISIBLEGROUPS);
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $discussiona = $forumgenerator->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $studenta->id,
            'groupid' => $groupa->id,
        ]);
        $discussionb = $forumgenerator->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $studentb->id,
            'groupid' => $groupb->id,
        ]);

        $this->setUser($studenta);
        $threads = $this->collect($course, $studenta->id);
        $ids = array_column($threads, 'discussionid');

        $this->assertContains((int)$discussiona->id, $ids);
        $this->assertContains((int)$discussionb->id, $ids);
    }

    /**
     * Deleted and private replies invisible to the viewer must not count as responses.
     *
     * @return void
     */
    public function test_deleted_and_private_posts_are_ignored(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $studenta = $this->getDataGenerator()->create_user();
        $studentb = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($studenta->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($studentb->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $forumgenerator->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $studenta->id,
            'message' => 'How does this work?',
        ]);

        $deleted = $forumgenerator->create_post([
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $teacher->id,
            'message' => 'Deleted teacher reply',
        ]);
        $DB->set_field('forum_posts', 'deleted', 1, ['id' => $deleted->id]);

        $forumgenerator->create_post([
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $teacher->id,
            'message' => 'Private reply for another student',
            'privatereplyto' => $studentb->id,
        ]);

        $firstpost = $DB->get_record('forum_posts', ['id' => $discussion->firstpost], '*', MUST_EXIST);

        $this->setUser($studenta);
        $threads = $this->collect($course, $studenta->id);
        $thread = $this->find_thread($threads, (int)$discussion->id);

        $this->assertSame((int)$firstpost->modified, $thread['lastactivity']);
        $this->assertSame(0, $thread['replycount']);
        $this->assertTrue($thread['noresponse']);
        $this->assertFalse($thread['teacherresponded']);
    }

    /**
     * A visible teacher reply must be detected through module-context capability.
     *
     * @return void
     */
    public function test_teacher_reply_is_detected(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $forumgenerator->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $student->id,
            'message' => 'Question?',
        ]);
        $forumgenerator->create_post([
            'discussion' => $discussion->id,
            'parent' => $discussion->firstpost,
            'userid' => $teacher->id,
            'message' => 'Teacher answer.',
        ]);

        $this->setUser($student);
        $threads = $this->collect($course, $student->id);
        $thread = $this->find_thread($threads, (int)$discussion->id);

        $this->assertSame(1, $thread['replycount']);
        $this->assertTrue($thread['teacherresponded']);
        $this->assertFalse($thread['noresponse']);
    }

    /**
     * A discussion with no visible reply must be reported deterministically.
     *
     * @return void
     */
    public function test_discussion_without_reply_is_detected(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);
        $forumgenerator = $this->getDataGenerator()->get_plugin_generator('mod_forum');
        $discussion = $forumgenerator->create_discussion([
            'course' => $course->id,
            'forum' => $forum->id,
            'userid' => $student->id,
            'message' => 'No answer yet?',
        ]);

        $this->setUser($student);
        $threads = $this->collect($course, $student->id);
        $thread = $this->find_thread($threads, (int)$discussion->id);

        $this->assertSame(0, $thread['replycount']);
        $this->assertTrue($thread['noresponse']);
    }

    /**
     * Create a course, two students, two groups and a grouped forum.
     *
     * @param int $groupmode SEPARATEGROUPS or VISIBLEGROUPS.
     * @return array
     */
    private function create_group_fixture(int $groupmode): array {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course([
            'groupmode' => $groupmode,
            'groupmodeforce' => 1,
        ]);
        $studenta = $this->getDataGenerator()->create_user();
        $studentb = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($studenta->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($studentb->id, $course->id, 'student');
        $groupa = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Group A']);
        $groupb = $this->getDataGenerator()->create_group(['courseid' => $course->id, 'name' => 'Group B']);
        $this->getDataGenerator()->create_group_member(['groupid' => $groupa->id, 'userid' => $studenta->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $groupb->id, 'userid' => $studentb->id]);
        $forum = $this->getDataGenerator()->create_module('forum', ['course' => $course->id]);

        return [$course, $forum, $studenta, $studentb, $groupa, $groupb];
    }

    /**
     * Collect using neutral filters.
     *
     * @param stdClass $course Course.
     * @param int $userid Viewer id.
     * @return array
     */
    private function collect(stdClass $course, int $userid): array {
        $collector = new forum_collector($course, $userid);
        return $collector->collect([
            'forumid' => 0,
            'groupid' => 0,
            'perioddays' => 0,
            'onlynoteacher' => 0,
            'recentonly' => 0,
        ], priority_calculator::get_criteria());
    }

    /**
     * Find a discussion in collected results.
     *
     * @param array $threads Threads.
     * @param int $discussionid Discussion id.
     * @return array
     */
    private function find_thread(array $threads, int $discussionid): array {
        foreach ($threads as $thread) {
            if ($thread['discussionid'] === $discussionid) {
                return $thread;
            }
        }
        $this->fail('Expected discussion was not collected.');
    }
}
