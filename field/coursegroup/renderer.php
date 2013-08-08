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

defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield_coursegroup_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name();
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        $replacements = array_fill_keys($tags, '');

        foreach ($tags as $tag) {
            if ($edit) {
                $replacements[$tag] = array('', array(array($this ,'display_edit'), array($entry)));
                break;
            } else {
                $parts = explode(':', trim($tag, '[]'));
                if (!empty($parts[1])) {
                    $type = $parts[1];
                } else {
                    $type = '';
                }
                $replacements[$tag] = array('html', $this->display_browse($entry, $type));
            }
        }

        return $replacements;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        global $CFG, $PAGE, $DB, $SITE;

        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        
        if ($field->course) {
            $courseid = $field->course;
        } else {
            $courseid = $entryid > 0 ? $entry->{"c{$fieldid}_content"} : '';
        }
            
        if ($field->group) {
            $groupid = $field->group;
        } else {
            $groupid = !empty($entry->{"c{$fieldid}_content1"}) ? $entry->{"c{$fieldid}_content1"} : 0;
        }
            
        $fieldname = "field_{$fieldid}_{$entryid}";
        // group course
        $courses = get_courses("all", "c.sortorder ASC", "c.id,c.fullname"); 
        $coursemenu = array(0 => get_string('choosedots'));
        foreach ($courses as $cid => $course) {
            $coursemenu[$cid] = $course->fullname;
        }
        if ($field->course) {
            $cname = get_string('coursenotfound', 'dataformfield_coursegroup', $courseid);
            if (!empty($coursemenu[$courseid])) {
                $cname = $coursemenu[$courseid];
            }
            $mform->addElement('html', html_writer::tag('h3', $cname));
        } else {
            $mform->addElement('select', 
                                "{$fieldname}_course",
                                null,
                                $coursemenu);
            $mform->setDefault("{$fieldname}_course", $courseid);
            
            // ajax
            $options = array(
                'coursefield' => "${fieldname}_course",
                'groupfield' => "{$fieldname}_group",
                'acturl' => "$CFG->wwwroot/mod/dataform/field/coursegroup/loadgroups.php"
            );

            $module = array(
                'name' => 'M.dataformfield_coursegroup_load_course_groups',
                'fullpath' => '/mod/dataform/field/coursegroup/coursegroup.js',
                'requires' => array('base','io','node')
            );

            $PAGE->requires->js_init_call('M.dataformfield_coursegroup_load_course_groups.init', array($options), false, $module);            
        }

        // group id
        if ($field->group) {
            if ($group = $DB->get_record('groups', array('id' => $groupid), 'name')) {
                $groupname = $group->name;
            } else {
                $groupname = get_string('groupnotfound', 'dataformfield_coursegroup', $groupid);
            }
            $mform->addElement('html', html_writer::tag('h3', $groupname));
        } else {
            $groupmenu = array('' => get_string('choosedots'));
            if ($courseid) {
                $groups = $DB->get_records_menu('groups', array('courseid' => $courseid), 'name', 'id,name');
                foreach ($groups as $gid => $groupname) {
                    $groupmenu[$gid] = $groupname;
                }
            }
            $mform->addElement('select', 
                                "{$fieldname}_group",
                                null,
                                $groupmenu);
            $mform->setDefault("{$fieldname}_group", $groupid);
            $mform->disabledIf("{$fieldname}_group", "{$fieldname}_course", 'eq', '');

            $mform->addElement('text', "{$fieldname}_groupid", null, array('class' => 'hide'));
            $mform->setDefault("{$fieldname}_groupid", $groupid);
        }

    }
   
    /**
     *
     */
    protected function display_browse($entry, $type = null) {
        global $DB;
        
        $field = $this->_field;
        $fieldid = $field->id();

        $courseid = 0;
        if (!empty($field->course)) {
            $courseid = (int) $field->course;
        } else if (!empty($entry->{"c{$fieldid}_content"})) {
            $courseid = (int) $entry->{"c{$fieldid}_content"};
        } else {
            return '';
        }
        
        $groupid = 0;
        if (!empty($field->group)) {
            $groupid = (int) $field->group;
        } else if (!empty($entry->{"c{$fieldid}_content1"})) {
            $groupid = (int) $entry->{"c{$fieldid}_content1"};
        }

        switch ($type) {            
            case 'course':
                // Return the course name
                if ($coursename = $DB->get_field('course', 'fullname', array('id' => $courseid))) {
                    return $coursename;
                }
                break;
            case 'group':
                // Return the group name
                if ($groupid and $groupname = $DB->get_field('groups', 'name', array('id' => $groupid))) {
                    return $groupname;
                }
                break;
                
            case 'courseid':
                Return $courseid;
                break;
                
            case 'groupid':
                Return $groupid;
                break;

            case '':
                Return "$courseid $groupid";
                break;
        }
        return '';
    }

    /**
     * Value is an array of (member,courseid,groupid) only one should be set
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $fieldid = $this->_field->id();

        if (is_array($value)){
            list($member, $course, $group) = $value;
        } else {
            $member = $course = $group = 0;
        }
    
        $elements = array();
        // Select yes/no for member
        $elements[] = &$mform->createElement('selectyesno', "f_{$i}_{$fieldid}_member");
        // Number field for course id
        $elements[] = &$mform->createElement('text', "f_{$i}_{$fieldid}_course");
        // Number field for group id
        $elements[] = &$mform->createElement('text', "f_{$i}_{$fieldid}_group");
        $mform->setDefault("f_{$i}_{$fieldid}_member", $member);
        $mform->setDefault("f_{$i}_{$fieldid}_course", $course);
        $mform->setDefault("f_{$i}_{$fieldid}_group", $group);
        $mform->disabledIf("coursegroupelements$i", "searchoperator$i", 'eq', '');
        
        return array($elements, array(get_string('member', 'dataformfield_coursegroup'), '<br />'. get_string('course'). ' ','<br />'. get_string('group'). ' '));
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldname = $this->_field->name();
 
        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);
        $patterns["[[$fieldname:course]]"] = array(true);
        $patterns["[[$fieldname:group]]"] = array(true);
        $patterns["[[$fieldname:courseid]]"] = array(false);
        $patterns["[[$fieldname:groupid]]"] = array(false);

        return $patterns; 
    }
}
