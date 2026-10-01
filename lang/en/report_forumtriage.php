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
 * English language strings.
 *
 * @package    report_forumtriage
 * @copyright  2026 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aianalysed'] = 'AI analysed {$a->analysed} of {$a->candidates} semantic candidates.';
$string['aiunavailable'] = 'Semantic analysis could not be completed. Deterministic results are still shown.';
$string['allforums'] = 'All forums';
$string['allgroups'] = 'All visible groups';
$string['authors'] = '{$a} authors';
$string['confidence:high'] = 'High confidence';
$string['confidence:low'] = 'Low confidence';
$string['confidence:medium'] = 'Medium confidence';
$string['deterministicnotice'] = 'The initial view is deterministic. Submit the filters to run semantic triage through AI Bridge.';
$string['filter:forum'] = 'Forum';
$string['filter:group'] = 'Group';
$string['filter:onlynoteacher'] = 'Only discussions without a teacher reply';
$string['filter:period'] = 'Activity period';
$string['filter:recentonly'] = 'Only recent questions';
$string['forum'] = 'Forum: {$a}';
$string['forumtriage:view'] = 'View forum triage report';
$string['group'] = 'Group: {$a}';
$string['invalidairesponse'] = 'AI returned an invalid structured response. Deterministic results are still shown.';
$string['lastactivity'] = 'Last activity: {$a}';
$string['mostdiscussed'] = 'Most discussed topics';
$string['needsattention'] = 'Needs attention';
$string['noaiyet'] = 'Run triage to generate semantic sections such as possibly unresolved and recurring questions.';
$string['noclusters'] = 'No recurring semantic cluster was identified in the analysed discussions.';
$string['noresponse'] = 'No response';
$string['noteacherreply'] = 'No teacher reply';
$string['nothreads'] = 'No visible discussions match the selected filters.';
$string['opendiscussion'] = 'Open discussion';
$string['period:all'] = 'Any time';
$string['period:days'] = 'Last {$a} day(s)';
$string['pluginname'] = 'Forum triage';
$string['possiblyunresolved'] = 'Possibly unresolved';
$string['priority'] = 'Priority: {$a}';
$string['priority:high'] = 'High';
$string['priority:medium'] = 'Medium';
$string['priority:normal'] = 'Normal';
$string['privacy:metadata'] = 'The Forum triage report does not store personal data. It reads data already stored by Moodle forums at request time and does not persist prompts or raw AI responses.';
$string['recurringquestions'] = 'Recurring questions';
$string['replies'] = '{$a} replies';
$string['runtriage'] = 'Run triage';
$string['sectionempty'] = 'No discussions in this section.';
$string['settings'] = 'Forum triage settings';
$string['settings:longthreadreplies'] = 'Replies that define a long thread';
$string['settings:longthreadreplies_desc'] = 'A thread at or above this visible reply count is flagged as long.';
$string['settings:maxaithreads'] = 'Maximum discussions per AI request';
$string['settings:maxaithreads_desc'] = 'Caps semantic candidates sent to AI Bridge. Remaining discussions still appear in deterministic sections.';
$string['settings:maxpostchars'] = 'Maximum characters per post sent to AI';
$string['settings:maxpostchars_desc'] = 'Post text is converted to plain text and truncated before the AI request.';
$string['settings:noteacherhours'] = 'Hours before a discussion without teacher reply becomes high priority';
$string['settings:noteacherhours_desc'] = 'Objective threshold used by PHP after the first post.';
$string['settings:recentdays'] = 'Recent-question window in days';
$string['settings:recentdays_desc'] = 'Used by the “Only recent questions” filter.';
$string['settings:unansweredhours'] = 'Hours before an unanswered discussion becomes high priority';
$string['settings:unansweredhours_desc'] = 'Objective threshold used by PHP. AI cannot override this priority rule.';
$string['stat:long'] = 'Long threads';
$string['stat:noteacher'] = 'Without teacher reply';
$string['stat:unanswered'] = 'No response';
$string['stat:visible'] = 'Visible discussions';
$string['teacherreplied'] = 'Teacher replied';
