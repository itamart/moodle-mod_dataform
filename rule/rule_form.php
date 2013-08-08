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
 * @package dataformrule
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->libdir/formslib.php");

/**
 *
 */
class dataformrule_form extends moodleform {
    protected $_rule = null;
    protected $_df = null;

    public function __construct($rule, $action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true) {
        $this->_rule = $rule;
        $this->_df = $this->_rule->df;
        
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);
    }
    
    /**
     *
     */
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
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
            $mform->setType('description', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEAN);
            $mform->setType('description', PARAM_CLEAN);
        }

        // enabled
        $mform->addElement('selectyesno', 'enabled', get_string('ruleenabled', 'dataform'));

        //-------------------------------------------------------------------------------
        $this->rule_definition();

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    /**
     *
     */
    protected function rule_definition() {
    }    
    
    /**
     *
     */
    public function add_action_buttons($cancel = true, $submit = null){
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
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($this->_df->name_exists('rules', $data['name'], $this->_rule->id())) {
            $errors['name'] = get_string('invalidname','dataform', get_string('rule', 'dataform'));
        }

        return $errors;
    }

    /**
     *
     */
    protected function menu_roles_used_in_context() {
        $roles = array(0 => get_string('choosedots'));    
        foreach (get_roles_used_in_context($this->_df->context) as $roleid => $role) {
            $roles[$roleid] = $role->name;
        }
        return $roles;
    }
}

/**
 *
 */
class dataformrule_notification_form extends dataformrule_form {

    /**
     *
     */
    function rule_definition() {

        $mform = &$this->_form;
        $rule = $this->_rule;

        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'settingshdr', get_string('settings'));
        
        // notification type: email, message
        $options = array(
            $rule::SEND_MESSAGE => get_string('message', 'message'),
            $rule::SEND_EMAIL => get_string('email', 'dataform'),
        );
        $mform->addElement('select', 'param1', get_string('type', 'dataform'), $options);
        
        // sender: author, manager
        $options = array(
            get_string('author', 'dataform'),
            get_string('manager', 'role')
        );
        $mform->addElement('select', 'param2', get_string('from'), $options);
        
        // recipient (param3): author, admin, roles, custom: user, email address
        $grp=array();
        $grp[] = &$mform->createElement('advcheckbox', 'author', null, get_string('author', 'dataform'), null, array(0,1));
        $grp[] = &$mform->createElement('advcheckbox', 'user', null, get_string('user'), null, array(0,2));
        $grp[] = &$mform->createElement('select', 'param4', null, array('' => get_string('choosedots')));
        $grp[] = &$mform->createElement('advcheckbox', 'role', null, get_string('role'), null, array(0,4));
        $grp[] = &$mform->createElement('select', 'param5', null, $this->menu_roles_used_in_context());
        $grp[] = &$mform->createElement('advcheckbox', 'admin', null, get_string('admin'), null, array(0,8));
        $grp[] = &$mform->createElement('advcheckbox', 'email', null, get_string('email'), null, array(0,16));
        $grp[] = &$mform->createElement('text', 'param6', null, array('size' => 32));
        $br = html_writer::empty_tag('br');
        $sp = '   ';
        $mform->addGroup($grp, 'recipientgrp', get_string('to'), array($br, $sp, $br, $sp, $br, $br, $sp), false);
        $mform->disabledIf('param4', 'user', 'notchecked');
        $mform->disabledIf('param5', 'roles', 'notchecked');
        $mform->disabledIf('param6', 'email', 'notchecked');        
    }

    /**
     *
     */
    function data_preprocessing(&$data){
        if (!empty($data->param3)) {
            $recipients = (int) $data->param3;
            foreach ($this->get_recipients() as $recipient => $key) {
                $data->$recipient = $recipients & $key;
            }
        }
    }

    /**
     *
     */
    function set_data($data) {
        $this->data_preprocessing($data);
        parent::set_data($data);
    }

    /**
     *
     */
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            // set recipient
            $recipients = 0;
            foreach ($this->get_recipients() as $recipient => $key) {
                $recipients = !empty($data->$recipient) ? $recipients | $data->$recipient : $recipients;
            }

            $data->param3 = $recipients;
        }
        return $data;
    }   

    /**
     *
     */
    function get_recipients() {
        return array(
            'author' => 1,
            'user' => 2,
            'role' => 4,
            'admin' => 8,
            'email' => 16,
        );
    }
}/**
 *
 */

/**
 *
 */
class dataformrule_entrycontent_form extends dataformrule_form {

}