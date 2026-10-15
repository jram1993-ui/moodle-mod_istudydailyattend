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
 * The main istudy configuration form used for entering attendance credit
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_daily_attend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Module instance settings form
 *
 * @package    mod_istudy
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_istudydailyattend_enter_dailyattend_form extends \moodleform
{

    public function definition()
	{
        $mform    = $this->_form;
		$advisorid = $this->_customdata['myteacher'];
		$attendance_date = $this->_customdata['attendance_date'];
		$day_of_week = $this->_customdata['day_of_week'];
		$schoolterm = $this->_customdata['schoolterm'];
		$number_of_sessions = get_config('mod_istudy', 'number_of_sessions');
		$this_advisor_name = mod_istudydailyattend\teacher_info::get_advisor_name($advisorid);

		$week_day[1] = 'monday';
		$week_day[2] = 'tuesday';
		$week_day[3] = 'wednesday';
		$week_day[4] = 'thursday';
		$week_day[5] = 'friday';

		// Get the times for each session
		$summer = '';
		if ($schoolterm == 3) {$summer = 'summer_';}
		$time_session[] = array() ;
		for ($t = 1; $t <= $number_of_sessions; $t++) {
			$time_session[$t] = get_config('mod_istudy', $summer . $week_day[$day_of_week] . 'session' . $t);
		}

		$this_schoolterm[1] = 'fall';
		$this_schoolterm[2] = 'spring';
		$this_schoolterm[3] = 'summer';
		// The form starts here
		$mform->addElement('html', '<link rel="stylesheet" type="text/css" href="styles-enter_dailyattend_form.css">');

		$mform->addElement('html', '<h1>' . get_config('mod_istudy', 'schoolname') . '</h1>');
		$mform->addElement('html', '<h2>' . mod_istudydailyattend\utils::put_comma_in_name($this_advisor_name) . ' - Daily Attendance</h2><br>');
		$mform->addElement('html', '<h3>' . date("l, M d, Y ", $attendance_date) . '</h3><br>');
		// The table starts here
		$mform->addElement('html', '<table><tr><th width="2%" class="cellborderall">#</th><th width="10%" class="cellborderall">ID#</th>'.
			'<th width="25%" class="cellborderall">STUDENT NAME</th><th width="20%" class="cellborderall">Attendance</th>'.
			'<th width="23%" class="cellborderall">COURSE NAME</th><th width="13%" class="cellborderall">Notes</th>' .
			'<th width="7%" class="cellborderall">START DATE</th></tr>');

		// List all of the students in alphabetical order by period
		$count = 0;
		for ($j = 1; $j <= $number_of_sessions; $j++) {
			// **Print the current period and start & end time before the list of students
			$mform->addElement('html',  '<tr><td colspan="7" class="mycell"><center><b>PERIOD ' . $j . '</b> (' . $time_session[$j] . ')</center></td></tr>');
			$session_count = 0;
			$this_session = $j;
			$mystudents = mod_istudydailyattend\student_info::get_students_for_session($advisorid, $attendance_date, $schoolterm, $day_of_week, $this_session);
			if ($mystudents == 'None') {
				$mform->addElement('html',  '<tr><td colspan="7" class="nonecell"><center><b>None</b></center></td></tr>');
			}
			else {
				foreach ($mystudents as $mystudent) {
					$count = $count + 1;
					$session_count = $session_count + 1;
					// Check to see if teacher is the advisor for student - returns * if true or '' if false
					$my_advisory_student = mod_istudydailyattend\teacher_info::advisor_for_student($mystudent->student_id, $this_advisor_name);
					$studentname = $mystudent->student_name . ' (' . $mystudent->grade . ')' . $my_advisory_student;
					$student_id = 'student_id' . $count;
					$attendanceid = 'attendanceid' . $count;
					$courseid = 'course' . $count;
					$sessionid = 'session' . $count;
					$attendance_status = 'attendance_status' . $count;
					$attendance_notes = 'notes' . $count;

					// If the student starts next week, change the background color of the text to red.
					// Distance learning students (100% of the time) - change color of the text and background to yellow.
					$distance_learning_yes = '';
					$distance_learning_only = mod_istudydailyattend\utils::get_field_data($mystudent->student_id, mod_istudydailyattend\utils::get_fieldid('distancelearningonly'));
					if ($mystudent->starts_next_week) {
						$distance_learning_yes = ' style="background-color:#f3afaf;"';
					}
					else if ($distance_learning_only == 'Yes'){
						$distance_learning_yes = ' style="background-color:#FFF6EF;"';
					}

					// Daily attendance id - it's the primary key used in the database
					$mform->addElement('hidden', $attendanceid, $mystudent->id);
					$mform->setType($attendanceid, PARAM_INT);

					// Session number
					$mform->addElement('hidden', $sessionid, $this_session);
					$mform->setType($sessionid, PARAM_INT);

					// Course ID
					$mform->addElement('hidden', $courseid, $mystudent->course);
					$mform->setType($courseid, PARAM_INT);

					// Student ID
					$mform->addElement('hidden', $student_id, $mystudent->student_id);
					$mform->setType($student_id, PARAM_INT);

					// Print the row with count, student id, and student name (
					$mform->addElement('html', '<tr><td><center>' . $session_count . '</center></td>' .
						'<td' . $distance_learning_yes . '><center>'. $mystudent->school_id . '</center></td>'.
						'<td' . $distance_learning_yes . ' class="studentname">' . $studentname . '</td>');

					// Absence Radio Buttons start here - N/A = Not Applicable, A = absent, P = present, D = present through distance learning, T = Tardy
					$radioarray = array();
					$radioarray[] = $mform->createElement('radio', $attendance_status, '', 'N/A', 0, ['class' => 'radiobuttons-NA']);
					$radioarray[] = $mform->createElement('radio', $attendance_status, '', 'A', 1, ['class' => 'radiobuttons-absent']);
					$radioarray[] = $mform->createElement('radio', $attendance_status, '', 'T', 4, ['class' => 'radiobuttons-tardy']);
					$radioarray[] = $mform->createElement('radio', $attendance_status, '', 'P', 2, ['class' => 'radiobuttons-present']);
					$radioarray[] = $mform->createElement('radio', $attendance_status, '', 'D', 3, ['class' => 'radiobuttons-distance']);
					$mform->addElement('html', '<td>');
						$mform->addGroup($radioarray, 'radioar', '', array(' '), false);
						$mform->setDefault($attendance_status, $mystudent->attendance_status);
						$mform->setType($attendance_status, PARAM_INT);
					$mform->addElement('html', '</td>');

					// Name of course
					$mform->addElement('html', '<td>');
					$course_and_credits = $mystudent->course_name . ' [' . $mystudent->credits . ']';
					$mform->addElement('html', $course_and_credits);
					$mform->addElement('html', '</td>');

					// Notes
					$mform->addElement('html', '<td>');
						$mform->addElement('textarea', $attendance_notes, '', 'wrap="virtual" rows="1" cols="20"');
						$notes_default = $mystudent->notes;
						$mform->setDefault($attendance_notes, $notes_default);
						$mform->setType($attendance_notes, PARAM_TEXT);
					$mform->addElement('html', '</td>');

					// Start date
					$starting_next_week = '';
					if ($mystudent->starts_next_week) {
						$starting_next_week = ' style="background-color:#f3afaf;"';
					}
					$mform->addElement('html', '<td' . $starting_next_week . '>' . mod_istudydailyattend\utils::check_date($mystudent->startdate) . '</td>');
					$mform->addElement('html', '</tr>');
				}
			}
		}

		$mform->addElement('html', '</table>');
		$mform->addElement('html', '<h4>N/A = Not Applicable, A = Absent, T = Tardy, P = Present in person, D = Distance learner, * = Advisory student</h4>');

		// Hidden id number for sending the count back.
		$mform->addElement('hidden', 'teacher_id', $advisorid);
        $mform->setType('teacher_id', PARAM_INT);
		$mform->addElement('hidden', 'count', $count);
        $mform->setType('count', PARAM_INT);
		$mform->addElement('hidden', 'attendance_date', $attendance_date);
        $mform->setType('attendance_date', PARAM_INT);
		$mform->addElement('hidden', 'day_of_week', $day_of_week);
        $mform->setType('day_of_week', PARAM_INT);

		// Hidden id number for sending the data back.
		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

		// Add standard buttons, common to all modules.
		$mform->addElement('html', '<br><h3>  </h3>');
        $this->add_action_buttons(true, 'Save changes');
	}
}