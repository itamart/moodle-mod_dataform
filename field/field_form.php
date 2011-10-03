<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
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

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

require_once("$CFG->libdir/formslib.php");

class mod_dataform_field_form extends moodleform {

    function definition() {

        $field = $this->_customdata['field'];
        $mform =& $this->_form;

        $mform->addElement('hidden', 'type', $field->type());
        $mform->setType('type', PARAM_ALPHA);

        $streditinga = $field->id() ? get_string('fieldedit', 'dataform', $field->name()) : get_string('fieldnew', 'dataform', $field->type());
        $mform->addElement('html', '<h2 class="mdl-align">'.format_string($streditinga).'</h2>');

    // buttons
    //-------------------------------------------------------------------------------
        $this->add_action_buttons();

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name and description
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'32'));
        $mform->addRule('name', null, 'required', null, 'client');
        
        $mform->addElement('text', 'description', get_string('description'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
            $mform->setType('description', PARAM_CLEAN);
        }

    //-------------------------------------------------------------------------------
        $this->field_definition();

    // buttons
    //-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    /**
     *
     */
    function field_definition() {
    }    
    
    /**
     *
     */
    function add_action_buttons(){
        $mform = &$this->_form;

        $buttonarray=array();
        // save and display
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savechanges'));
        // save and continue
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('savecontinue', 'dataform'));
        // cancel
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }

    /**
     *
     */
    function validation($data) {
        $errors= array();

        $df = $this->_customdata['df'];
        $field = $this->_customdata['field'];

        if ($df->name_exists('fields', $data['name'], $field->id())) {
            $errors['invalidname'] = get_string('invalidname','dataform', get_string('field', 'dataform'));
        }

        return $errors;
    }

}



/**
 *
 */
class mod_dataform_field_single_menu_form extends mod_dataform_field_form {

    function field_definition() {

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // options
        $mform->addElement('textarea', 'param1', get_string('fieldoptions', 'dataform'), 'wrap="virtual" rows="5" cols="30"');

        // default value
        $mform->addElement('text', 'param2', get_string('default'));

        // reserve param3 for options separator (e.g. radiobutton, image button)

        // allow add option
        $mform->addElement('selectyesno', 'param4', get_string('allowaddoption', 'dataform'));
        
    }
}


/**
 *
 */
class mod_dataform_field_multi_menu_form extends mod_dataform_field_form {

    function field_definition() {

        $field = $this->_customdata['field'];
        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // options
        $mform->addElement('textarea', 'param1', get_string('fieldoptions', 'dataform'), 'wrap="virtual" rows="10" cols="50"');

        // default options
        $mform->addElement('textarea', 'param2', get_string('fieldoptionsdefault', 'dataform'), 'wrap="virtual" rows="5" cols="50"');

        // options separator
        $mform->addElement('select', 'param3', get_string('fieldoptionsseparator', 'dataform'), array_map('current', $field->separators));

        // allow add option
        $mform->addElement('selectyesno', 'param4', get_string('allowaddoption', 'dataform'));

    }

}
