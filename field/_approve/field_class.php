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
 * @subpackage _approve
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield__approve extends dataformfield_no_content {

    public $type = '_approve';

    const _APPROVED = 'approve';

    /**
     * 
     */
    public static function get_field_objects($dataid) {
        $fieldobjects = array();

        $fieldobjects[self::_APPROVED] = (object) array('id' => self::_APPROVED, 'dataid' => $dataid, 'type' => '_approve', 'name' => get_string('approved', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => 'approved');

        return $fieldobjects;
    }

    /**
     *
     */
    public static function is_internal() {
        true;
    }
    
    /**
     * 
     */
    public function get_internalname() {
        return $this->field->internalname;
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return 'e.approved';
    }

    /**
     * 
     */
    public function get_search_sql($search) {
        $value = $search[2];
        return array(" e.approved = $value ", array(), false); 
    }

    /**
     *
     */
    public function parse_search($formdata, $i) {
        $fieldid = $this->field->id;
        if (isset($formdata->{"f_{$i}_$fieldid"})) {
            return $formdata->{"f_{$i}_$fieldid"};
        } else {
            return false;
        }
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return array('approved', 'Not approved');
    }

}
