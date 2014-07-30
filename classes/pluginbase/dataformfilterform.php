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
 * @package mod_dataform
 * @category filter
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dataform\pluginbase;

defined('MOODLE_INTERNAL') or die;

require_once("$CFG->libdir/formslib.php");

/*
 *
 */
abstract class dataformfilterform extends \moodleform {
    protected $_filter = null;

    /*
     *
     */
    public function __construct($filter, $action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true) {
        $this->_filter = $filter;

        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }

    /*
     *
     */
    protected function custom_sort_definition($customsort, $fields, $showlabel = false) {
        $mform = &$this->_form;

        $fieldoptions = $this->get_field_sort_options_menu($fields);

        $diroptions = array(
            0 => get_string('ascending', 'dataform'),
            1 => get_string('descending', 'dataform')
        );

        $fieldlabel = get_string('filtersortfieldlabel', 'dataform');

        $sortcriteria = array();
        $count = 0;

        // Add current options
        if ($customsort) {
            $sortfields = unserialize($customsort);
            foreach ($sortfields as $sortelement => $sortdir) {
                $sortelement = explode(',', $sortelement) + array(null);
                list($fieldid, $element) = $sortelement;
                if (!empty($fields[$fieldid])) {
                    $sortcriteria[] = array($fieldid, $element, $sortdir);
                }
            }
        }

        // Add 3 more
        $sortcriteria[] = array(null, null, null);
        $sortcriteria[] = array(null, null, null);
        $sortcriteria[] = array(null, null, null);

        // Add form definitions for sort criteria
        foreach ($sortcriteria as $criterion) {
            list($fieldid, $element, $sortdir) = $criterion;
            $i = $count + 1;
            $label = $showlabel ? "$fieldlabel$i" : '';

            $optionsarr = array();
            $optionsarr[] = &$mform->createElement('selectgroups', "sortfield$count", null, $fieldoptions);
            $optionsarr[] = &$mform->createElement('select', "sortdir$count", null, $diroptions);
            $mform->addGroup($optionsarr, "sortoptionarr$count", $label, ' ', false);

            $mform->setDefault("sortfield$count", "$fieldid,$element");
            $mform->setDefault("sortdir$count", $sortdir);

            $mform->disabledIf("sortdir$count", "sortfield$count", 'eq', '');

            if ($count) {
                $prev = $count - 1;
                $mform->disabledIf("sortoptionarr$count", "sortfield$prev", 'eq', '');
            }
            $count++;
        }
    }

    /*
     *
     */
    protected function custom_search_definition($customsearch, $fields, $showlabel = false) {
        $mform = &$this->_form;

        $andoroptions = array(
            '' => get_string('andor', 'dataform'),
            'AND' => get_string('and', 'dataform'),
            'OR' => get_string('or', 'dataform'),
        );

        $fieldoptions = $this->get_field_search_options_menu($fields);

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

            $searchfields = unserialize($customsearch);
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
            $label = $showlabel ? "$fieldlabel$i" : '';

            list($fieldid, $andor, $not, $operator, $value) = $searchcriterion;

            $arr = array();
            $arr[] = &$mform->createElement('select', "searchandor$count", null, $andoroptions);
            $arr[] = &$mform->createElement('selectgroups', "searchfield$count", null, $fieldoptions);
            $arr[] = &$mform->createElement('select', "searchnot$count", null, $isnotoptions);
            $arr[] = &$mform->createElement('select', "searchoperator$count", '', $operatoroptions);
            $arr[] = &$mform->createElement('text', "searchvalue$count", '');
            $mform->addGroup($arr, "customsearcharr$count", $label, ' ', false);

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
    protected function get_field_search_options_menu($fields) {
        $menu = array('' => array('' => get_string('field', 'dataform')));
        foreach ($fields as $fieldid => $field) {
            $fieldname = $field->name;
            if ($options = $field->get_search_options_menu()) {
                $menu[$fieldname] = $options;
            }
        }
        return $menu;
    }

    /*
     *
     */
    protected function get_field_sort_options_menu($fields) {
        $menu = array('' => array('' => get_string('field', 'dataform')));
        foreach ($fields as $fieldid => $field) {
            $fieldname = $field->name;
            if ($options = $field->get_sort_options_menu()) {
                $menu[$fieldname] = $options;
            }
        }
        return $menu;
    }

    /*
     *
     */
    protected function get_url_query($fields) {
        global $OUTPUT;

        $filter = $this->_filter;

        // parse custom settings
        $sorturlquery = '';
        $searchurlquery = '';

        if ($filter->customsort or $filter->customsearch) {
            // CUSTOM SORT
            if ($filter->customsort) {
                if ($sortfields = unserialize($filter->customsort)) {
                    $sorturlarr = array();
                    foreach ($sortfields as $idandelement => $sortdir) {
                        list($fieldid, $element) = explode(',', $idandelement);
                        if (empty($fields[$fieldid])) {
                            continue;
                        }

                        // Sort url query
                        $sorturlarr[] = "$fieldid $sortdir";
                    }
                    if ($sorturlarr) {
                        $sorturlquery = '&usort='. urlencode(implode(',', $sorturlarr));
                    }
                }
            }

            // CUSTOM SEARCH
            if ($filter->customsearch) {
                if ($searchfields = unserialize($filter->customsearch)) {
                    $searchurlarr = array();
                    foreach ($searchfields as $fieldid => $searchfield) {
                        if (empty($fields[$fieldid])) {
                            continue;
                        }
                        $fieldoptions = array();
                        if (!empty($searchfield['AND'])) {
                            $options = array();
                            foreach ($searchfield['AND'] as $option) {
                                if ($option) {
                                    $options[] = $fields[$fieldid]->format_search_value($option);
                                }
                            }
                            $fieldoptions[] = '<b>'. $fields[$fieldid]->name. '</b>:'. implode(' <b>and</b> ', $options);
                        }
                        if (!empty($searchfield['OR'])) {
                            $options = array();
                            foreach ($searchfield['OR'] as $option) {
                                if ($option) {
                                    $options[] = $fields[$fieldid]->format_search_value($option);
                                }
                            }
                            $fieldoptions[] = '<b>'. $fields[$fieldid]->name. '</b> '. implode(' <b>or</b> ', $options);
                        }
                        if ($fieldoptions) {
                            $searcharr[] = implode('<br />', $fieldoptions);
                        }
                    }
                    if (!empty($searcharr)) {
                        $searchurlquery = '&ucsearch='. \mod_dataform_filter_manager::get_search_url_query($searchfields);
                    }
                } else if ($filter->search) {
                    $searchurlquery = '&usearch='. urlendcode($filter->search);
                }
            }
        }

        return $sorturlquery. $searchurlquery;
    }
}
