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
 * Class utils
 *
 * @package    mod_istudydailyattend
 * @copyright  2024 Jesus Ramirez <jram1993@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class utils {

    // Set the time to 6:00 pm for this day.
    public static function set_to_12pm($mydatetoday) {
        // Adjust the day so that the time is set to 12:00 pm.
        // $mydatetoday = ((intval($mydatetoday/86400))*86400)+(86400/2);
        return $mydatetoday;
    }

    // Get the default month for the attendance cards
    public static function get_defaultmonth() {
        $mymonth[2] = get_config('mod_istudy', 'month02week01date');
        $mymonth[3] = get_config('mod_istudy', 'month03week01date');
        $mymonth[4] = get_config('mod_istudy', 'month04week01date');
        $mymonth[5] = get_config('mod_istudy', 'month05week01date');
        $mymonth[6] = get_config('mod_istudy', 'month06week01date');
        $mymonth[7] = get_config('mod_istudy', 'month07week01date');
        $mymonth[8] = get_config('mod_istudy', 'month08week01date');
        $mymonth[9] = get_config('mod_istudy', 'month09week01date');
        $mymonth[10] = get_config('mod_istudy', 'month10week01date');
        $mymonth[11] = get_config('mod_istudy', 'month11week01date');
        $default_month = 1;
        // Get the date  today.
        $mydatetoday = utils::set_to_12pm(time());
        // There are 86400 seconds in a day. Add 2 days so that the month will switch on the Wednesday of the first week of each month.
        $two_days = 86400+86400;
        for ($i = 2; $i <= 11; $i++) {
            $temp_date = utils::set_to_12pm(strtotime($mymonth[$i]));
            if ((($temp_date+$two_days) < $mydatetoday) AND ($temp_date > 0)) {
                $default_month = $i;
            }
        }
        return $default_month;
    }

    // Make sure that the date is not zero
    public static function check_date($submission) {
        if ($submission > 0) {
            return date("m-d-y", $submission);
        }
        else {
            return '';
        }
    }

    // **Replace the dash in the teacher name with a comma and space
    public static function put_comma_in_name($myteacher) {
        $postion = strrpos($myteacher,"-");
        $name_with_dash = substr($myteacher, $postion);
        $name_with_comma = ', ' . substr($name_with_dash,1);
        $new_name = str_replace($name_with_dash,$name_with_comma,$myteacher);
        return $new_name;
    }

    // Remove extra characters from the string
    public static function remove_extra_symbols($snip) {
        $snip = str_replace("'", "’", $snip); // remove apostrophe
        $snip = str_replace("\t", '', $snip); // remove tabs
        // $snip = str_replace("\n", '', $snip); // remove new lines
        // $snip = str_replace("\r", '', $snip); // remove carriage returns
        return $snip;
    }

    // **Get the exact date for the month, day, and year for the day chosen
    public static function get_attendance_date($this_start_of_week, $this_day_of_week) {
        // Monday
        $my_date_adjustment['1'] = 0;
        // Tuesday
        $my_date_adjustment['2'] = 1;
        // Wednesday
        $my_date_adjustment['3'] = 2;
        // Thursday
        $my_date_adjustment['4'] = 3;
        // Friday
        $my_date_adjustment['5'] = 4;

        $adjusted_date = utils::set_to_12pm(strtotime("+" . $my_date_adjustment[$this_day_of_week] . " day", $this_start_of_week));
        return $adjusted_date;
    }

    // **Return the term chosen - 1=Fall, 2=Spring, 3=Summer
    public static function get_term($term_choice) {
        $schoolterms[1] = 'fall';
        $schoolterms[2] = 'spring';
        $schoolterms[3] = 'summer';
        return $schoolterms[$term_choice];
    }

    // **Get the user_info_field id to use in searching user_info_data
    public static function get_fieldid($item_name) {
        global $DB;
        $item = $DB->get_record('user_info_field', ['shortname' => $item_name], 'id');
        return $item->id;
    }

    // **Search user_info_data for student data using the student id and user_info_field id
    public static function get_field_data($student_id, $itemid) {
        global $DB;
        if ($DB->record_exists('user_info_data', ['userid'=>$student_id, 'fieldid'=>$itemid])) {
            $search_results = $DB->get_record('user_info_data', ['userid'=>$student_id, 'fieldid'=>$itemid], 'data');
            return $search_results->data;
        }
        else {
            return '';
        }
    }

    // Get the course name using the course id
    public static function get_course_name($courseid) {
        global $DB;
        $table = 'istudycourse_hours';
        $select = "section = :section";
        $params =['section'=> $courseid];
        if ($this_course = $DB->get_record_select($table, $select, $params)) {
            return $this_course->subject;
        }
        // The course was not found so return None.
        return 'None';
    }

    // **Get Monday's date for each week for this school year.
    public static function dates_by_week() {
        $dates_by_week = [];
        $month[1] = 'month01';
        $month[2] = 'month02';
        $month[3] = 'month03';
        $month[4] = 'month04';
        $month[5] = 'month05';
        $month[6] = 'month06';
        $month[7] = 'month07';
        $month[8] = 'month08';
        $month[9] = 'month09';
        $month[10] = 'month10';
        $month[11] = 'month11';
        $week[1] = 'week01date';
        $week[2] = 'week02date';
        $week[3] = 'week03date';
        $week[4] = 'week04date';
        $count = 0;

		// Choose the month (#1-11)
		// There are 86400 seconds in a day. Add 5 or 7 days to find the date for Friday.
        $four_days = 86400+86400+86400+86400;
        $seven_days = 86400+86400+86400+86400+86400+86400+86400;
		for ($i = 1; $i <= 11; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $temp_monday_date_text = get_config('mod_istudy', $month[$i] . $week[$j]);
                $temp_monday_date = utils::set_to_12pm(strtotime($temp_monday_date_text));
                $temp_friday_date = $temp_monday_date + $four_days;
                $temp_friday_date_text = date("m/d/y", $temp_friday_date);
                if ($temp_monday_date > 0) {
                    // Each menu item will start with the week count.
                    $count++;
                    $week_label = 'Week ' . $count . ':  ';
                    if ($count <10) {
                        $week_label = 'Week 0' . $count . ':  ';
                    }
                    $dates_by_week[$temp_monday_date] = $week_label . $temp_monday_date_text . ' - ' . $temp_friday_date_text;
                    $last_saved_date =  $temp_monday_date;
                }
            }
        }
		for ($i = 1; $i <= 10; $i++) {
            $last_saved_date = $last_saved_date + $seven_days;
            $temp_monday_date =  $last_saved_date;
            $temp_monday_date_text = date("m/d/y", $temp_monday_date);
            $temp_friday_date = $temp_monday_date + $four_days;
            $temp_friday_date_text = date("m/d/y", $temp_friday_date);
            // Each menu item will start with the week count.
            $count++;
            $week_label = 'Week ' . $count . ':  ';
            $dates_by_week[$temp_monday_date] = $week_label . $temp_monday_date_text . ' - ' . $temp_friday_date_text;
        }
        return $dates_by_week;
    }

     // Return the term according to the date chosen (fall=1, spring=2, and summer=3)
    public static function get_this_term($date_chosen) {
        // Get the starting dates of each week for months 10 and 11.
        for ($m = 10; $m <= 11; $m++) {
            for ($w = 1; $w <= 4; $w++) {
                $week_start_date[$m][$w] = utils::set_to_12pm(strtotime(get_config('mod_istudy', 'month' . $m . 'week0' . $w . 'date')));
                $week_points[$m][$w] = get_config('mod_istudy', 'month' . $m . 'week0' . $w );
            }
        }

        // Find the last week of school.
        $last_week_found = false;
        $last_month = 11;
        $last_week = 4;
        $one_day = 86400;
        $four_days = 86400+86400+86400+86400;
        for ($m = 11; $m >= 10; $m--) {
            for ($w = 4; $w >= 1; $w--) {
                if (($week_points[$m][$w] > 0) and ($last_week_found == false)){
                    $last_month = $m;
                    $last_week = $w;
                    $last_week_found = true;
                }
            }
        }
        $spring_start_date_text = get_config('mod_istudy', 'spring_start_date');
        $spring_start_date = utils::set_to_12pm(strtotime($spring_start_date_text)) - $one_day;
        $last_school_day = $week_start_date[$last_month][$last_week]+ $four_days;

        // The default term is 1 (fall).
        $this_term = 1;
        // Check the date against the spring start date.
        if ($date_chosen > $spring_start_date) {
            $this_term = 2;
        }
        // Check the date against the summer start date.
        if ($date_chosen > $last_school_day) {
            $this_term = 3;
        }
        return $this_term;
    }

    // **Get Monday's date for each week for this school year.
    public static function get_default_week() {
        $month[1] = 'month01';
        $month[2] = 'month02';
        $month[3] = 'month03';
        $month[4] = 'month04';
        $month[5] = 'month05';
        $month[6] = 'month06';
        $month[7] = 'month07';
        $month[8] = 'month08';
        $month[9] = 'month09';
        $month[10] = 'month10';
        $month[11] = 'month11';
        $week[1] = 'week01date';
        $week[2] = 'week02date';
        $week[3] = 'week03date';
        $week[4] = 'week04date';

        // Find the Monday date for this week.
        $date_today = date("l");
		if ($date_today == 'Monday') {
			$monday_this_week = utils::set_to_12pm(strtotime("today"));
		}
		else {
			$monday_this_week = utils::set_to_12pm(strtotime("last Monday"));
		}
        // Adjust the default week if there are no classes this week.
        for ($i = 1; $i <= 11; $i++) {
            for ($j = 1; $j <= 4; $j++) {
                $temp_monday_date_text = get_config('mod_istudy', $month[$i] . $week[$j]);
                $temp_monday_date = utils::set_to_12pm(strtotime($temp_monday_date_text));
                if ($temp_monday_date >= $monday_this_week) {
                    return $temp_monday_date;
                }
            }
        }
        return $monday_this_week;
    }
}