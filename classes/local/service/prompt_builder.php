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

namespace report_forumtriage\local\service;

/**
 * Build a privacy-minimized structured prompt.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class prompt_builder {
    /**
     * Build the prompt sent to local_ai_bridge.
     *
     * @param array $threads Semantic candidates.
     * @param array $criteria Objective criteria.
     * @return string
     */
    public static function build(array $threads, array $criteria): string {
        $pseudonyms = [];
        $nextstaff = 1;
        $nextparticipant = 1;
        $payloadthreads = [];

        foreach ($threads as $thread) {
            $selectedposts = self::select_posts($thread['posts']);
            $posts = [];
            foreach ($selectedposts as $post) {
                $key = ($post['isstaff'] ? 'staff:' : 'participant:') . $post['userid'];
                if (!isset($pseudonyms[$key])) {
                    if ($post['isstaff']) {
                        $pseudonyms[$key] = 'T' . $nextstaff++;
                    } else {
                        $pseudonyms[$key] = 'U' . $nextparticipant++;
                    }
                }
                $posts[] = [
                    'postid' => $post['postid'],
                    'author' => $pseudonyms[$key],
                    'authorrole' => $post['isstaff'] ? 'staff' : 'participant',
                    'created' => $post['created'],
                    'text' => $post['text'],
                ];
            }

            $payloadthreads[] = [
                'discussionid' => $thread['discussionid'],
                'forumid' => $thread['forumid'],
                'groupid' => $thread['groupid'],
                'subject' => $thread['subject'],
                'created' => $thread['created'],
                'lastactivity' => $thread['lastactivity'],
                'replycount' => $thread['replycount'],
                'teacherresponded' => $thread['teacherresponded'],
                'noresponse' => $thread['noresponse'],
                'longthread' => $thread['islong'],
                'objectivepriority' => $thread['priority'],
                'posts' => $posts,
            ];
        }

        $payload = [
            'task' => 'Analyse only the supplied Moodle forum discussions for teacher triage.',
            'rules' => [
                'Return JSON only, with no Markdown fences.',
                'Never rank, score, label, or evaluate students.',
                'Do not infer personality, ability, motivation, emotion, health, or intent.',
                'Use only evidence contained in the supplied posts.',
                'Do not invent discussion IDs or post IDs.',
                'A thread may be marked possibly_unresolved only when the visible exchange leaves a substantive ' .
                'question apparently open.',
                'Cluster only genuinely similar questions or difficulties, and include at least two discussion IDs per cluster.',
                'priority must exactly match objectivepriority; priority is calculated by PHP and is not a model judgement.',
                'Names are intentionally pseudonymized. Do not attempt to identify authors.',
            ],
            'objectivecriteria' => [
                'unansweredhours' => $criteria['unansweredhours'],
                'noteacherhours' => $criteria['noteacherhours'],
                'longthreadreplies' => $criteria['longthreadreplies'],
            ],
            'response_schema' => [
                'threads' => [[
                    'discussionid' => 123,
                    'status' => 'possibly_unresolved|likely_resolved|needs_attention|unclear|informational',
                    'topic' => 'short topic',
                    'confidence' => 'high|medium|low',
                    'priority' => 'high|medium|normal',
                    'reason' => 'short evidence-based reason',
                    'summary' => 'short neutral summary',
                ]],
                'clusters' => [[
                    'topic' => 'short recurring topic',
                    'summary' => 'what is recurring',
                    'discussionids' => [123, 456],
                ]],
            ],
            'threads' => $payloadthreads,
        ];

        return json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    /**
     * Keep the first post and at most the seven most recent replies.
     *
     * @param array $posts Visible posts.
     * @return array
     */
    private static function select_posts(array $posts): array {
        if (count($posts) <= 8) {
            return $posts;
        }
        $first = reset($posts);
        $tail = array_slice($posts, -7);
        return array_merge([$first], $tail);
    }
}
