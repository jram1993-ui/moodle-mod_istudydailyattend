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
 * Prints a particular instance of istudydailyattend
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
 require_once(__DIR__.'/search_criteria_form.php');

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

 $modulecontext = \context_module::instance($cm->id);

 $PAGE->set_url('/mod/istudydailyattend/view.php', array('id' => $cm->id));
 $PAGE->set_title(format_string($moduleinstance->name));
 $PAGE->set_heading(format_string($course->fullname));
 $PAGE->set_context($modulecontext);

if (mod_istudydailyattend\teacher_info::not_a_teacher_or_admin()) {
	echo '<h1>You are denied access to this page...</h1>';
	$url = new moodle_url('/course/view.php', array('id' => $course->id));
	redirect($url, 'Returning to Course Page', 5);
}

// Output starts here.
echo $OUTPUT->header();

$mform = new mod_istudydailyattend_search_criteria_form();
$mform->set_data($istudydailyattendid);
if ($mform->is_cancelled()) {
	// Cancelled forms redirect to the course main page.
	$url = new moodle_url('/course/view.php', array('id' => $course->id));
	redirect($url, 'Returning to Course Page', 1);
} else if ($data = $mform->get_data()) {
	$this_start_of_week = $data->start_of_week;
	$this_schoolterm = mod_istudydailyattend\utils::get_this_term($this_start_of_week);
	// Get the exact date (mm/dd/yy)
	$this_day_of_week = $data->day_of_week;
	$USER->attendance_dateda = mod_istudydailyattend\utils::get_attendance_date($this_start_of_week, $this_day_of_week);
	$USER->schooltermda = $this_schoolterm;
	$USER->day_of_weekda = $this_day_of_week;
	$USER->advisoridda = $data->advisorid;
	$USER->just_savedda = 'No';
	$enter_dailyattendurl = new moodle_url('/mod/istudydailyattend/enter_dailyattend.php', array('id' => $cm->id));
	redirect($enter_dailyattendurl);
} else {
	// Form has not been submitted or there was an error
	// Just display the form
	$mform->set_data(array('id' => $id));
}

$mform->DISPLAY();
echo $OUTPUT->footer();