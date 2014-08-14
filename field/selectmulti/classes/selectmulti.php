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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package dataformfield
 * @subpackage selectmulti
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class dataformfield_selectmulti_selectmulti extends mod_dataform\pluginbase\dataformfield {
    protected $_options = array();

    /**
     * Returns list of seprators.
     *
     * @return array Array of arrays (name => string, chr => string)
     */
    public function get_separator_types() {
        return array(
            array('name' => get_string('newline', 'dataformfield_selectmulti'), 'chr' => '<br />'),
            array('name' => get_string('space', 'dataformfield_selectmulti'), 'chr' => '&#32;'),
            array('name' => get_string('comma', 'dataformfield_selectmulti'), 'chr' => '&#44;'),
            array('name' => get_string('commaandspace', 'dataformfield_selectmulti'), 'chr' => '&#44;&#32;')
        );
    }

    /**
     * Returns the field configured separator.
     *
     * @return string
     */
    public function get_separator() {
        $separatortypes = $this->separator_types;

        $selected = (int) $this->param3;
        return $separatortypes[$selected]['chr'];
    }

    /**
     *
     */
    public function format_search_value($searchparams) {
        list($not, $operator, $value) = $searchparams;
        if (is_array($value)) {
            $selected = implode(', ', $value['selected']);
            $allrequired = '('. ($value['allrequired'] ? get_string('requiredall') : get_string('requirednotall', 'dataform')). ')';
            return $not. ' '. $operator. ' '. $selected. ' '. $allrequired;
        } else {
            return false;
        }
    }

    /**
     * Overriding parent to adjust search value. The search value is expected to be
     * a list of strings separated by pipe |. The reserved string for all required is -000-.
     * Searching a value is searching for the index: '#n#'.
     * Equal is simple, e.g. '#1#3#' where the searched options are 1 and 3.
     * Like requires a disjunction of each option. e.g. '%#1#%' OR '%#3#%'
     * or
     */
    public function get_search_sql($search) {
        list($element, $not, $operator, $value) = $search;

        // If no options, no search
        if (!$options = $this->options_menu()) {
            return null;
        }

        $optionkeys = array_keys($options);

        // Search with all required
        if ($value == '-000-') {
            $value = '#'. implode('#', $optionkeys). '#';
            $search = array($element, $not, $operator, $value);
            return parent::get_search_sql($search);
        }

        $searchedvalues = array_map('trim', explode('|', $value));

        // Search equal to given list
        if ($operator == '=') {
            $searchedoptions = array_intersect($options, $searchedvalues);
            // If we are searching for a value that is not in options, search for the impossible
            if (count($searchedoptions) != count($searchedvalues)) {
                $search = array($element, $not, $operator, '##');
                return parent::get_search_sql($search);
            }

            // All searched values are there
            $value = '#'. implode('#', array_keys($searchedoptions)). '#';
            $search = array($element, $not, $operator, $value);
            return parent::get_search_sql($search);

        }

        // Search Like to given list
        $sql = '';
        $params = array();
        if ($operator == 'LIKE') {
            $searchsqls = array();

            $optionsstr = implode('#', $options);

            foreach ($searchedvalues as $searched) {
                if ($pos = strpos($optionsstr, $searched) or $pos !== false) {
                    $key = substr_count($optionsstr, '#', 0, $pos + 1) + 1;
                    $value = '#'. $key. '#';
                    $search = array($element, $not, $operator, $value);
                    $searchsqls[] = parent::get_search_sql($search);
                }
            }
            $sqlon = array();
            foreach ($searchsqls as $searchsql) {
                list($sqlon[], $paramon, ) = $searchsql;
                $params = array_merge($params, $paramon);
            }

            if ($sqlon) {
                $sql = '('. implode(' OR ', $sqlon). ')';
            }
        }

        return array($sql, $params, $this->is_dataform_content());
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
        $fieldid = $this->id;
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
            $options = $this->options_menu();
            $update = false;
            foreach ($newvalues as $newvalue) {
                if (!$optionkey = (int) array_search($newvalue, $options)) {
                    $update = true;
                    $optionkey = count($options) + 1;
                    $selected[] = $optionkey;
                    $options[$optionkey] = $newvalue;
                }
            }
            if ($update) {
                $this->param1 = implode("\n", $options);
                $this->update($this->data);
            }
        }

        // new contents
        if (!empty($selected)) {
            $contents[] = '#'. implode('#', $selected). '#';
        }

        return array($contents, $oldcontents);
    }

    /**
     *
     */
    public function options_menu($forceget = false) {
        if (!$this->_options or $forceget) {
            if ($this->param1) {
                $rawoptions = explode("\n", $this->param1);
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
    public function default_values() {
        $rawdefaults = explode("\n", $this->param2);
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

    // IMPORT EXPORT
    /**
     *
     */
    public function prepare_import_content($data, $importsettings, $csvrecord = null, $entryid = null) {
        // import only from csv
        if (!$csvrecord) {
            return $data;
        }

        // There is only one import pattern for this field
        $importsetting = reset($importsettings);

        $fieldid = $this->id;
        $csvname = $importsetting['name'];
        $allownew = $importsetting['allownew'];
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

        return $data;
    }

}
