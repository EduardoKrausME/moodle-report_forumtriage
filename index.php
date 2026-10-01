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
 * Course forum triage report.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

use report_forumtriage\form\filter_form;
use report_forumtriage\local\service\ai_analyser;
use report_forumtriage\local\service\forum_collector;
use report_forumtriage\local\service\priority_calculator;
use report_forumtriage\local\service\report_builder;

$courseid = required_param('course', PARAM_INT);
$course = get_course($courseid);
require_login($course);

$context = context_course::instance($course->id);
require_capability('report/forumtriage:view', $context);

$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/report/forumtriage/index.php', ['course' => $course->id]));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pluginname', 'report_forumtriage'));
$PAGE->set_heading($course->fullname);

$criteria = priority_calculator::get_criteria();
$collector = new forum_collector($course, (int)$USER->id);
$form = new filter_form(
    new moodle_url('/report/forumtriage/index.php', ['course' => $course->id]),
    [
        'courseid' => $course->id,
        'forums' => $collector->get_forum_options(),
        'groups' => $collector->get_group_options(),
    ]
);

$filters = [
    'forumid' => 0,
    'groupid' => 0,
    'perioddays' => 14,
    'onlynoteacher' => 0,
    'recentonly' => 0,
];
$ranai = false;
if ($data = $form->get_data()) {
    $filters = [
        'forumid' => (int)$data->forumid,
        'groupid' => (int)$data->groupid,
        'perioddays' => (int)$data->perioddays,
        'onlynoteacher' => !empty($data->onlynoteacher),
        'recentonly' => !empty($data->recentonly),
    ];
    $ranai = true;
} else {
    $form->set_data((object)(['course' => $course->id] + $filters));
}

$threads = $collector->collect($filters, $criteria);
$threads = priority_calculator::decorate($threads, $criteria);
$candidates = priority_calculator::semantic_candidates($threads, $criteria['maxaithreads']);

$analysis = [
    'available' => true,
    'ok' => true,
    'error' => null,
    'threads' => [],
    'clusters' => [],
    'discardedids' => 0,
];
if ($ranai && $candidates) {
    $analysis = (new ai_analyser())->analyse($candidates, $criteria);
}

$view = report_builder::build($threads, $analysis, $ranai);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_forumtriage'));
$form->display();

if (!$ranai) {
    echo $OUTPUT->notification(get_string('deterministicnotice', 'report_forumtriage'), 'info', false);
} else if (!$analysis['available']) {
    echo $OUTPUT->notification(get_string('aiunavailable', 'report_forumtriage'), 'warning', false);
} else if (!$analysis['ok']) {
    echo $OUTPUT->notification(get_string('invalidairesponse', 'report_forumtriage'), 'warning', false);
} else if ($candidates) {
    $message = (object)[
        'analysed' => count($analysis['threads']),
        'candidates' => count($candidates),
    ];
    echo $OUTPUT->notification(get_string('aianalysed', 'report_forumtriage', $message), 'info', false);
}

echo $OUTPUT->render_from_template('report_forumtriage/report', $view);
echo $OUTPUT->footer();
