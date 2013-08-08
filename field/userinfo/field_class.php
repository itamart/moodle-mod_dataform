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
 * @subpackage userinfo
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataformfield_userinfo extends dataformfield_base {

    public $type = 'userinfo';
    
    public $infoid;
    public $infoshortname;
    public $infotype;
    public $defaultdata;
    public $defaultdataformat;
    
    public function __construct($df = 0, $field = 0) {       
        parent::__construct($df, $field);
        $this->infoid = $this->field->param1;
        $this->infoshortname = $this->field->param2;
        $this->infotype = $this->field->param3;
        $this->defaultdata = $this->field->param4;
        $this->defaultdataformat = $this->field->param5;
    }

    /**
     * Sets up a field object
     */
    public function set_field($forminput = null) {
        global $DB;

        $infoid = !empty($this->field->param1) ? $this->field->param1 : null;
        parent::set_field($forminput);
        
        if ($this->field->param1 and ($this->field->param1 != $infoid or !$this->field->param2)) {
            $infoitems = 'shortname,datatype,defaultdata,defaultdataformat,param1,param2,param3,param4,param5';
            if ($info = $DB->get_record('user_info_field', array('id' => $this->field->param1), $infoitems)) {
                $this->field->param2 = $info->shortname;
                $this->field->param3 = $info->datatype;
                $this->field->param4 = $info->defaultdata;
                $this->field->param5 = $info->defaultdataformat;

                $this->field->param6 = $info->param1;
                $this->field->param7 = $info->param2;
                $this->field->param8 = $info->param3;
                $this->field->param9 = $info->param4;
                $this->field->param10 = $info->param5;
            }
        }
    }

    /**
     *
     */
    public function get_select_sql() {
        $id = " c{$this->field->id}.id AS c{$this->field->id}_id ";
        $content = $this->get_sql_compare_text('data'). " AS c{$this->field->id}_content";
        $content1 = $this->get_sql_compare_text('dataformat'). " AS c{$this->field->id}_content1";
        return " $id , $content , $content1 ";
    }

    /**
     *
     */
    protected function get_sql_compare_text($column = 'content') {
        global $DB;

        return $DB->sql_compare_text("c{$this->field->id}.$column");
    }

    /**
     *
     */
    public function get_sort_from_sql($paramname = 'sortie', $paramcount = '') {
        $fieldid = $this->field->id;
        if ($fieldid > 0) {
            $sql = " LEFT JOIN {user_info_data} c$fieldid ON 
                (c$fieldid.userid = e.userid AND c$fieldid.fieldid = :$paramname$paramcount) ";
            return array($sql, $this->infoid);
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
            return " JOIN {user_info_data} c$fieldid ON c$fieldid.userid = e.userid ";
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

