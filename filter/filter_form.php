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
 * @package mod
 * @subpackage dataform
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;
 
require_once ("$CFG->libdir/formslib.php");

/*
 *
 */
abstract class mod_dataform_filter_base_form extends moodleform {
    protected $_filter = null;
    protected $_df = null;

    /*
     *
     */
    public function __construct($df, $filter, $action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true) {
        $this->_filter = $filter;
        $this->_df = $df;
        
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);       
    }
    

    /*
     *
     */
    public function custom_sort_definition($customsort, $fields, $fieldoptions, $showlabel = false) {
        $mform = &$this->_form;
        $df = $this->_df;

        $diroptions = array(0 => get_string('ascending', 'dataform'),
                            1 => get_string('descending', 'dataform'));

        $fieldlabel = get_string('filtersortfieldlabel', 'dataform');
        $count = 0;


        // add current options
        if ($customsort) {
        
            $sortfields = unserialize($customsort);

            foreach ($sortfields as $fieldid => $sortdir) {
                if (empty($fields[$fieldid])) {
                    continue;
                }

                $i = $count + 1;
                $label = $showlabel ? "$fieldlabel$i" : '';
                
                $optionsarr = array();
                $optionsarr[] = &$mform->createElement('select', 'sortfield'. $count, '', $fieldoptions);
                $optionsarr[] = &$mform->createElement('select', 'sortdir'. $count, '', $diroptions);
                $mform->addGroup($optionsarr, 'sortoptionarr'. $count, $label, ' ', false);
                $mform->setDefault('sortfield'. $count, $fieldid);
                $mform->setDefault('sortdir'. $count, $sortdir);
                $count++;
            }
        }

        // add 3 more options
        for ($prevcount = $count; $count < ($prevcount + 3); $count++) {

            $i = $count + 1;
            $label = $showlabel ? "$fieldlabel$i" : '';

            $optionsarr = array();
            $optionsarr[] = &$mform->createElement('select', 'sortfield'. $count, '', $fieldoptions);
            $optionsarr[] = &$mform->createElement('select', 'sortdir'. $count, '', $diroptions);
            $mform->addGroup($optionsarr, 'sortoptionarr'. $count, $label, ' ', false);
            $mform->disabledIf('sortdir'. $count, 'sortfield'. $count, 'eq', 0);
            if ($count > $prevcount) {
                $mform->disabledIf('sortoptionarr'. $count, 'sortfield'. ($count-1), 'eq', 0);
            }
        }
    }

    /*
     *
     */
    public function custom_search_definition($customsearch, $fields, $fieldoptions, $showlabel = false) {
        $mform = &$this->_form;
        $df = $this->_df;

        $andoroptions = array(
            0 => get_string('andor', 'dataform'),
            'AND' => get_string('and', 'dataform'),
            'OR' => get_string('or', 'dataform'),
        );
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

        $fieldlabel = get_string('filtersearchfieldlabel', 'dataform');
        $count = 0;

        // add current options
        if ($customsearch) {

            $searchfields = unserialize($customsearch);
            // If not from form then the searchfields is aggregated and we need
            // to flatten them. An aggregated array should have a non-zero key 
            // (fieldid) in the first element.
            if (key($searchfields)) {
                $searcharr = array();
                foreach ($searchfields as $fieldid => $searchfield) {
                    if (empty($fields[$fieldid])) {
                        continue;
                    }

                    foreach ($searchfield as $andor => $searchoptions) {
                        foreach ($searchoptions as $searchoption) {
                            if ($searchoption) {
                                list($not, $operator, $value) = $searchoption;
                            } else {
                                list($not, $operator, $value) = array('', '', '');
                            }
                            $searcharr[] = array($fieldid, $andor, $not, $operator, $value);
                        }
                    }
                }
                $searchfields = $searcharr;
            }

            foreach ($searchfields as $searchcriterion) {
                if (count($searchcriterion) != 5) {
                    continue;
                }

                $i = $count + 1;
                $label = $showlabel ? "$fieldlabel$i" : '';
                

                list($fieldid, $andor, $not, $operator, $value) = $searchcriterion;

                $arr = array();
                // and/or option
                $arr[] = &$mform->createElement('select', 'searchandor'. $count, '', $andoroptions);
                $mform->setDefault('searchandor'. $count, $andor);
                // search field
                $arr[] = &$mform->createElement('select', 'searchfield'. $count, '', $fieldoptions);
                $mform->setDefault('searchfield'. $count, $fieldid);
                // not option
                $arr[] = &$mform->createElement('select', 'searchnot'. $count, null, $isnotoptions);
                $mform->setDefault('searchnot'. $count, $not);
                // search operator
                $arr[] = &$mform->createElement('select', 'searchoperator'. $count, '', $operatoroptions);
                $mform->setDefault('searchoperator'. $count, $operator);
                // field search elements
                list($elems, $separators) = $fields[$fieldid]->renderer()->display_search($mform, $count, $value);
                
                $arr = array_merge($arr, $elems);
                if ($separators) {
                    $sep = array_merge(array(' ', ' ', ' '), $separators);
                } else {
                    $sep = ' ';
                }
                $mform->addGroup($arr, "customsearcharr$count", $label, $sep, false);

                $count++;
            }
        }

        // add 3 more options
        for ($prevcount = $count; $count < ($prevcount + 3); $count++) {
            $i = $count + 1;
            $label = $showlabel ? "$fieldlabel$i" : '';

            $arr = array();
            $arr[] = &$mform->createElement('select', "searchandor$count", '', $andoroptions);
            $arr[] = &$mform->createElement('select', "searchfield$count", '', $fieldoptions);
            $mform->addGroup($arr, "customsearcharr$count", $label, ' ', false);
            $mform->disabledIf('searchfield'. $count, 'searchandor'. $count, 'eq', 0);
            if ($count > $prevcount) {
                $mform->disabledIf("searchoption$count", 'searchandor'. ($count-1), 'eq', 0);
            }
        }

        $mform->registerNoSubmitButton('addsearchsettings');
        $mform->addElement('submit', 'addsearchsettings', get_string('reload'));
    }

    /**
     *
     */
    public function html() {
        return $this->_form->toHtml();
    }    
}

/*
 *
 */
class mod_dataform_filter_form extends mod_dataform_filter_base_form {

    /*
     *
     */
    function definition() {
    
        $df = $this->_df;
        $filter = $this->_filter;
        $name = empty($filter->name) ? get_string('filternew', 'dataform') : $filter->name;
        $description = empty($filter->description) ? '' : $filter->description;
        $visible = !isset($filter->visible) ? 1 : $filter->visible;
        $fields = $df->get_fields();
        $fieldoptions = array(0 => get_string('choose')) + $df->get_fields(array('entry'), true);

        $mform = &$this->_form;
        
        //$mform->addElement('html', dataform_filter_manager::get_filter_url_query($filter));

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name and description
        $mform->addElement('text', 'name', get_string('name'));
        $mform->addElement('text', 'description', get_string('description'), array('size'=>'64'));
        $mform->setType('name', PARAM_TEXT);
        $mform->setType('description', PARAM_TEXT);
        $mform->setDefault('name', $name);
        $mform->setDefault('description', $description);

         // visibility
        $visibilityoptions = array(0=>'hidden',1=>'visible');
        $mform->addElement('select', 'visible', get_string('visible'), $visibilityoptions);
        $mform->setDefault('visible', $visible);

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'filterhdr', get_string('viewfilter', 'dataform'));
        $mform->setExpanded('filterhdr');

        // entries per page
        $options = array(0=>get_string('choose'),1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                            20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
        $mform->addElement('select', 'perpage', get_string('viewperpage', 'dataform'), $options);
        $mform->setDefault('perpage', $filter->perpage);

        // selection method
        $options = array(0 => get_string('filterbypage', 'dataform'), 1 => get_string('random', 'dataform'));
        $mform->addElement('select', 'selection', get_string('filterselection', 'dataform'), $options);
        $mform->setDefault('selection', $filter->selection);
        $mform->disabledIf('selection', 'perpage', 'eq', '0');

        // group by
        $mform->addElement('select', 'groupby', get_string('filtergroupby', 'dataform'), $fieldoptions);
        $mform->setDefault('groupby', $filter->groupby);

        // search
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $filter->search);

        // custom sort
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'customsorthdr', get_string('filtercustomsort', 'dataform'));
        $mform->setExpanded('customsorthdr');
        
        $this->custom_sort_definition($filter->customsort, $fields, $fieldoptions, true);
        
        // custom search
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'customsearchhdr', get_string('filtercustomsearch', 'dataform'));
        $mform->setExpanded('customsearchhdr');

        $this->custom_search_definition($filter->customsearch, $fields, $fieldoptions, true);

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    /*
     *
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $df = $this->_df;
        $filter = $this->_filter;

        // validate unique name
        if (empty($data['name']) or $df->name_exists('filters', $data['name'], $filter->id)) {
            $errors['name'] = get_string('invalidname','dataform', get_string('filter', 'dataform'));
        }

        return $errors;
    }
}

/*
 *
 */
class mod_dataform_advanced_filter_form extends mod_dataform_filter_base_form {
    /*
     *
     */
    function definition() {
    
        $df = $this->_df;
        $filter = $this->_filter;
        $view = $this->_customdata['view'];
        
        $name = empty($filter->name) ? get_string('filternew', 'dataform') : $filter->name;

        $fields = $view->get_view_fields();
        $fieldoptions = array(0 => get_string('choose'));
        foreach ($fields as $fieldid => $field) {
            $fieldoptions[$fieldid] = $field->name();
        }

        $mform = &$this->_form;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'advancedfilterhdr', get_string('filteradvanced', 'dataform'));
        $mform->setExpanded('advancedfilterhdr', false);
        
        // name and description
        $mform->addElement('text', 'name', get_string('name'));
        $mform->setType('name', PARAM_TEXT);
        $mform->setDefault('name', $name);

        // entries per page
        $options = array(0=>get_string('choose'),1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
                            20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);
        $mform->addElement('select', 'perpage', get_string('viewperpage', 'dataform'), $options);
        $mform->setDefault('perpage', $filter->perpage);

        // selection method
        //$options = array(0 => get_string('filterbypage', 'dataform'), 1 => get_string('random', 'dataform'));
        //$mform->addElement('select', 'selection', get_string('filterselection', 'dataform'), $options);
        //$mform->setDefault('selection', $filter->selection);
        //$mform->disabledIf('selection', 'perpage', 'eq', '0');

        // search
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setType('search', PARAM_TEXT);
        $mform->setDefault('search', $filter->search);

        // custom sort        
        $this->custom_sort_definition($filter->customsort, $fields, $fieldoptions, true);
        
        // custom search
        $this->custom_search_definition($filter->customsearch, $fields, $fieldoptions, true);

        // Save button
        $grp = array();
        $grp[] = $mform->createElement('submit', 'savebutton', get_string('savechanges'));       
        $grp[] = $mform->createElement('submit', 'newbutton', get_string('newfilter', 'filters'));
        $mform->addGroup($grp, "afiltersubmit_grp", null, ' ', false);
    }


}
