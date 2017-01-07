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
 * Filtering testcase
 *
 * @package    mod_dataform
 * @copyright  2015 Itamar Tzadok {@link http://substantialmethods.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @category   phpunit
 * @group      mod_dataform
 * @group      mod_dataform_filter
 * @group      mod_dataform_filter_search
 */
class mod_dataform_filter_search_testcase extends advanced_testcase {
    protected $course;
    protected $teacher;
    protected $student1;
    protected $student2;
    protected $dataformgenerator;

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

        // Generator.
        $generator = $this->getDataGenerator();
        $this->dataformgenerator = $generator->get_plugin_generator('mod_dataform');

        // Create a course we are going to add a data module to.
        $this->course = $generator->create_course();
        $courseid = $this->course->id;

        $roles = $DB->get_records_menu('role', array(), '', 'shortname,id');
        $editingteacherrolename = \mod_dataform\helper\testing::get_role_shortname('editingteacher');
        $studentrolename = \mod_dataform\helper\testing::get_role_shortname('student');

        // Teacher.
        $user = $this->getDataGenerator()->create_user(array('username' => 'teacher'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles[$editingteacherrolename]);
        $this->teacher = $user;

        // Student 1.
        $user = $this->getDataGenerator()->create_user(array('username' => 'student1'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles[$studentrolename]);
        $this->student1 = $user;

        // Student 2.
        $user = $this->getDataGenerator()->create_user(array('username' => 'student2'));
        $this->getDataGenerator()->enrol_user($user->id, $courseid, $roles[$studentrolename]);
        $this->student2 = $user;
    }

    /**
     * Test is/not empty criterion.
     */
    public function test_is_not_empty() {
        global $DB;

        $this->setAdminUser();

        // Add a group.
        $group = $this->getDataGenerator()->create_group(array('courseid' => $this->course->id));

        // Add a dataform.
        $dataform = $this->dataformgenerator->create_instance(array('course' => $this->course));
        $dataformid = $dataform->id;
        $df = \mod_dataform_dataform::instance($dataformid);

        // Add content fields.
        $fieldtypes = array(
            'text',
            'textarea',
            'select',
            'radiobutton',
            'selectmulti',
            'checkbox',
            'url',
            'number',
            'time',
        );
        $fields = array();
        foreach ($fieldtypes as $type) {
            $fields[$type] = $df->field_manager->add_field($type);
        }

        // Add csv view.
        $importview = $df->view_manager->add_view('csv');

        // Import entries.
        $options = array('settings' => array());

        $fieldid = dataformfield_entryauthor_entryauthor::INTERNALID;
        $options['settings'][$fieldid] = array('id' => array('name' => 'EAU:id'));

        $fieldid = dataformfield_entrygroup_entrygroup::INTERNALID;
        $options['settings'][$fieldid] = array('id' => array('name' => 'EGR:id'));

        foreach ($fields as $type => $field) {
            $settings = array('name' => $type);
            if (in_array($type, array('select', 'radiobutton', 'selectmulti', 'checkbox'))) {
                $settings['allownew'] = true;
            }
            $options['settings'][$field->id] = array('' => $settings);
        }

        $content1 = array(
            'EGR:id' => $group->id,
            'text' => 'Some single line text.',
            'textarea' => 'First line of multiline text.<br /> Second line of multiline text.',
            'select' => 'SL 1',
            'radiobutton' => 'RB 1',
            'selectmulti' => 'SLM 1',
            'checkbox' => 'CB 1',
            'url' => 'http://substantialmethods.com',
            'number' => '7',
            'time' => '22 July 2015 1:10 PM',
        );

        $content2 = array(
            'EGR:id' => 0,
            'text' => '',
            'textarea' => '',
            'select' => '',
            'radiobutton' => '',
            'selectmulti' => '',
            'checkbox' => '',
            'url' => '',
            'number' => '',
            'time' => '',
        );

        $csvdata = array(
            implode(',', array_keys($content1)),
            implode(',', $content1),
            implode(',', $content2),
            implode(',', $content2),
        );

        $data = new stdClass;
        $data->eids = array();
        $data->errors = array();
        $data = $importview->process_csv($data, implode("\n", $csvdata), $options);

        $importresult = $importview->execute_import($data);

        // Get an entry manager for a view.
        $entryman = $importview->entry_manager;

        // Search entry group.
        // Empty.
        $this->validate_search($df, $entryman, "AND,EGR,id,,,", 3, 2);

        // Not empty.
        $this->validate_search($df, $entryman, "AND,EGR,id,NOT,,", 3, 1);

        // Search fields.
        foreach ($fieldtypes as $type) {
            // Empty.
            $this->validate_search($df, $entryman, "AND,$type,content,,,", 3, 2);
            // Not empty.
            $this->validate_search($df, $entryman, "AND,$type,content,NOT,,", 3, 1);
        }

    }

    protected function validate_search($df, $entryman, $searchoptions, $viewable, $filtered) {
        $instance = $this->dataformgenerator->create_filter(array(
            'dataid' => $df->id,
            'searchoptions' => $searchoptions,
        ));
        $filter = new \mod_dataform\pluginbase\dataformfilter($instance);
        $entryman->set_content(array('filter' => $filter));
        $this->assertEquals($viewable, $entryman->get_count($entryman::COUNT_VIEWABLE));
        $this->assertEquals($filtered, $entryman->get_count($entryman::COUNT_FILTERED));
    }


}
