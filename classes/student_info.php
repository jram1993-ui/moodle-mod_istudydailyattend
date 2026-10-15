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
 * Class student_info
 *
 * @package    mod_istudydailyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_info {

    // Use the studentid to get the student first name and lastname - also include if the account has been deleted or suspended
    public static function get_single_student($studentid) {
        global $DB;
        $mystudent = $DB->get_record('user', ['id' => $studentid], 'firstname, lastname, deleted, suspended');
        return $mystudent;
    }

    // Break up the string and return the course info - subject, teacher, section.
    public static function get_mycourse($mycourse) {
        $course_info = new stdClass();
        $course_info->subject = strstr($mycourse, '/', true);
        $mycourse = strstr($mycourse, "/");
        $mycourse = substr($mycourse, 1);
        $course_info->teacher = strstr($mycourse, '/', true);
        $mycourse = strstr($mycourse, "/");
        $mycourse = substr($mycourse, 1);
        $course_info->section = trim($mycourse);
        return $course_info;
    }

    // Calculate the grade level from the graduation year
    public static function get_gradelevel($grad_year) {
        $currentmonth = date('m');
        $currentyear = date('Y');
        // If now in spring semester and student is a senior or 5th year senior, 0 years left to graduate
        if ($grad_year <= $currentyear) {
            $years_left = 0;
        }
        // If now in fall semester, add 1 to current year so that it is equal to the year for the spring semester
        else if ($currentmonth > 7) {
            $years_left = $grad_year - ($currentyear + 1);
        }
        // If now in spring semester and not a senior, just subtract the years
        else {
            $years_left = $grad_year - $currentyear;
        }
        $my_grade_level = 12 - $years_left;
        return $my_grade_level;
    }

    // Student and course info according to student_id, schoolterm, day_of_week, and this_session.
    public static function get_student_info($student_id, $schoolterm, $day_of_week, $this_session) {
        global $DB;
        // Student Info
        $student_info = new stdClass();
        if ($student_temp = $DB->get_record('user', ['id' => $student_id] , 'firstname, lastname, deleted, suspended')) {
            $student_info->lastname = $student_temp->lastname;
            $student_info->firstname = $student_temp->firstname;
            $student_info->deleted = $student_temp->deleted;
            $student_info->suspended = $student_temp->suspended;
            $student_info->school_id = utils::get_field_data($student_id, utils::get_fieldid('schoolid'));
            // Find the course and break it up into its components -- example: Spanish IA/Ramirez-Jesus/650512
            $session_name = $schoolterm . $day_of_week . 'session' . $this_session;
            $this_course_temp =  student_info::get_mycourse(utils::get_field_data($student_id, utils::get_fieldid($session_name)));
            $student_info->courseid = $this_course_temp->section;
            $student_info->course_name = $this_course_temp->subject;
            $session_credits_name = $schoolterm . $day_of_week . 'creditssession' . $this_session;
            $student_info->credits = utils::get_field_data($student_id, utils::get_fieldid($session_credits_name));
            $student_info->gradelevel = student_info::get_gradelevel(utils::get_field_data($student_id, utils::get_fieldid('graduationyear')));
            return $student_info;
        }
         else {
            $student_info->credits = 0;
            $student_info->suspended = true;
            $student_info->deleted = true;
            return $student_info;
        }
    }

    // **Get all students for the session according to date, day of the week, session, and teacher.
    public static function get_students_for_session($advisorid, $attendance_date, $schoolterm, $day_of_week, $this_session) {
        global $DB;

        $mystudent_list[]= array();
        $week_day[1] = 'monday';
        $week_day[2] = 'tuesday';
        $week_day[3] = 'wednesday';
        $week_day[4] = 'thursday';
        $week_day[5] = 'friday';
        $school_term[1] = 'fall';
        $school_term[2] = 'spring';
        $school_term[3] = 'summer';
        $todays_date = $attendance_date;

        // Check for a daily schedule. If 'No', student schedules are different each day of the week.
        // If 'Yes', Mondays schedule is applied to each day of the week (Monday-Friday)
        if ($schoolterm == 1 OR $schoolterm == 2) {
            $dailyschedule = get_config('mod_istudy', 'dailyschedule');
        }
        else {
            $dailyschedule = get_config('mod_istudy', 'summer_dailyschedule');
        }
        if ($dailyschedule == 'Yes') {
            $day_of_week = 1;
        }
        $teacher_name = teacher_info::get_advisor_name($advisorid);

        // Get all users that have not been deleted
        $session_name = $school_term[$schoolterm] . $week_day[$day_of_week] . 'session' . $this_session;
        $session_id = utils::get_fieldid($session_name);

        // Start the count at -1. $count = 0 will hold the first item student.
        $count = -1;

        $advisorid = (int) $advisorid;
        $students_with_attendance = $DB->get_records('istudy_dailyattend_enter', ['teacher_id' => $advisorid, 'attendance_date' => $attendance_date, 'session' => $this_session]);
        foreach ($students_with_attendance as $this_student) {
            $temp_saved_student = student_info::get_student_info($this_student->student_id, $school_term[$schoolterm], $week_day[$day_of_week], $this_session);
            // Don't include students that have been deleted or suspended
            if (($temp_saved_student->deleted == false) AND ($temp_saved_student->suspended == false)) {
                $count = $count + 1;
                $mystudent_list[$count] = new stdClass();
                $mystudent_list[$count]->student_id = $this_student->student_id;
                $mystudent_list[$count]->attendance_status = $this_student->attendance_status;
                $mystudent_list[$count]->notes = $this_student->notes;
                $mystudent_list[$count]->id = $this_student->id;
                $mystudent_list[$count]->course = $this_student->course;
                $mystudent_list[$count]->startdate = utils::get_field_data($this_student->student_id, utils::get_fieldid('startdate'));
                $mystudent_list[$count]->school_id = $temp_saved_student->school_id;
                $mystudent_list[$count]->student_name = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                $mystudent_list[$count]->grade = $temp_saved_student->gradelevel;
                $mystudent_list[$count]->credits = $temp_saved_student->credits;
                $mystudent_list[$count]->course_name = $temp_saved_student->course_name;
                // If the student completed the course, get the course name using the course id and credits = 0. Otherewise, the course name will be '' or it will have the new course name
                if ($mystudent_list[$count]->course_name == '' or $temp_saved_student->courseid <> $this_student->course) {
                    $mystudent_list[$count]->course_name = utils::get_course_name($this_student->course);
                    $mystudent_list[$count]->credits = 0;
                }
                // If the student starts next week, change the background of the student name and id to red.
                $mystudent_list[$count]->starts_next_week = false;
                if ($mystudent_list[$count]->startdate > $todays_date) {
                    $mystudent_list[$count]->starts_next_week = true;
                }
            }
        }
        // Get the students that are in the course, but have no saved attendance
        $already_saved_count = $count;

        // Get the id of all students that are taking a course with the teacher on the specified day and session
        $sql = "SELECT userid FROM {user_info_data} WHERE fieldid = " . $session_id . " AND data LIKE '%" . $teacher_name . "/%'";
        $params = null;
        $students_without_attendance = $DB->get_records_sql($sql, $params);
        foreach ($students_without_attendance as $this_student) {
            // If a student has already been saved, skip this student.
            $already_saved = false;
            if ($already_saved_count > -1) {
                for ($j = 0; $j <= $already_saved_count; $j++) {
                    if ($mystudent_list[$j]->student_id == $this_student->userid){
                        $already_saved = true;
                    }
                }
            }
            $temp_saved_student = student_info::get_student_info($this_student->userid, $school_term[$schoolterm], $week_day[$day_of_week], $this_session);
            // Only add students to the list if they don't have any attendance data for the day and if they have not been deleted or suspended
            If (($already_saved == false) and ($temp_saved_student->deleted == false) and ($temp_saved_student->suspended == false)) {
                $count = $count + 1;
                $mystudent_list[$count] = new stdClass();
                $mystudent_list[$count]->student_id = $this_student->userid;
                $mystudent_list[$count]->attendance_status = 0;
                $mystudent_list[$count]->startdate = utils::get_field_data($this_student->userid, utils::get_fieldid('startdate'));

                // If the student starts next week, change the background of the student name and id to red.
                $mystudent_list[$count]->starts_next_week = false;
                if ($mystudent_list[$count]->startdate > $todays_date) {
                    $mystudent_list[$count]->starts_next_week = true;
                }
                $mystudent_list[$count]->notes = "";
                $mystudent_list[$count]->id = -1;
                $mystudent_list[$count]->school_id = $temp_saved_student->school_id;
                $mystudent_list[$count]->student_name = $temp_saved_student->lastname . ', ' . $temp_saved_student->firstname;
                $mystudent_list[$count]->grade = $temp_saved_student->gradelevel;
                $mystudent_list[$count]->course = $temp_saved_student->courseid;
                $mystudent_list[$count]->course_name =$temp_saved_student->course_name;
                $mystudent_list[$count]->credits =$temp_saved_student->credits;
            }
        }
        if ($count == -1) {
            return 'None';
        }
        else {
            // Sort the student alphabetically
            for ($i=0; $i<=$count; $i++){
                $val = $mystudent_list[$i];
                $j = $i-1;
                while($j>=0 && $mystudent_list[$j]->student_name > $val->student_name){
                    $mystudent_list[$j+1] = $mystudent_list[$j];
                    $j--;
                }
                $mystudent_list[$j+1] = $val;
            }
            return $mystudent_list;
        }
    }
}