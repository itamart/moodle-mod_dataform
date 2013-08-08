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
 * @package dataformview
 * @subpackage single
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/grid/view_class.php");

/**
 * A dataform view class that displays one new entry for adding.
 * This may be useful for applications such as 'contact us' where anonymous
 * users can post messages to site admin but not see any posted messages.
 * TODO Implement return to caller view
 */
class dataformview_single extends dataformview_base {

    protected $type = 'single';
    protected $_editors = array('section', 'param2');
    protected $_vieweditors = array('section', 'param2');

    /**
     *
     */
    public function set_content() {
        if ($this->_returntoentriesform) {
            return;
        }
        
        // Editing a new entry
        if ($this->_editentries < 0) {
            // Make sure only 1 entry
            $this->_editentries = -1;
            return;
        }
        
        // If not new, must have an entry to edit/display
        if (!$eid = $this->_editentries and !$eid = required_param('eids', PARAM_INT)) {
            return;
        }   

        $this->set_filter(array('eids' => $eid));
        
        $options = array();
         // do we need ratings?
        if ($ratingoptions = $this->is_rating()) {
            $options['ratings'] = $ratingoptions;
        }
        // do we need comments?

        // Get the entries
        $this->_entries->set_content($options);
    }

    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        // get all the fields
        if (!$fields = $this->_df->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        $viewsmenu = html_writer::tag('div', '##viewsmenu##', array('class' => 'mdl-align'));
        $addnewentry = html_writer::tag('div', '##addnewentry##', array('class' => 'mdl-align'));
        $this->view->esection = html_writer::tag('div', $viewsmenu. $addnewentry);

        // set content
        $table = new html_table();
        $table->attributes['align'] = 'center';
        $table->attributes['cellpadding'] = '2';
        // fields
        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $name = new html_table_cell($field->name(). ':');
                $name->style = 'text-align:right;';
                $content = new html_table_cell("[[{$field->name()}]]");
                $row = new html_table_row();
                $row->cells = array($name, $content);
                $table->data[] = $row;
            }
        }
        // actions
        $row = new html_table_row();
        $actions = new html_table_cell('##edit##  ##delete##');
        $actions->colspan = 2;
        $row->cells = array($actions);
        $table->data[] = $row;
        // construct the table
        $entrydefault = html_writer::table($table);
        $this->view->eparam2 = html_writer::tag('div', $entrydefault, array('class' => 'mdl-align'));
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;
        
        $elements = array();

        // flatten the set to a list of elements
        foreach ($entriesset as $entry_definitions) {
            $elements = array_merge($elements, $entry_definitions);
        }

        // Add group heading 
        $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
        if ($name) {
            array_unshift($elements, array('html', $OUTPUT->heading($name, 3, 'main')));
        }
        // Wrap with entriesview
        array_unshift($elements, array('html', html_writer::start_tag('div', array('class' => 'entriesview'))));
        array_push($elements, array('html', html_writer::end_tag('div')));

        return $elements;
    }

    /**
     *
     */
    protected function entry_definition($fielddefinitions) {
        $elements = array();
        
        // split the entry template to tags and html
        $tags = array_keys($fielddefinitions);
        $parts = $this->split_tags($tags, $this->view->eparam2);
        
        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $fielddefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = array('html', $part);
            }
        }

        return $elements;      
    }

    /**
     *
     */
    protected function new_entry_definition($entryid = -1) {
        $elements = array();
        
        // get patterns definitions
        $fields = $this->_df->get_fields();
        $tags = array();
        $patterndefinitions = array();
        $entry = new object;
        foreach ($this->_tags['field'] as $fieldid => $patterns) {
            $field = $fields[$fieldid];
            $entry->id = $entryid;
            $options = array('edit' => true, 'manage' => true);
            if ($fielddefinitions = $field->get_definitions($patterns, $entry, $options)) {
                $patterndefinitions = array_merge($patterndefinitions, $fielddefinitions);
            }
            $tags = array_merge($tags, $patterns);
        }            
            
        // split the entry template to tags and html
        $parts = $this->split_tags($tags, $this->view->eparam2);

        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $patterndefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = array('html', $part);
            }
        }
        
        return $elements;
    }

}
