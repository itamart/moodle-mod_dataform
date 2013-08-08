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
 * @subpackage multiselect
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield_multiselect extends dataformfield_base {

    public $type = 'multiselect';
    protected $_options = array();
    public $separators = array(
            array('name' => 'New line', 'chr' => '<br />'),
            array('name' => 'Space', 'chr' => '&#32;'),
            array('name' => ',', 'chr' => '&#44;'),
            array('name' => ', (with space)', 'chr' => '&#44;&#32;')
    );

    
    /**
     * Class constructor
     *
     * @param var $df       dataform id or class object
     * @param var $field    field id or DB record
     */
    public function __construct($df = 0, $field = 0) {
        parent::__construct($df, $field);
        
        // Set the options
        $this->options_menu();
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
            $allrequired = '('. ($value['allrequired'] ? get_string('requiredall') : get_string('requirednotall', 'dataform')). ')';
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
        
        // TODO Handle search for empty field
        
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
                return array(" $not (".implode(" AND ", $conditions).") ", $params, true);
            } else {
                return array(" $not (".implode(" OR ", $conditions).") ", $params, true);
            }
        } else {
           return array(" ", $params);
        }
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
        $oldcontents = array();
        $contents = array();

        // old contents
        if (isset($entry->{"c{$fieldid}_content"})) {
            $oldcontents[] = $entry->{"c{$fieldid}_content"};
        }

        // parse values
        $selected = !empty($values['selected']) ? $values['selected'] : array();
        $newvalues = !empty($values['newvalue']) ? explode('#', $values['newvalue']) : array();

        // update new values in field type
        if ($newvalues) {
            $update = false;
            foreach ($newvalues as $newvalue) {
                if (!$optionkey = (int) array_search($newvalue, $this->_options)) {
                    $update = true;
                    $selected[] = count($this->_options);
                    $this->_options[] = $newvalue;
                }
            }
            if ($update) {
                $this->field->param1 = implode("\n", $this->_options);
                $this->update_field();
            }
        }

        // new contents
        if (!empty($selected)) {
            $contents[] = implode('#', $selected);
        }
        
        return array($contents, $oldcontents);
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
            $allownew = $importsettings[$fieldname]['allownew'];
            $labels = !empty($csvrecord[$csvname]) ? explode('#', $csvrecord[$csvname]) : null;

            if ($labels) {
                $options = $this->options_menu();
                $selected = array();
                $newvalues = array();
                foreach ($labels as $label) {
                    if (!$optionkey = array_search($label, $options)) {
                        if ($allownew) {
                            $newvalues[] = $label;
                            $selected[] = count($options) + count($newvalues);
                        }
                    } else {
                        $selected[] = $optionkey;                    
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
    public function default_values() {
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
