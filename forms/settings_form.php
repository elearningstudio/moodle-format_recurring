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
 * Collapsed Topics Information
 *
 * A topic based format that solves the issue of the 'Scroll of Death' when a course has many topics. All topics
 * except zero have a toggle that displays that topic. One or more topics can be displayed at any given time.
 * Toggles are persistent on a per browser session per course basis but can be made to persist longer by a small
 * code change. Full installation instructions, code adaptions and credits are included in the 'Readme.txt' file.
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
require_once($CFG->libdir . '/formslib.php');

class set_settings_form extends moodleform {

    public function definition() {
        global $DB;

        $mform = $this->_form;
        $instance = $this->_customdata;

        $defaults = $DB->get_record('format_recurring_settings',
                array('courseid' => $instance['courseid']));
        if (!$defaults) {

            $defaults = new stdClass();
            $defaults->recurring = 0;
            $defaults->course_dur_qty = 0;
            $defaults->course_dur_unit = 'weeks';
            $defaults->course_freq_qty = 0;
            $defaults->course_freq_unit = 'years';
            $defaults->expires = time();
            $defaults->start_rem = 0;
            $defaults->start_rem_dur_qty = 0;
            $defaults->start_rem_dur_unit = 'days';
            $defaults->start_rem_freq = 'once';
            $defaults->start_rem_qty = 0;
            $defaults->start_rem_unit = 'days';
            $defaults->end_rem = 0;
            $defaults->end_rem_dur_qty = 0;
            $defaults->end_rem_dur_unit = 'days';
            $defaults->end_rem_freq = 'once';
            $defaults->end_rem_qty = 0;
            $defaults->end_rem_unit = 'days';
        }

        $mform->addElement('header', 'setlayout', get_string('recurring_settings', 'format_recurring'));
        $mform->addHelpButton('recurring_settings', 'recurring_settings', 'format_recurring', '', true);

        $mform->addElement('advcheckbox', 'recurring', get_string('recurring_course_template', 'format_recurring'), false);
        $mform->setDefault('recurring', $defaults->recurring);

        $duration_options =
                array('days' => get_string('days', 'format_recurring'),
                    'weeks' => get_string('weeks', 'format_recurring'),
                    'months' => get_string('months', 'format_recurring'),
                    'years' => get_string('years', 'format_recurring'));

        $duration_group = array();
        $duration_group[] = & $mform->createElement('text', 'course_dur_qty', '', 'maxlength="4" size="4" ');
        $duration_group[] = & $mform->createElement('select', 'course_dur_unit', '', $duration_options);

        $mform->addGroup($duration_group, 'duration', get_string('course_duration', 'format_recurring'), array(' '), false);
        $mform->setDefault('course_dur_qty', $defaults->course_dur_qty);
        $mform->setDefault('course_dur_unit', $defaults->course_dur_unit);
        // Disable  frequency fields unless duration course checkbox is checked.
        $mform->disabledIf('duration', 'recurring');

        $recurring_options =
                array('days' => get_string('days', 'format_recurring'),
                    'weeks' => get_string('weeks', 'format_recurring'),
                    'months' => get_string('months', 'format_recurring'),
                    'years' => get_string('years', 'format_recurring'));

        $freq_group = array();
        $freq_group[] = & $mform->createElement('text', 'course_freq_qty', '', 'maxlength="4" size="4" ');
        $freq_group[] = & $mform->createElement('select', 'course_freq_unit', '', $recurring_options);

        $mform->addGroup($freq_group, 'freq', get_string('recurring_course_frequency', 'format_recurring'), array(' '), false);
        $mform->setDefault('course_freq_qty', $defaults->course_freq_qty);
        $mform->setDefault('course_freq_unit', $defaults->course_freq_unit);
        // Disable  frequency fields unless recurring course checkbox is checked.
        $mform->disabledIf('freq', 'recurring');

        $mform->addElement('date_selector', 'expires', get_string('recurring_course_expiry', 'format_recurring'));
        $mform->setDefault('expires', $defaults->expires);
        // Disable course expiry unless recurring course checkbox is checked.
        $mform->disabledIf('expires', 'recurring');

        // Course start reminder settings.
        $mform->addElement('header', 'setlayout', get_string('course_start_reminder', 'format_recurring'));

        $mform->addElement('advcheckbox', 'start_rem', get_string('send_course_start_reminders', 'format_recurring'), false);
        $mform->setDefault('start_rem', $defaults->start_rem);
        $mform->disabledIf('start_rem', 'recurring');

        $rem_options =
                array('days' => get_string('days', 'format_recurring'),
                    'weeks' => get_string('weeks', 'format_recurring'));

        $rem_group = array();
        $rem_group[] = & $mform->createElement('text', 'start_rem_dur_qty', '', 'maxlength="4" size="4" ');
        $rem_group[] = & $mform->createElement('select', 'start_rem_dur_unit', '', $rem_options);
        $rem_group[] = & $mform->createElement('static', 'rem_label',
                get_string('before_course_start', 'format_recurring'),
                get_string('before_course_start', 'format_recurring'));

        $mform->addGroup($rem_group, 'rem_group', get_string('send_reminder', 'format_recurring'), array(' '), false);
        $mform->setDefault('start_rem_dur_qty', $defaults->start_rem_dur_qty);
        $mform->setDefault('start_rem_dur_unit', $defaults->course_freq_unit);
        // Disable  reminder fields unless recurring course checkbox is checked.

        $mform->disabledIf('rem_group', 'start_rem');

        // Reminder frequency.
        $start_rem_freq =
                array('every' => get_string('every', 'format_recurring'),
                    'once' => get_string('once', 'format_recurring'));

        $start_rem_options =
                array('days' => get_string('days', 'format_recurring'),
                    'weeks' => get_string('weeks', 'format_recurring'));

        $start_rem_group = array();
        $start_rem_group[] = & $mform->createElement('select', 'start_rem_freq', '', $start_rem_freq);
        $start_rem_group[] = & $mform->createElement('text', 'start_rem_qty', '', 'maxlength="4" size="4" ');
        $start_rem_group[] = & $mform->createElement('select', 'start_rem_unit', '', $start_rem_options);
        $start_rem_group[] = & $mform->createElement('static', 'rem_label',
                get_string('before_course_start', 'format_recurring'),
                get_string('before_course_start', 'format_recurring'));

        $mform->addGroup($start_rem_group, 'start_rem_group',
                get_string('send_start_reminder', 'format_recurring'), array(' '), false);
        $mform->setDefault('start_rem_freq', $defaults->start_rem_freq);
        $mform->setDefault('start_rem_qty', $defaults->start_rem_qty);
        $mform->setDefault('start_rem_unit', $defaults->start_rem_unit);
        // Disable  reminder fields unless recurring course checkbox is checked.
        $mform->disabledIf('start_rem_qty', 'start_rem_freq', 'eq', 'once');
        $mform->disabledIf('start_rem_unit', 'start_rem_freq', 'eq', 'once');
        $mform->disabledIf('start_rem_group', 'start_rem');

        // Course end reminder settings.
        $mform->addElement('header', 'setlayout', get_string('course_end_reminder', 'format_recurring'));

        $mform->addElement('advcheckbox', 'end_rem', get_string('course_end_reminder', 'format_recurring'), false);
        $mform->setDefault('end_rem', $defaults->end_rem);
        $mform->disabledIf('end_rem', 'recurring');

        $end_rem_dur_options =
                array('days' => get_string('days', 'format_recurring'),
                    'weeks' => get_string('weeks', 'format_recurring'));

        $end_rem_dur_group = array();
        $end_rem_dur_group[] = & $mform->createElement('text', 'end_rem_dur_qty', '', 'maxlength="4" size="4" ');
        $end_rem_dur_group[] = & $mform->createElement('select', 'end_rem_dur_unit', '', $end_rem_dur_options);
        $end_rem_dur_group[] = & $mform->createElement('static', 'end_rem_dur_label',
                get_string('before_course_end', 'format_recurring'),
                get_string('before_course_end', 'format_recurring'));

        $mform->addGroup($end_rem_dur_group, 'end_rem_dur_group',
                get_string('send_reminder', 'format_recurring'), array(' '), false);
        $mform->setDefault('end_rem_dur_qty', $defaults->end_rem_dur_qty);
        $mform->setDefault('end_rem_dur_unit', $defaults->end_rem_dur_unit);
        // Disable  reminder fields unless recurring course checkbox is checked.
        $mform->disabledIf('end_rem_dur_group', 'end_rem');

        // Reminder frequency.
        $end_rem_freq =
                array('every' => get_string('every', 'format_recurring'),
                    'once' => get_string('once', 'format_recurring'));

        $end_rem_options =
                array('days' => get_string('days', 'format_recurring'),
                    'weeks' => get_string('weeks', 'format_recurring'));

        $end_rem_group = array();
        $end_rem_group[] = & $mform->createElement('select', 'end_rem_freq', '', $end_rem_freq);
        $end_rem_group[] = & $mform->createElement('text', 'end_rem_qty', '', 'maxlength="4" size="4" ');
        $end_rem_group[] = & $mform->createElement('select', 'end_rem_unit', '', $end_rem_options);
        $end_rem_group[] = & $mform->createElement('static', 'rem_label',
                get_string('before_course_end', 'format_recurring'),
                get_string('before_course_end', 'format_recurring'));

        $mform->addGroup($end_rem_group, 'end_rem_group',
                get_string('send_course_end_reminders', 'format_recurring'), array(' '), false);
        $mform->setDefault('end_rem_freq', $defaults->end_rem_freq);
        $mform->setDefault('end_rem_qty', $defaults->end_rem_qty);
        $mform->setDefault('end_rem_unit', $defaults->end_rem_unit);
        // Disable  reminder fields unless recurring course checkbox is checked.
        $mform->disabledIf('end_rem_qty', 'end_rem_freq', 'eq', 'once');
        $mform->disabledIf('end_rem_unit', 'end_rem_freq', 'eq', 'once');

        $mform->disabledIf('end_rem_group', 'end_rem');

        $mform->addElement('hidden', 'id', $instance['courseid']);
        $mform->setType('id', PARAM_INT);
        $this->add_action_buttons(true, get_string('savechanges', 'admin'));
    }

}
