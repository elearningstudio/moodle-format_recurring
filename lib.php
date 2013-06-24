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
 * This file contains general functions for the course format Recurring Courses
 *
 * @since 2.3
 * @package Contributed Plugin
 * @copyright 2009 Sam Hemelryk
 * @copyright 2012 Barry Oosthuizen - Recurring Courses format based on Topics format by Sam Hemelryk
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Indicates this format uses sections.
 *
 * @return bool Returns true
 */
function callback_recurring_uses_sections() {
    return true;
}

/**
 * Used to display the course structure for a course where format=topic
 *
 * This is called automatically by {@link load_course()} if the current course
 * format = weeks.
 *
 * @param array $path An array of keys to the course node in the navigation
 * @param stdClass $modinfo The mod info object for the current course
 * @return bool Returns true
 */
function callback_recurring_load_content(&$navigation, $course, $coursenode) {
    return $navigation->load_generic_course_sections($course, $coursenode, 'recurring');
}

/**
 * The string that is used to describe a section of the course
 * e.g. Topic, Week...
 *
 * @return string
 */
function callback_recurring_definition() {
    return get_string('topic');
}

/**
 *
 * @param type $course
 * @param type $section
 * @return type
 */
function callback_recurring_get_section_name($course, $section) {
    // We can't add a node without any text.
    if ((string) $section->name !== '') {
        return format_string($section->name, true, array('context' => get_context_instance(CONTEXT_COURSE, $course->id)));
    } else if ($section->section == 0) {
        return get_string('section0name', 'format_recurring');
    } else {
        return get_string('topic') . ' ' . $section->section;
    }
}

/**
 * Declares support for course AJAX features
 *
 * @see course_format_ajax_support()
 * @return stdClass
 */
function callback_recurring_ajax_support() {
    $ajaxsupport = new stdClass();
    $ajaxsupport->capable = true;
    $ajaxsupport->testedbrowsers = array('MSIE' => 6.0, 'Gecko' => 20061111, 'Safari' => 531, 'Chrome' => 6.0);
    return $ajaxsupport;
}

/**
 * Callback function to do some action after section move
 *
 * @param stdClass $course The course entry from DB
 * @return array This will be passed in ajax respose.
 */
function callback_recurring_ajax_section_move($course) {
    global $COURSE, $PAGE;

    $titles = array();
    rebuild_course_cache($course->id);
    $modinfo = get_fast_modinfo($COURSE);
    $renderer = $PAGE->get_renderer('format_recurring');
    if ($renderer && ($sections = $modinfo->get_section_info_all())) {
        foreach ($sections as $number => $section) {
            $titles[$number] = $renderer->section_title($section, $course);
        }
    }
    return array('sectiontitles' => $titles, 'action' => 'move');
}

/**
 * Displays the recurring course settings icon and text (link).
 *
 * @return string HTML to output.
 */
function format_recurring_settings_icon($course) {
    global $OUTPUT, $PAGE;

    if ($PAGE->user_is_editing()) {
        echo html_writer::tag('a', html_writer::tag('div', '',
                array('id' => 'recurring-settings')),
                array('title' => get_string("settings"),
            'href' => 'format/recurring/forms/settings.php?id='
            . $course . '&sesskey=' . sesskey()));
        echo html_writer::tag('a', html_writer::tag('div',
                get_string('recurring_settings', 'format_recurring'),
                array('id' => 'recurring-settings-link')),
                array('title' => get_string("settings"),
            'href' => 'format/recurring/forms/settings.php?id='
            . $course . '&sesskey=' . sesskey()));
        echo '</br></br>';
    }
}

/**
 * Sets the default format setting for the course
 *
 * @param int $courseid The course identifier.
 * @return object $setting The format settings.
 */
function format_recurring_set_default($courseid) {
    global $DB;

    // Default values...
    $setting = new stdClass();
    $setting->courseid = $courseid;
    $setting->recurring = '1';
    $setting->course_freq = '1';
    $setting->template = '1';
    $setting->expires = '0';

    return $setting;
}

/**
 * Calculates the duration based on quantity and unit (days, weeks, months or years)
 *
 * @param int $qty
 * @param string $unit e.g. days, weeks, months or years
 * @return int $duration
 */
function format_recurring_get_duration($qty, $unit) {

    $day = 86400;

    if ($unit === 'days') {
        $unit = 1;
    } else if ($unit === 'weeks') {
        $unit = 7;
    } else if ($unit === 'months') {
        $unit = 365.25 / 12;
    } else {
        $unit = 365.25;
    }

    $duration = $qty * $unit * $day;

    return $duration;
}

/**
 * Gets form data to update recurring course settings
 *
 * @param int $courseid
 * @param object $formdata
 * @param object $setting
 * @return object $updatedsetting
 */
function format_recurring_form_update($courseid, $formdata, $setting) {
    global $DB;

    // If recurring option is ticked save all form data.
    if ($formdata->recurring == 1) {
        $updatedsetting = new stdClass();
        $updatedsetting->id = $setting->id;
        $updatedsetting->courseid = $courseid;
        $updatedsetting->recurring = $formdata->recurring;
        $updatedsetting->course_dur_qty = $formdata->course_dur_qty;
        $updatedsetting->course_dur_unit = $formdata->course_dur_unit;
        $course_duration = format_recurring_get_duration($formdata->course_dur_qty, $formdata->course_dur_unit);
        $updatedsetting->course_dur = $course_duration;
        $updatedsetting->course_freq_qty = $formdata->course_freq_qty;
        $updatedsetting->course_freq_unit = $formdata->course_freq_unit;
        $course_frequency = format_recurring_get_duration($formdata->course_freq_qty, $formdata->course_freq_unit);
        $updatedsetting->course_freq = $course_frequency;
        $updatedsetting->template = $courseid;
        $updatedsetting->expires = $formdata->expires;

        // Save course start reminder options if ticked.
        if ($formdata->start_rem == 1) {
            $updatedsetting->start_rem = $formdata->start_rem;
            $updatedsetting->start_rem_dur_qty = $formdata->start_rem_dur_qty;
            $updatedsetting->start_rem_dur_unit = $formdata->start_rem_dur_unit;
            $updatedsetting->start_rem_freq = $formdata->start_rem_freq;
            $reminder_window = format_recurring_get_duration($formdata->start_rem_dur_qty, $formdata->start_rem_dur_unit);
            $updatedsetting->expires = $formdata->expires - $reminder_window;

            // Save multiple reminder frequency if option selected.
            if ($formdata->start_rem_freq == 'every') {
                $updatedsetting->start_rem_qty = $formdata->start_rem_qty;
                $updatedsetting->start_rem_unit = $formdata->start_rem_unit;
            }
        }

        // Save course end reminder options if ticked.
        if ($formdata->end_rem == 1) {
            $updatedsetting->end_rem = $formdata->end_rem;
            $updatedsetting->end_rem_dur_qty = $formdata->end_rem_dur_qty;
            $updatedsetting->end_rem_dur_unit = $formdata->end_rem_dur_unit;
            $updatedsetting->end_rem_freq = $formdata->end_rem_freq;

            // Save multiple reminder frequency if option selected.
            if ($formdata->end_rem_freq == 'every') {
                $updatedsetting->end_rem_qty = $formdata->end_rem_qty;
                $updatedsetting->end_rem_unit = $formdata->end_rem_unit;
            }
        }

        return $updatedsetting;

        //  If recurring setting is unticked - ignore all other settings.
    } else {
        $updatedsetting = new stdClass();
        $updatedsetting->id = $setting->id;
        $updatedsetting->courseid = $courseid;
        $updatedsetting->recurring = $formdata->recurring;
        return $updatedsetting;
    }
}

/**
 * Gets form data for inserting new recurring course setting
 *
 * @param int $courseid
 * @param object $formdata
 * @return object $insertedsetting
 */
function format_recurring_form_insert($courseid, $formdata) {
    global $DB;

    $insertedsetting = new stdClass();
    // If recurring option is ticked save all form data.
    if ($formdata->recurring == 1) {

        $insertedsetting->courseid = $courseid;
        $insertedsetting->recurring = $formdata->recurring;
        $insertedsetting->course_dur_qty = $formdata->course_dur_qty;
        $insertedsetting->course_dur_unit = $formdata->course_dur_unit;
        $course_duration = format_recurring_get_duration($formdata->course_dur_qty, $formdata->course_dur_unit);
        $insertedsetting->course_dur = $course_duration;
        $insertedsetting->course_freq_qty = $formdata->course_freq_qty;
        $insertedsetting->course_freq_unit = $formdata->course_freq_unit;
        $course_frequency = format_recurring_get_duration($formdata->course_freq_qty, $formdata->course_freq_unit);
        $insertedsetting->course_freq = $course_frequency;
        $insertedsetting->template = $courseid;
        $insertedsetting->expires = $formdata->expires;

        // Save course start reminder options if ticked.
        if ($formdata->start_rem == 1) {
            $insertedsetting->start_rem = $formdata->start_rem;
            $insertedsetting->start_rem_dur_qty = $formdata->start_rem_dur_qty;
            $insertedsetting->start_rem_dur_unit = $formdata->start_rem_dur_unit;
            $insertedsetting->start_rem_freq = $formdata->start_rem_freq;
            $reminder_window = format_recurring_get_duration($formdata->start_rem_dur_qty, $formdata->start_rem_dur_unit);
            $insertedsetting->expires = $formdata->expires - $reminder_window;

            // Save multiple reminder frequency if option selected.
            if ($formdata->start_rem_freq == 'every') {
                $insertedsetting->start_rem_qty = $formdata->start_rem_qty;
                $insertedsetting->start_rem_unit = $formdata->start_rem_unit;
            }
        }

        // Save course end reminder options if ticked.
        if ($formdata->end_rem == 1) {
            $insertedsetting->end_rem = $formdata->end_rem;
            $insertedsetting->end_rem_dur_qty = $formdata->end_rem_dur_qty;
            $insertedsetting->end_rem_dur_unit = $formdata->end_rem_dur_unit;
            $insertedsetting->end_rem_freq = $formdata->end_rem_freq;

            // Save multiple reminder frequency if option selected.
            if ($formdata->end_rem_freq == 'every') {
                $insertedsetting->end_rem_qty = $formdata->end_rem_qty;
                $insertedsetting->end_rem_unit = $formdata->end_rem_unit;
            }
        }

        return $insertedsetting;

    } else {
        //  If recurring setting is unticked - ignore all other settings.
        $insertedsetting->courseid = $courseid;
        $insertedsetting->recurring = $formdata->recurring;
        return $insertedsetting;
    }
}

/**
 * Gets form data for updatint or inserting new recurring course setting
 *
 * @global object $DB
 * @param int $courseid
 * @param object $formdata
 * @return object $updatedsettings or $insertedsettings
 */
function format_recurring_settings($courseid, $formdata) {
    global $DB;

    //  Check if settings already exists and update recurring settings record.
    if ($setting = $DB->get_record('format_recurring_settings', array('courseid' => $courseid))) {

        $updatedsettings = format_recurring_form_update($courseid, $formdata, $setting);

        if ($updatedsettings) {
            return $updatedsettings;
        }

        //  This is a new recurring settings record, insert new record into db.
    } else {

        $insertedsettings = format_recurring_form_insert($courseid, $formdata);
        if ($insertedsettings) {
            return $insertedsettings;
        }
    }
}

/**
 * Save (insert or update) settings for recurring course
 *
 * @param int $courseid
 * @param object $formdata
 */
function format_recurring_save_settings($courseid, $formdata) {
    global $DB;

    //  Check if settings already exists and update recurring settings record.
    if ($settings = format_recurring_settings($courseid, $formdata)) {

        if (isset($settings->id)) {

            $DB->update_record('format_recurring_settings', $settings);
        } else {

            $DB->insert_record('format_recurring_settings', $settings);
        }
    } else {

        mtrace('ERROR:  Could not save records');
    }
}

/**
 * cron function for cloning courses
 *
 */
function format_recurring_cron() {
    global $CFG;

    require_once($CFG->dirroot . '/course/externallib.php');
    $clone_courses = new format_recurring();
}

/**
 * Class for cloning recurring courses
 */
class format_recurring {

    public function __construct() {

        $dont_clone = 0;
        if ($recurring_courses = $this->get_recurring_courses($dont_clone)) {
            foreach ($recurring_courses as $recurring_course) {

                $coursecontext = context_course::instance($recurring_course->courseid);

                if ($course_record = $this->get_related_course($recurring_course->courseid)) {
                    mtrace('Course record found for recurring course ID: ' . $recurring_course->courseid);
                } else {
                    mtrace('No course record found for recurring course ID: ' . $recurring_course->courseid);
                    $course_record = null;
                }

                if ($new_enrolments = $this->get_user_enrolments($coursecontext)) {
                    mtrace('User enrolments found for recurring course ID: ' . $recurring_course->courseid);
                } else {
                    mtrace('No user enrolments found for recurring course ID: ' . $recurring_course->courseid);
                    $new_enrolments = null;
                }

                if (isset($new_enrolments)) {
                    $events = $this->generate_new_user_events($recurring_course, $new_enrolments, $course_record);
                } else {

                    mtrace('No welcome emails / course end reminders for new users in recurring courses saved');
                }
            }
        }

        $clone = 1;

        if ($recurring_courses = $this->get_recurring_courses($clone)) {

            foreach ($recurring_courses as $recurring_course) {

                $coursecontext = context_course::instance($recurring_course->courseid);

                if ($old_enrolments = $this->get_user_enrolments($coursecontext)) {
                    mtrace('User enrolments found for recurring course ID: ' . $recurring_course->courseid);
                } else {
                    mtrace('No user enrolments found for recurring course ID: ' . $recurring_course->courseid);
                    $old_enrolments = null;
                }

                if ($course_record = $this->get_related_course($recurring_course->courseid)) {
                    mtrace('Course record found for recurring course ID: ' . $recurring_course->courseid);
                } else {
                    mtrace('No course record found for recurring course ID: ' . $recurring_course->courseid);
                    $course_record = null;
                }

                $newcourse = $this->clone_course($recurring_course, $course_record);

                $new_course_context = context_course::instance($newcourse['id']);

                if ($new_enrolments = $this->get_user_enrolments($coursecontext)) {
                    mtrace('User enrolments found for recurring course ID: ' . $recurring_course->courseid);
                } else {
                    mtrace('No user enrolments found for recurring course ID: ' . $recurring_course->courseid);
                    $new_enrolments = null;
                }

                $updated_enrolments = $this->update_role_assignments_timemodified($coursecontext,
                        $new_course_context, $recurring_course->course_freq);

                if ($newcourse) {
                    $events = $this->generate_user_events($recurring_course, $new_enrolments, $course_record, $newcourse);
                    $updated = $this->update_recurring_settings($recurring_course, $newcourse['id']);
                } else {

                    mtrace('No user events / reminders for recurring courses saved due to problems with course cloning');
                    mtrace('Recurring course settings were not updated');
                }
            }
        } else {
            mtrace(get_string('nothing_to_clone', 'format_recurring'));
        }
    }

    /**
     * Updates timemodified field of role_assignments table.
     *
     * The timemodified field is used to determine when the user was enrolled in a course
     * and the enrolment duration is calculated from this point
     *
     * @param object $old_course_context
     * @param object $new_course_context
     * @param int $course_duration
     */
    public static function update_role_assignments_timemodified($old_course_context, $new_course_context, $course_duration) {
        global $DB;

        $new_context = $new_course_context->id;
        $old_context = $old_course_context->id;

        if ($update = $DB->execute("
                UPDATE {role_assignments} ra_new, {role_assignments} ra_old
                SET ra_new.timemodified = ra_old.timemodified + " . $course_duration . "
                WHERE ra_new.contextid = " . $new_context . "
                AND ra_old.contextid = " . $old_context . "
                AND ra_new.userid = ra_old.userid
                ")) {

            mtrace('Updated role assignment records (timemodified)');
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieve all recurring course settings
     *
     * @global object $DB
     * @return object $courses
     */
    public static function get_recurring_courses($clone_course) {
        global $DB;

        if ($clone_course == 1) {
            $today = time();
            $yesterday = $today - 86400;

            mtrace('Getting recurring courses');
            if ($courses = $DB->get_records_select('format_recurring_settings',
                    'recurring = 1 AND expires <= ' . $today . ' AND expires >= ' . $yesterday)) {

                return $courses;
            } else {
                mtrace('No courses due to be cloned at present');
            }
        } else {
            if ($courses = $DB->get_records_select('format_recurring_settings', 'recurring = 1')) {
                return $courses;
            } else {
                mtrace("No recurring courses found");
            }
        }
    }

    /**
     * Retrieve the course record specified in the recurring course settings table
     *
     * @global object $DB
     * @param int $courseid
     * @return object $course
     */
    public static function get_related_course($courseid) {
        global $DB;

        $course = $DB->get_record('course', array('id' => $courseid));

        return $course;
    }

    /**
     * Retrive the id of the last created course
     *
     * The next id will be used as the suffix in the newly created course
     *
     * @global object $DB
     * @return object
     */
    public static function get_last_course() {
        global $DB;

        $last_id = $DB->get_record_sql('SELECT MAX(id) AS id  FROM {course} ORDER BY id DESC');

        return $last_id;
    }

    /**
     * Clone the recurring course
     *
     * @global object $DB
     * @param object $recurring_course
     * @param object $course_record
     * @return boolean
     */
    public static function clone_course($recurring_course, $course_record) {
        global $DB;

        // The id of the course we are importing FROM.
        $importcourseid = $recurring_course->courseid;

        // The id of category where we want to place the CLONED course into.
        $categoryid = $course_record->category;

        $time_now = time();
        // If the CLONED course should be visible or not.
        $visible = 1;
        // The CLONEing options (these are the defaults).
        $options = array(
            array('name' => 'activities', 'value' => 1),
            array('name' => 'blocks', 'value' => 1),
            array('name' => 'filters', 'value' => 1),
            array('name' => 'users', 'value' => 1),
            array('name' => 'role_assignments', 'value' => 1),
            array('name' => 'comments', 'value' => 0),
            array('name' => 'userscompletion', 'value' => 0),
            array('name' => 'logs', 'value' => 0),
            array('name' => 'grade_histories', 'value' => 0)
        );

        $old_fullname = $course_record->fullname;
        $old_shortname = $course_record->shortname;
        $last_course = self::get_last_course();
        $new_id = $last_course->id + 1;

        // Work out what fullname to use for the new course.
        if ($fullname_number = self::find_string_after($old_fullname, '#')) {

            $new_fullname = strstr($old_fullname, '#', true) . '#' . $new_id;
        } else {

            $new_fullname = $old_fullname . '#' . $new_id;
        }

        // Work out what shortname to use for the new course.
        if ($shortname_number = self::find_string_after($old_shortname, '#')) {

            $new_shortname = strstr($old_shortname, '#', true) . '#' . $new_id;
        } else {

            $new_shortname = $old_shortname . '#' . $new_id;
        }

        if ($shortname_taken = $DB->get_record('course', array('shortname' => $new_shortname))) {

            mtrace('duplicate course found!!!!!!!!!  Duplicate Course Name: ' . $new_shortname);

            return false;
        } else {

            $USER = get_admin();

            $newcourse = core_course_external::duplicate_course($importcourseid,
                    $new_fullname, $new_shortname, $categoryid, $visible, $options);

            mtrace('Cloned new course with shortname "' . $newcourse['shortname'] . '" and id "' . $newcourse['id']);

            fix_course_sortorder();

            return $newcourse;
        }
    }

    /**
     * Update the format_recurring_settings record for the cloned and insert a new one for the new course
     * (This sets the newly created course as the template for future coures - Any changes that need to be
     *  cloned should be made in the new course)
     *
     * @global object $DB
     * @param object $course
     * @param int $newcourseid
     * @return boolean
     */
    public static function update_recurring_settings($course, $newcourseid) {
        global $DB;

        $new_course = new stdClass();

        $new_course->courseid = $newcourseid;
        $new_course->recurring = 1;
        $new_course->course_freq_qty = $course->course_freq_qty;
        $new_course->course_freq_unit = $course->course_freq_unit;
        $new_course->course_freq = $course->course_freq;
        $new_course->template = $course->template;
        $course_frequency = $course->course_freq;
        $expires = $course->expires;

        $new_course->expires = $expires + $course_frequency;
        $new_course->next_date = $course->next_date + $course->course_freq;
        $new_course->parent = $course->courseid;
        // Course duration (per user).
        $new_course->course_dur_qty = $course->course_dur_qty;
        $new_course->course_dur_unit = $course->course_dur_unit;
        $new_course->course_dur = $course->course_dur;
        // Course clone frequency.
        $new_course->course_freq_qty = $course->course_freq_qty;
        $new_course->course_freq_unit = $course->course_freq_unit;
        $new_course->course_freq = $course->course_freq;
        // Course start reminder duration.
        $new_course->start_rem = $course->start_rem;
        $new_course->start_rem_dur_qty = $course->start_rem_dur_qty;
        $new_course->start_rem_dur_unit = $course->start_rem_dur_unit;
        // Course start reminder frequency.
        $new_course->start_rem_freq = $course->start_rem_freq;
        $new_course->start_rem_qty = $course->start_rem_qty;
        $new_course->start_rem_unit = $course->start_rem_unit;
        // Course end reminder duration.
        $new_course->end_rem = $course->end_rem;
        $new_course->end_rem_dur_qty = $course->end_rem_dur_qty;
        $new_course->end_rem_dur_unit = $course->end_rem_dur_unit;
        // Course end reminder frequency.
        $new_course->end_rem_freq = $course->end_rem_freq;
        $new_course->end_rem_qty = $course->end_rem_qty;
        $new_course->end_rem_unit = $course->end_rem_unit;

        $old_cloned_course = new stdClass();
        $old_cloned_course->id = $course->id;
        $old_cloned_course->recurring = 0;

        $inserted = $DB->insert_record('format_recurring_settings', $new_course);
        $updated = $DB->update_record('format_recurring_settings', $old_cloned_course);

        return true;
    }

    /**
     * Find the course number (the string after # in the course fullname and shortname)
     *
     * @param string $haystack
     * @param string $needle
     * @param bool $case_insensitive
     * @return int
     */
    public static function find_string_after($haystack, $needle, $case_insensitive = false) {
        $strpos = ($case_insensitive) ? 'stripos' : 'strpos';
        $pos = $strpos($haystack, $needle);
        if (is_int($pos)) {
            return substr($haystack, $pos + strlen($needle));
        }
        // Most likely false or null.
        return $pos;
    }

    /**
     * Gets the user enrolments in the recurring course
     *
     * @global object $CFG
     * @global object $DB
     * @param object $context
     * @return object $enrolments or null
     */
    public static function get_user_enrolments($context) {
        global $CFG, $DB;

        if ($enrolments = $DB->get_records_select('role_assignments', 'contextid = ' . $context->id)) {

            return $enrolments;
        } else {
            mtrace('No user enrolments found in this course');
            return null;
        }
    }

    /**
     * Generates user events based on the recurring course settings
     *
     * The user events will be used to trigger individual user reminders based on their course start / end dates.
     *
     * @global object $CFG
     * @global object $DB
     * @param object $recurring_course
     * @param object $enrolments
     */
    public static function generate_user_events($recurring_course, $enrolments, $course_record, $newcourse) {
        global $CFG, $DB;

        mtrace('Generating user events');

        foreach ($enrolments as $enrolment) {
            self::process_user_reminders($recurring_course, $enrolment, $course_record, $newcourse);
        }
    }

    /**
     * Process reminders for course start dates based on recurring course settings
     *
     * @param object $recurring_course
     * @param type $enrolment
     * @param object $course_record
     * @param string $newcourse
     * @param string $courseurl
     */
    public static function process_start_reminders($recurring_course, $enrolment, $newcourse, $courseurl) {
        global $CFG, $DB;

        $course_duration = $recurring_course->course_dur;
        $course_start = $enrolment->timemodified + $recurring_course->course_freq;
        $course_end = $course_start + $course_duration;

        $user_rem_start = format_recurring_get_duration($recurring_course->start_rem_dur_qty,
                $recurring_course->start_rem_dur_unit);
        $start_reminder = $course_start - $user_rem_start;

        mtrace('Start reminders start on: ' . userdate($start_reminder));
        mtrace('course start: ' . userdate($course_start));
        mtrace('User reminder start before duration: ' . $user_rem_start);

        $reminder = new stdClass();
        $reminder->userid = $enrolment->userid;
        $reminder->courseid = $recurring_course->courseid;
        $reminder->course_start = $course_start;
        $reminder->course_end = $course_end;

        if (isset($recurring_course->start_rem)) {

            mtrace('Calculating course duration per user...');

            if ($new_reminder = $DB->insert_record('format_recurring_reminders', $reminder)) {
                mtrace('new reminder(s) saved for user');
            } else {
                mtrace('ERROR: new course start reminder could not be saved for this user');
            }

            // Course start reminders.
            if ($recurring_course->start_rem_freq == 'every') {

                mtrace('Creating multiple course start reminders for user');

                $start_reminder_frequency = format_recurring_get_duration($recurring_course->start_rem_qty,
                        $recurring_course->start_rem_unit);
                $start_reminders = $user_rem_start / $start_reminder_frequency;

                mtrace('Start_Reminder_Frequency = ' . $start_reminder_frequency);
                mtrace('Start_Reminders = ' . $start_reminders);
                mtrace('User_course_duration = ' . $user_rem_start);
                $i = 1;

                while ($i <= $start_reminders) {

                    $i++;
                    $user_event = new stdClass();
                    $user_event->userid = $enrolment->userid;
                    $user_event->name = $newcourse->shortname;
                    $user_event->description = get_string('reminder', 'format_recurring') . ': '
                            . '<a href="' . $courseurl . '">' . $newcourse->shortname . '</a> '
                            . get_string('course_starts', 'format_recurring')
                            . userdate($course_start);

                    $user_event->timestart = $start_reminder;
                    $user_event->timeduration = 0;
                    $user_event->timemodified = time();
                    $user_event->eventype = 'user';

                    if ($new_user_event = $DB->insert_record('event', $user_event)) {

                        mtrace('New user event (course start reminder) saved');
                    } else {

                        mtrace('ERROR: new user event could not be saved');
                    }

                    $start_reminder += $start_reminder_frequency;
                }
            } else {

                mtrace('Creating single reminder for user');
                // Save just one reminder before the course start date.
                $user_event = new stdClass();
                $user_event->userid = $enrolment->userid;
                $user_event->name = $newcourse->shortname;
                $user_event->description = get_string('reminder', 'format_recurring') . ' - ' . $newcourse;
                $user_event->timestart = $start_reminder;
                $user_event->timeduration = 0;
                $user_event->timemodified = time();
                $user_event->eventype = 'user';

                if ($new_user_event = $DB->insert_record('event', $user_event)) {

                    mtrace('New user event (course start reminder) saved');
                } else {

                    mtrace('ERROR: new user event could not be saved');
                }
            }
        }
    }

    /**
     * Process reminders for course end dates based on recurring course settings
     *
     * @param object $recurring_course
     * @param object $enrolment
     * @param object $course_record
     * @param string $newcourse
     * @param string $courseurl
     */
    public static function process_end_reminders($recurring_course, $enrolment, $newcourse, $courseurl) {
        global $CFG, $DB;

        $duration = $recurring_course->course_dur;
        $course_start = $enrolment->timemodified + $recurring_course->course_freq;
        $course_end = $course_start + $duration;

        $user_course_duration = format_recurring_get_duration($recurring_course->start_rem_dur_qty,
                $recurring_course->start_rem_dur_unit);
        $end_reminder = $course_end - $user_course_duration;

        $reminder = new stdClass();
        $reminder->userid = $enrolment->userid;
        $reminder->courseid = $recurring_course->courseid;
        $reminder->course_start = $course_start;
        $reminder->course_end = $course_end;

        if (isset($recurring_course->end_rem)) {

            mtrace('Calculating course duration per user...');

            if ($new_reminder = $DB->insert_record('format_recurring_reminders', $reminder)) {
                mtrace('new reminder(s) saved for user');
            } else {
                mtrace('ERROR: new course end reminder could not be saved for this user');
            }

            // Course end reminders.
            if ($recurring_course->end_rem_freq == 'every') {

                mtrace('Creating multiple course end reminders for user');

                $end_reminder_frequency = format_recurring_get_duration($recurring_course->end_rem_qty,
                        $recurring_course->end_rem_unit);
                $end_reminders = $user_course_duration / $end_reminder_frequency;

                mtrace('end_Reminder_Frequency = ' . $end_reminder_frequency);
                mtrace('end_Reminders = ' . $end_reminders);
                mtrace('User_course_duration = ' . $user_course_duration);
                $i = 1;

                while ($i <= $end_reminders) {

                    $i++;
                    $user_event = new stdClass();
                    $user_event->userid = $enrolment->userid;
                    $user_event->name = $newcourse->shortname;
                    $user_event->description = get_string('reminder', 'format_recurring') . ': '
                            . '<a href="' . $courseurl . '">' . $newcourse->shortname . '</a> '
                            . get_string('course_ends', 'format_recurring')
                            . userdate($course_end);
                    $user_event->timestart = $end_reminder;
                    $user_event->timeduration = 0;
                    $user_event->timemodified = time();
                    $user_event->eventype = 'user';

                    if ($new_user_event = $DB->insert_record('event', $user_event)) {

                        mtrace('New user event (course end reminder) saved');
                    } else {

                        mtrace('ERROR: new user event could not be saved');
                    }

                    $end_reminder += $end_reminder_frequency;
                }
            } else {

                mtrace('Creating single reminder for user');
                // Save just one reminder before the course end date.
                $user_event = new stdClass();
                $user_event->userid = $enrolment->userid;
                $user_event->name = $newcourse;
                $user_event->description = get_string('reminder', 'format_recurring') . ' - ' . $newcourse;
                $user_event->timestart = $end_reminder;
                $user_event->timeduration = $duration;
                $user_event->timemodified = time();
                $user_event->eventype = 'user';

                if ($new_user_event = $DB->insert_record('event', $user_event)) {

                    mtrace('New user event (course end reminder) saved');
                } else {

                    mtrace('ERROR: new user event could not be saved');
                }
            }
        }
    }

    /**
     * Process course start and end reminders
     *
     * @global object $CFG
     * @global object $DB
     * @param object $recurring_course
     * @param object $enrolment
     * @return boolean
     */
    public static function process_user_reminders($recurring_course, $enrolment, $course_record, $newcourse) {
        global $CFG, $DB;

        mtrace('Processing user reminder');

        $courseurl = new moodle_url('/course/view.php', array('id' => $newcourse['id']));

        self::process_start_reminders($recurring_course, $enrolment, $course_record, $courseurl);
        self::process_end_reminders($recurring_course, $enrolment, $course_record, $courseurl);
    }

    /**
     * Generate new user events
     *
     * @param object $recurring_course
     * @param object $enrolments
     */
    public static function generate_new_user_events($recurring_course, $enrolments, $course_record) {
        global $CFG, $DB;

        mtrace('Generating user events');

        foreach ($enrolments as $enrolment) {
            self::process_new_user_reminders($recurring_course, $enrolment, $course_record);
        }
    }

    /**
     * Process new user reminders
     *
     * @param object $recurring_course
     * @param object $enrolment
     * @return boolean
     */
    public static function process_new_user_reminders($recurring_course, $enrolment, $course_record) {
        global $CFG, $DB;

        mtrace('Processing user reminder');

        $courseurl = new moodle_url('/course/view.php', array('id' => $course_record->id));

        if ($initial_reminders = $DB->get_records_select('format_recurring_reminders',
                'courseid = ' . $course_record->id . ' AND userid = ' . $enrolment->userid)) {
            mtrace('Inital course start / end reminders already saved for this user in this course.  Nothing to do....');
        } else {
            mtrace('No inital course start / end reminders found...');

            self::process_new_start_reminders($recurring_course, $enrolment, $course_record, $courseurl);
            self::process_new_end_reminders($recurring_course, $enrolment, $course_record, $courseurl);
        }
    }

    /**
     * Process new course start reminders
     *
     * @param object $recurring_course
     * @param object $enrolment
     * @param object $course_record
     * @param string $newcourse
     * @param string $courseurl
     */
    public static function process_new_start_reminders($recurring_course, $enrolment, $course_record, $courseurl) {
        global $CFG, $DB;

        $course_duration = $recurring_course->course_dur;
        $course_start = $enrolment->timemodified;
        $course_end = $course_start + $course_duration;

        $user_rem_start = format_recurring_get_duration($recurring_course->start_rem_dur_qty,
                $recurring_course->start_rem_dur_unit);
        $start_reminder = $course_start;

        mtrace('Start reminders start on: ' . userdate($start_reminder));
        mtrace('course start: ' . userdate($course_start));
        mtrace('User reminder start before duration: ' . $user_rem_start);

        $reminder = new stdClass();
        $reminder->userid = $enrolment->userid;
        $reminder->courseid = $recurring_course->courseid;
        $reminder->course_start = $course_start;
        $reminder->course_end = $course_end;

        if (isset($recurring_course->start_rem)) {

            mtrace('Calculating course duration per user...');

            if ($new_reminder = $DB->insert_record('format_recurring_reminders', $reminder)) {
                mtrace('new reminder(s) saved for user');
            } else {
                mtrace('ERROR: new course start reminder could not be saved for this user.');
            }

            mtrace('Creating single welcome to course reminder for user');
            // Save just one reminder after the user has been enrolled into a recurring course for the first time.
            $user_event = new stdClass();
            $user_event->userid = $enrolment->userid;
            $user_event->name = $course_record->shortname;
            $user_event->description = get_string('reminder', 'format_recurring') . ' - ' . $course_record->shortname;
            $user_event->timestart = $start_reminder;
            $user_event->timeduration = 0;
            $user_event->timemodified = time();
            $user_event->eventype = 'user';

            if ($new_user_event = $DB->insert_record('event', $user_event)) {

                mtrace('New user event (course start reminder) saved');
            } else {

                mtrace('ERROR: new user event could not be saved - could not insert new record in the database');
            }
        }
    }

    /**
     * Process new course end reminder
     *
     * @param object $recurring_course
     * @param object $enrolment
     * @param object $course_record
     * @param string $newcourse
     * @param string $courseurl
     */
    public static function process_new_end_reminders($recurring_course, $enrolment, $course_record, $courseurl) {
        global $CFG, $DB;

        $duration = $recurring_course->course_dur;
        $course_start = $enrolment->timemodified;
        $course_end = $course_start + $duration;

        $user_course_duration = format_recurring_get_duration($recurring_course->start_rem_dur_qty,
                $recurring_course->start_rem_dur_unit);
        $end_reminder = $course_end - $user_course_duration;

        $reminder = new stdClass();
        $reminder->userid = $enrolment->userid;
        $reminder->courseid = $recurring_course->courseid;
        $reminder->course_start = $course_start;
        $reminder->course_end = $course_end;

        if (isset($recurring_course->end_rem)) {

            mtrace('Calculating course duration per user...');

            if ($new_reminder = $DB->insert_record('format_recurring_reminders', $reminder)) {
                mtrace('new reminder(s) saved for user');
            } else {
                mtrace('ERROR: new course end reminder could not be saved for this user.');
            }

            // Course end reminders.
            if ($recurring_course->end_rem_freq == 'every') {

                mtrace('Creating multiple course end reminders for user');

                $end_reminder_frequency = format_recurring_get_duration($recurring_course->end_rem_qty,
                        $recurring_course->end_rem_unit);
                $end_reminders = $user_course_duration / $end_reminder_frequency;

                mtrace('end_Reminder_Frequency = ' . $end_reminder_frequency);
                mtrace('end_Reminders = ' . $end_reminders);
                mtrace('User_course_duration = ' . $user_course_duration);
                $i = 1;

                while ($i <= $end_reminders) {

                    $i++;
                    $user_event = new stdClass();
                    $user_event->userid = $enrolment->userid;
                    $user_event->name = $course_record->shortname;
                    $user_event->description = get_string('reminder', 'format_recurring') . ': '
                            . '<a href="' . $courseurl . '">' . $course_record->shortname . '</a> '
                            . get_string('course_ends', 'format_recurring')
                            . userdate($course_end);
                    $user_event->timestart = $end_reminder;
                    $user_event->timeduration = 0;
                    $user_event->timemodified = time();
                    $user_event->eventype = 'user';

                    if ($new_user_event = $DB->insert_record('event', $user_event)) {

                        mtrace('New user event (course end reminder) saved');
                    } else {

                        mtrace('ERROR: new user event could not be saved.');
                    }

                    $end_reminder += $end_reminder_frequency;
                }
            } else {

                mtrace('Creating single reminder for user');
                // Save just one reminder before the course end date.
                $user_event = new stdClass();
                $user_event->userid = $enrolment->userid;
                $user_event->name = $recurring_course->shortname;
                $user_event->description = get_string('reminder', 'format_recurring') . ' - ' . $recurring_course->shortname;
                $user_event->timestart = $end_reminder;
                $user_event->timeduration = $duration;
                $user_event->timemodified = time();
                $user_event->eventype = 'user';

                if ($new_user_event = $DB->insert_record('event', $user_event)) {

                    mtrace('New user event (course end reminder) saved');
                } else {

                    mtrace('ERROR: new user event could not be saved .');
                }
            }
        }
    }
}
