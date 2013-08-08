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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 
/**
 * @package dataformfield
 * @subpackage select
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield_select extends dataformfield_base {

    public $type = 'select';
    
    protected $_options = array();

    /**
     * Update a field in the database
     */
    public function update_field($fromform = null) {
        global $DB;
        
        // before we update get the current options
        $oldoptions = $this->options_menu();
        // update
        parent::update_field($fromform);       

        // adjust content if necessary
        $adjustments = array();
        // Get updated options
        $newoptions = $this->options_menu(true);
        foreach ($newoptions as $newkey => $value) {
            if (!isset($oldoptions[$newkey]) or $value != $oldoptions[$newkey]) {
                if ($key = array_search($value, $oldoptions) or $key !== false) {
                    $adjustments[$key] = $newkey;
                }
            }
        }

        if (!empty($adjustments)) {
            // fetch all contents of the field whose content in keys
            list($incontent, $params) = $DB->get_in_or_equal(array_keys($adjustments));
            array_unshift($params, $this->field->id);
            $contents = $DB->get_records_select_menu('dataform_contents',
                                        " fieldid = ? AND content $incontent ",
                                        $params,
                                        '',
                                        'id,content'); 
            if ($contents) {
                if (count($contents) == 1) {
                    list($id, $content) = each($contents);
                    $DB->set_field('dataform_contents', 'content', $adjustments[$content], array('id' => $id));
                } else { 
                    $params = array();
                    $sql = "UPDATE {dataform_contents} SET content = CASE id ";
                    foreach ($contents as $id => $content) {
                        $newcontent = $adjustments[$content];
                        $sql .= " WHEN ? THEN ? ";
                        $params[] = $id;
                        $params[] = $newcontent;
                    }
                    list($inids, $paramids) = $DB->get_in_or_equal(array_keys($contents));
                    $sql .= " END WHERE id $inids ";
                    $params = array_merge($params, $paramids);
                    $DB->execute($sql, $params);
                }
            }
        }
        return true;
    }

    /**
     *
     */
    protected function content_names() {
        return array('selected', 'newvalue');
    }
    
    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->field->id;
        // old contents
        $oldcontents = array();
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontents[] = $entry->{"c{$fieldid}_content"};
        }
        // new contents
        $contents = array();

        $selected = $newvalue = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                $value = (string) $value;
                if (!empty($name) and !empty($value)) {
                    ${$name} = $value;
                }
            }
        }        
        // update new value in the field type
        if ($newvalue = s($newvalue)) {
            $options = $this->options_menu();
            if (!$selected = (int) array_search($newvalue, $options)) {
                $selected = count($options) + 1;
                $this->field->param1 = trim($this->field->param1). "\n$newvalue";
                $this->update_field();
            }
        }
        // add the content
        if (!is_null($selected)) {
            $contents[] = $selected;
        }

        return array($contents, $oldcontents);
    }

    /**
     * 
     */
    protected function get_sql_compare_text($column = 'content') {
        global $DB;
        return $DB->sql_compare_text("c{$this->field->id}.$column", 255);
    }

    /**
     *
     */
    public function get_search_value($value) {
        $options = $this->options_menu();
        if ($key = array_search($value, $options)) {
            return $key;
        } else {
            return '';
        }
    }

    /**
     * 
     */
    public function options_menu($forceget = false) {
        if (!$this->_options or $forceget) {
            if (!empty($this->field->param1)) {
                $rawoptions = explode("\n",$this->field->param1);
                foreach ($rawoptions as $key => $option) {
                    $option = trim($option);
                    if ($option != '') {
                        $this->_options[$key + 1] = $option;
                    }
                }
            }
        }
        return $this->_options;
    }

    /**
     * 
     */
    public function prepare_import_content(&$data, $importsettings, $csvrecord = null, $entryid = null) {
        // import only from csv
        if ($csvrecord) {
            $fieldid = $this->field->id;
            $fieldname = $this->name();
            $csvname = $importsettings[$fieldname]['name'];
            $allownew = !empty($importsettings[$fieldname]['allownew']) ? true : false;
            $label = !empty($csvrecord[$csvname]) ? $csvrecord[$csvname] : null;
            
            if ($label) {
                $options = $this->options_menu();
                if ($optionkey = array_search($label, $options)) {
                    $data->{"field_{$fieldid}_{$entryid}_selected"} = $optionkey;
                } else if ($allownew) {
                    $data->{"field_{$fieldid}_{$entryid}_newvalue"} = $label;
                }                    
            }
        }
    
        return true;
    }

}
