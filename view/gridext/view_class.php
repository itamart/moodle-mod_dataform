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
 * @subpackage gridext
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/view/grid/view_class.php");

class dataform_view_gridext extends dataform_view_grid {

    protected $type = 'gridext';
    protected $_editors = array('section', 'param2', 'param4', 'param5');
    protected $_vieweditors = array('section', 'param4', 'param5');
    
    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        parent::generate_default_view();
        
        $this->view->eparam4 = '';
        $this->view->eparam5 = '';
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;
        
        $listheader = $this->view->eparam4;
        $listfooter = $this->view->eparam5;

        // flatten the set to a list of elements
        $elements = array();
        $elements[] = array('html', $listheader);

        foreach ($entriesset as $entry_definitions) {
            $elements = array_merge($elements, $entry_definitions);
        }

        $elements[] = array('html', $listfooter);

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

}
