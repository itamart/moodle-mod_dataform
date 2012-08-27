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

class mod_dataform_rule_form extends moodleform {

    function definition() {
    
        $df = $this->_customdata['df'];
        $rule = $this->_customdata['rule'];
        $name = empty($rule->name) ? get_string('rulenew', 'dataform') : $rule->name;
        $description = empty($rule->description) ? '' : $rule->description;

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

         // enabled
        $mform->addElement('selectyesno', 'enabled', get_string('enable'));
        $mform->setDefault('enabled', $rule->enabled);

        // prevent options
        $grp = array();
        $grp[] = &$mform->createElement('advcheckbox', 'view', null, get_string('ruledenyview', 'dataform'), null, array(0,1));
        $grp[] = &$mform->createElement('advcheckbox', 'viewown', null, get_string('ruledenyviewbyother', 'dataform'), null, array(0,1));
        $grp[] = &$mform->createElement('advcheckbox', 'edit', null, get_string('ruledenyedit', 'dataform'), null, array(0,1));
        $grp[] = &$mform->createElement('advcheckbox', 'delete', null, get_string('ruledenydelete', 'dataform'), null, array(0,1));
        $mform->addGroup($grp, 'preventarr', get_string('ruleaction', 'dataform'), '<br />', false);

        // condition
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'conditionhdr', get_string('rulecondition', 'dataform'));

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
        if ($rule->condition) {

            $searchfields = unserialize($rule->condition);

            foreach ($searchfields as $fieldid => $searchfield) {
                foreach ($searchfield as $andor => $searchoptions) {
                    foreach ($searchoptions as $searchoption) {
                        if ($searchoption) {
                            list($not, $operator, $value) = $searchoption;
                        } else {
                            list($not, $operator, $value) = array('', '=', '');
                        }

                        $mform->addElement('html', '<tr style="border-bottom:1px solid #dddddd;"><td valign="top" nowrap="nowrap">');

                        $optionsarr = array();
                        // and/or option
                        $optionsarr[] = &$mform->createElement('select', 'searchandor'. $count, '', $andoroptions);
                        // searach field
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
            }
        }

        // add 3 more options
        for ($prevcount = $count; $count < ($prevcount + 3); $count++) {
            $mform->addElement('html', '<tr style="border-bottom:1px solid #dddddd;"><td valign="top" nowrap="nowrap">');

            $optionsarr = array();
            $optionsarr[] = &$mform->createElement('select', 'searchandor'. $count, '', $andoroptions);
            $optionsarr[] = &$mform->createElement('select', 'searchfield'. $count, '', $fieldsoptions);
            $mform->addGroup($optionsarr, 'searchoption'. $count, null, ' ', false);
            $mform->disabledIf('searchfield'. $count, 'searchandor'. $count, 'eq', 0);
            if ($count > $prevcount) {
                $mform->disabledIf('searchoption'. $count, 'searchandor'. ($count-1), 'eq', 0);
            }
            $mform->addElement('html', '</td><td valign="top" nowrap="nowrap"></td></tr>');
        }

        $mform->addElement('html', '</table>');

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true);
    }

    /**
     *
     */
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $df = $this->_customdata['df'];
        $rule = $this->_customdata['rule'];

        if (empty($data['name']) or $df->rule_name_exists($data['name'], $rule->id)) {
            $errors['name'] = get_string('invalidname','dataform', get_string('rule', 'dataform'));
        }

        return $errors;
    }

}
