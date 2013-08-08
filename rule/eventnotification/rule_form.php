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
 * @subpackage eventnotification
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/rule/rule_form.php");

class dataformrule_eventnotification_form extends dataformrule_notification_form {

    /**
     *
     */
    function rule_definition() {

        parent::rule_definition();
        
        $mform = &$this->_form;
        $rule = $this->_rule;

        // recipient (param6): author, admin, roles, custom: user, email address
        $grp=array();
        foreach ($rule->get_event_options() as $key => $event) {
            $grp[] = &$mform->createElement('advcheckbox', $event, null, get_string($event, 'dataformrule_eventnotification'), null, array(0,$key));
        }
        $br = html_writer::empty_tag('br');
        $mform->addGroup($grp, 'eventgrp', get_string('event', 'dataformrule_eventnotification'), $br, false);
    }

    /**
     *
     */
    function data_preprocessing(&$data){
        parent::data_preprocessing($data);
        if (!empty($data->param7)) {
            $events = (int) $data->param7;
            foreach ($this->_rule->get_event_options() as $key => $event) {
                $data->$event = $events & $key;
            }
        }
    }

    /**
     *
     */
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            // set events
            $events = 0;
            foreach ($this->_rule->get_event_options() as $key => $event) {
                $events = !empty($data->$event) ? $events | $data->$event : $events;
            }

            $data->param7 = $events;
        }
        return $data;
    }

}
