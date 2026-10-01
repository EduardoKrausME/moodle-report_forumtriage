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
 * Deterministic priority calculation.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class priority_calculator {
    /**
     * Return normalized objective criteria.
     *
     * @return array
     */
    public static function get_criteria(): array {
        return [
            'unansweredhours' => max(1, (int)(get_config('report_forumtriage', 'unansweredhours') ?: 24)),
            'noteacherhours' => max(1, (int)(get_config('report_forumtriage', 'noteacherhours') ?: 48)),
            'longthreadreplies' => max(2, (int)(get_config('report_forumtriage', 'longthreadreplies') ?: 10)),
            'recentdays' => max(1, (int)(get_config('report_forumtriage', 'recentdays') ?: 7)),
            'maxaithreads' => min(200, max(1, (int)(get_config('report_forumtriage', 'maxaithreads') ?: 50))),
            'maxpostchars' => min(10000, max(250, (int)(get_config('report_forumtriage', 'maxpostchars') ?: 2000))),
        ];
    }

    /**
     * Decorate threads with deterministic long-thread and priority fields.
     *
     * @param array $threads Thread records.
     * @param array $criteria Objective criteria.
     * @param int|null $now Current timestamp for deterministic tests.
     * @return array
     */
    public static function decorate(array $threads, array $criteria, ?int $now = null): array {
        $now ??= time();
        foreach ($threads as &$thread) {
            $thread['islong'] = $thread['replycount'] >= $criteria['longthreadreplies'];
            $agehours = max(0, ($now - $thread['created']) / HOURSECS);
            $thread['agehours'] = $agehours;

            $participantstarted = !$thread['starterisstaff'];
            if (($participantstarted && $thread['noresponse'] && $agehours >= $criteria['unansweredhours'])
                || ($participantstarted && !$thread['teacherresponded'] && $agehours >= $criteria['noteacherhours'])) {
                $thread['priority'] = 'high';
            } else if (($participantstarted && ($thread['noresponse'] || !$thread['teacherresponded'])) || $thread['islong']) {
                $thread['priority'] = 'medium';
            } else {
                $thread['priority'] = 'normal';
            }
        }
        unset($thread);
        return $threads;
    }

    /**
     * Select threads which can benefit from semantic interpretation.
     *
     * @param array $threads Decorated threads.
     * @param int $limit Maximum number of candidates.
     * @return array
     */
    public static function semantic_candidates(array $threads, int $limit): array {
        $candidates = array_values(array_filter($threads, static function (array $thread): bool {
            if ($thread['islong']) {
                return true;
            }
            if ($thread['starterisstaff']) {
                return false;
            }
            return $thread['noresponse'] || !$thread['teacherresponded'] || $thread['questionlike']
                || (!$thread['lastauthorisstaff'] && $thread['replycount'] > 0);
        }));

        $weights = ['high' => 3, 'medium' => 2, 'normal' => 1];
        usort($candidates, static function (array $a, array $b) use ($weights): int {
            $prioritycmp = ($weights[$b['priority']] ?? 0) <=> ($weights[$a['priority']] ?? 0);
            if ($prioritycmp !== 0) {
                return $prioritycmp;
            }
            return $b['lastactivity'] <=> $a['lastactivity'];
        });

        return array_slice($candidates, 0, max(0, $limit));
    }
}
