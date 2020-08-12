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
 * Importer tests
 *
 * @package    tool_uploadactivitycompletions
 * @copyright  2020 Tim St.Clair (https://github.com/frumbert/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Importer tests
 *
 * @package    tool_uploadactivitycompletions
 * @copyright  2020 Tim St.Clair (https://github.com/frumbert/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
class tool_uploadactivitycompletions_importer_testcase extends advanced_testcase {

    /**
     * course
     *
     * @var mixed
     */
    protected $course;

    /**
     * page
     *
     * @var mixed
     */
    protected $page;

    /**
     * student
     *
     * @var mixed
     */
    protected $student;

    /**
     * cm
     *
     * @var mixed
     */
    protected $cm;

    /**
     * Prepares things before this test case is initialised
     * @return void
     */
    public static function setUpBeforeClass() {
        global $CFG;
        require_once($CFG->libdir . '/csvlib.class.php');
        require_once($CFG->libdir . '/completionlib.php');
        require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
    }

    /**
     * Setup testcase.
     * @return void
     */
    public function setUp() {
        $this->resetAfterTest();

        // Setup test course.
        $this->course = $this->getDataGenerator()->create_course(array(
            'fullname' => 'C1b49aa30-e719-11e6-9835-f723b46a2688 Full Name',
            'shortname' => 'C1b49aa30-e719-11e6-9835-f723b46a2688 Short Name',
            'idnumber' => 'C1b49aa30-e719-11e6-9835-f723b46a2688',
            'enablecompletion' => 1
        ));

        // Setup test page.
        $this->page = $this->getDataGenerator()->create_module('page', array('course' => $this->course->id),
                array('completion' => 2, 'completionview' => 1, 'idnumber' => 'C1b49aa30-e719-11e6-9835-f723b46a2688'));

        // Setup test user.
        $this->student = self::getDataGenerator()->create_user((array(
            'username' => 'student1',
            'firstname' => 'Student1',
            'lastname' => 'Student1'
        )));

        $this->cm = get_coursemodule_from_instance('page', $this->page->id);

        $criterion = new completion_criteria_activity();

        // Criteria for course.
        $criteriadata = new \stdClass();
        $criteriadata->id = $this->course->id;
        $criteriadata->criteria_activity = array($this->cm->id => 1);

        $criterion->update_config($criteriadata);

        // Handle overall aggregation.
        $aggdata = array(
            'course'        => $this->course->id,
            'criteriatype'  => null
        );
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ACTIVITY;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_COURSE;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();

        $aggdata['criteriatype'] = COMPLETION_CRITERIA_TYPE_ROLE;
        $aggregation = new completion_aggregation($aggdata);
        $aggregation->setMethod(COMPLETION_AGGREGATION_ALL);
        $aggregation->save();
    }

    /**
     * Confirms that a single course and single activity can be created
     *
     * @return void
     */
    public function test_create() {
        $source = __DIR__.'/fixtures/onecourseresult.csv';
        $content = file_get_contents($source);

        $importer = new tool_uploadactivitycompletions_importer($content, null, null);
        $importid = $importer->get_importid();

        $importer = new tool_uploadactivitycompletions_importer(null, null, null, $importid, null);
        $importer->execute();

        // Check completion status.
        // Get the current state for the activity and user.
        $completion = new completion_info($this->course);
        $currentstate = $completion->get_data($this->cm, false, $this->student->id, null);

        $this->assertEquals(COMPLETION_VIEWED, $currentstate->viewed);
    }

    /**
     * Confirms an error text is returned if an none existent user exists
     *
     * @return void
     */
    public function test_invalid_user() {
        $source = __DIR__.'/fixtures/onecourseresult_nouser.csv';
        $content = file_get_contents($source);

        $importer = new tool_uploadactivitycompletions_importer($content, null, null);
        $importid = $importer->get_importid();

        $importer = new tool_uploadactivitycompletions_importer(null, null, null, $importid, null);
        $results = $importer->execute(new tool_uploadactivitycompletions_tracker(tool_uploadactivitycompletions_tracker::OUTPUT_PLAIN, false));

        $this->assertRegexp("/User with username student999 does not exist/", $results);
    }


    /**
     * Confirms an error text is returned if an none existent course exists
     *
     * @return void
     */
    public function test_invalid_course() {
        $source = __DIR__.'/fixtures/onecourseresult_nocourse.csv';
        $content = file_get_contents($source);

        $importer = new tool_uploadactivitycompletions_importer($content, null, null);
        $importid = $importer->get_importid();

        $importer = new tool_uploadactivitycompletions_importer(null, null, null, $importid, null);
        $results = $importer->execute(new tool_uploadactivitycompletions_tracker(tool_uploadactivitycompletions_tracker::OUTPUT_PLAIN, false));

        $this->assertRegexp("/Course with idnumber B1b49aa30-e719-11e6-9835-f723b46a2688 does not exist/", $results);
    }

    /**
     * Confirms an error text is returned if empty CSV file
     *
     * @return void
     */
    public function test_empty_csv() {
        $source = __DIR__.'/fixtures/empty.csv';
        $content = file_get_contents($source);

        $importer = new tool_uploadactivitycompletions_importer($content, null, null);
        $error = $importer->get_error();

        $this->assertTrue($error != "", "error".$error);
    }

    /**
     * Confirms an error text is returned if not enough columns in CSV file
     *
     * @return void
     */
    public function test_not_enough_columns() {
        $source = __DIR__.'/fixtures/notenoughcolumns.csv';
        $content = file_get_contents($source);

        $importer = new tool_uploadactivitycompletions_importer($content, null, null);
        $error = $importer->get_error();

        $this->assertTrue($error != "", "error".$error);
    }

}