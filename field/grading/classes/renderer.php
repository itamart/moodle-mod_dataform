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
 * @package dataformfield_grading
 * @copyright 2018 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

/**
 *
 */
class dataformfield_grading_renderer extends \mod_dataform\pluginbase\dataformfieldrenderer {

    /**
     *
     */
    protected function replacements(array $patterns, $entry, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name;
        $edit = !empty($options['edit']);

        $replacements = array_fill_keys(array_keys($patterns), '');

        // Edit mode.
        foreach ($patterns as $pattern => $cleanpattern) {
            if ($cleanpattern == "[[$fieldname]]") {
                if ($edit and $field->can_update_grade($entry)) {
                    $replacements[$pattern] = array(array($this, 'display_edit'), array($entry));
                }
            } else if ($cleanpattern == "[[$fieldname:lastupdated]]") {
                $replacements[$pattern] = $this->display_last_updated($entry);
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

        $fieldname = "field_{$fieldid}_{$entry->id}";
        $mform->addElement('hidden', $fieldname, 1);
        $mform->setType($fieldname, PARAM_INT);
    }

    /**
     *
     */
    public function display_last_updated($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id;

        $strtime = '';
        if (isset($entry->{"c{$fieldid}_content"})) {
            if ($content = $entry->{"c{$fieldid}_content"}) {
                $format = !empty($params['format']) ? $params['format'] : '';
                $strtime = userdate($content, $format);
            }
        }
        return $strtime;
    }

    /**
     * Overriding {@link dataformfieldrenderer::get_pattern_import_settings()}
     * to allow only the base pattern.
     */
    public function get_pattern_import_settings(&$mform, $patternname, $header) {
        return array(array(), array());
    }

    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name;

        $patterns = parent::patterns();
        foreach ($this->_patterns as $key => $pattern) {
            $sep = $key ? ':' : '';
            $show = isset($pattern['show']) ? $pattern['show'] : false;
            $patterns["[[$fieldname$sep$key]]"] = array($show, $fieldname);
        }

        return $patterns;
    }

    protected $_patterns = array(
        '' => array(
            'show' => true,
            'editable' => true,
            'description' => 'Triggers grade update on entry submission; does not display anything.',
        ),
        'lastupdated' => array(
            'show' => true,
            'description' => 'Displays the time of last grade updated as recorded in the field.',
        ),
    );
}
