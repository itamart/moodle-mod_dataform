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
 * @package    mod_dataform
 * @category   phpunit
 * @copyright  2012 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;

global $CFG;
require_once("$CFG->dirroot/mod/dataform/lib.php");
require_once("$CFG->dirroot/mod/dataform/mod_class.php");
require_once("$CFG->dirroot/mod/dataform/view/import/view_class.php");

/**
 * Unit tests for dataform import
 *
 * @package    mod_dataform
 * @copyright  2012 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataform_import_test extends advanced_testcase {
    public $dataform = null; // A dataform instance.

    /**
     * Set up function. In this instance we are setting up a dataform
     * with fields and views to be used in the unit tests.
     */
    protected function setUp() {
        global $DB, $CFG;
        parent::setUp();

        $this->resetAfterTest(true);

        // we already have 2 users, we need 98 more - let's ignore the fact that guest can not post anywhere
        //for($i=3;$i<=100;$i++) {
        //    $this->getDataGenerator()->create_user();
        //}

        // create a dataform module
        $course = $this->getDataGenerator()->create_course();
        $dataform = $this->getDataGenerator()->create_module('dataform', array('course'=>$course->id));
        $this->dataform = new dataform($dataform);

        // Set up data for the test dataform.
        $files = array(
            'dataform_fields'  => __DIR__.'/fixtures/test_dataform_fields.csv',
            'dataform_views' => __DIR__.'/fixtures/test_dataform_views.csv',
            'dataform_filters' => __DIR__.'/fixtures/test_dataform_filters.csv',
        );

        // do not use enclosure
        $this->loadDataSet($this->createCsvDataSet($files));        
    }

    /**
     * Test 1: Number of imported entries and contents.
     */
    function test_import_section() {
        global $DB;

        // Test 0: Setup
        $this->assertEquals(2, $DB->count_records('dataform_fields'));
        $this->assertEquals(1, $DB->count_records('dataform_views'));
        $this->assertEquals(1, $DB->count_records('dataform_filters'));
        
        
        // Import entries
        $importview = new dataform_view_import($this->dataform, 1);
        $data = (object) array('eids' => array());
        $options = array('settings' => array(
            -6 => array('author:id' => array('name' => 'author:id')),
            1 => array('Text' => array('name' => 'Text'))            
        ));
        $importcontent = array(
            "author:id,Text",
            "2,Hello",
            "2,World"
        );
        $data = $importview->process_csv($data, implode("\n", $importcontent), $options);

        // Test 1: Guest should not be able to import entries
        $this->setGuestUser();
        $importresult = $importview->execute_import($data);      
        $this->assertEquals(true, $importresult);
        $this->assertEquals(0, $DB->count_records('dataform_entries'));
        $this->assertEquals(0, $DB->count_records('dataform_contents'));
        
        // Test 2: Admin should be able to import entries
        $this->setAdminUser();
        $importresult = $importview->execute_import($data);      
        $this->assertEquals(true, $importresult);
        $this->assertEquals(2, $DB->count_records('dataform_entries'));
        $this->assertEquals(2, $DB->count_records('dataform_contents'));

        // Test 3: Filter
        $importview->set_filter(array('filterid' => 1));
        $dfentries = new dataform_entries($this->dataform, $importview);
        $entries = $dfentries->get_entries();
        $this->assertEquals(1, count($entries));
    }
}
