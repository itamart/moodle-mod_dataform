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
 * Field testcase
 *
 * @package    dataformfield_checkbox
 * @category   phpunit
 * @group      dataformfield_checkbox
 * @copyright  2017 Itamar Tzadok {@link http://substantialmethods.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dataformfield_checkbox_testcase extends advanced_testcase {
    protected $df;
    protected $view;
    protected $checkbox;

    /**
     * Test set up.
     *
     * This is executed before running any test in this file.
     */
    public function setUp() {
        $this->resetAfterTest();

        // Reset dataform local cache.
        \mod_dataform_instance_store::unregister();

        $this->setAdminUser();
        $this->df = $this->get_a_dataform();

        // SETUP
        // Add a field.
        $checkbox = $this->df->field_manager->add_field('checkbox');
        $checkbox->update((object) array('param1' => "SM01\nSM02\nSM03\nSM04"));
        $this->checkbox = $checkbox;

        // Add a view.
        $this->view = $this->df->view_manager->add_view('aligned');
        $template = "[[checkbox]]\n[[checkbox:hasselection]]";
        $this->view->set_default_entry_template($template);
    }

    /**
     * Test default config.
     */
    public function test_patterns() {
        $view = $this->view;
        $checkbox = $this->checkbox;

        // Add an entry.
        $data = (object) array(
            "field_{$checkbox->id}_-1_selected" => array(1,3),
            'submitbutton_save' => 'Save'
        );
        $entryman = $view->entry_manager;
        list(, $eids) = $entryman->process_entries('update', array(-1), $data, true);
        $entryid = reset($eids);

        $filter = $view->filter;
        $filter->eids = $entryid;
        $entryman->set_content(array('filter' => $filter));
        $entries = $entryman->entries;
        $entry = reset($entries);

        // Patterns.
        $patternstoverify = array('checkbox', 'checkbox:hasselection');
        $patternvalues = $this->get_pattern_values($entry, $patternstoverify);
        $this->assertNotEquals('', $patternvalues['checkbox']);
        $this->assertEquals(1, $patternvalues['checkbox:hasselection']);

        // Update entry.
        $data = (object) array(
            "field_{$checkbox->id}_{$entryid}_selected" => array(),
            'submitbutton_save' => 'Save'
        );
        $entryman = $view->entry_manager;
        list(, $eids) = $entryman->process_entries('update', array($entryid), $data, true);
        $entryid = reset($eids);

        $filter = $view->filter;
        $filter->eids = $entryid;
        $entryman->set_content(array('filter' => $filter));
        $entries = $entryman->entries;
        $entry = reset($entries);

        // Patterns.
        $patternstoverify = array('checkbox', 'checkbox:hasselection');
        $patternvalues = $this->get_pattern_values($entry, $patternstoverify);
        $this->assertEquals('', $patternvalues['checkbox']);
        $this->assertEquals(0, $patternvalues['checkbox:hasselection']);
    }

    protected function get_pattern_values($entry, array $patterns) {
        $patternvalues = array_fill_keys($patterns, null);

        foreach ($patterns as $pattern) {
            // Get the field by name.
            list($fieldname, ) = array_pad(explode(':', $pattern, 2), 2, null);
            if (!$field = $this->df->field_manager->get_field_by_name($fieldname)) {
                continue;
            }

            // Get the pattern value replacement.
            $fieldpattern = '[['. trim($pattern, '[]'). ']]';
            if ($replacements = $field->renderer->get_replacements(array($fieldpattern), $entry)) {
                // Take the first: should be value.
                $value = reset($replacements);
                $patternvalues[$pattern] = $value;
            }
        }
        return $patternvalues;
    }

    /**
     * Sets up a dataform activity in a course.
     *
     * @return mod_dataform_dataform
     */
    protected function get_a_dataform($course = null) {
        $course = !$course ? $this->getDataGenerator()->create_course() : $course;

        // Create a dataform instance.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_dataform');
        $dataform = $generator->create_instance(array('course' => $course));
        $dataformid = $dataform->id;

        return \mod_dataform_dataform::instance($dataformid);
    }

}
