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
 * Prints a particular instance of istudy
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_istudydailyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/enter_dailyattend_form.php');

global $DB, $USER;

/**
 * Course module id.
 */
$id = optional_param('id', 0, PARAM_INT);

/**
 * Activity instance id.
 */
$i = optional_param('i', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('istudydailyattend', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('istudydailyattend', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('istudydailyattend', array('id' => $i), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('istudydailyattend', $moduleinstance->id, $course->id, false, MUST_EXIST);
}
$istudydailyattendid = $cm->instance;

require_login($course, true, $cm);

$context = \context_module::instance($cm->id);
$attendance_date = $USER->attendance_dateda;
$day_of_week = $USER->day_of_weekda;
$schoolterm = $USER->schooltermda;
$myteacher = $USER->advisoridda;

// Page setup
$PAGE->set_url('/mod/istudy/enter_dailyattend.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Output starts here.
echo $OUTPUT->header();

// Display form.
$formparams = array('cm' => $cm, 'modcontext' => $context, 'myteacher' => $myteacher, 'attendance_date' => $attendance_date, 'day_of_week' => $day_of_week, 'schoolterm' => $schoolterm);
$mform = new mod_istudydailyattend_enter_dailyattend_form(null, $formparams);
$mform->set_data($istudydailyattendid);
if ($mform->is_cancelled()) {
	// Cancelled forms redirect to the course main page.
	$url = new moodle_url('/course/view.php', array('id' => $course->id));
	redirect($url);
	return;
} else if ($data = $mform->get_data()) {
	// Get the form data
	$data = $mform->get_data();

	// Get the course data
	$submission = new stdClass();
	$submission->timemodified = time();
	$submission->teacher_id = $data->teacher_id;
	$submission->attendance_date = $data->attendance_date;
	$submission->day_of_week = $data->day_of_week;
	$student_count = $data->count;
	if ($student_count > 0) {
		for ($count = 1; $count <= $student_count; $count++) {
			// Get the attendance data
			$submission->student_id = $data->{'student_id'.$count};
			$submission->course = $data->{'course'.$count};
			$submission->session = $data->{'session'.$count};
			$submission->attendance_status = $data->{'attendance_status'.$count};
			$submission->notes = mod_istudydailyattend\utils::remove_extra_symbols($data->{'notes'.$count});
			$submission->id = $data->{'attendanceid'.$count};
			try {
				$transaction = $DB->start_delegated_transaction();
				// The record already exists
				$table = 'istudy_dailyattend_enter';
				$select = "course = :course AND teacher_id = :teacher_id AND student_id = :student_id AND session = :session AND attendance_date = :attendance_date";
				$params =['course'=> $submission->course, 'teacher_id'=>$submission->teacher_id, 'student_id'=>$submission->student_id,
					'session'=>$submission->session, 'attendance_date'=>$submission->attendance_date];
				if ($studentattendanceid = $DB->get_record_select($table, $select, $params)) {
						$submission->id = $studentattendanceid->id;
						$DB->update_record('istudy_dailyattend_enter', $submission);
				}
				// The record is new
				else {
					$DB->insert_record('istudy_dailyattend_enter', $submission);
				}
				// Assuming that one of the inserts worked, we get to the following line.
				$transaction->allow_commit();
			} catch(Exception $e) {
				$transaction->rollback($e);
				$this_student = mod_istudydailyattend\student_info::get_single_student($submission->student_id);
				$student_with_error = 'The entry for ' . $this_student->firstname . ' ' . $this_student->lastname . ' was not saved. An error was found in the assignment details.';
				echo $OUTPUT->notification($student_with_error);
			}
		}
		$USER->just_savedda = 'Yes';
		$enter_dailyattendurl = new moodle_url('/mod/istudydailyattend/enter_dailyattend.php', array('id' => $cm->id));
		redirect($enter_dailyattendurl, 'ATTENDANCE IS BEING SAVED', 0);
	}
	else {
		echo $OUTPUT->notification('There are no students to save');
	}

} else {
	// Form has not been submitted or there was an error
	// Just display the form
	$mform->set_data(array('id' => $id));
}

// Finish the page.
if ($USER->just_savedda == 'Yes') {
	echo $OUTPUT->notification('Changes saved', 'notifysuccess');
	$USER->just_savedda == 'No';
}
$mform->display();
echo $OUTPUT->footer();