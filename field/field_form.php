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
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->libdir/formslib.php");

class mod_dataform_field_form extends moodleform {
    protected $_field = null;
    protected $_df = null;

    function definition() {        
        $this->_field = $this->_customdata['field'];
        $this->_df = $this->_field->df();
        $mform = &$this->_form;

        $streditinga = $this->_field->id() ? get_string('fieldedit', 'dataform', $this->_field->name()) : get_string('fieldnew', 'dataform', $this->_field->type());
        $mform->addElement('html', html_writer::tag('h2', format_string($streditinga), array('class' => 'mdl-align')));

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
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
            $mform->setType('description', PARAM_CLEAN);
        }

        // visible
        $options = array(
            dataform_field_base::VISIBLE_NONE => get_string('fieldvisiblenone', 'dataform'),
            dataform_field_base::VISIBLE_OWNER => get_string('fieldvisibleowner', 'dataform'),
            dataform_field_base::VISIBLE_ALL => get_string('fieldvisibleall', 'dataform'),
        );
        $mform->addElement('select', 'visible', get_string('fieldvisibility', 'dataform'), $options);

        // Editable
        //$options = array(-1 => get_string('unlimited'), 0 => get_string('none'));
        $options = array(-1 => get_string('yes'), 0 => get_string('no'));
        //$options = $options + array_combine(range(1,50), range(1,50));
        $mform->addElement('select', 'edits', get_string('fieldeditable', 'dataform'), $options);
        $mform->setDefault('edits', -1);

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
