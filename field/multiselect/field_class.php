<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-multiselect
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

class dataform_field_multiselect extends dataform_field_base {

    public $type = 'multiselect';
    public $separators = array(
            array('name' => 'New line', 'chr' => '<br />'),
            array('name' => 'Space', 'chr' => '&#32;'),
            array('name' => ',', 'chr' => '&#44;'),
            array('name' => ', (with space)', 'chr' => '&#44;&#32;')
    );

    
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

        if ($newvalues = explode('#', s($newvalue))) {
            $options = $this->options_menu();
            $count = count($options);
            foreach ($newvalues as $newvalue) {
                if (!$optionkey = (int) array_search($newvalue, $options)) {
                    $this->field->param1 = trim($this->field->param1). "\n$newvalue";
                    $count++;
                    $selected[] = $count;
                }
            }
            if ($count > count($options)) {
                $this->update_field();
            }
        }

        $selected = implode('#', $selected);
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
    public function parse_search($formdata, $i) {
        $fieldname = "f_{$i}_{$this->field->id}";
        $selected = optional_param($fieldname, array(), PARAM_NOTAGS);
        if ($selected) {
            $allrequired = optional_param("{$fieldname}_allreq", 0, PARAM_BOOL);
            return array('selected'=>$selected, 'allrequired'=>$allrequired);
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        if (is_array($value)){
            $selected = implode(', ', $value['selected']);
            $allrequired = '('. ($value['allrequired'] ? get_string('requiredall') : get_string('requirednotall')). ')';
            return $not. ' '. $operator. ' '. $selected. ' '. $allrequired;
        } else {
            return false;
        }
    }  

    /**
     *
     */
    public function get_search_sql($search) {
        global $DB;
        
        list($not, , $value) = $search;

        static $i=0;
        $i++;
        $name = "df_{$this->field->id}_{$i}_";
        $params = array();

        $allrequired = $value['allrequired'];
        $selected    = $value['selected'];
        $content = "c{$this->field->id}.content";
        $varcharcontent = $DB->sql_compare_text($content, 255);

        if ($selected) {
            $conditions = array();
            foreach ($selected as $key => $sel) {
                $xname = $name. $key;
                $likesel = str_replace('%', '\%', $sel);
                $likeselsel = str_replace('_', '\_', $likesel);

                $conditions[] = "({$varcharcontent} = :{$xname}a".
                                   ' OR '. $DB->sql_like($content, ":{$xname}b").
                                   ' OR '. $DB->sql_like($content, ":{$xname}c").
                                   ' OR '. $DB->sql_like($content, ":{$xname}d"). ")";
                                   
                $params[$xname.'a'] = $sel;
                $params[$xname.'b'] = "$likesel#%";
                $params[$xname.'c'] = "%#$likesel";
                $params[$xname.'d'] = "%#$likesel#%";
            }
            if ($allrequired) {
                return array(" $not (".implode(" AND ", $conditions).") ", $params);
            } else {
                return array(" $not (".implode(" OR ", $conditions).") ", $params);
            }
        } else {
           return array(" ", $params);
        }
    }

    /**
     *
     */
    public function format_content($content) {
        if (!empty($content)) {
            $content = $this->get_content($content); // expected an array
            $optionscount = count(explode("\n", $this->field->param1));

            $vals = array();
            foreach ($content as $key => $val) {
                if ($key === 'xxx') {
                    continue;
                }
                if ((int) $val > $optionscount) {
                    continue;
                }
                $vals[] = $val;
            }

            if (empty($vals)) {
                return null;
            } else {
                return implode('#', $vals);
            }
            
        } else {
            return null;
        }
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
            $labels = !empty($csvrecord[$csvname]) ? explode('#', $csvrecord[$csvname]) : null;
            
            if ($labels) {
                $options = $this->options_menu();
                $selected = array();
                $newvalues = array();
                foreach ($labels as $label) {
                    if ($optionkey = array_search($label, $options)) {
                        $selected[] = $optionkey;
                    } else if ($allownew) {
                        $newvalues[] = $label;
                    }
                }
                if ($selected) {
                    $data->{"field_{$fieldid}_{$entryid}_selected"} = $selected;
                }
                if ($newvalues) {
                    $data->{"field_{$fieldid}_{$entryid}_newvalue"} = implode('#', $newvalues);
                }
            }
        }
    
        return true;
    }

    /**
     *
     */
    protected function get_content($content) {
        return reset($content);
    }

    /**
     * 
     */
    protected function default_values() {
        $rawdefaults = explode("\n",$this->field->param2);
        $options = $this->options_menu();

        $defaults = array();
        foreach ($rawdefaults as $default) {
            $default = trim($default);
            if ($default and $key = array_search($default, $options)) {
                $defaults[] = $key;
            }
        }
        return $defaults;
    }
}
