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

namespace mod_dataform\pluginbase;

/**
 * @package mod_dataform
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Dataform rule form helper
 */
class dataformruleform_helper {

    /**
     *
     */
    public static function general_definition($mform, $dataformid, $prefix = null) {
        global $CFG;

        $paramtext = (!empty($CFG->formatstringstriptags) ? PARAM_TEXT : PARAM_CLEAN);

        // -------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name
        $mform->addElement('text', $prefix. 'name', get_string('name'), array('size' => '32'));
        $mform->addRule($prefix. 'name', null, 'required', null, 'client');
        $mform->setType($prefix. 'name', $paramtext);

        // description
        $mform->addElement('text', $prefix. 'description', get_string('description'), array('size' => '64'));
        $mform->setType($prefix. 'description', PARAM_CLEAN);

        // enabled
        $mform->addElement('selectyesno', $prefix. 'enabled', get_string('ruleenabled', 'dataform'));
        $mform->setDefault($prefix. 'enabled', 1);

        // Time from
        $mform->addElement('date_time_selector', $prefix. 'timefrom', get_string('from'), array('optional' => true));

        // Time to
        $mform->addElement('date_time_selector', $prefix. 'timeto', get_string('to'), array('optional' => true));

        // Views
        $options = array(0 => get_string('all'), 1 => get_string('selected', 'form'));
        $mform->addElement('select', $prefix. 'viewselection', get_string('views', 'dataform'), $options);

        $items = array();
        if ($items = \mod_dataform_view_manager::instance($dataformid)->views_menu) {
            $items = array_combine($items, $items);
        }
        $select = &$mform->addElement('select', $prefix. 'views', null, $items);
        $select->setMultiple(true);
        $mform->disabledIf($prefix. 'views', $prefix. 'viewselection', 'eq', 0);
    }

    /**
     *
     */
    public static function fields_selection_definition($mform, $dataformid, $prefix = null) {
        $options = array(0 => get_string('all'), 1 => get_string('selected', 'form'));
        $mform->addElement('select', $prefix. 'fieldselection', get_string('fields', 'dataform'), $options);

        $items = array();
        if ($items = \mod_dataform_field_manager::instance($dataformid)->fields_menu) {
            $items = array_combine($items, $items);
        }
        $select = &$mform->addElement('select', $prefix. 'fields', null, $items);
        $select->setMultiple(true);
        $mform->disabledIf($prefix. 'fields', $prefix. 'fieldselection', 'eq', 0);
    }

    /**
     *
     */
    public static function entries_filter_definition($mform, $dataformid, $customsearch, $prefix = null) {
        $options = array('' => get_string('choosedots'));
        $fm = \mod_dataform_filter_manager::instance($dataformid);
        if ($filters = $fm->get_filters(null, true, true)) {
            $options = $options + $filters;
        }
        $mform->addElement('select', $prefix. 'filterid', get_string('filter', 'dataform'), $options);

        // Custom filter
        self::custom_search_definition($mform, $dataformid, $customsearch);
    }

    /*
     *
     */
    public static function custom_search_definition($mform, $dataformid, $customsearch) {
        $fields = \mod_dataform_field_manager::instance($dataformid)->get_fields();

        $andoroptions = array(
            '' => get_string('andor', 'dataform'),
            'AND' => get_string('and', 'dataform'),
            'OR' => get_string('or', 'dataform'),
        );

        $fieldoptions = self::get_field_search_options_menu($fields);

        $isnotoptions = array(
            '' => get_string('is', 'dataform'),
            'NOT' => get_string('not', 'dataform'),
        );
        $operatoroptions = array(
            '' => get_string('empty', 'dataform'),
            '=' => get_string('equal', 'dataform'),
            '>' => get_string('greaterthan', 'dataform'),
            '<' => get_string('lessthan', 'dataform'),
            '>=' => get_string('greaterorequal', 'dataform'),
            '<=' => get_string('lessorequal', 'dataform'),
            'BETWEEN' => get_string('between', 'dataform'),
            'LIKE' => get_string('contains', 'dataform'),
            'IN' => get_string('in', 'dataform'),
        );

        // Add current options
        $searchcriteria = array();
        if ($customsearch) {
            $searchfields = $customsearch;
            // If not from form then the searchfields is aggregated and we need
            // to flatten them. An aggregated array should have a non-zero key
            // (fieldid) in the first element.
            if (key($searchfields)) {
                foreach ($searchfields as $fieldid => $searchfield) {
                    if (empty($fields[$fieldid])) {
                        continue;
                    }

                    foreach ($searchfield as $andor => $searchoptions) {
                        foreach ($searchoptions as $searchoption) {
                            if (!is_array($searchoption) or count($searchoption) != 4) {
                                continue;
                            }
                            list($element, $not, $operator, $value) = $searchoption;
                            $searchcriteria[] = array("$fieldid,$element", $andor, $not, $operator, $value);
                        }
                    }
                }
            }
        }

        // Add 3 more empty options
        $searchcriteria[] = array(null, null, null, null, null);
        $searchcriteria[] = array(null, null, null, null, null);
        $searchcriteria[] = array(null, null, null, null, null);

        // Add form definition for each existing criterion
        $fieldlabel = get_string('filtersearchfieldlabel', 'dataform');
        $count = 0;
        foreach ($searchcriteria as $searchcriterion) {
            if (count($searchcriterion) != 5) {
                continue;
            }

            $i = $count + 1;

            list($fieldid, $andor, $not, $operator, $value) = $searchcriterion;

            $arr = array();
            $arr[] = &$mform->createElement('select', "searchandor$count", null, $andoroptions);
            $arr[] = &$mform->createElement('selectgroups', "searchfield$count", null, $fieldoptions);
            $arr[] = &$mform->createElement('select', "searchnot$count", null, $isnotoptions);
            $arr[] = &$mform->createElement('select', "searchoperator$count", '', $operatoroptions);
            $arr[] = &$mform->createElement('text', "searchvalue$count", '');
            $mform->addGroup($arr, "customsearcharr$count", "$fieldlabel$i", ' ', false);

            $mform->setType("searchvalue$count", PARAM_TEXT);

            $mform->setDefault("searchandor$count", $andor);
            $mform->setDefault("searchfield$count", $fieldid);
            $mform->setDefault("searchnot$count", $not);
            $mform->setDefault("searchoperator$count", $operator);
            $mform->setDefault("searchvalue$count", $value);

            $mform->disabledIf("searchfield$count", "searchandor$count", 'eq', '');
            $mform->disabledIf("searchnot$count", "searchfield$count", 'eq', '');
            $mform->disabledIf("searchoperator$count", "searchfield$count", 'eq', '');
            $mform->disabledIf("searchvalue$count", "searchoperator$count", 'eq', '');

            if ($count) {
                $prev = $count - 1;
                $mform->disabledIf("customsearcharr$count", "searchfield$prev", 'eq', '');
            }

            $count++;
        }
    }

    /*
     *
     */
    public static function get_field_search_options_menu($fields) {
        $menu = array('' => array('' => get_string('field', 'dataform')));
        foreach ($fields as $fieldid => $field) {
            $fieldname = $field->name;
            if ($options = $field->get_search_options_menu()) {
                $menu[$fieldname] = $options;
            }
        }
        return $menu;
    }

    /**
     *
     */
    public static function get_custom_search_from_form($formdata, $dataformid) {
        $df = \mod_dataform_dataform::instance($dataformid);
        if ($fields = $df->field_manager->get_fields()) {
            $searchfields = array();
            foreach ($formdata as $var => $unused) {
                if (strpos($var, 'searchandor') !== 0) {
                    continue;
                }

                $i = (int) str_replace('searchandor', '', $var);
                // check if trying to define a search criterion
                if ($searchandor = $formdata->{"searchandor$i"}) {
                    if ($searchelement = $formdata->{"searchfield$i"}) {
                        list($fieldid, $element) = explode(',', $searchelement);
                        $not = !empty($formdata->{"searchnot$i"}) ? $formdata->{"searchnot$i"} : '';
                        $operator = isset($formdata->{"searchoperator$i"}) ? $formdata->{"searchoperator$i"} : '';
                        $value = isset($formdata->{"searchvalue$i"}) ? $formdata->{"searchvalue$i"} : '';

                        // Don't add empty criteria on cleanup (unless operator is Empty and thus doesn't need search value)
                        if ($operator and !$value) {
                            continue;
                        }

                        // Aggregate by fieldid and searchandor,
                        if (!isset($searchfields[$fieldid])) {
                            $searchfields[$fieldid] = array();
                        }
                        if (!isset($searchfields[$fieldid][$searchandor])) {
                            $searchfields[$fieldid][$searchandor] = array();
                        }
                        $searchfields[$fieldid][$searchandor][] = array($element, $not, $operator, $value);
                    }
                }
            }
            if ($searchfields) {
                return $searchfields;
            }
        }
        return null;
    }


    /**
     *
     */
    public static function general_validation($data, $files, $prefix = null) {
        $errors = array();

        // Time from and time to
        if (!empty($data[$prefix. 'timefrom']) and !empty($data[$prefix. 'timeto']) and $data[$prefix. 'timeto'] <= $data[$prefix. 'timefrom']) {
            $errors[$prefix. 'timeto'] = get_string('errorinvalidtimeto', 'dataform');
        }

        return $errors;
    }

}
