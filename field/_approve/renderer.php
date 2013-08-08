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
 * @subpackage _approve
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die();

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield__approve_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $df = $this->_field->df();

        $canapprove = has_capability('mod/dataform:approve', $df->context);
        $edit = !empty($options['edit']) ? $options['edit'] and $canapprove : false;
        $replacements = array();
        // just one tag, empty until we check df settings
        $replacements['##approve##'] = '';
        
        if ($df->data->approval) {
            if (!$entry or $edit) {
                $replacements['##approve##'] = array('', array(array($this,'display_edit'), array($entry)));

            // existing entry to browse 
            } else {
                $replacements['##approve##'] = array('html', $this->display_browse($entry));
            }
        }

        return $replacements;
    }

    /**
     * 
     */
    public function display_search(&$mform, $i = 0, $value = '') {
        $field = $this->_field;
        $fieldid = $field->id();

        $options = array(0 => ucfirst(get_string('approvednot', 'dataform')), 1 => ucfirst(get_string('approved', 'dataform')));
        $select = &$mform->createElement('select', "f_{$i}_$fieldid", null, $options);
        $select->setSelected($value);
        // disable the 'not' and 'operator' fields
        $mform->disabledIf("searchnot$i", "f_{$i}_$fieldid", 'neq', 2);
        $mform->disabledIf("searchoperator$i", "f_{$i}_$fieldid", 'neq', 2);
        
        return array($select, null);
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry, array $options = null) {

        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;

        if ($entryid > 0) {
            $checked = $entry->approved;
        } else {
            $checked = 0;
        }

        $fieldname = "field_{$fieldid}_{$entryid}";
        $mform->addElement('checkbox', $fieldname, null);
        $mform->setDefault($fieldname, $checked);
    }

    /**
     * 
     */
    protected function display_browse($entry, $params = null) {
        global $OUTPUT;
        
        $field = $this->_field;
        if ($entry and $entry->approved) {
            $approved = 'approved';
            $approval = 'disapprove';
            $approvedimagesrc = 'i/tick_green_big';
        } else {
            $approved = 'disapproved';
            $approval = 'approve';
            $approvedimagesrc = 'i/cross_red_big';
        }
        $strapproved = get_string($approved, 'dataform');
        
        $approvedimage = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url($approvedimagesrc),
                                                            'class' => "iconsmall",
                                                            'alt' => $strapproved,
                                                            'title' => $strapproved));
                                                            
        if (has_capability('mod/dataform:approve', $field->df()->context)) {
            return html_writer::link(
                new moodle_url($entry->baseurl, array($approval => $entry->id, 'sesskey' => sesskey())),
                $approvedimage
            );
        } else {
            return $approvedimage;
        }
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $cat = get_string('actions', 'dataform');

        $patterns = array();
        $patterns["##approve##"] = array(true, $cat);

        return $patterns; 
    }
}
