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
 * Forum triage settings.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('report_forumtriage_settings', get_string('settings', 'report_forumtriage'));

    $settings->add(new admin_setting_configtext(
        'report_forumtriage/unansweredhours',
        get_string('settings:unansweredhours', 'report_forumtriage'),
        get_string('settings:unansweredhours_desc', 'report_forumtriage'),
        24,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_forumtriage/noteacherhours',
        get_string('settings:noteacherhours', 'report_forumtriage'),
        get_string('settings:noteacherhours_desc', 'report_forumtriage'),
        48,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_forumtriage/longthreadreplies',
        get_string('settings:longthreadreplies', 'report_forumtriage'),
        get_string('settings:longthreadreplies_desc', 'report_forumtriage'),
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_forumtriage/recentdays',
        get_string('settings:recentdays', 'report_forumtriage'),
        get_string('settings:recentdays_desc', 'report_forumtriage'),
        7,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_forumtriage/maxaithreads',
        get_string('settings:maxaithreads', 'report_forumtriage'),
        get_string('settings:maxaithreads_desc', 'report_forumtriage'),
        50,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_forumtriage/maxpostchars',
        get_string('settings:maxpostchars', 'report_forumtriage'),
        get_string('settings:maxpostchars_desc', 'report_forumtriage'),
        2000,
        PARAM_INT
    ));

    $ADMIN->add('reports', $settings);
} else {
    $settings = null;
}
