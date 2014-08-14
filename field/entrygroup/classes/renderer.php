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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package dataformfield
 * @subpackage entrygroup
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

/**
 *
 */
class dataformfield_entrygroup_renderer extends mod_dataform\pluginbase\dataformfieldrenderer {

    /**
     *
     */
    protected function replacements(array $patterns, $entry, array $options = null) {
        $field = $this->_field;
        $edit = !empty($options['edit']);

        // Set the group object
        $group = new stdClass;
        if ($entry->id < 0) { // new record (0)
            $entry->groupid = $field->df->currentgroup;
            $group->id = $entry->groupid;
            $group->idnumber = null;
            $group->name = null;
            $group->hidepicture = null;
            $group->picture = null;

        } else {
            $group->id = $entry->groupid;
            $group->idnumber = $entry->groupidnumber;
            $group->name = $entry->groupname;
            $group->hidepicture = $entry->grouphidepic;
            $group->picture = $entry->grouppic;
        }

        $replacements = array();

        foreach ($patterns as $pattern) {
            $replacements[$pattern] = '';
            list(, $pvar) = explode(':', trim($pattern, '[]'));
            switch ($pvar) {
                case 'id':
                    if (!empty($group->id)) {
                        $replacements[$pattern] = $group->id;
                    }
                    break;

                case 'idnumber':
                    if (!empty($group->id)) {
                        $replacements[$pattern] = $group->idnumber;
                    }
                    break;

                case 'name':
                    $replacements[$pattern] = $group->name;
                    break;

                // case 'description':
                    // $replacements[$pattern] = $group->description;
                    // break;

                case 'picture':
                    $replacements[$pattern] = print_group_picture($group, $field->get_df()->course->id, false, true);
                    break;

                case 'picturelarge':
                    $replacements[$pattern] = print_group_picture($group, $field->get_df()->course->id, true, true);
                    break;

                case 'edit':
                    if ($edit and has_capability('mod/dataform:manageentries', $field->get_df()->context)) {
                        $replacements[$pattern] = array(array($this, 'display_edit'), array($entry));
                    } else {
                        $replacements[$pattern] = $group->name;
                    }
                    break;
            }
        }

        return $replacements;
    }

    /**
     * Overriding {@link dataformfieldrenderer::get_pattern_import_settings()}
     * to return import settings only for id, idnumber.
     *
     * @param moodleform $mform
     * @param string $pattern
     * @return array
     */
    public function get_pattern_import_settings(&$mform, $patternname, $header) {
        $allowedpatternparts = array('id', 'idnumber');

        $fieldname = $this->_field->name;
        $patternpart = trim(str_replace($fieldname, '', $patternname), ':');

        if (!in_array($patternpart, $allowedpatternparts)) {
            return array(array(), array());
        }
        return parent::get_pattern_import_settings($mform, $patternname, $header);
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        $field = $this->_field;
        $fieldid = $field->id;
        $entryid = $entry->id;
        $fieldname = "entry_{$entryid}_groupid";

        $selected = $entry->groupid;
        static $groupsmenu = null;
        if (is_null($groupsmenu)) {
            $groupsmenu = array(0 => get_string('choosedots'));
            if ($groups = groups_get_activity_allowed_groups($field->df->cm)) {
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
        $fieldname = $this->_field->name;
        $cat = get_string('pluginname', 'dataformfield_entrygroup');

        $patterns = array();
        $patterns["[[$fieldname:id]]"] = array(true, $cat);
        $patterns["[[$fieldname:name]]"] = array(true, $cat);
        $patterns["[[$fieldname:idnumber]]"] = array(true, $cat);
        // $patterns["[[$fieldname:description]]"] = array(true, $cat);
        $patterns["[[$fieldname:picture]]"] = array(true, $cat);
        $patterns["[[$fieldname:picturelarge]]"] = array(false, $cat);
        $patterns["[[$fieldname:edit]]"] = array(true, $cat);

        return $patterns;
    }
}
