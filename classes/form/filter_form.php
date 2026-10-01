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

namespace report_forumtriage\form;

use moodleform;

/**
 * Report filter form.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_form extends moodleform {
    /**
     * Define the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $forums = $this->_customdata['forums'] ?? [];
        $groups = $this->_customdata['groups'] ?? [];
        $courseid = (int)($this->_customdata['courseid'] ?? 0);

        $mform->addElement('hidden', 'course', $courseid);
        $mform->setType('course', PARAM_INT);

        $forumoptions = [0 => get_string('allforums', 'report_forumtriage')] + $forums;
        $groupoptions = [0 => get_string('allgroups', 'report_forumtriage')] + $groups;

        $mform->addElement('select', 'forumid', get_string('filter:forum', 'report_forumtriage'), $forumoptions);
        $mform->setType('forumid', PARAM_INT);

        $mform->addElement('select', 'groupid', get_string('filter:group', 'report_forumtriage'), $groupoptions);
        $mform->setType('groupid', PARAM_INT);

        $periods = [
            0 => get_string('period:all', 'report_forumtriage'),
            1 => get_string('period:days', 'report_forumtriage', 1),
            7 => get_string('period:days', 'report_forumtriage', 7),
            14 => get_string('period:days', 'report_forumtriage', 14),
            30 => get_string('period:days', 'report_forumtriage', 30),
            90 => get_string('period:days', 'report_forumtriage', 90),
        ];
        $mform->addElement('select', 'perioddays', get_string('filter:period', 'report_forumtriage'), $periods);
        $mform->setType('perioddays', PARAM_INT);
        $mform->setDefault('perioddays', 14);

        $mform->addElement('advcheckbox', 'onlynoteacher', get_string('filter:onlynoteacher', 'report_forumtriage'));
        $mform->setType('onlynoteacher', PARAM_BOOL);

        $mform->addElement('advcheckbox', 'recentonly', get_string('filter:recentonly', 'report_forumtriage'));
        $mform->setType('recentonly', PARAM_BOOL);

        $this->add_action_buttons(false, get_string('runtriage', 'report_forumtriage'));
    }
}
