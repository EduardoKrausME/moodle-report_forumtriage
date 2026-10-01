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

use local_ai_bridge\api;
use Throwable;

/**
 * AI Bridge adapter for forum triage.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ai_analyser {
    /** AI Bridge purpose. */
    private const PURPOSE = 'forumtriage-analysis';

    /**
     * Analyse candidate threads without persisting prompt or raw response.
     *
     * @param array $threads Semantic candidates.
     * @param array $criteria Objective criteria.
     * @return array
     */
    public function analyse(array $threads, array $criteria): array {
        if (!$threads) {
            return [
                'available' => true,
                'ok' => true,
                'error' => null,
                'threads' => [],
                'clusters' => [],
                'discardedids' => 0,
            ];
        }

        if (!class_exists('\\local_ai_bridge\\api')) {
            return $this->failure('bridgeunavailable');
        }

        $prioritybyid = [];
        foreach ($threads as $thread) {
            $prioritybyid[(int)$thread['discussionid']] = (string)$thread['priority'];
        }

        try {
            $prompt = prompt_builder::build($threads, $criteria);
            $response = api::generate(
                self::PURPOSE,
                [['role' => 'user', 'content' => $prompt]]
            );
            $parsed = analysis_parser::parse((string)$response->text, $prioritybyid);
            return ['available' => true] + $parsed;
        } catch (Throwable $e) {
            return $this->failure('bridgeerror');
        }
    }

    /**
     * Return a generic failure without leaking provider error details.
     *
     * @param string $error Error key.
     * @return array
     */
    private function failure(string $error): array {
        return [
            'available' => false,
            'ok' => false,
            'error' => $error,
            'threads' => [],
            'clusters' => [],
            'discardedids' => 0,
        ];
    }
}
