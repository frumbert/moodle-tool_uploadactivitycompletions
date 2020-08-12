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
 * Links and settings
 *
 * Class containing a set of helpers, based on admin\tool\uploadcourse by 2013 Frédéric Massart.
 *
 * @package    tool_uploadactivitycompletions
 * @copyright  2020 Tim St.Clair (https://github.com/frumbert/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/mod/page/lib.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->libdir . '/enrollib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Class containing a set of helpers.
 *
 * @package   tool_uploadactivitycompletions
 * @copyright 2020 Tim St.Clair (https://github.com/frumbert/)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tool_uploadactivitycompletions_helper {
    /**
     * Validate we have the minimum info to create/update course result
     *
     * @param object $record The record we imported
     * @return bool true if validated
     */
    public static function validate_import_record($record) {
        if (empty($record->coursevalue)) {
            return false;
        }
        if (empty($record->uservalue)) {
            return false;
        }
        if (empty($record->sectionname)) {
            return false;
        }
        if (empty($record->activityname)) {
            return false;
        }
        return true;
    }

    /**
     * Retrieve a course by its required column name.
     *
     * @param string $field name (e.g. idnumber, shortname)
     * @return object course or null
     */
    public static function get_course_by_field($field, $value) {
        global $DB;

        $courses = $DB->get_records('course', [$field => $value]);

        if (count($courses) == 1) {
            $course = array_pop($courses);
            return $course;
        } else {
            return null;
        }
    }

    /**
     * Retrieve a user by its required column name.
     *
     * @param string $field name (e.g. idnumber, username)
     * @return object course or null
     */
    public static function get_user_by_field($field, $value) {
        global $DB;

        $users = $DB->get_records('user', [$field => $value]);

        if (count($users) == 1) {
            $users = array_pop($users);
            return $users;
        } else {
            return null;
        }
    }

    // given a course, section name and activity name, return the modinfo instance (cm)
    public static function find_activity_in_section($course,$section_name,$activity_name) {
        $modinfo = get_fast_modinfo($course);
        $cminfo = $modinfo->get_cms();
        foreach ($cminfo as $inst) {
            $section = $inst->get_section_info();
            if (strcasecmp($section_name, $section->name) == 0 && strcasecmp($activity_name, $inst->name) == 0) {
                return $inst;
            }
        }
        return null;
    }

//     public static function get_course_section($course, $name) {
//         global $DB;

//         // topic 0 might or might not have a name, so look it up by index
//         if ($name === "0" && $record = $DB->get_records('course_sections', ['course' => $course->id, 'section' => 0], 'section', 'id')) {
//             return array_pop($record);

//         // otherwise match section by its name (case insensitive)
//         } else if ($record = $DB->get_records('course_sections', ['course' => $course->id, 'name' => $name], 'section', 'id')) {
//             return array_pop($record);

//         } else {
//             return null;
//         }
//     }

//     // return the instance of the activity with the specified name in the course section
//     public static function get_section_activity($course, $section, $find) {
//         global $DB;

//         $mi = get_fast_modinfo($course);
// echo "<pre>";
// var_dump($mi);
// echo "</pre>";
// exit;

//         // all activities in this section that have completion enabled 
//         $sql = '
//             SELECT cm.id, cm.instance, m.name
//             FROM {course_modules} cm INNER JOIN {modules} m
//             ON m.id = cm.module
//             WHERE cm.course = ?
//             AND cm.section = ?
//             AND cm.completion > 0
//         ';

//         // select from table mdl_{$module} where name=$find to find cm
//         if ($rows = $DB->get_records_sql($sql, [$course->id, $section->id])) {
//             foreach ($rows as $row) {
//                 if ($DB->record_exists($row->name, ['name' => $find, 'course' => $course->id])) {
//                     $data = get_course_and_cm_from_instance($row->instance, $row->name, $course->id);
//                     return $data[1];
//                 }
//             }
//         }
//         return false;
//     }

    /**
     * Update page activity viewed
     *
     * This will show a developer debug warning when run in Moodle UI because
     * of the function set_module_viewed in completionlib.php details copied below:
     *
     * Note that this function must be called before you print the page header because
     * it is possible that the navigation block may depend on it. If you call it after
     * printing the header, it shows a developer debug warning.
     *
     * @param object $record Validated Imported Record
     * @param integer $studentrole role value of a student
     * @return object $response contains details of processing
     */
    public static function mark_activity_as_completed($record, $studentrole) {
        global $DB, $USER;

        $response = new \stdClass();
        $response->added = 0;
        $response->skipped = 0;
        $response->error = 0;
        $response->message = null;

        // Get the course record.
        if ($course = self::get_course_by_field($record->coursefield, $record->coursevalue)) {
            $response->course = $course;

            // get the user record
            if ($user =self::get_user_by_field($record->userfield, $record->uservalue)) {
                $response->user = $user;

                if ($cm = self::find_activity_in_section($course,$record->sectionname,$record->activityname)) {

               // // get the section (topic) in the course
                // if ($section = self::get_course_section($course, $record->sectionname)) {

                //     // get the cm id for this named activity
                //     if ($cm = self::get_section_activity($course, $section, $record->activityname)) {

                        // ensure the user is enrolled in this course
                        enrol_try_internal_enrol($course->id, $user->id, $studentrole->id);

                        // get the current completion state to avoid re-completion
                        $completion = new completion_info($course);
                        $currentstate = $completion->get_data($cm, false, $user->id, null);

                        // if the user can't override completion we need to bail
                        if (!$completion->user_can_override_completion($USER)) {
                            $response->message = 'Configured user unable to override completion in course ' . $course->fullname;
                            $response->skipped = 1;
                            $response->added = 0;
                        } else {
                            // override completion of this activity
                            $completion->update_state($cm, COMPLETION_COMPLETE, $user->id, true);

                            // $newstate = $completion->get_data($cm, false, $user->id, null);
                            // var_dump($currentstate, $newstate);
                            // exit;

                            $response->message = 'Activity "' . $record->activityname . '" in topic "' . $record->sectionname . '" was completed on behalf of user.';
                            $response->skipped = 0;
                            $response->added = 1;
                        }
                    // } else {
                    //     $response->message = 'Unable to find activity "' . $record->activityname . '" in course "' . $course->fullname . '"';
                    //     $response->skipped = 1;
                    //     $response->added = 0;
                    // }
                } else {
                    $response->message = 'Unable to find activity "' . $record->activityname . '" in topic "' . $record->sectionname . '" in course "' . $course->fullname . '"';
                    $response->skipped = 1;
                    $response->added = 0;
                }
            } else {
                $response->message = 'Unable to find user matching "' . $record->uservalue . '"';
                $response->skipped = 1;
                $response->added = 0;
            }
        } else {
            $response->message = 'Unable to find course matching "' . $record->coursevalue . '"';
            $response->skipped = 1;
            $response->added = 0;
        }
        return $response;
    }
}