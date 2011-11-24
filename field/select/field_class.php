<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-select
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain, including:
 * @copyright 1999 Moodle Pty Ltd http://moodle.com
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field_select extends dataform_field_base {

    public $type = 'select';

    /**
     * 
     */
    public function update_content($entry, array $values = null) {
        global $DB;

        $fieldid = $this->field->id;
        
        $selected = $newvalue = null;
        if (!empty($values)) {
            foreach ($values as $name => $value) {
                $names = explode('_', $name);
                if (!empty($names[3]) and !empty($value)) {
                    ${$names[3]} = $value;
                }
            }
        }

        if ($newvalue = s($newvalue)) {
            $options = $this->options_menu();
            if (!$selected = (int) array_search($newvalue, $options)) {
                $selected = count($options) + 1;
                $this->field->param1 = trim($this->field->param1). "\n$newvalue";
                $this->update_field();
            }
        }

        $oldcontent = isset($entry->{"c{$fieldid}_content"}) ? $entry->{"c{$fieldid}_content"} : null;
        $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
        
        $rec = new object();
        $rec->fieldid = $this->field->id;
        $rec->entryid = $entry->id;
        $rec->content = $selected;

        if (!empty($oldcontent)) {
            if ($selected != $oldcontent) {
                if (empty($selected)) {
                    $this->delete_content($entry->id);
                } else {
                    $rec->id = $contentid; // MUST_EXIST
                    return $DB->update_record('dataform_contents', $rec);
                 }
            }
        } else {
            if (!empty($selected)) {
                return $DB->insert_record('dataform_contents', $rec);
            }
        }
        return true;
    }

    /**
     * 
     */
    function get_sql_compare_text() {
        global $DB;
        return $DB->sql_compare_text("c{$this->field->id}.content", 255);
    }

    /**
     * 
     */
    public function options_menu() {
        $rawoptions = explode("\n",$this->field->param1);
        $options = array();
        $key = 1;
        foreach ($rawoptions as $option) {
            $option = trim($option);
            if ($option) {
                $options[$key] = $option;
                $key++;
            }
        }
        return $options;
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
            $allownew = $importsettings[$fieldname]['allownew'];
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
