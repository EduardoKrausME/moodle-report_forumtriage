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

/**
 * Build Mustache-ready report data from deterministic facts and validated AI annotations.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_builder {
    /**
     * Build report context.
     *
     * @param array $threads Decorated deterministic threads.
     * @param array $analysis Validated AI result.
     * @param bool $ranai Whether semantic analysis was requested.
     * @return array
     */
    public static function build(array $threads, array $analysis, bool $ranai): array {
        $annotations = $analysis['threads'] ?? [];
        $viewsbyid = [];
        $attention = [];
        $noresponse = [];
        $possiblyunresolved = [];

        foreach ($threads as $thread) {
            $annotation = $annotations[$thread['discussionid']] ?? null;
            $view = self::thread_view($thread, $annotation);
            $viewsbyid[$thread['discussionid']] = $view;

            if (!$thread['starterisstaff'] && $thread['noresponse']) {
                $noresponse[] = $view;
            }
            if ($annotation && $annotation['status'] === 'possibly_unresolved') {
                $possiblyunresolved[] = $view;
            }
            if ($thread['priority'] !== 'normal'
                || ($annotation && in_array($annotation['status'], ['possibly_unresolved', 'needs_attention'], true))) {
                $attention[] = $view;
            }
        }

        usort($attention, [self::class, 'compare_priority']);
        usort($noresponse, [self::class, 'compare_priority']);
        usort($possiblyunresolved, [self::class, 'compare_priority']);

        $discussedthreads = $threads;
        usort($discussedthreads, static function (array $a, array $b): int {
            $replycmp = $b['replycount'] <=> $a['replycount'];
            return $replycmp ?: ($b['lastactivity'] <=> $a['lastactivity']);
        });
        $mostdiscussed = [];
        foreach (array_slice($discussedthreads, 0, 10) as $thread) {
            if ($thread['replycount'] < 1) {
                continue;
            }
            $mostdiscussed[] = $viewsbyid[$thread['discussionid']];
        }

        $clusters = [];
        foreach ($analysis['clusters'] ?? [] as $cluster) {
            $items = [];
            foreach ($cluster['discussionids'] as $discussionid) {
                if (isset($viewsbyid[$discussionid])) {
                    $items[] = $viewsbyid[$discussionid];
                }
            }
            if (count($items) < 2) {
                continue;
            }
            $clusters[] = [
                'topic' => $cluster['topic'],
                'summary' => $cluster['summary'],
                'threads' => $items,
            ];
        }

        $stats = [
            ['label' => get_string('stat:visible', 'report_forumtriage'), 'value' => count($threads)],
            ['label' => get_string('stat:unanswered', 'report_forumtriage'), 'value' => count(array_filter(
                $threads,
                static fn(array $t): bool => !$t['starterisstaff'] && $t['noresponse']
            ))],
            ['label' => get_string('stat:noteacher', 'report_forumtriage'), 'value' => count(array_filter(
                $threads,
                static fn(array $t): bool => !$t['starterisstaff'] && !$t['teacherresponded']
            ))],
            ['label' => get_string('stat:long', 'report_forumtriage'), 'value' => count(array_filter(
                $threads,
                static fn(array $t): bool => $t['islong']
            ))],
        ];

        return [
            'hasthreads' => !empty($threads),
            'stats' => $stats,
            'attention' => $attention,
            'hasattention' => !empty($attention),
            'noresponse' => $noresponse,
            'hasnoresponse' => !empty($noresponse),
            'possiblyunresolved' => $possiblyunresolved,
            'haspossiblyunresolved' => !empty($possiblyunresolved),
            'clusters' => $clusters,
            'hasclusters' => !empty($clusters),
            'mostdiscussed' => $mostdiscussed,
            'hasmostdiscussed' => !empty($mostdiscussed),
            'ranai' => $ranai,
        ];
    }

    /**
     * Prepare one thread for display.
     *
     * @param array $thread Deterministic thread.
     * @param array|null $annotation AI annotation.
     * @return array
     */
    private static function thread_view(array $thread, ?array $annotation): array {
        $confidence = $annotation['confidence'] ?? '';
        $status = $annotation['status'] ?? '';

        return [
            'discussionid' => $thread['discussionid'],
            'subject' => $thread['subject'],
            'forumname' => $thread['forumname'],
            'forumlabel' => get_string('forum', 'report_forumtriage', $thread['forumname']),
            'hasgroup' => $thread['groupid'] > 0 && $thread['groupname'] !== '',
            'groupname' => $thread['groupname'],
            'grouplabel' => $thread['groupname'] !== ''
                ? get_string('group', 'report_forumtriage', $thread['groupname']) : '',
            'url' => $thread['url'],
            'replycount' => $thread['replycount'],
            'replieslabel' => get_string('replies', 'report_forumtriage', $thread['replycount']),
            'authorcount' => $thread['authorcount'],
            'authorslabel' => get_string('authors', 'report_forumtriage', $thread['authorcount']),
            'lastactivity' => $thread['lastactivity'],
            'lastactivitytext' => userdate($thread['lastactivity']),
            'lastactivitylabel' => get_string('lastactivity', 'report_forumtriage', userdate($thread['lastactivity'])),
            'teacherresponded' => $thread['teacherresponded'],
            'noteacherresponded' => !$thread['teacherresponded'] && !$thread['starterisstaff'],
            'islong' => $thread['islong'],
            'priority' => $thread['priority'],
            'prioritylabel' => get_string('priority:' . $thread['priority'], 'report_forumtriage'),
            'prioritytext' => get_string('priority', 'report_forumtriage',
                get_string('priority:' . $thread['priority'], 'report_forumtriage')),
            'priorityclass' => self::priority_class($thread['priority']),
            'hasanalysis' => $annotation !== null,
            'topic' => $annotation['topic'] ?? '',
            'summary' => $annotation['summary'] ?? '',
            'reason' => $annotation['reason'] ?? '',
            'status' => $status,
            'confidence' => $confidence,
            'confidencelabel' => $confidence ? get_string('confidence:' . $confidence, 'report_forumtriage') : '',
        ];
    }

    /**
     * Sort display items by deterministic priority and then newest activity.
     *
     * @param array $a First item.
     * @param array $b Second item.
     * @return int
     */
    private static function compare_priority(array $a, array $b): int {
        $weights = ['high' => 3, 'medium' => 2, 'normal' => 1];
        $cmp = ($weights[$b['priority']] ?? 0) <=> ($weights[$a['priority']] ?? 0);
        return $cmp ?: ($b['lastactivity'] <=> $a['lastactivity']);
    }

    /**
     * Bootstrap badge class for objective priority.
     *
     * @param string $priority Priority.
     * @return string
     */
    private static function priority_class(string $priority): string {
        return match ($priority) {
            'high' => 'badge-danger',
            'medium' => 'badge-warning',
            default => 'badge-secondary',
        };
    }
}
