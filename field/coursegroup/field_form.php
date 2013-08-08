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
 * @subpackage coursegroup
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/field/field_form.php");

class dataformfield_coursegroup_form extends dataformfield_form {

    /**
     *
     */
    function field_definition() {
        global $CFG, $PAGE, $DB, $SITE;

        $mform =& $this->_form;

    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'fieldattributeshdr', get_string('fieldattributes', 'dataform'));
        
        // course
        $courses = get_courses("all", "c.sortorder ASC", "c.id,c.fullname"); 
        $options = array(0 => get_string('choosedots'));
        foreach ($courses as $courseid => $course) {
            $options[$courseid] = $course->fullname;
        }
        $mform->addElement('select', 'param1', get_string('course'), $options);

        // group id
        $options = array(0 => get_string('choosedots'));
        if (!empty($this->_field->field->param1)) {
            $course = $this->_field->field->param1;
            $groups = $DB->get_records_menu('groups', array('courseid' => $course), 'name', 'id,name');
        } else {
            // an arbitrary limit of 100 registered options
            $options = $options + range(1, 100);
        }
        $mform->addElement('select', "param2", get_string('group'), $options);
        $mform->disabledIf("param2", "param1", 'eq', '');
       
        // ajax
        $options = array(
            'coursefield' => 'param1',
            'groupfield' => 'param2',
            'acturl' => "$CFG->wwwroot/mod/dataform/field/coursegroup/loadgroups.php"
        );

        $module = array(
            'name' => 'M.dataformfield_coursegroup_load_course_groups',
            'fullpath' => '/mod/dataform/field/coursegroup/coursegroup.js',
            'requires' => array('base','io','node')
        );

        $PAGE->requires->js_init_call('M.dataformfield_coursegroup_load_course_groups.init', array($options), false, $module);       
    }

}
