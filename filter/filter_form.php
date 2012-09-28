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
 * @package mod-dataform
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once ("$CFG->libdir/formslib.php");

class mod_dataform_filter_form extends moodleform {

    function definition() {
    
        $df = $this->_customdata['df'];
        $filter = $this->_customdata['filter'];
        $name = empty($filter->name) ? get_string('filternew', 'dataform') : $filter->name;
        $description = empty($filter->description) ? '' : $filter->description;
        $visible = !isset($filter->visible) ? 1 : $filter->visible;
        $fields = $df->get_fields();
        $fieldsoptions = array(0 => get_string('choose')) + $df->get_fields(array(-1), true);

        $mform =& $this->_form;

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
        $mform->addElement('select', 'groupby', get_string('filtergroupby', 'dataform'), $fieldsoptions);
        $mform->setDefault('groupby', $filter->groupby);

        // search
        $mform->addElement('text', 'search', get_string('search'));
        $mform->setDefault('search', $filter->search);

        // custom sort
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'customsorthdr', get_string('filtercustomsort', 'dataform'));

        // field sort
        $diroptions = array(0 => get_string('ascending', 'dataform'),
                            1 => get_string('descending', 'dataform'));

        $count = 0;
        // add current options
        if ($filter->customsort) {
            $sortfields = unserialize($filter->customsort);

            foreach ($sortfields as $fieldid => $sortdir) {
                if (empty($fields[$fieldid])) {
                    continue;
                }

                $optionsarr = array();
                $optionsarr[] = &$mform->createElement('select', 'sortfield'. $count, '', $fieldsoptions);
                $optionsarr[] = &$mform->createElement('select', 'sortdir'. $count, '', $diroptions);
                $mform->addGroup($optionsarr, 'sortoptionarr'. $count, null, ' ', false);
                $mform->setDefault('sortfield'. $count, $fieldid);
                $mform->setDefault('sortdir'. $count, $sortdir);
                $count++;
            }
        }

        // add 3 more options
        for ($prevcount = $count; $count < ($prevcount + 3); $count++) {
            $optionsarr = array();
            $optionsarr[] = &$mform->createElement('select', 'sortfield'. $count, '', $fieldsoptions);
            $optionsarr[] = &$mform->createElement('select', 'sortdir'. $count, '', $diroptions);
            $mform->addGroup($optionsarr, 'sortoptionarr'. $count, null, ' ', false);
            $mform->disabledIf('sortdir'. $count, 'sortfield'. $count, 'eq', 0);
            if ($count > $prevcount) {
                $mform->disabledIf('sortoptionarr'. $count, 'sortfield'. ($count-1), 'eq', 0);
            }
        }

        // custom search
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'customsearchhdr', get_string('filtercustomsearch', 'dataform'));

        $andoroptions = array(0 => ' and/or ', 'AND' => 'AND', 'OR' => 'OR');
        $operatoroptions = array(
                        '=' => 'Equal',
                        //'<>' => 'Not equal',
                        '>' => 'Greater than',
                        '<' => 'Less than',
                        '>=' => 'Greater than or equal',
                        '<=' => 'Less than or equal',
                        'BETWEEN' => 'BETWEEN',
                        'LIKE' => 'LIKE',
                        'IN' => 'IN'
        );

        $count = 0;

        $mform->addElement('html', '<table>');

        // add current options
        if ($filter->customsearch) {

            $searchfields = unserialize($filter->customsearch);
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
                                list($not, $operator, $value) = array('', '=', '');
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

                list($fieldid, $andor, $not, $operator, $value) = $searchcriterion;
                $mform->addElement('html', '<tr style="border-bottom:1px solid #dddddd;"><td valign="top" nowrap="nowrap">');

                $optionsarr = array();
                // and/or option
                $optionsarr[] = &$mform->createElement('select', 'searchandor'. $count, '', $andoroptions);
                // search field
                $optionsarr[] = &$mform->createElement('select', 'searchfield'. $count, '', $fieldsoptions);
                $mform->addGroup($optionsarr, 'searchoption'. $count, null, ' ', false);
                $mform->setDefault('searchandor'. $count, $andor);
                $mform->setDefault('searchfield'. $count, $fieldid);

                $mform->addElement('html', '</td><td valign="top" nowrap="nowrap">');
                $operatorarr = array();
                // not option
                $operatorarr[] = &$mform->createElement('checkbox', 'searchnot'. $count, null, 'NOT');
                // search operator
                $operatorarr[] = &$mform->createElement('select', 'searchoperator'. $count, '', $operatoroptions);
                $mform->addGroup($operatorarr, 'searchoperatorarr'. $count, null, ' ', false);
                $mform->setDefault('searchnot'. $count, $not);
                $mform->setDefault('searchoperator'. $count, $operator);

                $mform->addElement('html', '</td><td valign="top" nowrap="nowrap" style="width:10px;">');
                // field search elements
                $fields[$fieldid]->patterns()->display_search($mform, $count, $value);
                $mform->addElement('html', '</td></tr>');

                $count++;
            }
        }

        // add 3 more options
        for ($prevcount = $count; $count < ($prevcount + 3); $count++) {
            $mform->addElement('html', '<tr style="border-bottom:1px solid #dddddd;"><td valign="top" nowrap="nowrap">');

            $optionsarr = array();
            $optionsarr[] = &$mform->createElement('select', "searchandor$count", '', $andoroptions);
            $optionsarr[] = &$mform->createElement('select', "searchfield$count", '', $fieldsoptions);
            $mform->addGroup($optionsarr, "searchoption$count", null, ' ', false);
            $mform->disabledIf('searchfield'. $count, 'searchandor'. $count, 'eq', 0);
            if ($count > $prevcount) {
                $mform->disabledIf("searchoption$count", 'searchandor'. ($count-1), 'eq', 0);
            }
            $mform->addElement('html', '</td><td valign="top" nowrap="nowrap"></td></tr>');
        }

        $mform->addElement('html', '</table>');
        $mform->registerNoSubmitButton('addsearchsettings');
        $mform->addElement('submit', 'addsearchsettings', get_string('reload'));

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $df = $this->_customdata['df'];
        $filter = $this->_customdata['filter'];

        // validate unique name
        if (empty($data['name']) or $df->name_exists('filters', $data['name'], $filter->id)) {
            $errors['name'] = get_string('invalidname','dataform', get_string('filter', 'dataform'));
        }

        return $errors;
    }

}
