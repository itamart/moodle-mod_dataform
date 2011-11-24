<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage view-blockext
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
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

require_once("$CFG->dirroot/mod/dataform/view/block/view_class.php");

class dataform_view_blockext extends dataform_view_block {

    protected $type = 'blockext';
    protected $_editors = array('section', 'param1', 'param2', 'param3');
    
    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        parent::generate_default_view();
        
        $this->view->eparam1 = '';
        $this->view->eparam3 = '';
    }

    /**
     *
     */
    public function set_view_tags($params){
        $tags = $this->_patterns['view'];
        $replacements = $this->get_tags_replacements($this->patterns($tags, $params));
        
        $this->view->esection = str_replace($tags, $replacements, $this->view->esection);
        $this->view->eparam1 = str_replace($tags, $replacements, $this->view->eparam1);
        $this->view->eparam3 = str_replace($tags, $replacements, $this->view->eparam3);
    }

    /**
     *
     */
    public function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;
        
        $entries_set = $this->get_entries_definition($entriesset, $name);

        $listheader = $this->view->eparam1;
        $listfooter = $this->view->eparam3;

        // flatten the set to a list of elements
        $elements = array();
        $elements[] = array('html', $listheader);

        foreach ($entries_set as $entry_definitions) {
            $elements = array_merge($elements, $entry_definitions);
        }

        $elements[] = array('html', $listfooter);

        // if this group is named wrap it with entriesview class
        // this is actually meant as a way to omit the wrapper in csv export
        // but may not be the best way to achieve that so TODO
        if ($name) {
            $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
            array_unshift($elements, array('html', $OUTPUT->heading($name, 3, 'main')));
            array_unshift($elements, array('html', html_writer::start_tag('div', array('class' => 'entriesview'))));
            array_push($elements, array('html', html_writer::start_tag('div')));
        }
        return $elements;
    }

}
