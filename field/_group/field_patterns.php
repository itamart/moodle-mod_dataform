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
 * certain copyrights on the Database module may obtain.
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

defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/field_patterns.php");

/**
 *
 */
class mod_dataform_field__group_patterns extends mod_dataform_field_patterns {

    /**
     * 
     */
    public function get_replacements($tags = null, $entry = null, $edit = false, $editable = false) {
        $field = $this->_field;
        $fieldname = $field->get('internalname');

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

        // no edit mode
        $replacements = array();

        foreach ($tags as $tag) {
              switch ($tag) {
                case '##group:id##':
                    $replacements[$tag] = array('html', $gropu->id);
                    break;

                case '##group:name##':
                    $replacements[$tag] = array('html', $group->name);
                    break;

//                    case '##group:description##':
//                        $replacements[$tag] = array('html', $group->description);
//                        break;

                case '##group:picture##':
                    $replacements[$tag] = array('html',print_group_picture($group, $field->df()->course->id, false, true));
                    break;

                case '##group:picturelarge##':
                    $replacements[$tag] = array('html',print_group_picture($group, $field->df()->course->id, true, true));
                    break;

                case '##group:edit##':
                    if ($edit and has_capability('mod/dataform:manageentries', $field->df()->context)) {
                        $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                    } else {
                        $replacements[$tag] = '';
                    }
                    break;
            }
        }

        return $replacements;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {
        $fieldid = $this->_field->id();
        $entryid = $entry->id;
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
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $cat = get_string('groupinfo', 'dataform');

        $patterns = array();
        $patterns['##group:id##'] = array(true, $cat);
        $patterns['##group:name##'] = array(true, $cat);
        //$patterns['##group:description##'] = array(true, $cat);
        $patterns['##group:picture##'] = array(true, $cat);
        $patterns['##group:picturelarge##'] = array(false, $cat);
        $patterns['##group:edit##'] = array(true, $cat);

        return $patterns; 
    }
}
