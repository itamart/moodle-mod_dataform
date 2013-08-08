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
 * @subpackage _entry
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield__entry extends dataformfield_no_content {
    public $type = '_entry';

    const _ENTRY = 'entry';

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
        
        $fieldobjects[self::_ENTRY] = (object) array('id' => self::_ENTRY, 'dataid' => $dataid, 'type' => '_entry', 'name' => get_string('entry', 'dataform'), 'description' => '', 'visible' => 2, 'internalname' => '');
        
        return $fieldobjects;
    }
    
}
