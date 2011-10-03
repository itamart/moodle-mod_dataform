<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/. 
 *
 * @package mod-dataform
 * @subpackage view-tabular
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
 
/**
 * A template for displaying dataform entries in a tabular list
 * Parameters used:
 * param1 - list header section
 * param2 - repeated entry section
 * param3 - table cell alignment 
 */

class dataform_view_tabular extends dataform_view_base {

    protected $type = 'tabular';    
    protected $_editors = array('section', 'param2');
    
    /**
     * 
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


        // set content table
        $table = new html_table();
        $table->attributes['align'] = 'center';
        $table->attributes['cellpadding'] = '2';
        $header = array();
        $entry = array();
        $align = array();        
        // author picture
        $header[] = '';
        $entry[] = '##author:picture##';
        $align[] = 'center';        
        // author name
        $header[] = '';
        $entry[] = '##author:name##';
        $align[] = 'left';
        // fields
        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $header[] = $field->field->name;
                $entry[] = '[['. $field->field->name. ']]';
                $align[] = 'left';
            }
        }
        // multiedit
        $header[] = '##multiedit:icon##';
        $entry[] = '##edit##';
        $align[] = 'center';
        // multidelete
        $header[] = '##multidelete:icon##';
        $entry[] = '##delete##';
        $align[] = 'center';
        // multiapprove
        $header[] = '##multiapprove:icon##';
        $entry[] = '##approve##';
        $align[] = 'center';
        // selectallnone
        $header[] = '##selectallnone##';
        $entry[] = '##select##';
        $align[] = 'center';
        
        // construct the table
        $table->head = $header;
        $table->align = $align;
        $table->data[] = $entry;
        $this->view->eparam2 = html_writer::table($table);

    }
    
    /**
     *
     */
    public function set_view_tags($params){
        $tags = $this->_patterns['view'];
        $replacements = $this->get_tags_replacements($this->patterns($tags, $params));
        
        $this->view->esection = str_replace($tags, $replacements, $this->view->esection);
        $this->view->eparam2 = str_replace($tags, $replacements, $this->view->eparam2);
    }






    /**
     *
     */
    public function group_entries_definition($entriesset, $name = '') {
        global $CFG, $OUTPUT, $GLOBALS;
        
        $tablehtml = trim($this->view->eparam2);
        $opengroupdiv = html_writer::start_tag('div', array('class' => 'entriesview'));
        $closegroupdiv = html_writer::end_tag('div');
        $groupheading = $OUTPUT->heading($name, 3, 'main');

        $elements = array();
        $entries_set = $this->get_entries_definition($entriesset, $name);

        // if there are no field definition just return everything as html
        if (empty($entries_set)) {
            $elements[] = array('html', $opengroupdiv. $groupheading. $tablehtml. $closegroupdiv);
        
        } else {

            // clean any prefix and get the open table tag
            //$tablehtml = preg_replace('/^[\s\S]*<table/i', '<table', $tablehtml);        
            $tablepattern = '/^<table[^>]*>/i';
            preg_match($tablepattern, $tablehtml, $match); // must be there
            $tablehtml = trim(preg_replace($tablepattern, '', $tablehtml));
            $opentable = reset($match);
            // clean any suffix and get the close table tag
            $tablehtml = trim(preg_replace('/<\/table>$/i', '', $tablehtml));
            $closetable = '</table>'; 

            // get the header row if required
            $headerrow = '';
            if ($require_headerrow = $this->view->param1) {
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
            if ($require_headerrow and empty($headerrow)) {
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
            $elements[] = array('html', $opengroupdiv. $groupheading. $opentable. $headerrow. '<tbody>');
            
            // do the entries
            // get tags from the first item in the entry set
            $tags = array_keys(reset(reset($entries_set))); 

            foreach ($entries_set as $fielddefinitions) {
                $definitions = reset($fielddefinitions);
                $parts = $this->split_tags($tags, $entrytemplate);
                
                foreach ($parts as $part) {
                    if (in_array($part, $tags)) {
                        if ($def = $definitions[$part]) {
                            $elements[] = $def;
                        }
                    } else {
                        $elements[] = array('html', $part);
                    }
                }
            }
            
            // finish the table
            $elements[] = array('html', '</tbody>'. $closetable. $closegroupdiv);
            
        }

        return $elements;
    }

    /**
     * 
     */
    public function entry_definition($fielddefinitions) {
        $elements = array();
        // just store the fefinitions
        //   and group_entries_definition will process them
        $elements[] = $fielddefinitions; 
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
            
        $elements[] = $patterndefinitions; 

        $i--;
        
        return $elements;
    }
}
