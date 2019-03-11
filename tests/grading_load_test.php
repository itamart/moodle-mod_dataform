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

defined('MOODLE_INTERNAL') or die;

/**
 * Grading test case.
 *
 * @package    mod_dataform
 * @category   phpunit
 * @group      mod_dataform_grading_load_test
 * @group      mod_dataform_grading
 * @group      mod_dataform
 * @copyright  2018 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dataform_grading_load_testcase extends advanced_testcase {

    protected $course;
    protected $roles;
    protected $students;
    

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        global $DB;

        $this->resetAfterTest();

        // Reset dataform local cache.
        \mod_dataform_instance_store::unregister();

        // Create a course we are going to add a data module to.
        $this->course = $this->getDataGenerator()->create_course();
        $courseid = $this->course->id;

        $this->roles = $DB->get_records_menu('role', array(), '', 'shortname,id');
    }

    /**
     * 
     */
    public function test_calculated_grading_1_student_1_field() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Course.
        $courseid = $this->course->id;

        // Create students.
        $this->students = [];
        for ($i = 1; $i <= 1; $i++) {
            $user = $this->getDataGenerator()->create_user(array('username' => "student$i"));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $this->roles['student']);
            $this->students[$i] = $user;
        }

        // Dataform.
        $params = array(
            'name' => 'Calculated Grade Dataform',
            'course' => $courseid,
            'grade' => 100,
            'gradeitems' => serialize(array(0 => array('ca' => '##:select##'))),
        );
        $dataform = $this->getDataGenerator()->create_module('dataform', $params);
        $df = mod_dataform_dataform::instance($dataform->id);

        // Add select field.
        $field = $df->field_manager->add_field('select');
        $field->update((object) array('name' => 'select', 'param1' => "20\n40\n60"));

        // Add a view.
        $view = $df->view_manager->add_view('aligned');

        // Get an entry manager.
        $entryman = $view->entry_manager;

        // Add entry per student with data.
        $dataformgenerator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        foreach ($this->students as $student) {
            $data = [
                'dataid' => $dataform->id,
                'userid' => $student->id,
                'select_selected' => 1,
            ];

            $dataformgenerator->create_entry($data);
        }

        // Fetch the grade item.
        $gitem = $this->fetch_grade_item($df->id, 0);

        // Simulate execution of the scheduled grading task.
        $gradingtask = new \mod_dataform\task\grade_update();
        $gradingtask->execute();

        // Grade should be 20.
        $grade = $gitem->get_grade($this->students[1]->id, false);
        $this->assertEquals(20, $grade->finalgrade);
    }

    /**
     * 
     */
    public function test_calculated_grading_1_student_5_fields() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Course.
        $courseid = $this->course->id;

        // Create students.
        $this->students = [];
        for ($i = 1; $i <= 1; $i++) {
            $user = $this->getDataGenerator()->create_user(array('username' => "student$i"));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $this->roles['student']);
            $this->students[$i] = $user;
        }

        // Dataform.
        $formula = '##:select1##+##:select2##+##:select3##+##:select4##+##:select5##/2';
        $params = array(
            'name' => 'Calculated Grade Dataform',
            'course' => $courseid,
            'grade' => 100,
            'gradeitems' => serialize(array(0 => array('ca' => $formula))),
        );
        $dataform = $this->getDataGenerator()->create_module('dataform', $params);
        $df = mod_dataform_dataform::instance($dataform->id);

        // Add select fields.
        for ($i = 1; $i <= 5; $i++) {
            $field = $df->field_manager->add_field('select');
            $field->update((object) array('name' => "select$i", 'param1' => "20\n40\n60"));
        }

        // Add a view.
        $view = $df->view_manager->add_view('aligned');

        // Get an entry manager.
        $entryman = $view->entry_manager;

        // Add entry per student with data.
        $dataformgenerator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        foreach ($this->students as $student) {
            $data = [
                'dataid' => $dataform->id,
                'userid' => $student->id,
                'select1_selected' => 1,
                'select2_selected' => 1,
                'select3_selected' => 1,
                'select4_selected' => 1,
                'select5_selected' => 1,
            ];

            $dataformgenerator->create_entry($data);
        }

        // Fetch the grade item.
        $gitem = $this->fetch_grade_item($df->id, 0);

        // Simulate execution of the scheduled grading task.
        $gradingtask = new \mod_dataform\task\grade_update();
        $gradingtask->execute();

        // Grade should be 20.
        $grade = $gitem->get_grade($this->students[1]->id, false);
        $this->assertEquals(90, $grade->finalgrade);
    }

    /**
     * 
     */
    public function test_calculated_grading_100_1_field() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Course.
        $courseid = $this->course->id;

        // Create students.
        $this->students = [];
        for ($i = 1; $i <= 100; $i++) {
            $user = $this->getDataGenerator()->create_user(array('username' => "student$i"));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $this->roles['student']);
            $this->students[$i] = $user;
        }

        // Dataform.
        $params = array(
            'name' => 'Calculated Grade Dataform',
            'course' => $courseid,
            'grade' => 100,
            'gradeitems' => serialize(array(0 => array('ca' => '##:select##'))),
        );
        $dataform = $this->getDataGenerator()->create_module('dataform', $params);
        $df = mod_dataform_dataform::instance($dataform->id);

        // Add select field.
        $field = $df->field_manager->add_field('select');
        $field->update((object) array('name' => 'select', 'param1' => "20\n40\n60"));

        // Add a view.
        $view = $df->view_manager->add_view('aligned');

        // Get an entry manager.
        $entryman = $view->entry_manager;

        // Add entry per student with data.
        $dataformgenerator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        foreach ($this->students as $student) {
            $data = [
                'dataid' => $dataform->id,
                'userid' => $student->id,
                'select_selected' => 1,
            ];

            $dataformgenerator->create_entry($data);
        }

        // Fetch the grade item.
        $gitem = $this->fetch_grade_item($df->id, 0);

        // Simulate execution of the scheduled grading task.
        $gradingtask = new \mod_dataform\task\grade_update();
        $gradingtask->execute();

        // Grade should be 20.
        $grade = $gitem->get_grade($this->students[1]->id, false);
        $this->assertEquals(20, $grade->finalgrade);
    }

    /**
     * 
     */
    public function test_calculated_grading_100_student_5_fields() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Course.
        $courseid = $this->course->id;

        // Create students.
        $this->students = [];
        for ($i = 1; $i <= 100; $i++) {
            $user = $this->getDataGenerator()->create_user(array('username' => "student$i"));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $this->roles['student']);
            $this->students[$i] = $user;
        }

        // Dataform.
        $formula = '##:select1##+##:select2##+##:select3##+##:select4##+##:select5##/2';
        $params = array(
            'name' => 'Calculated Grade Dataform',
            'course' => $courseid,
            'grade' => 100,
            'gradeitems' => serialize(array(0 => array('ca' => $formula))),
        );
        $dataform = $this->getDataGenerator()->create_module('dataform', $params);
        $df = mod_dataform_dataform::instance($dataform->id);

        // Add select fields.
        for ($i = 1; $i <= 5; $i++) {
            $field = $df->field_manager->add_field('select');
            $field->update((object) array('name' => "select$i", 'param1' => "20\n40\n60"));
        }

        // Add a view.
        $view = $df->view_manager->add_view('aligned');

        // Get an entry manager.
        $entryman = $view->entry_manager;

        // Add entry per student with data.
        $dataformgenerator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        foreach ($this->students as $student) {
            $data = [
                'dataid' => $dataform->id,
                'userid' => $student->id,
                'select1_selected' => 1,
                'select2_selected' => 1,
                'select3_selected' => 1,
                'select4_selected' => 1,
                'select5_selected' => 1,
            ];

            $dataformgenerator->create_entry($data);
        }

        // Fetch the grade item.
        $gitem = $this->fetch_grade_item($df->id, 0);

        // Simulate execution of the scheduled grading task.
        $gradingtask = new \mod_dataform\task\grade_update();
        $gradingtask->execute();

        // Grade should be 20.
        $grade = $gitem->get_grade($this->students[1]->id, false);
        $this->assertEquals(90, $grade->finalgrade);
    }

    /**
     * 
     */
    public function test_calculated_grading_1000_1_field() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Course.
        $courseid = $this->course->id;

        // Create students.
        $this->students = [];
        for ($i = 1; $i <= 1000; $i++) {
            $user = $this->getDataGenerator()->create_user(array('username' => "student$i"));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $this->roles['student']);
            $this->students[$i] = $user;
        }

        // Dataform.
        $params = array(
            'name' => 'Calculated Grade Dataform',
            'course' => $courseid,
            'grade' => 100,
            'gradeitems' => serialize(array(0 => array('ca' => '##:select##'))),
        );
        $dataform = $this->getDataGenerator()->create_module('dataform', $params);
        $df = mod_dataform_dataform::instance($dataform->id);

        // Add select field.
        $field = $df->field_manager->add_field('select');
        $field->update((object) array('name' => 'select', 'param1' => "20\n40\n60"));

        // Add a view.
        $view = $df->view_manager->add_view('aligned');

        // Get an entry manager.
        $entryman = $view->entry_manager;

        // Add entry per student with data.
        $dataformgenerator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        foreach ($this->students as $student) {
            $data = [
                'dataid' => $dataform->id,
                'userid' => $student->id,
                'select_selected' => 1,
            ];

            $dataformgenerator->create_entry($data);
        }

        // Fetch the grade item.
        $gitem = $this->fetch_grade_item($df->id, 0);

        // Simulate execution of the scheduled grading task.
        $gradingtask = new \mod_dataform\task\grade_update();
        $gradingtask->execute();

        // Grade should be 20.
        $grade = $gitem->get_grade($this->students[1]->id, false);
        $this->assertEquals(20, $grade->finalgrade);
    }

    /**
     * 
     */
    public function test_calculated_grading_1000_student_5_fields() {
        global $DB;

        $this->resetAfterTest();

        $this->setAdminUser();

        // Course.
        $courseid = $this->course->id;

        // Create students.
        $this->students = [];
        for ($i = 1; $i <= 1000; $i++) {
            $user = $this->getDataGenerator()->create_user(array('username' => "student$i"));
            $this->getDataGenerator()->enrol_user($user->id, $courseid, $this->roles['student']);
            $this->students[$i] = $user;
        }

        // Dataform.
        $formula = '##:select1##+##:select2##+##:select3##+##:select4##+##:select5##/2';
        $params = array(
            'name' => 'Calculated Grade Dataform',
            'course' => $courseid,
            'grade' => 100,
            'gradeitems' => serialize(array(0 => array('ca' => $formula))),
        );
        $dataform = $this->getDataGenerator()->create_module('dataform', $params);
        $df = mod_dataform_dataform::instance($dataform->id);

        // Add select fields.
        for ($i = 1; $i <= 5; $i++) {
            $field = $df->field_manager->add_field('select');
            $field->update((object) array('name' => "select$i", 'param1' => "20\n40\n60"));
        }

        // Add a view.
        $view = $df->view_manager->add_view('aligned');

        // Get an entry manager.
        $entryman = $view->entry_manager;

        // Add entry per student with data.
        $dataformgenerator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        foreach ($this->students as $student) {
            $data = [
                'dataid' => $dataform->id,
                'userid' => $student->id,
                'select1_selected' => 1,
                'select2_selected' => 1,
                'select3_selected' => 1,
                'select4_selected' => 1,
                'select5_selected' => 1,
            ];

            $dataformgenerator->create_entry($data);
        }

        // Fetch the grade item.
        $gitem = $this->fetch_grade_item($df->id, 0);

        // Simulate execution of the scheduled grading task.
        $gradingtask = new \mod_dataform\task\grade_update();
        $gradingtask->execute();

        // Grade should be 20.
        $grade = $gitem->get_grade($this->students[1]->id, false);
        $this->assertEquals(90, $grade->finalgrade);
    }

    /**
     * Returns the grade item with the specified item number, for the specified Dataform.
     */
    protected function fetch_grade_item($dataformid, $itemnumber = 0) {
        $grademan = new \mod_dataform_grade_manager($dataformid);
        $gradeitems = $grademan->grade_items;
        if (!empty($gradeitems[$itemnumber])) {
            return $gradeitems[$itemnumber];
        }
        return false;
    }

}
