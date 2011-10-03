<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage field-_group
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on Database module may obtain.
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

require_once($CFG->dirroot.'/mod/dataform/field/field_class.php');

class dataform_field__group extends dataform_field_base {

    public $type = '_group';

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        
        $groupinfo = array(
            '##group:id##',
            '##group:name##',
            '##group:description##',
            '##group:picture##',
            '##group:picturelarge##',
            '##group:edit##',
        );
        
        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns = array('groupinfo' => array('groupinfo' => array()));
                               
            foreach ($groupinfo as $pattern) {
                $patterns['groupinfo']['groupinfo'][$pattern] = $pattern;
            }

        } else {

            $patterns = array();

            // set the group object
            $group = new object();
            if ($entry->id < 0) { // new record (0)
                $entry->groupid = $this->df->currentgroup;
                $group->id = $entry->groupid;
                $group->name = null;
                $group->hidepicture = null;
                $group->picture = null;

            } else {
                $group->id = $entry->groupid;
                $group->name = $entry->groupname;
                $group->hidepicture = $entry->grouphidepic;
                $group->picture = $entry->grouppic;
            }

            foreach ($tags as $tag) {
                  switch ($tag) {
                    case '##group:id##':
                        $patterns[$tag] = array('html', $gropu->id);
                        break;

                    case '##group:name##':
                        $patterns[$tag] = array('html', $group->name);
                        break;

                    case '##group:description##':
                        $patterns[$tag] = array('html', $group->description);
                        break;

                    case '##group:picture##':
                        $patterns[$tag] = array('html',print_group_picture($group, $this->df->course->id, false, true));
                        break;

                    case '##group:picturelarge##':
                        $patterns[$tag] = array('html',print_group_picture($group, $this->df->course->id, true, true));
                        break;

                    case '##group:edit##':
                        if ($edit and has_capability('mod/dataform:approve', $this->df->context)) {
                            $patterns[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                        } else {
                            $patterns[$tag] = '';
                        }
                        break;
                }
            }
        }
        
        return $patterns;
    }
    
    /**
     *
     */
    public function display_edit(&$mform, $entry) {

        $entryid = $entry->id;
        $fieldid = $this->field->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        $selected = $entry->groupid;

        static $groupsmenu = null;
        if (is_null($groupsmenu)) {
            $groupsmenu = array(0 => get_string('choosedots'));        
            if ($groups = groups_get_activity_allowed_groups($this->df->cm)) {
                foreach ($groups as $groupid => $group) {
                    $groupsmenu[$groupid] = $group->name;
                }
            }
        }

        $mform->addElement('select', $fieldname, null, $groupsmenu);
        $mform->setDefault($fieldname, $selected);
    }

    /**
     * 
     */
    public function display_search($mform, $i) {
        return '';
    }
    
    /**
     * 
     */
    public function update_content($entry, array $values = null) {
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

    /**
     *
     */
    public function export_text_value($entry) {
        return $entry->groupid;
    }

    /**
     * 
     */
    public function get_internalname() {
        return $this->field->internalname;
    }


}
