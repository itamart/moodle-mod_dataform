<?php
// This file is part of Moodle - http://moodle.org/
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
 * @package dataformfield_entry
 * @copyright 2015 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

/**
 *
 */
class dataformfield_entry_renderer extends mod_dataform\pluginbase\dataformfieldrenderer {

    /**
     *
     */
    protected function replacements(array $patterns, $entry, array $options = null) {
        $field = $this->_field;
        $edit = !empty($options['edit']);

        $replacements = array_fill_keys($patterns, '');

        foreach ($patterns as $pattern) {
            list(, $internalname) = explode(':', trim($pattern, '[]'));

            if ($internalname == 'type') {
                if ($edit and has_capability('mod/dataform:manageentries', $field->df->context)) {
                    $replacements[$pattern] = array(array($this, 'display_edit'), array($entry));
                } else {
                    $replacements[$pattern] = $entry->type;
                }

            } else if ($internalname == 'id' and $entry->id > 0) {
                $replacements[$pattern] = $entry->id;
            }
        }
        return $replacements;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        $field = $this->_field;
        $fieldid = $field->id;
        $entryid = $entry->id;
        $fieldname = "entry_{$entryid}_type";

        $selected = $entry->type;

        // Entry type.
        $menu = array('' => get_string('choosedots'));
        if ($entrytypes = $field->df->entrytypes) {
            $types = array_map('trim', explode(',', $entrytypes));
            $menu = array_merge($menu, array_combine($types, $types));
        }

        $mform->addElement('select', $fieldname, null, $menu);
        $mform->setDefault($fieldname, $selected);
    }

    /**
     * Overriding {@link dataformfieldrenderer::get_pattern_import_settings()}
     * to return import settings only for username, id, idnumber.
     *
     * @param moodleform $mform
     * @param string $pattern
     * @return array
     */
    public function get_pattern_import_settings(&$mform, $patternname, $header) {
        $allowedpatternparts = array('id', 'type');

        $fieldname = $this->_field->name;
        $patternpart = trim(str_replace($fieldname, '', $patternname), ':');

        if (!in_array($patternpart, $allowedpatternparts)) {
            return array(array(), array());
        }
        return parent::get_pattern_import_settings($mform, $patternname, $header);
    }

    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name;
        $cat = get_string('pluginname', 'dataformfield_entry');

        $patterns = array();

        $patterns["[[$fieldname:id]]"] = array(true, $cat);
        $patterns["[[$fieldname:type]]"] = array(true, $cat);

        return $patterns;
    }
}
