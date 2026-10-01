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

use core_text;
use JsonException;

/**
 * Validate and sanitize structured AI output.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class analysis_parser {
    /**
     * Parse JSON and discard any IDs not present in the request.
     *
     * @param string $raw Raw model text.
     * @param array $prioritybyid discussion id => deterministic priority.
     * @return array
     */
    public static function parse(string $raw, array $prioritybyid): array {
        $raw = trim($raw);
        if (str_starts_with($raw, str_repeat(chr(96), 3))) {
            $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw) ?? $raw;
            $raw = preg_replace('/\s*```$/', '', $raw) ?? $raw;
        }

        try {
            $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return self::invalid('invalidjson');
        }

        if (!is_array($decoded) || !isset($decoded['threads']) || !is_array($decoded['threads'])
            || !isset($decoded['clusters']) || !is_array($decoded['clusters'])) {
            return self::invalid('invalidschema');
        }

        $allowedids = array_map('intval', array_keys($prioritybyid));
        $allowedlookup = array_fill_keys($allowedids, true);
        $statuses = ['possibly_unresolved', 'likely_resolved', 'needs_attention', 'unclear', 'informational'];
        $confidences = ['high', 'medium', 'low'];
        $threads = [];
        $discardedids = 0;

        foreach ($decoded['threads'] as $item) {
            if (!is_array($item) || !isset($item['discussionid'])) {
                continue;
            }
            $discussionid = (int)$item['discussionid'];
            if (!isset($allowedlookup[$discussionid])) {
                $discardedids++;
                continue;
            }

            $status = (string)($item['status'] ?? 'unclear');
            if (!in_array($status, $statuses, true)) {
                $status = 'unclear';
            }
            $confidence = (string)($item['confidence'] ?? 'low');
            if (!in_array($confidence, $confidences, true)) {
                $confidence = 'low';
            }

            $threads[$discussionid] = [
                'discussionid' => $discussionid,
                'status' => $status,
                'topic' => self::text($item['topic'] ?? ''),
                'confidence' => $confidence,
                'priority' => $prioritybyid[$discussionid],
                'reason' => self::text($item['reason'] ?? ''),
                'summary' => self::text($item['summary'] ?? ''),
            ];
        }

        $clusters = [];
        foreach ($decoded['clusters'] as $cluster) {
            if (!is_array($cluster) || !isset($cluster['discussionids']) || !is_array($cluster['discussionids'])) {
                continue;
            }
            $ids = [];
            foreach ($cluster['discussionids'] as $id) {
                $id = (int)$id;
                if (isset($allowedlookup[$id])) {
                    $ids[$id] = $id;
                } else {
                    $discardedids++;
                }
            }
            $ids = array_values($ids);
            if (count($ids) < 2) {
                continue;
            }
            $clusters[] = [
                'topic' => self::text($cluster['topic'] ?? ''),
                'summary' => self::text($cluster['summary'] ?? ''),
                'discussionids' => $ids,
            ];
        }

        return [
            'ok' => true,
            'error' => null,
            'threads' => $threads,
            'clusters' => $clusters,
            'discardedids' => $discardedids,
        ];
    }

    /**
     * Compact model text to safe plain text.
     *
     * @param mixed $value Value.
     * @return string
     */
    private static function text(mixed $value): string {
        if (!is_scalar($value)) {
            return '';
        }
        $text = trim(strip_tags((string)$value));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        return core_text::substr($text, 0, 2000);
    }

    /**
     * Build an invalid result.
     *
     * @param string $error Error key.
     * @return array
     */
    private static function invalid(string $error): array {
        return [
            'ok' => false,
            'error' => $error,
            'threads' => [],
            'clusters' => [],
            'discardedids' => 0,
        ];
    }
}
