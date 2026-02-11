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
 * Define for Chooser form.
 *
 * @package     report_lmsace_reports
 * @copyright  2023 LMSACE <https://lmsace.com> dev team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Course selector form.
 */
class course_selector_form extends moodleform {

    /**
     * Definitions.
     *
     * @return void
     */
    public function definition() {

        $mform = $this->_form;
        $mform->addElement('course', 'courseinfo', get_string('course'));
        $this->add_action_buttons(false, get_string('generatereport', 'report_lmsace_reports'));

        // Make the form inline.
        $mform->updateAttributes(['class' => 'form-inline']);
    }
}

/**
 * User selector form.
 */
class user_selector_form extends moodleform {

    /**
     * Definitions.
     *
     * @return void
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $options = [
            'ajax' => 'core_search/form-search-user-selector',
        ];

        $users = [];
        if (isset($this->_customdata['userinfo']) && $this->_customdata['userinfo']) {
            $user = $DB->get_record('user', ['id' => $this->_customdata['userinfo']]);
            $users[$user->id] = fullname($user);
        }
        $mform->addElement('autocomplete', 'userinfo', get_string('user'), $users, $options);
        $this->add_action_buttons(false, get_string('generatereport', 'report_lmsace_reports'));

        // Make the form inline.
        $mform->updateAttributes(['class' => 'form-inline']);

    }
}

/**
 * Teacher selector form.
 */
class teacher_selector_form extends moodleform {

    /**
     * Definitions.
     *
     * @return void
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $options = [
            'ajax' => 'core_search/form-search-user-selector',
        ];

        $users = [];
        if (isset($this->_customdata['teacherinfo']) && $this->_customdata['teacherinfo']) {
            $user = $DB->get_record('user', ['id' => $this->_customdata['teacherinfo']]);
            if ($user) {
                $users[$user->id] = fullname($user);
            }
        }
        $mform->addElement('autocomplete', 'teacherinfo', get_string('teacher', 'report_lmsace_reports'), $users, $options);
        $this->add_action_buttons(false, get_string('generatereport', 'report_lmsace_reports'));

        // Make the form inline.
        $mform->updateAttributes(['class' => 'form-inline']);
    }
}

/**
 * Evaluation teacher selector form.
 */
class evaluation_teacher_selector_form extends moodleform {

    /**
     * Definitions.
     *
     * @return void
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $options = [
            'ajax' => 'core_search/form-search-user-selector',
        ];

        $users = [];
        if (isset($this->_customdata['evalteacher']) && $this->_customdata['evalteacher']) {
            $user = $DB->get_record('user', ['id' => $this->_customdata['evalteacher']]);
            if ($user) {
                $users[$user->id] = fullname($user);
            }
        }
        $mform->addElement('autocomplete', 'evalteacher',
            get_string('selectteacherforevaluation', 'report_lmsace_reports'), $users, $options);
        $this->add_action_buttons(false, get_string('generatereport', 'report_lmsace_reports'));

        // Make the form inline.
        $mform->updateAttributes(['class' => 'form-inline']);
    }
}
