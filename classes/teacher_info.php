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
 * @package    mod_istudydailyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_istudydailyattend;

use stdClass;

/**
 * Class teacher_info
 *
 * @package    mod_istudydailyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class teacher_info {

    // Check to see if not a teacher or admin.
    public static function not_a_teacher_or_admin() {
        global $DB, $USER;

        $advisorid = $USER->id;
        $itemid = utils::get_fieldid('whichteacher');
        $search_results = $DB->get_record('user_info_data', ['userid'=>$advisorid, 'fieldid'=>$itemid], 'data');
        if ($search_results->data == 'Parent' OR $search_results->data == 'Student') {
            return true;
        }
        else {
            return false;
        }
     }

    // If the teacher is the advisor for the student, return '*'.
    public static function advisor_for_student($student_id, $this_advisor_name){
        $this_student_advisor = utils::get_field_data($student_id, utils::get_fieldid('advisor'));
        if ($this_student_advisor == $this_advisor_name) {
            return '*';
        }
        else {
            return '';
        }
    }

    /**
     * Get a single advisor according to the position of the name on the list.
     *
     * @param [integer] $chosen_advisor
     * @return [string]
     */
    public static function get_advisor($chosen_advisor) {
        global $DB;
        $sql = "SELECT * FROM {istudyteachers} ORDER By lastname ASC, firstname ASC";
        $params = null;
        $all_advisors = $DB->get_records_sql($sql, $params);
        $advisor = [];
        $count = 0;
        $advisor[$count] = 'All';
        // Create the list of advisors
       foreach ($all_advisors as $this_advisor) {
            $count = $count + 1;
            $teacher_lastname = $this_advisor->lastname;
            $teacher_firstname = $this_advisor->firstname;
            $advisor[$count] = $teacher_lastname . '-' . $teacher_firstname;
        }
        // Return the advisor.
        return $advisor[$chosen_advisor];
    }

    // **Get a list of all advisors or teachers.
    public static function get_advisors() {
        global $USER, $DB;
        $sql = "SELECT * FROM {istudyteachers} ORDER By lastname ASC, firstname ASC";
        $params = null;
        $all_advisors = $DB->get_records_sql($sql, $params);
        foreach ($all_advisors as $this_advisor) {
            $advisor[$this_advisor->teacherid] = $this_advisor->lastname . '-' . $this_advisor->firstname;
        }
        // If an administrator, return list of all teachers - If a teacher, only return the teacher name
        $advisorid = $USER->id;
        $current_advisor = utils::get_field_data($advisorid, utils::get_fieldid('whichteacher'));
        if ($current_advisor == "Administrator") {
            return $advisor;
        }
        else {
            $temp_advisor[$advisorid] = $advisor[$advisorid];
            return $temp_advisor;
        }
    }

    /**
     * Gets the advisor ($myteacher) room number.
     *
     * @param [type] $myteacher
     * @return void
     */
    public static function get_advisor_room($myteacher) {
        global $DB;
        // Separate the first name and last name that are joined with a dash (-).
        $teacher_lastname = strstr($myteacher, '-', true);
        $myteacher_temp = strstr($myteacher, '-');
        $teacher_firstname = substr($myteacher_temp, 1);

        $this_teacher = new stdClass();
        $sql = "SELECT * FROM {istudyteachers} WHERE lastname = '" .  $teacher_lastname . "' AND firstname = '" .  $teacher_firstname . "'";
        $params = null;
        $this_teacher = $DB->get_record_sql($sql, $params);

        return $this_teacher->room;
    }

    // Get which advisor - name.
    public static function get_whichadvisor($advisorid) {
        $which_advisor = utils::get_field_data($advisorid, utils::get_fieldid('whichteacher'));

        if ($which_advisor == "Administrator") {
            $advisor_name = $which_advisor;
        }
        // If not an administrator, return the advisor name (e.g. Ramirez-Jesus)
        else {
            $advisor_name = strstr($which_advisor, '/', true);
        }
        return $advisor_name;
    }

    // Get advisor name
    public static function get_advisor_name($advisorid) {
        global $DB;
        $this_teacher = new stdClass();
        $sql = "SELECT * FROM {istudyteachers} WHERE teacherid = '" . $advisorid . "'";
        $params = null;
        $this_teacher = $DB->get_record_sql($sql, $params);
        $advisor_name = $this_teacher->lastname . '-' . $this_teacher->firstname;
        return $advisor_name;
    }

     /**
     * Gets the moodle id for the teacher.
     *
     */
    public static function get_whichadvisorid($myteacher) {
        global $DB;
        // Separate the first name and last name that are joined with a dash (-).
        $teacher_lastname = strstr($myteacher, '-', true);
        $myteacher_temp = strstr($myteacher, '-');
        $teacher_firstname = substr($myteacher_temp, 1);

        $this_teacher = new stdClass();
        $sql = "SELECT * FROM {istudyteachers} WHERE lastname = '" .  $teacher_lastname . "' AND firstname = '" .  $teacher_firstname . "'";
        $params = null;
        $this_teacher = $DB->get_record_sql($sql, $params);

        return $this_teacher->teacherid;
    }
}