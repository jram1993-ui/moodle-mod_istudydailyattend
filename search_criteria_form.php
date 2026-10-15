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
 * The main istudydailyattend configuration form
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_istudydailyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

/**
 * Module instance settings form
 *
 * @package    mod_istudydailyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_istudydailyattend_search_criteria_form extends moodleform
{

    public function definition()
	{
		$mform = $this->_form;
		$mform->addElement('html', '<link rel="stylesheet" type="text/css" href="styles-search_criteria_form.css">');
		$mform->addElement('html', '<h3>Daily Attendance</h3><br><table><tr><th colspan="6">');
		$mform->addElement('html', '<h4>Choose the day of the week</h4><h4>you are working on</h4></th></tr>');

		// Choose the week.
		$date_today = date("l");
		$my_day_today = [
			"Saturday" => 1,
			"Sunday" => 1,
			"Monday" => 1,
			"Tuesday" => 2,
			"Wednesday" => 3,
			"Thursday" => 4,
			"Friday" => 5
		];

		$weeks_for_menu = mod_istudydailyattend\utils::dates_by_week();
		$default_week = mod_istudydailyattend\utils::get_default_week();
		$mform->addElement('html', '<tr><td class="mycell2">Choose the Week</td><td colspan="3">');
		$select = $mform->addElement('select', 'start_of_week', '', $weeks_for_menu);
		// Set default to first advisor on the list
		$select->setSelected($default_week);
		$mform->addElement('html', '</td></tr>');

		// Choose to print student schedule only or all reports
		$mform->addElement('html', '<tr><td class="mycell2" valign="top" rowspan="2">Choose the day of the week</td>');
		$mform->addElement('html', '</td><td class="mycell">');
		$mform->addElement('radio', 'day_of_week', '','Monday', 1, ['class' => 'myradiobuttons']);
		$mform->addElement('html', '</td><td class="mycell">');
		$mform->addElement('radio', 'day_of_week', '','Tuesday', 2, ['class' => 'myradiobuttons']);
		$mform->addElement('html', '</td><td class="mycell">');
		$mform->addElement('radio', 'day_of_week', '','Wednesday', 3, ['class' => 'myradiobuttons']);
		$mform->addElement('html', '</td><tr><td class="mycell">');
		$mform->addElement('radio', 'day_of_week', '','Thursday', 4, ['class' => 'myradiobuttons']);
		$mform->addElement('html', '</td><td class="mycell">');
		$mform->addElement('radio', 'day_of_week', '','Friday', 5, ['class' => 'myradiobuttons']);
		$mform->addElement('html', '</td><td></td></tr>');

		$mform->setDefault('day_of_week', $my_day_today[$date_today]);
		$mform->setType('day_of_week', PARAM_INT);

		// Choose the Advisory Teacher
		$advisors = mod_istudydailyattend\teacher_info::get_advisors();
		$mform->addElement('html', '<tr><td class="mycell2">Choose the Advisor</td><td colspan="6">');
		$select = $mform->addElement('select', 'advisorid', '', $advisors);
		// Set default to first advisor on the list
		$mform->addElement('html', '</td></tr>');

		$mform->addElement('html', '</table>');

		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
		$mform->addElement('html', '<br><h3>  </h3>');

		// Add standard buttons, common to all modules.
		$mform->addElement('html', '<div class="buttonsetup">');
			$this->add_action_buttons($cancel = true, $submitlabel='Submit');
		$mform->addElement('html', '</div></html>');
    }
}