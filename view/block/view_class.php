<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/
 *
 * @package mod-dataform
 * @subpackage view-block
 * @author Itamar Tzadok
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's standard Database activity module. To the extent that the
 * Dataform code corresponds to the Database code (1.9.11+ (20110323)),
 * certain copyrights on certain files may obtain.
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

require_once("$CFG->dirroot/mod/dataform/view/view_class.php");

class dataform_view_block extends dataform_view_base {

    protected $type = 'block';
    protected $_editors = array('section', 'param2');
    
    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        // get all the fields for that database
        if (!$fields = $this->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        // set views and filters menus and quick search
        $table = new html_table();
        $table->attributes['align'] = 'center';
        $table->attributes['cellpadding'] = '2';
        // first row: menus
        $row1 = new html_table_row();
        $viewsmenu = new html_table_cell('##viewsmenu##');
        $seperator = new html_table_cell('     ');
        $filtersmenu = new html_table_cell('##filtersmenu##');
        $quicksearch = new html_table_cell('##quicksearch##');
        $quickperpage = new html_table_cell('##quickperpage##');
        $row1->cells = array($viewsmenu, $seperator, $filtersmenu, $quicksearch, $quickperpage);
        foreach ($row1->cells as $cell) {
            $cell->style = 'border:0 none;';
        }
        // second row: add entries 
        $row2 = new html_table_row();
        $addentries = new html_table_cell('##addnewentry##');
        $addentries->colspan = 5;
        $row2->cells = array($addentries);
        foreach ($row2->cells as $cell) {
            $cell->style = 'border:0 none;';
        }        
        // third row: paging bar
        $row3 = new html_table_row();
        $pagingbar = new html_table_cell('##pagingbar##');
        $pagingbar->colspan = 5;
        $row3->cells = array($pagingbar);
        foreach ($row3->cells as $cell) {
            $cell->style = 'border:0 none;';
        }
        // construct the table
        $table->data = array($row1, $row2, $row3);
        $sectiondefault = html_writer::table($table);
        $this->view->esection = html_writer::tag('div', $sectiondefault, array('class' => 'mdl-align'));


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
        $actions = new html_table_cell('##edit##  ##more##  ##delete##  ##approve##');
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
    public function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;
        
        $entries_set = $this->get_entries_definition($entriesset, $name);

        // flatten the set to a list of elements
        $listbody = array();
        foreach ($entries_set as $entry_definitions) {
            $listbody = array_merge($listbody, $entry_definitions);
        }

        $elements = array();
        $elements[] = array('html', '<div class="entriesview">');
        if ($name) {
            $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
            $elements[] = array('html', $OUTPUT->heading($name, 3, 'main'));
        }
        $elements = array_merge($elements, $listbody);
        $elements[] = array('html', '</div>');
        return $elements;
    }

    /**
     *
     */
    public function entry_definition($fielddefinitions) {
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
    public function new_entry_definition() {
        $elements = array();
        
        static $i = -1;
        
        // get patterns definitions
        $fields = $this->get_fields();
        $tags = array();
        $patterndefinitions = array();
        $entry = new object;
        foreach ($this->_patterns['field'] as $fieldid => $patterns) {
            $field = $fields[$fieldid];
            $entry->id = $i;
            if ($fieldpatterns = $field->patterns($patterns, $entry, true, true)) {
                $patterndefinitions = array_merge($patterndefinitions, $fieldpatterns);
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

        $i--;
        
        return $elements;
    }

}
