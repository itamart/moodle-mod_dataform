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

defined('MOODLE_INTERNAL') || die();

/**
 * Calculations testcase.
 *
 * @package    mod_dataform
 * @copyright  2016 Itamar Tzadok {@link http://substantialmethods.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @category   phpunit
 * @group      mod_dataform
 * @group      mod_dataform_calculations
 */
class mod_dataform_calculations_testcase extends advanced_testcase {
    protected $course;

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
    }

    /**
     * Tests sum calculation with number fields.
     */
    public function test_sum_number_field() {
        global $DB;

        $generator = $this->getDataGenerator();
        $modgenerator = $generator->get_plugin_generator('mod_dataform');

        $this->setAdminUser();

        // Add a dataform.
        $dataform = $modgenerator->create_instance(array('course' => $this->course));
        $dataformid = $dataform->id;
        $df = \mod_dataform_dataform::instance($dataformid);

        // Add number fields.
        $fieldparams = array('dataid' => $dataformid, 'type' => 'number');
        $modgenerator->create_field((object) (array_merge($fieldparams, array('name' => 'num1'))));
        $modgenerator->create_field((object) (array_merge($fieldparams, array('name' => 'num2'))));

        // Add view.
        $view = $df->view_manager->add_view('grid');
        $view->set_default_view_template('##entries##');
        $formulas = array(
            '%%F[[ENT:id]]:=[[num1]]%%',
            '%%F[[ENT:id]]:=[[num2]]%%',
            '%%F:=[[num1]]+[[num2]]%%',
            '%%F:=SUM([[num1]],[[num2]])%%',
            '%%F1:=SUM(_F[[ENT:id]]_)%%',
            '%%F2:=SUM(_F[[ENT:id]]_);2%%',
            '%%F:=SUM(_F1_)*2%%',
        );
        $entrytemplate = implode(',', $formulas);
        $view->set_default_entry_template($entrytemplate);
        $view->update($view->data);

        // Empty, empty.
        $view->entry_manager->delete_entries();
        $entryparams = array();
        $modgenerator->create_entry(array_merge($entryparams, array('dataid' => $dataformid)));
        $viewcontent = $view->display();
        $viewcontent = clean_param($viewcontent, PARAM_NOTAGS);
        $actual = explode(',', $viewcontent);
        $i = -1;
        $this->assertEquals('0', $actual[++$i]);
        $this->assertEquals('0', $actual[++$i]);
        $this->assertEquals('', $actual[++$i]);
        $this->assertEquals('', $actual[++$i]);
        $this->assertEquals('0', $actual[++$i]);
        $this->assertEquals('0.00', $actual[++$i]);
        $this->assertEquals('0', $actual[++$i]);

        // Empty, 8.
        $view->entry_manager->delete_entries();
        $entryparams = array('num2' => 8);
        $modgenerator->create_entry(array_merge($entryparams, array('dataid' => $dataformid)));
        $viewcontent = $view->display();
        $viewcontent = clean_param($viewcontent, PARAM_NOTAGS);
        $actual = explode(',', $viewcontent);
        $i = -1;
        $this->assertEquals('0', $actual[++$i]);
        $this->assertEquals('8', $actual[++$i]);
        $this->assertEquals('', $actual[++$i]);
        $this->assertEquals('', $actual[++$i]);
        $this->assertEquals('8', $actual[++$i]);
        $this->assertEquals('8.00', $actual[++$i]);
        $this->assertEquals('16', $actual[++$i]);

        // 7, 0.
        $view->entry_manager->delete_entries();
        $entryparams = array('num1' => 7, 'num2' => 0);
        $modgenerator->create_entry(array_merge($entryparams, array('dataid' => $dataformid)));
        $viewcontent = $view->display();
        $viewcontent = clean_param($viewcontent, PARAM_NOTAGS);
        $actual = explode(',', $viewcontent);
        $i = -1;
        $this->assertEquals('7', $actual[++$i]);
        $this->assertEquals('0', $actual[++$i]);
        $this->assertEquals('7', $actual[++$i]);
        $this->assertEquals('7', $actual[++$i]);
        $this->assertEquals('7', $actual[++$i]);
        $this->assertEquals('7.00', $actual[++$i]);
        $this->assertEquals('14', $actual[++$i]);

        // 7.25, 8.
        $view->entry_manager->delete_entries();
        $entryparams = array('num1' => 7.25, 'num2' => 8);
        $modgenerator->create_entry(array_merge($entryparams, array('dataid' => $dataformid)));
        $viewcontent = $view->display();
        $viewcontent = clean_param($viewcontent, PARAM_NOTAGS);
        $actual = explode(',', $viewcontent);
        $i = -1;
        $this->assertEquals('7', $actual[++$i]);
        $this->assertEquals('8', $actual[++$i]);
        $this->assertEquals('15', $actual[++$i]);
        $this->assertEquals('15', $actual[++$i]);
        $this->assertEquals('15', $actual[++$i]);
        $this->assertEquals('15.00', $actual[++$i]);
        $this->assertEquals('30', $actual[++$i]);
    }

}
