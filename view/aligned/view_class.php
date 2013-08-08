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
 * @subpackage aligned
 * @copyright 2012 Itamar Tzadok 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/view_class.php");

class dataformview_aligned extends dataformview_base {

    protected $type = 'aligned';
    protected $_editors = array('section');
    
    protected $_columns = null;
    
    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        // get all the fields
        if (!$fields = $this->_df->get_fields()) {
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
        $this->view->param2 = '';
        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $fieldname = $field->name();
                $this->view->param2 .= "[[$fieldname]]\n";
            }
        }
        // actions
        $this->view->param2 .= "##edit##\n##delete##";
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;
        
        $elements = array();

        // Generate the header row
        $tableheader = '';
        if ($this->has_headers()) {
            $columns = $this->get_columns();
            foreach ($columns as $column) {
                list(,$header,$class) = $column;
                $tableheader .= html_writer::tag('th', $header, array('class' => $class));
            }
            $tableheader = html_writer::tag('thead', html_writer::tag('tr', $tableheader));

            // Set view tags in header row
            $tags = $this->_tags['view'];
            $replacements = $this->patterns()->get_replacements($tags);
            $tableheader = str_replace($tags, $replacements, $tableheader);
        }
        // Open table and wrap header with thead
        $elements[] = array('html', html_writer::start_tag('table', array('class' => 'generaltable')). $tableheader);

        // flatten the set to a list of elements, wrap with tbody and close table
        $elements[] = array('html', html_writer::start_tag('tbody'));
        foreach ($entriesset as $entryid => $entry_definitions) {
            $elements = array_merge($elements, $entry_definitions);
        }
        $elements[] = array('html', html_writer::end_tag('tbody'). html_writer::end_tag('table'));

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
        // Get the columns definition from the view template
        $columns = $this->get_columns();

        // Generate entry table row
        $elements[] = array('html', html_writer::start_tag('tr'));
        foreach ($columns as $column) {
            list($tag,,$class) = array_map('trim', $column);
            if (!empty($fielddefinitions[$tag])) {
                $fielddefinition = $fielddefinitions[$tag];
                if ($fielddefinition[0] == 'html') {
                    $elements[] = array('html', html_writer::tag('td', $fielddefinition[1], array('class' => $class)));
                } else {
                    $elements[] = array('html', html_writer::start_tag('td', array('class' => $class)));
                    $elements[] = $fielddefinition;
                    $elements[] = array('html', html_writer::end_tag('td'));
                }
            } else {
                $elements[] = array('html', html_writer::tag('td', '', array('class' => $class)));
            }
        }
        $elements[] = array('html', html_writer::end_tag('tr'));
                
        return $elements;      
    }

    /**
     *
     */
    protected function new_entry_definition($entryid = -1) {
        $elements = array();
        
        // Get the columns definition from the view template
        $columns = $this->get_columns();


        // Get field definitions for new entry
        $fields = $this->_df->get_fields();
        $entry = (object) array('id' => $entryid);
        $fielddefinitions = array();
        foreach ($this->_tags['field'] as $fieldid => $patterns) {
            $field = $fields[$fieldid];
            $options = array('edit' => true, 'manage' => true);
            if ($definitions = $field->get_definitions($patterns, $entry, $options)) {
                $fielddefinitions = array_merge($fielddefinitions, $definitions);
            }
        }            

        // Generate entry table row
        $elements[] = array('html', html_writer::start_tag('tr'));
        foreach ($columns as $column) {
            list($tag,,$class) = array_map('trim', $column);
            if (!empty($fielddefinitions[$tag])) {
                $fielddefinition = $fielddefinitions[$tag];
                if ($fielddefinition[0] == 'html') {
                    $elements[] = array('html', html_writer::tag('td', $fielddefinition[1], array('class' => $class)));
                } else {
                    $elements[] = array('html', html_writer::start_tag('td', array('class' => $class)));
                    $elements[] = $fielddefinition;
                    $elements[] = array('html', html_writer::end_tag('td'));
                }
            } else {
                $elements[] = array('html', html_writer::tag('td', '', array('class' => $class)));
            }
        }
        $elements[] = array('html', html_writer::end_tag('tr'));
        
        return $elements;
    }

    /**
     *
     */
    protected function get_columns() {
        if (empty($this->_columns)) {
            $this->_columns = array();
            $columns = explode("\n", $this->view->param2);
            foreach ($columns as $column) {
                $column = trim($column);
                if (empty($column)) {
                    continue;
                }
                $arr = explode("|", $column);
                $tag = $arr[0]; // Must exist
                $header = !empty($arr[1]) ? $arr[1] : '';
                $class = !empty($arr[2]) ? $arr[2] : '';

                $definition = array($tag, $header, $class);                
                $this->_columns[] = $definition;            
            }
        }
        return $this->_columns;
    }

    /**
     *
     */
    protected function has_headers() {
        foreach ($this->get_columns() as $column) {
            if (!empty($column[1])) {
                return true;
            }
        }
        return false;
    }
    
    /**
     *
     */
    protected function set__patterns($data = null) {
        parent::set__patterns($data);
        
        // get patterns from param2
        if ($data) {
            $text = !empty($data->param2) ? ' '. $data->param2 : '';
            if (trim($text)) {
                // This view patterns
                if ($patterns = $this->patterns()->search($text)) {
                    $this->_tags['view'] = array_merge($this->_tags['view'], $patterns);
                }
                // Field patterns
                if ($fields = $this->_df->get_fields()) {
                    foreach ($fields as $fieldid => $field) {
                        if ($patterns = $field->renderer()->search($text)) {
                            $this->_tags['field'][$fieldid] = $patterns;
                        }
                    }
                }
                
            }
            $this->view->patterns = serialize($this->_tags);
        }
    }
    
}
