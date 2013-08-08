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
 * @subpackage gradeitem
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield_gradeitem extends dataformfield_base {

    public $type = 'gradeitem';
    
    public $itemid;
    public $itemname;
    public $itemtype;
    public $itemmodule;
    public $iteminstance;
    
    public function __construct($df = 0, $field = 0) {       
        parent::__construct($df, $field);
        $this->itemid = $this->field->param1;
        $this->itemname = $this->field->param2;
        $this->itemtype = $this->field->param3;
        $this->itemmodule= $this->field->param4;
        $this->iteminstance = $this->field->param5;
    }

    /**
     * Sets up a field object
     */
    public function set_field($forminput = null) {
        global $DB;

        $itemid = !empty($this->field->param1) ? $this->field->param1 : null;
        parent::set_field($forminput);
        
        if ($this->field->param1 and ($this->field->param1 != $itemid or !$this->field->param2)) {
            $gradeiteminfo = 'id,itemname,itemtype,itemmodule,iteminstance';
            if ($item = $DB->get_record('grade_items', array('id' => $this->field->param1), $gradeiteminfo)) {
                $this->field->param2 = $item->itemname;
                $this->field->param3 = $item->itemtype;
                $this->field->param4 = $item->itemmodule;
                $this->field->param5 = $item->iteminstance;
            }
        }
    }

    /**
     *
     */
    public function get_select_sql() {
        $id = " c{$this->field->id}.id AS c{$this->field->id}_id ";
        $content = $this->get_sql_compare_text(). " AS c{$this->field->id}_content";
        return " $id , $content ";
    }

    /**
     *
     */
    protected function get_sql_compare_text($column = 'content') {
        global $DB;

        return $DB->sql_compare_text("c{$this->field->id}.finalgrade");
    }

    /**
     *
     */
    public function get_sort_from_sql($paramname = 'sortie', $paramcount = '') {
        $fieldid = $this->field->id;
        if ($fieldid > 0) {
            $sql = " LEFT JOIN {grade_grades} c$fieldid ON (c$fieldid.userid = e.userid AND c$fieldid.itemid = :$paramname$paramcount) ";
            return array($sql, $this->itemid);
        } else {
            return null;
        }
    }

    /**
     *
     */
    public function get_search_from_sql() {
        $fieldid = $this->field->id;
        if ($fieldid > 0) {
            return " JOIN {grade_grades} c$fieldid ON c$fieldid.userid = e.userid ";
        } else {
            return '';
        }
    }

    /**
     *
     */
    public function is_dataform_content() {
        return false;
    }

    
}

