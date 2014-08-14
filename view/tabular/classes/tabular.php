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
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package dataformview
 * @subpackage tabular
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * A template for displaying dataform entries in a tabular list
 * Parameters used:
 * param1 - activity grading
 * param2 - repeated entry section
 * param3 - table header
 */

class dataformview_tabular_tabular extends mod_dataform\pluginbase\dataformview {

    protected $_editors = array('section', 'param2');

    /**
     *
     */
    public static function get_file_areas() {
        return array('section', 'param2');
    }

    /**
     * Generates the default entry template for a new view instance or when reseting an existing instance.
     *
     * @return void
     */
    public function set_default_entry_template($content = null) {
        // get all the fields
        if (!$fields = $this->df->field_manager->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        if ($content === null) {
            $entryactions = get_string('fieldname', 'dataformfield_entryactions');
            $entryauthor = get_string('fieldname', 'dataformfield_entryauthor');

            // set content table
            $table = new html_table();
            $table->attributes['align'] = 'center';
            $table->attributes['cellpadding'] = '2';
            $header = array();
            $entry = array();
            $align = array();
            // author picture
            $header[] = '';
            $entry[] = "[[$entryauthor:picture]]";
            $align[] = 'center';
            // author name
            $header[] = '';
            $entry[] = "[[$entryauthor:name]]";
            $align[] = 'left';
            // fields
            foreach ($fields as $field) {
                if ($field->id > 0) {
                    $header[] = $field->name;
                    $entry[] = "[[$field->name]]";
                    $align[] = 'left';
                }
            }
            // multiedit
            $header[] = "[[$entryactions:bulkedit]]&nbsp;[[$entryactions:bulkdelete]]&nbsp;[[$entryactions:selectallnone]]";
            $entry[] = "[[$entryactions:edit]]&nbsp;[[$entryactions:delete]]&nbsp;[[$entryactions:select]]";
            $align[] = 'center';

            // construct the table
            $table->head = $header;
            $table->align = $align;
            $table->data[] = $entry;
            $content = html_writer::table($table);
        }
        $this->param2 = $content;
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        global $CFG, $OUTPUT;

        $tablehtml = trim($this->param2);
        $opengroupdiv = html_writer::start_tag('div', array('class' => 'entriesview'));
        $closegroupdiv = html_writer::end_tag('div');
        if ($name) {
            $name = ($name == 'newentry' ? get_string('entrynew', 'dataform') : $name);
        }
        $groupheading = $OUTPUT->heading($name, 3, 'main');

        $elements = array();

        // if there are no field definition just return everything as html
        if (empty($entriesset)) {
            $elements[] = $opengroupdiv. $groupheading. $tablehtml. $closegroupdiv;

        } else {

            // clean any prefix and get the open table tag
            // $tablehtml = preg_replace('/^[\s\S]*<table/i', '<table', $tablehtml);
            $tablepattern = '/^<table[^>]*>/i';
            preg_match($tablepattern, $tablehtml, $match); // must be there
            $tablehtml = trim(preg_replace($tablepattern, '', $tablehtml));
            $opentable = reset($match);
            // clean any suffix and get the close table tag
            $tablehtml = trim(preg_replace('/<\/table>$/i', '', $tablehtml));
            $closetable = '</table>';

            // get the header row if required
            $headerrow = '';
            if ($requireheaderrow = $this->param3) {
                if (strpos($tablehtml, '<thead>') === 0) {
                    // get the header row and remove from subject
                    $theadpattern = '/^<thead>[\s\S]*<\/thead>/i';
                    preg_match($theadpattern, $tablehtml, $match);
                    $tablehtml = trim(preg_replace($theadpattern, '', $tablehtml));
                    $headerrow = reset($match);
                }
            }
            // we may still need to get the header row
            // but first remove tbody tags
            if (strpos($tablehtml, '<tbody>') === 0) {
                $tablehtml = trim(preg_replace('/^<tbody>|<\/tbody>$/i', '', $tablehtml));
            }
            // assuming a simple two rows structure for now
            // if no theader the first row should be the header
            if ($requireheaderrow and empty($headerrow)) {
                // assuming header row does not contain nested tables
                $trpattern = '/^<tr>[\s\S]*<\/tr>/i';
                preg_match($trpattern, $tablehtml, $match);
                $tablehtml = trim(preg_replace($trpattern, '', $tablehtml));
                $headerrow = '<thead>'. reset($match). '</thead>';
            }
            // the reset of $tablehtml should be the entry template
            $entrytemplate = $tablehtml;
            // construct elements
            // first everything before the entrytemplate as html
            $elements[] = $opengroupdiv. $groupheading. $opentable. $headerrow. '<tbody>';

            // do the entries
            // get tags from the first item in the entry set
            $tagsitem = reset($entriesset);
            $tagsitem = reset($tagsitem);
            $tags = array_keys($tagsitem);
            $htmlparts = '';

            foreach ($entriesset as $fielddefinitions) {
                $definitions = reset($fielddefinitions);
                $parts = $this->split_tags($tags, $entrytemplate);

                foreach ($parts as $part) {
                    if (in_array($part, $tags)) {
                        if ($definitions[$part]) {
                            if ($htmlparts) {
                                $elements[] = $htmlparts;
                                $htmlparts = '';
                            }
                            $elements[] = $definitions[$part];
                        }
                    } else {
                        $htmlparts .= $part;
                    }
                }
            }

            // finish the table
            $elements[] = "$htmlparts </tbody> $closetable $closegroupdiv";

        }

        return $elements;
    }

    /**
     *
     */
    protected function entry_definition($fielddefinitions, array $options = null) {
        $elements = array();
        // just store the fefinitions
        //   and group_entries_definition will process them
        $elements[] = $fielddefinitions;
        return $elements;
    }

    /**
     *
     */
    protected function new_entry_definition($entryid = -1) {
        $elements = array();

        // get patterns definitions
        $fields = $this->get_fields();
        $fielddefinitions = array();
        $entry = new stdClass;

        if ($fieldpatterns = $this->get_pattern_set('field')) {
            foreach ($fieldpatterns as $fieldid => $patterns) {
                $field = $fields[$fieldid];
                $entry->id = $entryid;
                $options = array('edit' => true, 'manage' => true);
                if ($definitions = $field->get_definitions($patterns, $entry, $options)) {
                    $fielddefinitions = array_merge($fielddefinitions, $definitions);
                }
            }
        }

        $elements[] = $fielddefinitions;
        return $elements;
    }
}
