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
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->libdir/formslib.php");

class dataformfield_form extends moodleform {
    protected $_field = null;
    protected $_df = null;

    public function __construct($field, $action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true) {
        $this->_field = $field;
        $this->_df = $field->df();
        
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);       
    }
    
    function definition() {        
        $mform = &$this->_form;

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'32'));
        $mform->addRule('name', null, 'required', null, 'client');
        
        // description
        $mform->addElement('text', 'description', get_string('description'), array('size'=>'64'));

        // visible
        $options = array(
            dataformfield_base::VISIBLE_NONE => get_string('fieldvisiblenone', 'dataform'),
            dataformfield_base::VISIBLE_OWNER => get_string('fieldvisibleowner', 'dataform'),
            dataformfield_base::VISIBLE_ALL => get_string('fieldvisibleall', 'dataform'),
        );
        $mform->addElement('select', 'visible', get_string('fieldvisibility', 'dataform'), $options);

        // Editable
        //$options = array(-1 => get_string('unlimited'), 0 => get_string('none'));
        $options = array(-1 => get_string('yes'), 0 => get_string('no'));
        //$options = $options + array_combine(range(1,50), range(1,50));
        $mform->addElement('select', 'edits', get_string('fieldeditable', 'dataform'), $options);
        $mform->setDefault('edits', -1);

        // Label
        $mform->addElement('textarea', 'label', get_string('fieldlabel', 'dataform'), array('cols' => 60, 'rows' => 5));
        $mform->addHelpButton('label', 'fieldlabel', 'dataform');

        // Strings strip tags
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
            $mform->setType('label', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
            $mform->setType('description', PARAM_CLEANHTML);
            $mform->setType('label', PARAM_CLEANHTML);
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
    function add_action_buttons($cancel = true, $submit = null){
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
    function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($this->_df->name_exists('fields', $data['name'], $this->_field->id())) {
            $errors['name'] = get_string('invalidname','dataform', get_string('field', 'dataform'));
        }

        return $errors;
    }

}
