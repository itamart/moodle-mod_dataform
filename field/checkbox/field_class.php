<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-checkbox
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain, including:
 * @copyright 1999 Moodle Pty Ltd http://moodle.com
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

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field_checkbox extends dataform_field_multi_menu {

    public $type = 'checkbox';

    /**
     *
     */
    public function parse_search($formdata, $i) {
        $selected = array();
        
        $fieldname = "f_{$i}_{$this->field->id}";
        foreach (array_keys($this->options_menu()) as $cb) {
            if (!empty($formdata->{"{$fieldname}_$cb"})) {
                $selected[] = $cb;
            }
        }
        if ($selected) {
            if (!empty($formdata->{"{$fieldname}_allreq"})) {
                $allrequired = $formdata->{"{$fieldname}_allreq"};
            } else {
                $allrequired = '';
            }
            return array('selected'=>$selected, 'allrequired'=>$allrequired);
        } else {
            return false;
        }
    }

    /**
     *
     */
    protected function render(&$mform, $fieldname, $options, $selected) {
        $elemgrp = array();
        foreach ($options as $i => $option) {
            $cb = &$mform->createElement('advcheckbox', $fieldname. '_'. $i, null, $option, array('group' => 1), array(0, $i));
            $elemgrp[] = $cb;
            if (in_array($i, $selected)) {
                $cb->setChecked(true);
            }
        }
        $mform->addGroup($elemgrp, "{$fieldname}_grp",null, $this->separators[(int) $this->field->param2]['chr'], false);
        // add checkbox controller
    }



}
