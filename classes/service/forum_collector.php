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

namespace report_forumtriage\service;

use context_module;
use core_text;
use course_modinfo;
use moodle_url;
use stdClass;

/**
 * Collect visible forum discussions using Moodle forum visibility APIs.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class forum_collector {
    /** @var stdClass */
    private stdClass $course;

    /** @var int */
    private int $userid;

    /** @var stdClass */
    private stdClass $user;

    /** @var course_modinfo */
    private course_modinfo $modinfo;

    /** @var array */
    private array $staffcache = [];

    /**
     * Constructor.
     *
     * @param stdClass $course Course record.
     * @param int $userid Current viewer id.
     */
    public function __construct(stdClass $course, int $userid) {
        global $DB;

        $this->course = $course;
        $this->userid = $userid;
        $this->user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
        $this->modinfo = get_fast_modinfo($course, $userid);
    }

    /**
     * Return forum filter options the current viewer can actually open.
     *
     * @return array Forum id => name.
     */
    public function get_forum_options(): array {
        $options = [];
        foreach ($this->get_visible_forum_cms() as $forumid => $cm) {
            $options[$forumid] = strip_tags(format_string($cm->name, true, ['context' => context_module::instance($cm->id)]));
        }
        natcasesort($options);
        return $options;
    }

    /**
     * Return only groups which are visible in at least one accessible forum.
     *
     * @return array Group id => name.
     */
    public function get_group_options(): array {
        global $CFG;
        require_once($CFG->dirroot . '/group/lib.php');

        $groups = [];
        foreach ($this->get_visible_forum_cms() as $cm) {
            $context = context_module::instance($cm->id);
            $groupmode = groups_get_activity_groupmode($cm, $this->course);
            if ($groupmode == NOGROUPS) {
                continue;
            }

            if ($groupmode == VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $context, $this->userid)) {
                $visible = groups_get_all_groups($this->course->id, 0, $cm->groupingid, 'g.id,g.name');
            } else {
                $visible = groups_get_all_groups($this->course->id, $this->userid, $cm->groupingid, 'g.id,g.name');
            }

            foreach ($visible as $group) {
                $groups[(int)$group->id] = format_string($group->name, true, ['context' => $context]);
            }
        }
        natcasesort($groups);
        return $groups;
    }

    /**
     * Collect visible discussions.
     *
     * @param array $filters forumid, groupid, perioddays, onlynoteacher, recentonly.
     * @param array $criteria Normalized report criteria.
     * @return array
     */
    public function collect(array $filters, array $criteria): array {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/forum/lib.php');
        require_once($CFG->dirroot . '/group/lib.php');

        $now = time();
        $perioddays = max(0, (int)($filters['perioddays'] ?? 0));
        $since = $perioddays > 0 ? $now - ($perioddays * DAYSECS) : 0;
        $recentcutoff = $now - ((int)$criteria['recentdays'] * DAYSECS);
        $requestedforumid = max(0, (int)($filters['forumid'] ?? 0));
        $requestedgroupid = max(0, (int)($filters['groupid'] ?? 0));
        $onlynoteacher = !empty($filters['onlynoteacher']);
        $recentonly = !empty($filters['recentonly']);
        $threads = [];

        foreach ($this->get_visible_forum_cms() as $forumid => $cm) {
            if ($requestedforumid && $requestedforumid !== (int)$forumid) {
                continue;
            }

            $context = context_module::instance($cm->id);
            $groupmode = groups_get_activity_groupmode($cm, $this->course);
            if ($requestedgroupid) {
                if ($groupmode == NOGROUPS || !groups_group_visible($requestedgroupid, $this->course, $cm)) {
                    continue;
                }
            }

            $forum = $DB->get_record('forum', ['id' => $forumid], '*', MUST_EXIST);
            $rows = forum_get_discussions(
                $cm,
                '',
                true,
                -1,
                -1,
                false,
                -1,
                0,
                $requestedgroupid ?: 0,
                $since
            );

            foreach ($rows as $row) {
                $discussionid = (int)$row->discussionid;
                $discussion = $DB->get_record('forum_discussions', ['id' => $discussionid], '*', MUST_EXIST);

                if (!forum_user_can_see_discussion($forum, $discussion, $context, $this->user)) {
                    continue;
                }

                $allposts = $DB->get_records('forum_posts', ['discussion' => $discussionid], 'created ASC, id ASC');
                $visibleposts = [];
                foreach ($allposts as $post) {
                    if (!forum_user_can_see_post($forum, $discussion, $post, $this->user, $cm, true)) {
                        continue;
                    }
                    $visibleposts[(int)$post->id] = $post;
                }

                if (!isset($visibleposts[(int)$discussion->firstpost])) {
                    continue;
                }

                $firstpost = $visibleposts[(int)$discussion->firstpost];
                $posts = array_values($visibleposts);
                $lastpost = end($posts);
                // Never let a deleted/private post influence visible activity metadata.
                $lastactivity = (int)$lastpost->modified;
                if ($since && $lastactivity < $since) {
                    continue;
                }

                $teacherresponded = false;
                $authorids = [];
                foreach ($posts as $post) {
                    $authorids[(int)$post->userid] = true;
                    if ((int)$post->id !== (int)$firstpost->id && $this->is_staff_user((int)$post->userid, $context)) {
                        $teacherresponded = true;
                    }
                }

                $firstplain = $this->plain_text((string)$firstpost->message, (int)$criteria['maxpostchars']);
                $subject = trim(strip_tags((string)$firstpost->subject));
                if ($subject === '') {
                    $subject = trim(strip_tags((string)$discussion->name));
                }
                $questionlike = $this->looks_like_question($subject . ' ' . $firstplain);
                if ($recentonly && (!$questionlike || (int)$firstpost->created < $recentcutoff)) {
                    continue;
                }

                $replycount = max(0, count($posts) - 1);
                if ($onlynoteacher && $teacherresponded) {
                    continue;
                }

                $promptposts = [];
                foreach ($posts as $post) {
                    $promptposts[] = [
                        'postid' => (int)$post->id,
                        'userid' => (int)$post->userid,
                        'isstaff' => $this->is_staff_user((int)$post->userid, $context),
                        'created' => (int)$post->created,
                        'text' => $this->plain_text((string)$post->message, (int)$criteria['maxpostchars']),
                    ];
                }

                $groupname = '';
                if ((int)$discussion->groupid > 0) {
                    $groupname = (string)groups_get_group_name((int)$discussion->groupid);
                }

                $threads[] = [
                    'discussionid' => $discussionid,
                    'forumid' => (int)$forum->id,
                    'cmid' => (int)$cm->id,
                    'forumname' => strip_tags(format_string($forum->name, true, ['context' => $context])),
                    'groupid' => (int)$discussion->groupid,
                    'groupname' => $groupname,
                    'subject' => strip_tags(format_string($discussion->name, true, ['context' => $context])),
                    'created' => (int)$firstpost->created,
                    'lastactivity' => $lastactivity,
                    'replycount' => $replycount,
                    'authorids' => array_map('intval', array_keys($authorids)),
                    'authorcount' => count($authorids),
                    'starteruserid' => (int)$firstpost->userid,
                    'starterisstaff' => $this->is_staff_user((int)$firstpost->userid, $context),
                    'lastauthorisstaff' => $this->is_staff_user((int)$lastpost->userid, $context),
                    'teacherresponded' => $teacherresponded,
                    'noresponse' => $replycount === 0,
                    'questionlike' => $questionlike,
                    'posts' => $promptposts,
                    'url' => (new moodle_url('/mod/forum/discuss.php', ['d' => $discussionid]))->out(false),
                ];
            }
        }

        usort($threads, static fn(array $a, array $b): int => $b['lastactivity'] <=> $a['lastactivity']);
        return $threads;
    }

    /**
     * Return accessible forum course modules.
     *
     * @return array Forum instance id => cm_info.
     */
    private function get_visible_forum_cms(): array {
        $result = [];
        $instances = $this->modinfo->get_instances_of('forum');
        foreach ($instances as $cm) {
            if (!$cm->uservisible) {
                continue;
            }
            $context = context_module::instance($cm->id);
            if (!has_capability('mod/forum:viewdiscussion', $context, $this->userid)) {
                continue;
            }
            $result[(int)$cm->instance] = $cm;
        }
        return $result;
    }

    /**
     * Determine whether a post author has a teacher-like forum capability.
     *
     * The capability is intentionally checked in the module context so custom
     * teacher roles and overrides are respected without relying on role names.
     *
     * @param int $userid User id.
     * @param context_module $context Forum context.
     * @return bool
     */
    private function is_staff_user(int $userid, context_module $context): bool {
        $key = $context->id . ':' . $userid;
        if (!array_key_exists($key, $this->staffcache)) {
            $this->staffcache[$key] = has_capability('mod/forum:editanypost', $context, $userid);
        }
        return $this->staffcache[$key];
    }

    /**
     * Convert HTML to compact plain text for AI use.
     *
     * @param string $html Source text.
     * @param int $maxlength Maximum characters.
     * @return string
     */
    private function plain_text(string $html, int $maxlength): string {
        $text = html_to_text($html, 0, false);
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);
        if (core_text::strlen($text) > $maxlength) {
            $text = core_text::substr($text, 0, $maxlength) . '…';
        }
        return $text;
    }

    /**
     * Cheap local heuristic used only for candidate selection and the recent-question filter.
     *
     * @param string $text Plain text.
     * @return bool
     */
    private function looks_like_question(string $text): bool {
        if (str_contains($text, '?')) {
            return true;
        }
        return (bool)preg_match(
            '/\b(como|por que|porque|qual|quais|quando|onde|d[uú]vida|algu[eé]m sabe|how|why|what|when|where)\b/ui',
            core_text::strtolower($text)
        );
    }
}
