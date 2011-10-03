<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-calculated
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
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

require_once("$CFG->dirroot/mod/dataform/field/field_form.php");

class mod_dataform_field_calculated_form extends mod_dataform_field_form {

    /**
     *
     */
    function field_definition() {

        $df = $this->_customdata['df'];
        $mform =& $this->_form;

    
    // formula
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldformulahdr', get_string('formula', 'dataform'));
        
        $numberfields = $df->get_fields_by_type('number', true);
        $fieldmenu = array(0 => get_string('choose'));
        if ($numberfields) {
            $fieldmenu = $fieldmenu + $numberfields;
        } else {
            $fieldmenu[0] = get_string('fieldnonematching', 'dataform');
        }

        // negation
        $mform->addElement('checkbox', 'param1', get_string('negation', 'dataform'));

        // operand1
        $mform->addElement('select', 'param2', get_string('operand', 'dataform'), $fieldmenu);
        ////$mform->addHelpButton('param2', array('viewforedit', get_string('operand', 'dataform'), 'dataform'));
        
        // operator
        $operators = array(0 => get_string('choose'), '+' => '+', '-' => '-', '*' => '*', '/' => '/', '%' => '%');
        $mform->addElement('select', 'param3', get_string('operator', 'dataform'), $operators);
        $mform->disabledIf('param3', 'param2', 'eq', 0);

        // operand2
        $mform->addElement('select', 'param4', get_string('operand', 'dataform'), $fieldmenu);
        $mform->disabledIf('param4', 'param3', 'eq', 0);
        ////$mform->addHelpButton('param4', array('calculatedoperand', get_string('operand', 'dataform'), 'dataform'));

    // target value
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldvaluehdr', get_string('settings', 'dataform'));
        
        // random
        $mform->addElement('selectyesno', 'param5', get_string('random', 'dataform'));
        $mform->setDefault('param5', 0);

        // value
        $mform->addElement('text', 'param6', get_string('value', 'dataform'), array('size' => 8));
        $mform->setDefault('param6', '');
        $mform->addRule('param6', null, 'numeric', null, 'client');        
        
        // min value
        $mform->addElement('text', 'param7', get_string('minvalue', 'dataform'), array('size' => 8));
        $mform->setDefault('param7', '');
        $mform->disabledIf('param7', 'param5', 'eq', 0);
        $mform->addRule('param7', null, 'numeric', null, 'client');        

        // range steps
        $mform->addElement('text', 'param8', get_string('rangesteps', 'dataform'), array('size' => 8));
        $mform->setDefault('param8', '');
        $mform->disabledIf('param8', 'param5', 'eq', 0);
        $mform->addRule('param8', null, 'numeric', null, 'client');        
    }

}
