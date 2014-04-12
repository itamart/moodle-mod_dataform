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
 * mod_dataform data generator
 *
 * @package    mod_dataform
 * @category   phpunit
 * @copyright  2012 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;

/**
 * Page module PHPUnit data generator class
 *
 * @package    mod_dataform
 * @category   phpunit
 * @copyright  2014 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dataform_generator extends testing_module_generator {

    /**
     * Create new dataform module instance
     * @param array|stdClass $record
     * @param array $options (mostly course_module properties)
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        return parent::create_instance($record, (array)$options);
    }
    
    /**
     * Generates a dataform view.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_view($record, array $options = null) {
        $record = (object)(array)$record;
        $df = mod_dataform_dataform::instance($record->dataid);
        $view = $df->view_manager->get_view($record->type);
        $view->generate_default_view();
        $view->name = $record->name;
        $view->add($view->data);
        
        // Set as default if specified
        if (!empty($record->default)) {
            $df->view_manager->process_views('default', $view->id, true);
        }
        return $view->data;
    }
    
    /**
     * Generates a dataform field.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_field($record, array $options = null) {
        $record = (object)(array)$record;
        $df = mod_dataform_dataform::instance($record->dataid);
        $field = $df->field_manager->get_field($record->type);
        $field->name = $record->name;
        $field->create($field->data);
        return $field->data;
    }
    
    /**
     * Generates a dataform entry.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_entry($record, array $options = null) {
        global $DB;
        
        // Convert timecreated and timemodified
        $record['timecreated'] = !empty($record['timecreated']) ? strtotime($record['timecreated']) : 0;
        $record['timemodified'] = !empty($record['timemodified']) ? strtotime($record['timemodified']) : 0;
        
        $df = \mod_dataform_dataform::instance($record['dataid']);
        $entry = \mod_dataform\pluginbase\dataformentry::blank_instance($df, (object)(array)$record);
        $entry->id = $DB->insert_record('dataform_entries', $entry);
        
        // Add content
        if ($fields = $df->field_manager->get_fields()) {
            foreach ($fields as $field) {
                if (!empty($record[$field->name])) {
                    $field->update_content($entry, array($record[$field->name]));
                }
            }
        }
            
        return $entry;
    }
    
}