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
 * @subpackage _group
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataformfield__group extends dataformfield_no_content {

    public $type = '_group';

    const _GROUP = 'group';

    /**
     *
     */
    public static function is_internal() {
        true;
    }
    
    /**
     *
     */
    public static function get_field_objects($dataid) {
        $fieldobjects = array();
        
        $fieldobjects[self::_GROUP] = (object) array('id' => self::_GROUP, 'dataid' => $dataid, 'type' => '_group', 'name' => get_string('group', 'dataformfield__group'), 'description' => '', 'visible' => 2, 'internalname' => 'groupid');

        return $fieldobjects;
    }

    /**
     * 
     */
    public function get_internalname() {
        return $this->field->internalname;
    }
}
