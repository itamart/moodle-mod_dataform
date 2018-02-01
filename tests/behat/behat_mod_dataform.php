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
 * Steps definitions related with the dataform activity.
 *
 * @package    mod_dataform
 * @category   tests
 * @copyright  2013 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given;
use Behat\Gherkin\Node\TableNode as TableNode;
use Behat\Gherkin\Node\PyStringNode as PyStringNode;

/**
 * Dataform-related steps definitions.
 *
 * @package    mod_dataform
 * @category   tests
 * @copyright  2013 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_dataform extends behat_base {

    /**
     * Each element specifies:
     * - The data generator sufix used.
     * - The required fields.
     * - The mapping between other elements references and database field names.
     * @var array
     */
    protected static $elements = array(
        'views' => array(
            'datagenerator' => 'view',
            'required' => array('type', 'dataform', 'name'),
            'switchids' => array('dataform' => 'dataid'),
        ),
        'fields' => array(
            'datagenerator' => 'field',
            'required' => array('type', 'dataform', 'name'),
            'switchids' => array('dataform' => 'dataid'),
        ),
        'filters' => array(
            'datagenerator' => 'filter',
            'required' => array('dataform', 'name'),
            'switchids' => array('dataform' => 'dataid'),
        ),
        'grade items' => array(
            'datagenerator' => 'grade_item',
            'required' => array('dataform', 'name', 'grade'),
            'switchids' => array('dataform' => 'dataid'),
        ),
        'entries' => array(
            'datagenerator' => 'entry',
            'required' => array('dataform'),
            'switchids' => array('dataform' => 'dataid', 'user' => 'userid', 'group' => 'groupid'),
        ),
    );

    /**
     * Runs the specified scenario if exists.
     *
     * @Given /^I run dataform scenario "(?P<scenario_name_string>(?:[^"]|\\")*)"$/
     * @Given /^I run dataform scenario "(?P<scenario_name_string>(?:[^"]|\\")*)" with:$/
     * @param string $name
     * @param TableNode $data
     */
    public function i_run_dataform_scenario_with($name, TableNode $data = null) {
        $scenarioname = 'scenario_'. str_replace(' ', '_', $name);
        if (method_exists($this, $scenarioname)) {
            return $this->$scenarioname($data);
        }
        return array();
    }

    /**
     * Resets (truncates) all dataform tables to remove any records and reset sequences.
     * This set of steps is essential for any standalone scenario that adds entries with content
     * since such a scenario has to refer to input elements by the name field_{fieldid}_{entryid}
     * (or field_{fieldid}_-1 for a new entry) and the ids have to persist between runs.
     *
     * @Given /^a fresh site for dataform scenario$/
     * @return array
     */
    public function start_afresh_steps() {
        global $DB;

        // Dataform module id.
        $moduleid = $DB->get_field('modules', 'id', array('name' => 'dataform'));
        // CM ids.
        if ($cmids = $DB->get_records('course_modules', array('module' => $moduleid), '', 'id,id AS cmid')) {
            // Delete properly any existing dataform instances.
            foreach ($cmids as $cmid) {
                course_delete_module($cmid);
            }
        }

        // Clean up tables.
        $tables = array(
            'dataform',
            'dataform_contents',
            'dataform_entries',
            'dataform_fields',
            'dataform_filters',
            'dataform_views',
        );

        $prefix = $DB->get_prefix();
        foreach ($tables as $table) {
            $DB->execute("TRUNCATE TABLE {$prefix}{$table}");
        }

        // Clean up instance store cache.
        \mod_dataform_instance_store::unregister();


        // Add a course.
        $data = array(
            array('fullname', 'shortname', 'category'),
            array('Course 1', 'C1', '0'),
        );
        $table = new TableNode($data);
        $this->execute('behat_data_generators::the_following_exist', array('courses', $table));

        // Add users.
        $data = array(
            array('username', 'firstname', 'lastname', 'email'),
            array('teacher1', 'Teacher', '1', 'teacher1@asd.com '),
            array('assistant1', 'Assistant', '1', 'assistant1@asd.com'),
            array('assistant2', 'Assistant', '2', 'assistant2@asd.com'),
            array('student1', 'Student', '1', 'student1@asd.com'),
            array('student2', 'Student', '2', 'student2@asd.com'),
            array('student3', 'Student', '3', 'student3@asd.com'),
        );
        $table = new TableNode($data);
        $this->execute('behat_data_generators::the_following_exist', array('users', $table));

        // Enrol users in course.
        $teacherrole = \mod_dataform\helper\testing::get_role_shortname('editingteacher');
        $assistantrole = \mod_dataform\helper\testing::get_role_shortname('teacher');
        $studentrole = \mod_dataform\helper\testing::get_role_shortname('student');
        $data = array(
            array('user', 'course', 'role'),
            array('teacher1', 'C1', $teacherrole),
            array('assistant1', 'C1', $assistantrole),
            array('assistant2', 'C1', $assistantrole),
            array('student1', 'C1', $studentrole),
            array('student2', 'C1', $studentrole),
        );
        $table = new TableNode($data);
        $this->execute('behat_data_generators::the_following_exist', array('course enrolments', $table));

        // Add groups.
        $data = array(
            array('name', 'description', 'course', 'idnumber'),
            array('Group 1', 'Anything', 'C1', 'G1'),
            array('Group 2', 'Anything', 'C1', 'G2'),
        );
        $table = new TableNode($data);
        $this->execute('behat_data_generators::the_following_exist', array('groups', $table));

        // Add group members.
        $data = array(
            array('user', 'group'),
            array('student1', 'G1'),
            array('student2', 'G2'),
        );
        $table = new TableNode($data);
        $this->execute('behat_data_generators::the_following_exist', array('group members', $table));
    }

    /**
     * Starts afresh with a dataform activity 'Test dataform' in Course 1.
     * See {@link behat_mod_dataform::i_start_afresh()}.
     *
     * @Given /^a fresh site with dataform "(?P<dataform_name_string>(?:[^"]|\\")*)"$/
     * @Given /^I start afresh with dataform "(?P<dataform_name_string>(?:[^"]|\\")*)"$/
     * @param string $name
     */
    public function i_start_afresh_with_dataform($name) {
        $this->start_afresh_steps();

        // Test dataform.
        $data = array(
            array('activity', 'course', 'idnumber', 'name', 'intro'),
            array('dataform', 'C1', 'dataform1', $name, $name),
        );
        $table = new TableNode($data);
        $this->execute('behat_data_generators::the_following_exist', array('activities', $table));
    }

    /**
     * Creates dataform fields.
     *
     * @Given /^the following dataform "(?P<element_string>(?:[^"]|\\")*)" exist:$/
     *
     * @param TableNode $data
     */
    public function the_following_dataform_exist($elementname, TableNode $data) {
        // Now that we need them require the data generators.
        require_once(__DIR__ . '/../generator/lib.php');

        $generator = testing_util::get_data_generator()->get_plugin_generator('mod_dataform');

        $elementdatagenerator = self::$elements[$elementname]['datagenerator'];
        $requiredfields = self::$elements[$elementname]['required'];
        if (!empty(self::$elements[$elementname]['switchids'])) {
            $switchids = self::$elements[$elementname]['switchids'];
        }

        foreach ($data->getHash() as $elementdata) {

            // Check if all the required fields are there.
            foreach ($requiredfields as $requiredfield) {
                if (!isset($elementdata[$requiredfield])) {
                    throw new Exception($elementname . ' requires the field ' . $requiredfield . ' to be specified');
                }
            }

            // Switch from human-friendly references to ids.
            if (isset($switchids)) {
                foreach ($switchids as $element => $field) {
                    $methodname = 'get_' . $element . '_id';

                    // Not all the switch fields are required, default vars will be assigned by data generators.
                    if (isset($elementdata[$element])) {
                        // Temp $id var to avoid problems when $element == $field.
                        $id = $this->{$methodname}($elementdata[$element]);
                        unset($elementdata[$element]);
                        $elementdata[$field] = $id;
                    }
                }
            }

            // Creates element.
            $methodname = 'create_' . $elementdatagenerator;
            if (method_exists($generator, $methodname)) {
                // Using data generators directly.
                $generator->{$methodname}($elementdata);

            } else if (method_exists($this, 'process_' . $elementdatagenerator)) {
                // Using an alternative to the direct data generator call.
                $this->{'process_' . $elementdatagenerator}($elementdata);
            } else {
                throw new Exception($elementname . ' data generator is not implemented');
            }
        }
    }

    /**
     * Creates a Dataform instance.
     *
     * @Given /^the following dataform exists:$/
     * @param TableNode $data
     */
    public function the_following_dataform_exists(TableNode $data) {

        $datahash = $data->getRowsHash();

        // Compile grade items if exist.
        $gradeitems = array();
        foreach ($datahash as $key => $value) {
            if (strpos($key, 'gradeitem') !== 0) {
                continue;
            }
            if (empty($value)) {
                continue;
            }
            list(, $itemnumber, $var) = explode(' ', $key);
            if (empty($gradeitems[$itemnumber])) {
                $gradeitems[$itemnumber] = array();
            }

            $gradeitems[$itemnumber][$var] = $value;
            unset($datahash[$key]);
        }
        if ($gradeitems) {
            $datahash['gradeitems'] = serialize($gradeitems);
        }

        $headers = array_keys($datahash);
        array_unshift($headers, 'activity');

        $values = array_values($datahash);
        array_unshift($values, 'dataform');

        $data = array($headers, $values);

        $table = new TableNode($data);
        $this->execute('behat_data_generators::the_following_exist', array('activities', $table));
    }

    /**
     * Resets user data in the specified dataform.
     * This is a backend step.
     *
     * @Given /^user data in dataform "(?P<dataform_idn_string>(?:[^"]|\\")*)" is reset$/
     * @param string $idnumber
     */
    public function user_data_in_dataform_is_reset($idnumber) {
        $dataformid = $this->get_dataform_id($idnumber);
        $df = new \mod_dataform_dataform($dataformid);
        $df->reset_user_data();
    }

    /* ACTIVITY SETUP STEPS */

    /**
     * Adds a dataform as teacher 1 in course 1 and displays the dataform.
     * The step begins in a new test site.
     *
     * @Given /^I add a dataform with "(?P<dataform_url_string>(?:[^"]|\\")*)"$/
     * @param string $data
     */
    public function i_add_a_dataform_with($data) {
        $this->execute('behat_auth::i_log_in_as', array('teacher1'));
        $this->execute('behat_navigation::i_am_on_course_homepage_with_editing_mode_on', array('Course 1'));
        $this->execute('behat_course::i_add_to_section', array('Dataform', '1'));
        $this->execute('behat_forms::i_expand_all_fieldsets', array());

        $this->dataform_form_fill_steps($data);

        $this->execute('behat_forms::press_button', array('Save and return to course'));
    }

    /**
     * Validates dataform activity settings.
     * The step begins in the activity form.
     *
     * @Then /^the dataform settings should match "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $data
     */
    public function the_dataform_settings_should_match($data) {
        return $this->dataform_form_match_steps($data);
    }

    /**
     * Adds a test dataform as teacher 1 in course 1 and displays the dataform.
     * The step begins in a new test site.
     *
     * @Given /^I add a test dataform$/
     * @param string $dataformname
     * @param TableNode $table
     */
    public function i_add_a_test_dataform() {
        $this->execute('behat_auth::i_log_in_as', array('teacher1'));
        $this->execute('behat_navigation::i_am_on_course_homepage_with_editing_mode_on', array('Course 1'));
        $this->execute('behat_course::i_add_to_section', array('Dataform', '1'));

        $data = array('Name', 'Test Dataform');
        $table = new TableNode($data);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($table));

        $this->execute('behat_forms::press_button', array('Save and display'));
    }

    /**
     * Deletes the dataform.
     * This step begins inside the designated dataform.
     * Useful at the end of standalone scenarios for cleanup.
     *
     * @Given /^I delete this dataform$/
     */
    public function i_delete_this_dataform() {
        $this->execute('behat_navigation::i_navigate_to_node_in', array('Delete activity', 'Dataform activity administration'));
        $this->execute('behat_forms::press_button', array('Yes'));
    }

    /**
     * Go to the specified manage tab of the current dataform.
     * The step begins from the dataform's course page.
     *
     * @Given /^I go to manage dataform "(?P<tab_name_string>(?:[^"]|\\")*)"$/
     * @param string $tabname
     */
    public function i_go_to_manage_dataform($tabname) {
        $node = get_string("dataform:manage$tabname", 'dataform');
        $path = "Dataform activity administration";
        $this->execute('behat_navigation::i_navigate_to_node_in', array($node, $path));
    }

    /* FIELD */

    /**
     * Adds a field of the specified type to the current dataform with the provided table data (usually Name).
     * The step begins in the dataform fields index.
     *
     * @Given /^I add a dataform field "(?P<field_type_string>(?:[^"]|\\")*)" with "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $type
     * @param string $data
     */
    public function i_add_a_dataform_field_with($type, $data) {
        $fieldclass = 'dataformfield_'. $type;
        $pluginname = get_string('pluginname', $fieldclass);


        // Open the form.
        $this->execute('behat_forms::i_set_the_field_to', array(get_string('fieldadd', 'dataform'), $pluginname));

        // Fill the form.
        $func = "field_form_fill_steps_$type";
        $func = method_exists($this, $func) ? $func : "field_form_fill_steps_base";
        $this->$func($data);

        // Save.
        $this->execute('behat_forms::press_button', array(get_string('savechanges')));
        $this->execute('behat_general::i_wait_to_be_redirected', array());
    }

    /**
     * Sets a dataform field setting to the given content.
     * The step begins in the Fields manage tab.
     *
     * @Given /^I set dataform field "(?P<field_name_string>(?:[^"]|\\")*)" options to "(?P<options_string>(?:[^"]|\\")*)"$/
     * @param string $name
     * @param string $content
     */
    public function i_set_dataform_field_options_to($name, $content) {

        $this->execute('behat_general::click_link', array($name));
        $this->execute('behat_forms::i_expand_all_fieldsets', array());

        $content = implode("\n", explode('\n', $content));
        $this->execute('behat_forms::i_set_the_field_to', array('Options', $content));
        $this->execute('behat_forms::press_button', array('Save changes'));

    }

    /* VIEW */

    /**
     * Adds a view of the specified type to the current dataform with the provided table data (usually Name).
     * The step begins in the dataform's Manage | Views tab.
     *
     * @Given /^I add a dataform view "(?P<view_type_string>(?:[^"]|\\")*)" with "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $type
     * @param string $data
     */
    public function i_add_a_dataform_view_with($type, $data) {
        $viewclass = 'dataformview_'. $type;
        $pluginname = get_string('pluginname', $viewclass);


        // Open the form.
        $this->execute('behat_forms::i_set_the_field_to', array(get_string('viewadd', 'dataform'), $pluginname));

        // Fill the form.
        $formfields = array(
            'Name',
            'Description',
            'Visibility',
            'Filter',
            'Per page',
            'section_editor[text]',
            'param1',
            'param2',
            'param3',
            'param4',
            'param5',
            'param6',
            'param7',
            'param8',
            'param9',
            'param10',
        );
        $table = $this->convert_data_to_table($formfields, $data);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($table));

        // Save.
        $this->execute('behat_forms::press_button', array(get_string('savechanges')));
        $this->execute('behat_general::i_wait_to_be_redirected', array());
    }

    /**
     * Sets a view as the default view of a dataform instance.
     * The step begins in the dataform's Manage | Views tab
     * with the designated view (by name) already added.
     *
     * @Given /^I set "(?P<view_name_string>(?:[^"]|\\")*)" as default view$/
     * @param string $name
     */
    public function i_set_as_default_view($name) {
        // Click the Default button of the view.
        $idsetdefault = 'id_'. str_replace(' ', '_', $name). '_set_default';
        $this->execute('behat_general::click_link', array($idsetdefault));
    }

    /**
     * Sets the view's view template to specified text passed as PyStringNode.
     * Useful for setting textareas.
     * The step begins in a form.
     *
     * @Given /^view "(?P<view_name_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" has the following view template:$/
     * @param string $viewname
     * @param string $dataformid
     * @param PyStringNode $content
     */
    public function view_in_dataform_has_the_following_view_template($viewname, $dataformid, PyStringNode $content) {
        $df = mod_dataform_dataform::instance($dataformid);
        $view = $df->view_manager->get_view_by_name($viewname);
        $view->set_default_view_template((string) $content);
        $view->update($view->data);
    }

    /**
     * Sets the view's entry template to specified text passed as PyStringNode.
     * The step begins in a form.
     *
     * @Given /^view "(?P<view_name_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" has the following entry template:$/
     * @param string $viewname
     * @param string $dataformid
     * @param PyStringNode $content
     */
    public function view_in_dataform_has_the_following_entry_template($viewname, $dataformid, PyStringNode $content) {
        $df = mod_dataform_dataform::instance($dataformid);
        $view = $df->view_manager->get_view_by_name($viewname);
        $view->set_default_entry_template((string) $content);
        $view->update($view->data);
    }

    /**
     * Updates the submission settings of the specified view.
     *
     * @Given /^view "(?P<viewname_string>(?:[^"]|\\")*)" in "(?P<dataform_string>(?:[^"]|\\")*)" has the following submission settings:$/
     * @param string $viewname
     * @param string $didnumber
     * @param TableNode $data
     */
    public function view_in_has_the_following_submission_settings($viewname, $didnumber, TableNode $data) {
        global $DB;

        $data = (object) $data->getRowsHash();

        // Get the dataform id.
        if (!$dataformid = $DB->get_field('course_modules', 'instance', array('idnumber' => $didnumber))) {
            throw new Exception('The specified dataform with idnumber "' . $idnumber . '" does not exist');
        }

        $df = new \mod_dataform_dataform($dataformid);

        // Get the view.
        if (!$view = $df->view_manager->get_view_by_name($viewname)) {
            return;
        }

        // Collate submission settings.
        $settings = array();
        // Submission display.
        if (!empty($data->submissiondisplay)) {
            $settings['display'] = $data->submissiondisplay;
        }
        // Buttons.
        $buttons = $view->get_submission_buttons();
        foreach ($buttons as $name) {
            $buttonenable = $name.'buttonenable';
            if (!empty($data->$buttonenable)) {
                $buttoncontent = $name.'button_label';
                $settings[$name] = !empty($data->$buttoncontent) ? $data->$buttoncontent : null;
            }
        }

        // Submission Redirect.
        if (!empty($data->submissionredirect)) {
            if ($redirectview = $df->view_manager->get_view_by_name($data->submissionredirect)) {
                $settings['redirect'] = $redirectview->id;
            }
        }
        // Submission timeout.
        if (!empty($data->submissiontimeout)) {
            $settings['timeout'] = $data->submissiontimeout;
        }
        // Submission message.
        if (!empty($data->submissionmessage)) {
            $settings['message'] = $data->submissionmessage;
        }
        // Display after submission.
        if (!empty($data->submissiondisplayafter)) {
            $settings['displayafter'] = 1;
        }

        $view->submission = $settings;

        // Update the view.
        $view->update($view->data);
    }

    /**
     * Sets the css template of the specified dataform to the text passed as PyStringNode.
     *
     * @Given /^dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" has the following css:$/
     * @param string $dataformid
     * @param PyStringNode $content
     */
    public function dataform_has_the_following_css($dataformid, PyStringNode $content) {
        $rec = new stdClass;
        $rec->css = (string) $content;
        $df = mod_dataform_dataform::instance($dataformid);
        $df->update($rec);
    }

    /**
     * Sets the js template of the specified dataform to the text passed as PyStringNode.
     *
     * @Given /^dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" has the following js:$/
     * @param string $dataformid
     * @param PyStringNode $content
     */
    public function dataform_has_the_following_js($dataformid, PyStringNode $content) {
        $rec = new stdClass;
        $rec->js = (string) $content;
        $df = mod_dataform_dataform::instance($dataformid);
        $df->update($rec);
    }

    /* FILTER */

    /**
     * Adds a filter with the specified data to the current dataform.
     * The step begins in the dataform's Manage | Filters tab.
     *
     * @Given /^I add a dataform filter with "(?P<form_data_string>(?:[^"]|\\")*)"$/
     * @param string $data
     */
    public function i_add_a_dataform_filter_with($data) {


        // Open the form.
        $this->execute('behat_general::click_link', array(get_string('filteradd', 'dataform')));

        // Fill the form.
        $formfields = array(
            'Name',
            'Description',
            'Visible',
            'Per page',
            'sortfield0',
            'sortdir0',
            'sortfield1',
            'sortdir1',
            'sortfield2',
            'sortdir2',
            'Search',
            'searchandor0',
            'searchfield0',
            'searchnot0',
            'searchoperator0',
            'searchvalue0',
            'searchandor1',
            'searchfield1',
            'searchnot1',
            'searchoperator1',
            'searchvalue1',
            'searchandor2',
            'searchfield2',
            'searchnot2',
            'searchoperator2',
            'searchvalue2',
        );
        $table = $this->convert_data_to_table($formfields, $data);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($table));

        // Save.
        $this->execute('behat_forms::press_button', array(get_string('savechanges')));
        $this->execute('behat_general::i_wait_to_be_redirected', array());

    }

    /**
     * Sets a sort criterion in the dataform filter.
     * The step begins in the dataform filter form.
     *
     * @Given /^I set sort criterion "(?P<number_string>(?:[^"]|\\")*)" to "(?P<filter_element_string>(?:[^"]|\\")*)" "(?P<sort_direction_string>(?:[^"]|\\")*)"$/
     * @param string $number
     * @param string $fieldelement fieldid,elementname (e.g. 1,content)
     * @param string $direction 0|1 (Ascending|Descending)
     */
    public function i_set_sort_criterion_to($number, $fieldelement, $direction) {
        $i = (int) $number - 1;
        $sortfield = "sortfield$i";
        $sortdir = "sortdir$i";

        $this->execute('behat_forms::i_set_the_field_to', array($sortfield, $fieldelement));
        $this->execute('behat_forms::i_set_the_field_to', array($sortdir, $direction));
    }

    /**
     * Sets a search criterion in the dataform filter.
     * The step begins in the dataform filter form.
     *
     * @Given /^I set search criterion "(?P<number_string>(?:[^"]|\\")*)" to "(?P<filter_element_string>(?:[^"]|\\")*)" "(?P<filter_andor_string>(?:[^"]|\\")*)" "(?P<filter_not_string>(?:[^"]|\\")*)" "(?P<filter_operator_string>(?:[^"]|\\")*)" "(?P<filter_value_string>(?:[^"]|\\")*)"$/
     * @param string $number
     * @param string $andor AND|OR
     * @param string $field fieldid,elementname (e.g. 1,content)
     * @param string $not <empty>|NOT
     * @param string $operator <empty>|=|>|<|>=|<=|BETWEEN|LIKE|IN
     */
    public function i_set_search_criterion_to($number, $andor, $field, $not, $operator, $value) {
        $i = (int) $number - 1;
        $searchandor = "searchandor$i";
        $searchfield = "searchfield$i";
        $searchnot = "searchnot$i";
        $searchoperator = "searchoperator$i";
        $searchvalue = "searchvalue$i";

        $this->execute('behat_forms::i_set_the_field_to', array($searchandor, $andor));
        $this->execute('behat_forms::i_set_the_field_to', array($searchfield, $field));
        $this->execute('behat_forms::i_set_the_field_to', array($searchnot, $not));
        $this->execute('behat_forms::i_set_the_field_to', array($searchoperator, $operator));
        $this->execute('behat_forms::i_set_the_field_to', array($searchvalue, $value));
    }

    /* FORM EDITING */

    /**
     * Prepends text to the field's content.
     * The step begins in a form.
     *
     * @Given /^I prepend "(?P<text_string>(?:[^"]|\\")*)" to field "(?P<field_string>(?:[^"]|\\")*)"$/
     * @param string $text
     * @param string $field
     */
    public function i_prepend_to_field($text, $field) {

        $fieldnode = $this->find_field($field);
        $value = $fieldnode->getValue();
        $data = array($field, $text. $value);
        $table = new TableNode($data);
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($table));

    }

    /**
     * Appends text to the field's content.
     * The step begins in a form.
     *
     * @Given /^I append "(?P<text_string>(?:[^"]|\\")*)" to field "(?P<field_string>(?:[^"]|\\")*)"$/
     * @param string $text
     * @param string $locator
     */
    public function i_append_to_field($text, $locator) {
        $node = $this->find_field($field);
        $value = $node->getValue(). $text;
        $field = behat_field_manager::get_form_field($node, $this->getSession());
        $field->set_value($value);
    }

    /**
     * Replaces text in the field's content.
     * The step begins in a form.
     *
     * @Given /^I replace in field "(?P<field_string>(?:[^"]|\\")*)" "(?P<text_string>(?:[^"]|\\")*)" with "(?P<replacement_string>(?:[^"]|\\")*)"$/
     * @param string $locator
     * @param string $text
     * @param string $replacement
     */
    public function i_replace_in_field_with($locator, $text, $replacement) {
        $node = $this->find_field($locator);
        $field = behat_field_manager::get_form_field($node, $this->getSession());
        $value = $field->get_value();
        $value = str_replace($text, $replacement, $value);

        // Hack to remove new line characters from editor field value.
        if (get_class($field) == 'behat_form_editor') {
            $value = str_replace(array("\n", "\r"), '', $value);
        }

        $field->set_value($value);
    }

    /**
     * Returns list of steps for uploading image into an editor.
     *
     * @Given /^I upload image "(?P<imagename_string>(?:[^"]|\\")*)" to editor "(?P<field_string>(?:[^"]|\\")*)"$/
     * @param string $imagename
     * @param string $locator
     * @return array Array of Given objects.
     */
    public function i_upload_image_to_editor($imagename, $locator) {
        global $CFG;

        $path = "$CFG->wwwroot/mod/dataform/tests/fixtures/$imagename";
        $this->execute('behat_general::i_click_on', array('Image', 'button'));
        $this->execute('behat_forms::i_set_the_field_to', array('Enter URL', $path));
        $this->execute('behat_forms::i_set_the_field_to', array('Description not necessary', 'checked'));
        $this->execute('behat_general::i_click_on', array('Save image', 'button'));
    }

    /**
     * Sets field value to the specified text passed as PyStringNode.
     * Useful for setting textareas.
     * The step begins in a form.
     *
     * @Given /^I set the field "(?P<field_name_string>(?:[^"]|\\")*)" to$/
     * @param string $name
     * @param PyStringNode $content
     */
    public function i_set_the_field_to($name, PyStringNode $content) {
        $this->execute('behat_forms::i_set_the_field_to', array($name, $content));
    }

    /**
     * Fills a textarea with the specified text replacing \n with new lines.
     * The step begins in a form.
     *
     * @Given /^I fill textarea "(?P<field_string>(?:[^"]|\\")*)" with "(?P<text_string>(?:[^"]|\\")*)"$/
     * @param string $name
     * @param string $content
     */
    public function i_fill_textarea_with($name, $content) {

        $content = implode("\n", explode('\n', $content));
        $this->execute('behat_forms::i_set_the_field_to', array($name, $content));
    }

    /**
     * Generic press enter on field. Click on the element of the specified type.
     *
     * @When /^I press Enter on "(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)"$/
     * @param string $element Element we look for
     * @param string $selectortype The type of what we look for
     */
    public function i_press_enter_on($element, $selectortype) {

        // Gets the node based on the requested selector type and locator.
        $node = $this->get_selected_node($selectortype, $element);
        $this->ensure_node_is_visible($node);
        $node->keyPress(13);
    }

    /* ACTIVITY PARTICIPATION STEPS */

    /**
     * Enters the specified dataform in the specified course as the specified user.
     *
     * @Given /^I am in dataform "(?P<dataformname_string>(?:[^"]|\\")*)" "(?P<coursename_string>(?:[^"]|\\")*)" as "(?P<username_string>(?:[^"]|\\")*)"$/
     * @Given /^I am in dataform "(?P<dataformname_string>(?:[^"]|\\")*)" "(?P<coursename_string>(?:[^"]|\\")*)"$/
     * @param string $dataformname
     * @param string $coursename
     */
    public function i_am_in_dataform_as($dataformname, $coursename, $username = null) {
        if ($username) {
            $this->execute('behat_auth::i_log_in_as', array($username));
        }
        $this->execute('behat_navigation::i_am_on_course_homepage', array($coursename));
        $this->execute('behat_general::click_link', array($dataformname));
    }

    /**
     * Opens Dataform url.
     *
     * @Given /^I go to dataform page "(?P<dataform_url_string>(?:[^"]|\\")*)"$/
     * @param string $url
     */
    public function i_go_to_dataform_page($url) {
        $this->getSession()->visit($this->locate_path("/mod/dataform/$url"));
    }

    /**
     * Opens the specified dataform view.
     *
     * @Given /^I am on view "(?P<viewname_string>(?:[^"]|\\")*)" in dataform "(?P<idnumber_string>(?:[^"]|\\")*)"$/
     * @throws coding_exception
     * @param string $viewname The full name of the view.
     * @param string $idnumber The id number of the dataform.
     * @return void
     */
    public function i_am_on_view_in_dataform($viewname, $idnumber) {
        $dataformid = $this->get_dataform_id($idnumber);
        $viewid = $this->get_dataform_view_id($viewname, $dataformid);
        $this->i_go_to_dataform_page("view.php?d=$dataformid&view=$viewid");
    }

    /**
     * Adds a dataform entry with the specified values.
     * The step begins on the front page of a dataform activity ready to browse.
     *
     * @Given /^I add a dataform entry with:$/
     * @param string $data
     */
    public function i_add_a_dataform_entry_with($data) {
        $this->execute('behat_general::click_link', array('Add a new entry'));
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', array($data));
        $this->execute('behat_forms::press_button', array('Save'));
    }

    /**
     * Verifies that a new entry cannot be added neither via button nor via url.
     *
     * @Given /^I cannot add a new entry in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" view "(?P<view_id_string>(?:[^"]|\\")*)"$/
     * @param string $dataformid
     * @param string $viewid
     */
    public function i_cannot_add_a_new_entry_in_dataform_view($dataformid, $viewid) {
        $this->i_do_not_see('Add a new entry');
        $this->i_go_to_dataform_page("view.php?d=$dataformid&view=$viewid&editentries=-1");
        $this->i_do_not_see('Save');
        $this->i_go_to_dataform_page("view.php?d=$dataformid&view=$viewid");
    }

    /**
     * Verifies that an entry cannot be edited neither via edit link nor via url.
     *
     * @Given /^I cannot edit entry "(?P<entry_id_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" view "(?P<view_id_string>(?:[^"]|\\")*)"$/
     * @param string $entryid
     * @param string $dataformid
     * @param string $viewid
     */
    public function i_cannot_edit_entry_in_dataform_view($entryid, $dataformid, $viewid) {
        $this->does_not_exist("id_editentry$entryid", 'link');
        $this->i_go_to_dataform_page("view.php?d=$dataformid&view=$viewid&editentries=$entryid");
        $this->does_not_exist('Save', 'button');
        $this->i_go_to_dataform_page("view.php?d=$dataformid&view=$viewid");
    }

    /**
     * Verifies that an entry cannot be deleted neither via delete link nor via url.
     *
     * @Given /^I cannot delete entry "(?P<entry_id_string>(?:[^"]|\\")*)" with content "(?P<text_string>(?:[^"]|\\")*)" in dataform "(?P<dataform_id_string>(?:[^"]|\\")*)" view "(?P<view_id_string>(?:[^"]|\\")*)"$/
     * @param string $entryid
     * @param string $content
     * @param string $dataformid
     * @param string $viewid
     */
    public function i_cannot_delete_entry_with_content_in_dataform_view($entryid, $content, $dataformid, $viewid) {
        $this->does_not_exist("id_deleteentry$entryid", 'link');
        $url = 'view.php?d='. $dataformid. '&view='. $viewid. '&delete='. $entryid. '&sesskey='. sesskey();
        $this->i_go_to_dataform_page($url);
        $this->i_see($content);
    }

    /* REPHRASES */

    /**
     * Checks, that page contains specified text. It also checks if the text is visible when running Javascript tests.
     *
     * @Then /^I see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function i_see($text) {
        return ($this->execute('behat_general::assert_page_contains_text', array($text)));
    }

    /**
     * Checks, that page doesn't contain specified text. When running Javascript tests it also considers that texts may be hidden.
     *
     * @Then /^I do not see "(?P<text_string>(?:[^"]|\\")*)"$/
     * @throws ExpectationException
     * @param string $text
     */
    public function i_do_not_see($text) {
        return ($this->execute('behat_general::assert_page_not_contains_text', array($text)));
    }

    /**
     * Checks the provided element and selector type exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" exists$/
     * @throws ElementNotFoundException Thrown by behat_base::find
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function exists($element, $selectortype) {
        return ($this->execute('behat_general::should_exist', array($element, $selectortype)));
    }

    /**
     * Checks that the provided element and selector type not exists in the current page.
     *
     * This step is for advanced users, use it if you don't find anything else suitable for what you need.
     *
     * @Then /^"(?P<element_string>(?:[^"]|\\")*)" "(?P<selector_string>[^"]*)" does not exist$/
     * @throws ExpectationException
     * @param string $element The locator of the specified selector
     * @param string $selectortype The selector type
     */
    public function does_not_exist($element, $selectortype) {
        return ($this->execute('behat_general::should_not_exist', array($element, $selectortype)));
    }

    /* UTILITY STEPS */
    /**
     * Opens the course homepage.
     *
     * @Given /^I am on "(?P<coursefullname_string>(?:[^"]|\\")*)" course gradebook$/
     * @throws coding_exception
     * @param string $coursefullname The full name of the course.
     * @return void
     */
    public function i_am_on_course_gradebook($coursefullname) {
        global $DB;
        $course = $DB->get_record("course", array("fullname" => $coursefullname), 'id', MUST_EXIST);
        $url = new moodle_url('/grade/report/grader/index.php', ['id' => $course->id]);
        $this->getSession()->visit($this->locate_path($url->out_as_local_url(false)));
    }

    /* SCENARIOS */

    /**
     * Returns list of steps for manage view scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_view_management(TableNode $data) {

        if (!$items = $data->getHash()) {
            return;
        }

        $dataformname = "View management";

        $this->i_start_afresh_with_dataform($dataformname);

        $this->execute('behat_auth::i_log_in_as', array('teacher1'));
        $this->execute('behat_navigation::i_am_on_course_homepage', array('Course 1'));
        $this->execute('behat_general::click_link', array($dataformname));
        $this->i_go_to_manage_dataform('views');

        foreach ($items as $item) {
            $viewtype = $item['viewtype'];
            $viewname = !empty($item['viewname']) ? $item['viewname'] : "view$viewtype";

            $this->i_add_a_dataform_view_with($viewtype, $viewname);
            $this->i_see($viewname);
            $this->execute('behat_general::click_link', array("Delete $viewname"));
            $this->execute('behat_forms::press_button', array('Continue'));
            $this->i_do_not_see($viewname);
        }
    }

    /**
     * Returns list of steps for view required field scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_view_required_field(TableNode $data) {

        if (!$items = $data->getHash()) {
            return;
        }

        $this->start_afresh_steps();

        $dataformname = 'View required field';
        $dataformidn = 'dfidn';

        // Dataform.
        $arr = array(
            array('course', 'C1'),
            array('idnumber', $dataformidn),
            array('name', $dataformname),
        );
        $table = new TableNode($arr);
        $this->the_following_dataform_exists($table);

        // Get the dataform so that we can replace patterns in the views.
        $df = mod_dataform_dataform::instance($this->get_dataform_id($dataformidn));

        $this->i_am_in_dataform_as($dataformname, 'Course 1', 'teacher1');

        // Field.
        $arr = array(
            array('name', 'type', 'dataform'),
            array('Field 01', 'text', $dataformidn),
        );
        $table = new TableNode($arr);
        $this->the_following_dataform_exist('fields', $table);

        foreach ($items as $item) {
            $viewtype = $item['viewtype'];
            $viewname = !empty($item['viewname']) ? $item['viewname'] : "view$viewtype";
            $entrytemplate = $item['entrytemplate'];

            /* View */
            $arr = array(
                array('name', 'type', 'dataform', 'default'),
                array($viewname, $viewtype, $dataformidn, 1),
            );
            $table = new TableNode($arr);
            $this->the_following_dataform_exist('views', $table);

            // Replace the field pattern in entry template with required.
            $view = $df->view_manager->get_view_by_name($viewname);
            $view->replace_patterns_in_view(array('[[Field 01]]'), array('[[*Field 01]]'));

            // Go to the view.
            $this->i_am_on_view_in_dataform($viewname, $dataformidn);

            $this->execute('behat_general::click_link', array('Add a new entry'));
            $this->execute('behat_general::i_click_on', array('id_field_1_-1', 'field'));
            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see('You must supply a value here');
            $this->execute('behat_general::should_exist', array('Save', 'button'));

            $this->execute('behat_forms::i_set_the_field_to', array('id_field_1_-1', "The field is required in $viewname"));
            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see("The field is required in $viewname");
        }
    }

    /**
     * Returns list of steps for view image in template scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_view_image_in_template(TableNode $data) {

        if (!$items = $data->getHash()) {
            return;
        }

        $dataformname = "View image in template";

        $this->i_start_afresh_with_dataform($dataformname);

        $this->execute('behat_auth::i_log_in_as', array('teacher1'));
        $this->execute('behat_navigation::i_navigate_to_node_in', array('My private files', 'My profile'));
        $this->execute('behat_repository_upload::i_upload_file_to_filemanager', array('mod/dataform/tests/fixtures/test_image.jpg', 'Files'));
        $this->execute('behat_general::i_click_on', array('Save changes', 'button'));
        $this->execute('behat_general::i_am_on_home_page');

        $this->execute('behat_navigation::i_am_on_course_homepage', array('Course 1'));
        $this->execute('behat_general::click_link', array($dataformname));

        foreach ($items as $item) {
            $viewtype = $item['viewtype'];
            $viewname = !empty($item['viewname']) ? $item['viewname'] : "view$viewtype";

            /* View */
            $this->i_go_to_manage_dataform('views');
            $this->execute('behat_forms::i_set_the_field_to', array('Add a view', $viewtype));
            $this->execute('behat_forms::i_expand_all_fieldsets', array());
            $this->execute('behat_forms::i_set_the_field_to', array('Name', $viewname));
            $this->execute('behat_general::i_click_on', array('Image', 'button'));
            $this->execute('behat_general::i_click_on', array('Browse repositories...', 'button'));
            $this->execute('behat_general::i_click_on', array('Private files', 'link'));
            $this->execute('behat_general::i_click_on', array('test_image.jpg', 'link'));
            $this->execute('behat_general::i_click_on', array('Select this file', 'button'));
            $this->execute('behat_general::i_click_on', array('Description not necessary', 'checkbox'));
            $this->execute('behat_general::i_click_on', array('Save image', 'button'));
            $this->execute('behat_forms::press_button', array('Save changes'));

            $this->i_set_as_default_view($viewname);

            $this->execute('behat_general::click_link', array('Browse'));

            $this->execute('behat_general::should_exist', array('//img[contains(@src, \'pluginfile.php\')]', 'xpath_element'));
            $this->execute('behat_general::should_exist', array('//img[contains(@src, \'test_image.jpg\')]', 'xpath_element'));
        }
    }

    /**
     * Returns list of steps for view submission buttons scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_view_submission_buttons(TableNode $data) {
        global $DB;


        if (!$items = $data->getHash()) {
            return;
        }

        $dataformname = 'View submission buttons';
        $dataformidn = 'dfidn';

        $submissionoptions = array(
            'save' => '',
            'savecont' => '',
            'savenew' => '',
            'savecontnew' => '',
            'savenewcont' => '',
            'cancel' => '',
        );
        $allbuttons = base64_encode(serialize($submissionoptions));

        $this->start_afresh_steps();

        // Dataform.
        $arr = array(
            array('course', 'C1'),
            array('idnumber', $dataformidn),
            array('name', $dataformname),
        );
        $table = new TableNode($arr);
        $this->the_following_dataform_exists($table);

        // Fields.
        $arr = array(
            array('name', 'type', 'dataform', 'editable'),
            array('Field 01', 'text', $dataformidn, 1),
        );
        $table = new TableNode($arr);
        $this->the_following_dataform_exist('fields', $table);

        foreach ($items as $item) {

            $viewtype = $item['viewtype'];
            $viewname1 = !empty($item['viewname1']) ? $item['viewname1'] : "view{$viewtype}1";
            $viewname2 = !empty($item['viewname2']) ? $item['viewname2'] : "view{$viewtype}2";

            $actor = $item['actor'];

            // Views (one default with ALL submission buttons and one with no submission buttons.
            $arr = array(
                array('name', 'type', 'dataform', 'default', 'visible', 'submission'),
                array($viewname1, $viewtype, $dataformidn, 1, 1, $allbuttons),
                array($viewname2, $viewtype, $dataformidn, 0, 1, ''),
            );
            $table = new TableNode($arr);
            $this->the_following_dataform_exist('views', $table);

            // Log in
            $this->execute('behat_auth::i_log_in_as', array($actor));
            $this->execute('behat_navigation::i_am_on_course_homepage', array('Course 1'));
            $this->execute('behat_general::click_link', array($dataformname));

            $field01xpath = "//div[contains(concat(' ', normalize-space(@class), ' '), ' Field_01 ')]//input[@type='text']";

            // SAVE: The entry should be added.
            $this->execute('behat_general::click_link', array('Add a new entry'));
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 01'));
            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see('Entry 01');

            // CANCEL: The entry should not be added.
            $this->execute('behat_general::click_link', array('Add a new entry'));
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 02'));
            $this->execute('behat_forms::press_button', array('Cancel'));
            $this->i_see('Entry 01');
            $this->i_do_not_see('Entry 02');

            // SAVE and CONTINUE: The entry should be added and should stay in form.
            $this->execute('behat_general::click_link', array('Add a new entry'));
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 03'));
            $this->execute('behat_forms::press_button', array('Save and Continue'));
            $this->i_do_not_see('Add a new entry');
            $this->i_do_not_see('Entry 01');
            $this->execute('behat_forms::the_field_with_xpath_matches_value', array($field01xpath, 'Entry 03'));

            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 02'));
            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see('Add a new entry');
            $this->i_see('Entry 01');
            $this->i_see('Entry 02');

            // SAVE as NEW (existing entry): A new entry should be added.
            $this->execute('behat_general::i_click_on_in_the', array('Edit', 'link', 'Entry 02', 'table_row'));
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 03'));
            $this->execute('behat_forms::press_button', array('Save as New'));
            $this->i_see('Add a new entry');
            $this->i_see('Entry 01');
            $this->i_see('Entry 02');
            $this->i_see('Entry 03');

            // SAVE as NEW (new entry): The entry should be added.
            $this->execute('behat_general::click_link', array('Add a new entry'));
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 04'));
            $this->execute('behat_forms::press_button', array('Save as New'));
            $this->i_see('Add a new entry');
            $this->i_see('Entry 01');
            $this->i_see('Entry 02');
            $this->i_see('Entry 03');
            $this->i_see('Entry 04');

            // SAVE and START NEW (new entry): The entry should be added and and new entry form opened.
            $this->execute('behat_general::click_link', array('Add a new entry'));
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 05'));
            $this->execute('behat_forms::press_button', array('Save and Start New'));
            $this->i_do_not_see('Add a new entry');
            $this->execute('behat_forms::the_field_with_xpath_matches_value', array($field01xpath, ''));

            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 06'));
            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see('Add a new entry');
            $this->i_see('Entry 01');
            $this->i_see('Entry 02');
            $this->i_see('Entry 03');
            $this->i_see('Entry 04');
            $this->i_see('Entry 05');
            $this->i_see('Entry 06');

            // SAVE and START NEW (existing entry): The entry should be updated and new entry form opened.
            $this->execute('behat_general::i_click_on_in_the', array('Edit', 'link', 'Entry 04', 'table_row'));
            $this->execute('behat_forms::the_field_with_xpath_matches_value', array($field01xpath, 'Entry 04'));

            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 04 modified'));
            $this->execute('behat_forms::press_button', array('Save and Start New'));
            $this->i_do_not_see('Add a new entry');
            $this->execute('behat_forms::the_field_with_xpath_matches_value', array($field01xpath, ''));

            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 07'));
            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see('Add a new entry');
            $this->i_see('Entry 01');
            $this->i_see('Entry 02');
            $this->i_see('Entry 03');
            $this->i_see('Entry 04 modified');
            $this->i_see('Entry 05');
            $this->i_see('Entry 06');
            $this->i_see('Entry 07');

            // SAVE as NEW and CONTINUE (new entry): The entry should be added and and remain in its form.
            $this->execute('behat_general::click_link', array('Add a new entry'));
            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 08'));
            $this->execute('behat_forms::press_button', array('Save as New and Continue'));
            $this->i_do_not_see('Add a new entry');
            $this->execute('behat_forms::the_field_with_xpath_matches_value', array($field01xpath, 'Entry 08'));

            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 08 modified'));
            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see('Add a new entry');
            $this->i_see('Entry 01');
            $this->i_see('Entry 02');
            $this->i_see('Entry 03');
            $this->i_see('Entry 04 modified');
            $this->i_see('Entry 05');
            $this->i_see('Entry 06');
            $this->i_see('Entry 07');
            $this->i_see('Entry 08 modified');

            // SAVE as NEW and CONTINUE (existing entry): The entry should be added and remain in its form.
            $this->execute('behat_general::i_click_on_in_the', array('Edit', 'link', 'Entry 08 modified', 'table_row'));
            $this->execute('behat_forms::the_field_with_xpath_matches_value', array($field01xpath, 'Entry 08 modified'));

            $this->execute('behat_forms::i_set_the_field_with_xpath_to', array($field01xpath, 'Entry 09'));
            $this->execute('behat_forms::press_button', array('Save as New and Continue'));
            $this->i_do_not_see('Add a new entry');
            $this->execute('behat_forms::the_field_with_xpath_matches_value', array($field01xpath, 'Entry 09'));

            $this->execute('behat_forms::press_button', array('Save'));
            $this->i_see('Add a new entry');
            $this->i_see('Entry 01');
            $this->i_see('Entry 02');
            $this->i_see('Entry 03');
            $this->i_see('Entry 04 modified');
            $this->i_see('Entry 05');
            $this->i_see('Entry 06');
            $this->i_see('Entry 07');
            $this->i_see('Entry 08 modified');
            $this->i_see('Entry 09');

            // No submission buttons.
            $this->i_am_on_view_in_dataform($viewname2, $dataformidn);
            $this->i_do_not_see('Add a new entry');
            $this->does_not_exist('id_editentry1', 'link');

            // I shouldn't be able to edit via the url.

            $this->execute('behat_auth::i_log_out', array());
            $this->user_data_in_dataform_is_reset($dataformidn);
        }

    }

    /**
     * Returns list of steps for manage field scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_field_management(TableNode $data) {

        if (!$items = $data->getHash()) {
            return;
        }

        $dataformname = "Field management";

        $this->i_start_afresh_with_dataform($dataformname);

        $this->execute('behat_auth::i_log_in_as', array('teacher1'));
        $this->execute('behat_navigation::i_am_on_course_homepage', array('Course 1'));
        $this->execute('behat_general::click_link', array($dataformname));
        $this->i_go_to_manage_dataform('fields');

        foreach ($items as $item) {
            $fieldtype = $item['fieldtype'];
            $fieldname = !empty($item['fieldname']) ? $item['fieldname'] : "field$fieldtype";

            // Add.
            $this->i_add_a_dataform_field_with($fieldtype, $fieldname);
            $this->i_see($fieldname);
            // Edit.
            $this->execute('behat_general::click_link', array("Edit $fieldname"));
            $this->i_see("Editing '$fieldname'");
            $this->execute('behat_forms::i_set_the_field_to', array('Description', "$fieldname modified"));
            $this->execute('behat_forms::press_button', array('Save changes'));
            $this->i_see("$fieldname modified");
            // Delete.
            $this->execute('behat_general::click_link', array("Delete $fieldname"));
            $this->execute('behat_forms::press_button', array('Continue'));
            $this->i_do_not_see($fieldname);
        }
    }

    /**
     * Returns list of steps for manage access rule scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_access_rule_management(TableNode $data) {
        $data = $data->getRowsHash();
        $ruletype = $data['ruletype'];
        $typename = get_string('typename', "block_dataformaccess$ruletype");
        $rulename = !empty($data['rulename']) ? $data['rulename'] : "New $typename rule";


        $this->i_start_afresh_with_dataform('Test Dataform');

        $this->execute('behat_auth::i_log_in_as', array('teacher1'));
        $this->execute('behat_navigation::i_am_on_course_homepage', array('Course 1'));
        $this->execute('behat_general::click_link', array('Test Dataform'));
        $this->i_go_to_manage_dataform('access');

        // Add a rule.
        $this->execute('behat_general::click_link', array('id_add_'. $ruletype. '_access_rule'));
        $this->i_see("New $typename rule");

        // Update the rule.
        $this->execute('behat_general::click_link', array('id_editaccess'. $ruletype. '1'));
        $this->execute('behat_forms::i_set_the_field_to', array('Name', "New $typename rule modified"));
        $this->execute('behat_forms::press_button', array('Save changes'));
        $this->i_see("New $typename rule modified");

        // Delete the rule.
        $this->execute('behat_general::click_link', array('id_deleteaccess'. $ruletype. '1'));
        $this->i_do_not_see("New $typename rule modified");
    }

    /**
     * Returns list of steps for manage notification rule scenario.
     *
     * @param TableNode $data Scenario data.
     * @return array Array of Given objects.
     */
    protected function scenario_notification_rule_management(TableNode $data) {
        $data = $data->getRowsHash();
        $ruletype = !empty($data['ruletype']) ? $data['ruletype'] : null;
        $typename = get_string('typename', "block_dataformnotification$ruletype");
        $rulename = !empty($data['rulename']) ? $data['rulename'] : "New $typename rule";


        $this->i_start_afresh_with_dataform('Test Dataform');

        $this->execute('behat_auth::i_log_in_as', array('teacher1'));
        $this->execute('behat_navigation::i_am_on_course_homepage', array('Course 1'));
        $this->execute('behat_general::click_link', array('Test Dataform'));
        $this->execute('behat_navigation::i_navigate_to_in_current_page_administration', array('Manage notification rules'));

        // Add a rule.
        $this->execute('behat_general::click_link', array('id_add_'. $ruletype. '_notification_rule'));
        $this->i_see("New $typename rule");

        // Update the rule.
        $this->execute('behat_general::click_link', array('id_editnotification'. $ruletype. '1'));
        $this->execute('behat_forms::i_set_the_field_to', array('Name', "New $typename rule modified"));
        $this->execute('behat_forms::i_set_the_field_to', array('Events', 'Entry created'));
        $this->execute('behat_forms::i_set_the_field_to', array('Admin', 'Check'));
        $this->execute('behat_forms::press_button', array('Save changes'));
        $this->i_see("New $typename rule modified");

        // Delete the rule.
        $this->execute('behat_general::click_link', array('id_deletenotification'. $ruletype. '1'));
        $this->i_do_not_see("New $typename rule modified");
    }

    /* HELPERS */

    /**
     * Returns list of steps for filling a dataform mod_form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function dataform_form_fill_steps($data) {

        $formfields = array(
            'Name',
            'Description',
            'Display description on course page',
            'Activity icon',
            'Inline view',
            'Embedded',
            'Available from',
            'Due',
            'Duration',
            'Number of intervals',
            'Maximum entries',
            'Required entries',
            'Separate participants',
            'Group entries',
            'Anonymize entries',
            'Editing time limit (minutes)',
            'Grade',
            'Grading method',
            'Calculation',
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $this->execute('behat_forms::i_set_the_field_to', array($name, $val));
        }

    }

    /**
     * Returns list of steps for filling a dataform mod_form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function dataform_form_match_steps($data) {

        $formfields = array(
            'Name',
            'Description',
            'Display description on course page',
            'Activity icon',
            'Inline view',
            'Embedded',
            'Available from',
            'Due',
            'Duration',
            'Number of intervals',
            'Maximum entries',
            'Required entries',
            'Separate participants',
            'Group entries',
            'Anonymize entries',
            'Editing time limit (minutes)',
            'Grade',
            'Grading method',
            'Calculation',
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            if ($name == 'Description') {
                $this->execute('behat_forms::the_field_matches_value', array($name, "<p>$val</p>"));
                continue;
            }

            $this->execute('behat_forms::the_field_matches_value', array($name, $val));
        }

    }

    /**
     * Returns list of steps for filling a dataformfield general form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_base($data) {

        $formfields = array(
            'Name',
            'Description',
            'Visible',
            'Editable',
            'Template',
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $this->execute('behat_forms::i_set_the_field_to', array($name, $val));
        }

    }

    /**
     * Returns list of steps for filling a dataformfield selectmulti specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_selectmulti($data) {
        $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return;
        }

        $formfields = array(
            'Options',
            'Default values',
            'Options separator',
            'Allow adding options'
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            // Fix value for text area.
            if ($name == 'Options' or $name == 'Default values' and $val) {
                $val = implode("\n", explode('\n', $val));
            }

            $this->execute('behat_forms::i_set_the_field_to', array($name, $val));
        }

    }

    /**
     * Returns list of steps for filling a dataformfield selectmulti form.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_checkbox($data) {
        return $this->field_form_fill_steps_selectmulti($data);
    }

    /**
     * Returns list of steps for filling a dataformfield select specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_select($data) {
        $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return;
        }

        $formfields = array(
            'Options',
            'Default value',
            'Allow adding options'
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            // Fix value for text area.
            if ($name == 'Options' and $val) {
                $val = implode("\n", explode('\n', $val));
            }

            $this->execute('behat_forms::i_set_the_field_to', array($name, $val));
        }

    }

    /**
     * Returns list of steps for filling a dataformfield text specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_text($data) {
        $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return;
        }

        $formfields = array(
            'Auto link', // Auto link.
            'param2', // Width.
            'param3', // Width unit.
            'Format', // alphanumeric|lettersonly|numeric|email|nopunctuation.
            'param5', // Number of character (minlength|maxlength|rangelength).
            'param6', // Min (integer).
            'param7', // Nax (integer).
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $this->execute('behat_forms::i_set_the_field_to', array($name, $val));
        }

    }

    /**
     * Returns list of steps for filling a dataformfield number specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_number($data) {
        $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return;
        }

        $formfields = array(
            'Decimals',
            'param2', // Width.
            'param3', // Width unit.
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            $this->execute('behat_forms::i_set_the_field_to', array($name, $val));
        }

    }

    /**
     * Returns list of steps for filling a dataformfield radiobutton specific form settings.
     *
     * @param string $data Tab delimited field form data.
     * @return array Array of Given objects.
     */
    protected function field_form_fill_steps_radiobutton($data) {
        $this->field_form_fill_steps_base($data);

        if (!$data = $this->truncate_data_vals($data, 5)) {
            return;
        }

        $formfields = array(
            'Options',
            'Default value',
            'Options separator',
            'Allow adding options'
        );

        $vals = explode("\t", trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            if (!$val = trim($vals[$key])) {
                continue;
            }

            // Fix value for text area.
            if ($name == 'Options' and $val) {
                $val = implode("\n", explode('\n', $val));
            }

            $this->execute('behat_forms::i_set_the_field_to', array($name, $val));
        }

    }

    /**
     * Returns the data with first n values truncated.
     *
     * @param string $data Tab delimited field form data.
     * @param int $num Number of values to truncate.
     * @return string
     */
    protected function truncate_data_vals($data, $num) {
        $truncated = array_slice(explode("\t", trim($data)), $num);
        return implode("\t", $truncated);
    }

    /**
     * Converts filter data from string to TableNode.
     *
     * @param string $data Tab delimited filter form data.
     * @return TableNode
     */
    protected function convert_data_to_table($formfields, $data, $delimiter = "\t") {
        $vals = explode($delimiter, trim($data));
        $names = array_slice($formfields, 0, count($vals));
        $tabledata = array();
        foreach ($names as $key => $name) {
            $tabledata[] = array($name, $vals[$key]);
        }
        return new TableNode($tabledata);
    }

    /**
     * Gets the user id from it's username.
     * @throws Exception
     * @param string $username
     * @return int
     */
    protected function get_user_id($username) {
        global $DB;

        if (empty($username)) {
            return 0;
        }

        if (!$id = $DB->get_field('user', 'id', array('username' => $username))) {
            throw new Exception('The specified user with username "' . $username . '" does not exist');
        }
        return $id;
    }

    /**
     * Gets the dataform id from it's idnumber.
     * @throws Exception
     * @param string $idnumber
     * @return int
     */
    protected function get_dataform_id($idnumber) {
        global $DB;

        if (!$id = $DB->get_field('course_modules', 'instance', array('idnumber' => $idnumber))) {
            throw new Exception('The specified dataform with idnumber "' . $idnumber . '" does not exist');
        }

        return $id;
    }

    /**
     * Gets the view id from the dataform id by view name.
     * @throws Exception
     * @param string $dataformid.
     * @return int
     */
    protected function get_dataform_view_id($viewname, $dataformid) {
        global $DB;

        if (!$id = $DB->get_field('dataform_views', 'id', array('name' => $viewname, 'dataid' => $dataformid))) {
            throw new Exception('The specified dataform view with name "' . $viewname . '" does not exist');
        }

        return $id;
    }

    /**
     * Gets the group id from it's idnumber.
     * @throws Exception
     * @param string $idnumber
     * @return int
     */
    protected function get_group_id($idnumber) {
        global $DB;

        if (empty($idnumber)) {
            return 0;
        }

        if (!$id = $DB->get_field('groups', 'id', array('idnumber' => $idnumber))) {
            throw new Exception('The specified group with idnumber "' . $idnumber . '" does not exist');
        }
        return $id;
    }

}
