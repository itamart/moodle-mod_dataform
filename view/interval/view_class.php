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
 * @subpackage interval
 * @copyright 2012 Itamar Tzadok 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/grid/view_class.php");
require_once("$CFG->dirroot/mod/dataform/entries_class.php");

class dataformview_interval extends dataformview_grid {

    protected $type = 'interval';
    
    protected $selection;
    protected $interval;
    protected $custom;
    protected $resetnext;
    protected $page;
    protected $cache = null;
    
    /**
     * Constructor
     */
    public function __construct($df = 0, $view = 0) {
        parent::__construct($df, $view);
        
        $this->selection = $this->_filter->onpage = dataform_entries::SELECT_FIRST_PAGE;
        if (!empty($this->view->param4)) {
             $this->selection = $this->_filter->onpage = $this->view->param4;
        }
        $this->interval = !empty($this->view->param5) ? $this->view->param5 : 0; 
        $this->custom = !empty($this->view->param6) ? $this->view->param6 : 0;
        $this->resetnext = !empty($this->view->param8) ? $this->view->param8 : 100;

        $this->page = $this->_filter->page;

        // set or clean up cache according to interval
        if (!$this->interval and !empty($this->view->param7)) {
            $this->view->param7 = null;
            $this->update();
        }
    }

    /**
     *
     */
    public function is_caching() {
        if ($this->interval) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 
     */
    public function update_cache_content($entriesset) {
        $this->cache->content = $entriesset;
        $this->view->param7 = serialize($this->cache);
        $this->update();
    }

    /**
     *
     */
    public function get_cache_filter_options() {
        $options = array();        
        // setting the cache may change page number
        if ($this->page > 0) {
            // next is used and page advances            
            $options['page'] = $this->page;
        }       
        return $options;
    }

    /**
     * 
     */
    public function get_cache_content() {
        $refresh = $this->set_cache();
        if (!$refresh and isset($this->cache->content)) {
            return $this->cache->content;
        } else {
            return null;
        }
    }

    /**
     * 
     */
    protected function set_cache() {

        // assumes we are caching and interval is set
        $now = time();
        if (!empty($this->view->param7)) {
            $this->cache = unserialize($this->view->param7);
            if (!empty($this->cache->next)) {
                $this->page = $this->cache->next;
            }
        } else {
            // first time
            $this->cache = new object();
            $this->cache->time = 0;
        }

        // get checktime
        switch ($this->interval) {
            case 'monthly':
                $checktime = mktime(0, 0, 0, date('m'), 1, date('Y'));
                break;
        
            case 'weekly':
                $checktime = strtotime('last '. get_string('firstdayofweek', 'dataform'));
                break;
                
            case 'daily':
                $checktime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
                break;
                
            case 'hourly':
                $checktime = mktime(date('H'), 0, 0, date('m'), date('d'), date('Y'));
                break;
                
            case 'custom':
                $checktime = $now - ($now % $this->custom);
                break;

            default:
                $checktime = $now;
                break;
        }
        
        if ($checktime > $this->cache->time) {
            $this->cache->time = $checktime;
            
            if ($this->selection == dataform_entries::SELECT_NEXT_PAGE) {
                $this->cache->next++;
                if ($this->cache->next > $this->resetnext) {
                    $this->cache->next = 0;
                }
                $this->page = $this->cache->next;
            }
            return true;
        } else {
            return false;
        }
    }

}
