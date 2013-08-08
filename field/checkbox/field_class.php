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
 * @subpackage checkbox
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/multiselect/field_class.php");

class dataformfield_checkbox extends dataformfield_multiselect {

    public $type = 'checkbox';

    /**
     *
     */
    protected function content_names() {
        $optioncount = count(explode("\n",$this->field->param1));
        $contentnames = array('newvalue');
        foreach (range(1, $optioncount) as $key) {
            $contentnames[] = "selected_$key";
        }
        // Add contentname selected for import
        $contentnames[] = 'selected';

        return $contentnames;
    }
    
    /**
     *
     */
    protected function format_content($entry, array $values = null) {
        $fieldid = $this->field->id;
        $entryid = $entry->id;

        // When called by import values are already collated in selected
        if (!empty($values['selected'])) {
            return parent::format_content($entry, $values);
        }

        // When called by form submission collate the selected to one array
        $selected = array();
        if (!empty($values)) {
            $optioncount = count(explode("\n",$this->field->param1));
            foreach (range(1, $optioncount) as $key) {
                if (!empty($values["selected_$key"])) {
                    $selected[] = $key;
                }
            }
        }
        $values['selected'] = $selected;

        return parent::format_content($entry, $values);
    }
    
    /**
     *
     */
    public function parse_search($formdata, $i) {
        $selected = array();
        
        $fieldname = "f_{$i}_{$this->field->id}";
        foreach (array_keys($this->options_menu()) as $cb) {
            if (!empty($formdata->{"{$fieldname}_$cb"})) {
                $selected[] = $cb;
            }
        }
        if ($selected) {
            if (!empty($formdata->{"{$fieldname}_allreq"})) {
                $allrequired = $formdata->{"{$fieldname}_allreq"};
            } else {
                $allrequired = '';
            }
            return array('selected'=>$selected, 'allrequired'=>$allrequired);
        } else {
            return false;
        }
    }
}
