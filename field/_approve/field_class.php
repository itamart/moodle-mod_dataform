<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
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

require_once("$CFG->dirroot/mod/dataform/mod_class.php");

class dataform_field__approve extends dataform_field_base {

    public $type = '_approve';

    /**
     * 
     */
    public function get_internalname() {
        return $this->field->internalname;
    }

    /**
     * 
     */
    public function patterns($tags = null, $entry = null, $edit = false, $editable = false) {
        global $CFG;

        // if no tags requested, return select menu
        if (is_null($tags)) {
            $patterns = array('actions' => array('actions' => array()));
            $patterns['actions']['actions']['##approve##'] = '##approve##';
            
        } else {
            $patterns = array('##approve##' =>  '');
            // there is only one possible tag here so no check
            if ($this->df->data->approval) {
                if ((!$entry or $edit) and has_capability('mod/dataform:approve', $this->df->context)) {
                    $patterns['##approve##'] = array('', array(array($this,'display_edit'), array($entry)));
                } else {    // existing entry to browse 
                    $patterns['##approve##'] = array('html', $this->display_browse($entry));
                }
            }
        }
            
        return $patterns;
    }
            
    /**
     * 
     */
    public function display_search($mform, $i = 0, $value = '') {
        $options = array(0 => ucfirst(get_string('approvednot', 'dataform')), 1 => ucfirst(get_string('approved', 'dataform')));
        $select = &$mform->addElement('select', 'f_'. $i. '_'. $this->field->id, null, $options);
        $select->setSelected($value);
        // disable the 'not' and 'operator' fields
        $mform->disabledIf("searchnot$i", 'f_'. $i. '_'. $this->field->id, 'neq', 2);
        $mform->disabledIf("searchoperator$i", 'f_'. $i. '_'. $this->field->id, 'neq', 2);
    }

    /**
     * 
     */
    public function update_content($entry, array $values = null) {
/*        global $DB;
        
        $value = !empty($values) ? reset($values) : 0;

        $rec = new object();
        $rec->id = $entry->id;
        $rec->approved = $value;
        return $DB->update_record('dataform_entries', $rec);
*/    }

    /**
     *
     */
    public function export_text_value($entry) {
        return $entry->approved;
    }

    /**
     * 
     */
    public function get_sort_sql() {
        return 'e.approved';
    }

    /**
     * 
     */
    public function get_search_sql($search) {
        $value = $search[2];
        return array(" e.approved = $value ", array()); 
    }

    /**
     * returns an array of distinct content of the field
     */
    public function get_distinct_content($sortdir = 0) {
        global $DB;
        
        $sortdir = $sortdir ? 'DESC' : 'ASC';
        $contentfull = $this->get_sort_sql();
        
        $sql = "SELECT DISTINCT $contentfull
                    FROM {dataform_entries} e
                    WHERE $contentfull IS NOT NULL'.
                    ORDER BY $contentfull $sortdir";

        $distinctvalues = array();
        if ($options = $DB->get_records_sql($sql)) {
            foreach ($options as $data) {
                $value = $data->approved;
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
    }

    /**
     *
     */
    public function display_edit(&$mform, $entry) {

        $fieldid = $this->field->id;
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
    public function display_browse($entry) {
        global $OUTPUT;
        
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
                                                            
        if (has_capability('mod/dataform:approve', $this->df->context)) {
            return '<a href="'. $entry->baseurl. '&amp;'. $approval. '='. $entry->id. '&amp;sesskey='. sesskey(). '">'.
                    $approvedimage. '</a>';
        } else {
            return $approvedimage;
        }
    }

}
