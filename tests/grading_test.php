<?php
// This file is part of Moodle - http://moodle.org/.
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

defined('MOODLE_INTERNAL') or die;

/**
 * Grading test case.
 *
 * @package    mod_dataform
 * @category   phpunit
 * @group      mod_dataform_grading
 * @group      mod_dataform
 * @copyright  2014 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dataform_grading_testcase extends advanced_testcase {

    protected $course;
    protected $teacher;
    protected $student1;
    protected $student2;

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        global $DB;

        $this->resetAfterTest();

        // Create a course we are going to add a data module to.
        $this->course = $this->getDataGenerator()->create_course();
        $courseid = $this->course->id;

        $roles = $DB->get_records_menu('role', array(), '', 'shortname,id');

        // Teacher
        $user = $this->getDataGenerator()->create_user(array('username' => 'teacher'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles['editingteacher']);
        $this->teacher = $user;

        // Student 1
        $user = $this->getDataGenerator()->create_user(array('username' => 'student1'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles['student']);
        $this->student1 = $user;

        // Student 2
        $user = $this->getDataGenerator()->create_user(array('username' => 'student2'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles['student']);
        $this->student2 = $user;
    }

    /**
     * Set up function. In this instance we are setting up dataform
     * entries to be used in the unit tests.
     */
    public function test_calculated_grading() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Course
        $courseid = $this->course->id;

        // Dataform
        $params = array(
            'course' => $courseid,
            'grade' => 100,
            'gradecalc' => '##numentries##',
        );
        $dataform = $this->getDataGenerator()->create_module('dataform', $params);
        $df = mod_dataform_dataform::instance($dataform->id);

        // Add a view
        $view = $df->view_manager->add_view('aligned');
        // Get an entry manager
        $entryman = $view->entry_manager;

        // Fetch the grade item
        $params = array(
            'itemtype' => 'mod',
            'itemmodule' => 'dataform',
            'iteminstance' => $df->id,
            'courseid' => $courseid,
            'itemnumber' => 0
        );
        $gitem = grade_item::fetch($params);

        // Student 1 grade
        $grade = $gitem->get_grade($this->student1->id, false);
        $this->assertEquals(null, $grade->finalgrade);

        // Add 5 entries.
        $this->setUser($this->student1);
        $eids = range(-1, -5, -1);
        list(, $eids) = $entryman->process_entries('update', $eids, (object) array('submitbutton_save' => 'Save'), true);

        // Grade should be 5.
        $grade = $gitem->get_grade($this->student1->id, false);
        $this->assertEquals(5, $grade->finalgrade);

        // Delete 1 entry.
        $entrytodelete = reset($eids);
        list(, $eids) = $entryman->process_entries('delete', $entrytodelete, null, true);

        // Grade should be 4.
        $grade = $gitem->get_grade($this->student1->id, false);
        $this->assertEquals(4, $grade->finalgrade);

        $this->setAdminUser();
        $df->delete();
    }
}
