<?php
// This file is part of the recurring courses course format
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
 * Recurring Course Information
 *
 * A topic based format 
 *
 * @package    course/format
 * @subpackage recurring
 * @version    See the value of '$plugin->version' in below.
 * @copyright  &copy; 2012-onwards Barry Oosthuizen in respect to modifications of standard topics format.
 * @author     Barry Oosthuizen - barry at elearningstudio dot co dot uk {@link http://moodle.org/user/profile.php?id=520295}
 * @link       http://docs.moodle.org/en/Recurring_Courses_course_format
 * @license    http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
require_once('../../../../config.php');
require_once('../lib.php');
require_once('./settings_form.php');
require_once('../config.php');

defined('MOODLE_INTERNAL') || die();

$courseid = required_param('id', PARAM_INT); // Course id.

if (!($course = $DB->get_record('course', array('id' => $courseid)))) {
    print_error('invalidcourseid', 'error');
}

$recurring_setting = format_recurring_set_default($course->id);

preload_course_contexts($courseid);
if (!$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id)) {
    print_error('nocontext');
}
require_login($course);

$PAGE->set_context($coursecontext);
$PAGE->set_url('/course/format/recurring/forms/settings.php',
        array('id' => $courseid, 'sesskey' => sesskey()));
$PAGE->set_pagelayout('course');
$PAGE->set_pagetype('course-view-recurring');
$PAGE->set_other_editing_capability('moodle/course:manageactivities');
$PAGE->set_title(get_string('settings') . ' - ' . $course->fullname . ' ' . get_string('course'));
$PAGE->set_heading(get_string('formatsettings', 'format_recurring') . ' - ' . $course->fullname . ' ' . get_string('course'));

require_sesskey();
require_capability('moodle/course:update', $coursecontext);
require_capability('course/recurring:settings', $coursecontext);

$courseurl = new moodle_url('/course/view.php', array('id' => $courseid));

if ($PAGE->user_is_editing()) {
    $mform = new set_settings_form(null, array('courseid' => $courseid,
                'recurring' => $recurring_setting->recurring, 'course_freq' => $recurring_setting->course_freq,
                'template' => $recurring_setting->template, 'expires' => $recurring_setting->expires));

    $saved = '';

    if ($mform->is_cancelled()) {
        redirect($courseurl);
    } else if ($formdata = $mform->get_data()) {

        $saved = format_recurring_save_settings($courseid, $formdata);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->box_start('generalbox');
    $mform->display();
    echo $saved;
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
} else {
    redirect($courseurl);
}
