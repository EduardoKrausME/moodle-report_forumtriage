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

/**
 * Library callbacks for the forum triage report.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add the report to course navigation.
 *
 * @param navigation_node $navigation Course navigation node.
 * @param stdClass $course Course record.
 * @param context_course $context Course context.
 * @return void
 */
function report_forumtriage_extend_navigation_course($navigation, $course, $context): void {
    if (!report_forumtriage_can_access($context)) {
        return;
    }

    $url = new moodle_url('/report/forumtriage/index.php', ['course' => $course->id]);
    $navigation->add(
        get_string('pluginname', 'report_forumtriage'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        null,
        new pix_icon('i/report', '')
    );
}

/**
 * Check whether the current user can access the course report.
 *
 * This capability does not replace or bypass mod_forum visibility checks.
 *
 * @param context_course $context Course context.
 * @return bool
 */
function report_forumtriage_can_access(context_course $context): bool {
    return has_capability('report/forumtriage:view', $context);
}
