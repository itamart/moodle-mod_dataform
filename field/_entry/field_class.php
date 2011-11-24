<?php

/**
 * This file is part of the Dataform module for Moodle
 *
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package mod-dataform
 *
 */

require_once("$CFG->dirroot/mod/dataform/field/field_class.php");

class dataform_field__entry extends dataform_field_base {

    public $type = '_entry';
    
    /**
     * 
     */
    public function update_content($entryid, array $values = null) {
        return true;
    }

    /**
     * 
     */
    public function get_search_sql($search) {
        return array(" ", array());
    }

    /**
     * 
     */
    public function parse_search($formdata, $i) {
        return '';
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return '';
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        return false;
    }

}
