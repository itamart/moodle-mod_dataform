<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/
 *
 * @package mod-dataform
 * @subpackage view-interval
 * @author Itamar Tzadok
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's standard Database activity module. To the extent that the
 * Dataform code corresponds to the Database code (1.9.11+ (20110323)),
 * certain copyrights on certain files may obtain.
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

require_once("$CFG->dirroot/mod/dataform/view/block/view_form.php");

class mod_dataform_view_interval_form extends mod_dataform_view_block_form {

    /**
     *
     */
    function view_definition_before_gps() {
        $mform =& $this->_form;

        // specifications
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'intervalsettingshdr', get_string('settings', 'dataformview_interval'));

        // interval
        $intervals = array(0 => get_string('always'),
                        'hourly' => get_string('hourly', 'dataformview_interval'),
                        'daily' => get_string('daily', 'dataformview_interval'),
                        'weekly' => get_string('weekly', 'dataformview_interval'),
                        'monthly' => get_string('monthly', 'dataformview_interval'),
                        'custom' => get_string('custom', 'dataformview_interval'));
        
        $strminutes = get_string('minutes');
        $strhours = get_string('hours');
        $strdays = get_string('days');

        $intervalgrp=array();
        $intervalgrp[] = &$mform->createElement('select', 'param5', null, $intervals);
        $intervalgrp[] = &$mform->createElement('select', 'customminutes', null, range(0, 59));
        $intervalgrp[] = &$mform->createElement('select', 'customhours', null, range(0, 23));
        $intervalgrp[] = &$mform->createElement('select', 'customdays', null, range(0, 60));
        $mform->addGroup($intervalgrp, 'intervalgrp', get_string('refresh', 'dataformview_interval'), array("    $strminutes: ", " $strhours: ", " $strdays: "), false);
        $mform->disabledIf('customminutes', 'param5', 'neq', 'custom');
        $mform->disabledIf('customhours', 'param5', 'neq', 'custom');
        $mform->disabledIf('customdays', 'param5', 'neq', 'custom');
        
        // selection type
        $types = array(dataform_entries::SELECT_FIRST => get_string('first', 'dataformview_interval'),
                        dataform_entries::SELECT_LAST => get_string('last', 'dataformview_interval'),
                        dataform_entries::SELECT_NEXT => get_string('next'),
                        dataform_entries::SELECT_RANDOM => get_string('random', 'dataformview_interval'));
        $mform->addElement('select', 'param4', get_string('selection', 'dataformview_interval'), $types);
        
        // reset next
        $mform->addElement('text', 'param8', get_string('resetnext', 'dataformview_interval'), array('size' => 8));
        $mform->setType('param8', PARAM_INT);
        $mform->setDefault('param8', 100);
        $mform->disabledIf('param8', 'param4', 'neq', 'next');
        $mform->addRule('param8', null, 'numeric', null, 'client');
        
        parent::view_definition_before_gps();
    }

    /**
     *
     */
    function data_preprocessing(&$default_values){
        if (!empty($default_values->param6)){
            $customsecs = $default_values->param6;
            $customdays = floor($customsecs / 86400);
            $daysinsecs = $customdays * 86400;
            $customhours = floor(($customsecs - $daysinsecs) / 3600);
            $hoursinsecs = $customhours * 3600;
            $customminutes = floor(($customsecs - ($daysinsecs + $hoursinsecs)) / 60);
            $default_values->customdays = $customdays;
            $default_values->customhours = $customhours;
            $default_values->customminutes = $customminutes;
        }
    }

    /**
     *
     */
    function set_data($default_values) {
        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }

    /**
     *
     */
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            // set custom refresh (param6)
            $customrefreshsecs = 0;
            if (!empty($data->customminutes)) {
                $customrefreshsecs += ($data->customminutes * 60);
                unset($data->customminutes);
            }
            if (!empty($data->customhours)) {
                $customrefreshsecs += ($data->customhours * 3600);
                unset($data->customhours);
            }
            if (!empty($data->customdays)) {
                $customrefreshsecs += ($data->customdays * 86400);
                unset($data->customdays);
            }
            $data->param6 = $customrefreshsecs;
        }
        return $data;
    }

}
