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
 * @subpackage identifier
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield_identifier_renderer extends dataformfield_renderer {

    /**
     *
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->name();
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        // No rules support
        $replacements = array_fill_keys($tags, '');

        // Only one tag
        $tag = "[[$fieldname]]";
        if (array_key_exists($tag, $replacements)) {
            if ($edit) {
                $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry)));
            } else {
                $replacements[$tag] = array('html', $this->display_browse($entry));
            }
        }

        return $replacements;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;

        $content = '';
        if ($entryid > 0 and !empty($entry->{"c{$fieldid}_content"})){
            $content = $entry->{"c{$fieldid}_content"};
        }

        // Include reference to field in entry form only when there is no content
        // so as to generate once
        if (empty($content)) {
            $fieldname = "field_{$fieldid}_{$entryid}";
            $mform->addElement('hidden', $fieldname, 1);
            $mform->setType($fieldname, PARAM_NOTAGS);
        }
    }

    /**
     *
     */
    protected function display_browse($entry, $params = null) {
        $field = $this->_field;
        $fieldid = $field->id();

        $content = '';
        if (isset($entry->{"c{$fieldid}_content"})) {
            $content = strtoupper($entry->{"c{$fieldid}_content"});
        }

        return $content;
    }

    /**
     * Array of patterns this field supports
     */
    protected function patterns() {
        $fieldname = $this->_field->name();

        $patterns = parent::patterns();
        $patterns["[[$fieldname]]"] = array(true);

        return $patterns;
    }

}
