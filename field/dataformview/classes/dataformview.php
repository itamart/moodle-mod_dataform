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
 * @subpackage dataformview
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class dataformfield_dataformview_dataformview extends mod_dataform\pluginbase\dataformfield_nocontent {
    public $refdataform = null;
    public $refview = null;
    public $reffilterid = null;
    public $css = null;

    protected $_localview = null;
    
    /*
     *
     */
    public function __construct($field) {
        global $DB;
        
        parent::__construct($field);

        // Get the dataform
        if (!$this->param1) {
            return;
        }

        try {
            $df = mod_dataform_dataform::instance($this->param1, null, true);
        } catch (Exception $e) {
            return;
        }
        // TODO Add capability check on view entries
        
        // Get the view
        if (!$this->param2 or !$view = mod_dataform_view_manager::instance($df->id)->get_view_by_id($this->param2)) {
            return;
        }
        $this->refdataform = $df;
        $this->refview = $view;
        $this->localview =  $this->get_local_view();
        $this->reffilterid = $this->param3 ? $this->param3 : 0;
    }
    
    /*
     *
     */
    public function get_local_view() {
        if (!$this->_localview) {
             $this->_localview = $this->df->currentview;
        }
        
        return $this->_localview;
    }

    /**
     * Overriding parent to return no sort/search options.
     * 
     * @return array
     */
    public function get_sort_options_menu() {
        return array();
    }    
    
}

