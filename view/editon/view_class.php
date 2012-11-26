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
 * @subpackage editon
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/grid/view_class.php");

/**
 * A dataform view class that displays one new entry for adding.
 * This may be useful for applications such as 'contact us' where anonymous
 * users can post messages to site admin but not see any posted messages.
 * TODO Implement return to caller view
 */
class dataform_view_editon extends dataform_view_grid {

    protected $type = 'editon';
    protected $_editors = array('section', 'param2', 'param7');

    const RETURN_SELF = 0;
    const RETURN_NEW = 1;
    const RETURN_CALLER = 2;
    
    /**
     * Overriding parent method to always redirect to adding new  entry
     */
    public function process_data() {
        // If cancelled redirect
        if ($cancel = optional_param('cancel', 0, PARAM_BOOL)) {
            // Redirect if needed (to new form or to caller view)
            if ($redirecturl = $this->get_return_url()) {
                redirect($redirecturl);
            }
        }

        // Process entry
        $processed = $this->process_entries_data();

        // Proceed to redirect
        if ($this->_editentries and !($this->_editentries < 0)) {
            // New entries return the new entry id in _editentries so that we know they are new
            // So _editentries must be emptied
            $this->_editentries = '';
            $response = '';
            $timeout = -1;
    
            list($strnotify, $processedeids) = $processed;       
            if ($entriesprocessed = ($processedeids ? count($processedeids) : 0)) {
               $response = $this->get_response_for_submission(); 
            } else {
               $response = get_string('submitfailure', 'dataformview_editon');
            }
            $timeout = $this->get_response_timeout();
            
            // Redirect if needed (to new form or to caller view)
            if ($redirecturl = $this->get_return_url()) {
                redirect($redirecturl, $response, $timeout);
            }
                        
            // otherwise proceed to show response to submission and 'add new' link 
        }
        
        // Proceed to form (new or current)
        if ($this->view->param6 == self::RETURN_NEW) {
            $this->_editentries = -1;
        }
    }

    /**
     * No need to set content
     */
    public function set_content() {
    }

    /**
     *
     */
    public function display(array $params = null) {
        // set display params
        $params['notify'] = false;
        if ($this->user_is_editing()) {
            $params['controls'] = false;
        }
        return parent::display($params);
    }

    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        // get all the fields
        if (!$fields = $this->_df->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

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
        // construct the table
        $entrydefault = html_writer::table($table);
        $this->view->eparam2 = html_writer::tag('div', $entrydefault, array('class' => 'mdl-align'));
    }

    /**
     * Overriding the base method to return elements for one
     * new entry.
     * @param array $entriesset entryid => array(entry, edit, editable)
     */
    public function get_entries_definition() {       
        $elements = array();

        $display_definition = $this->_display_definition;
        foreach ($display_definition as $name => $entriesset) {
            if ($name != 'newentry') {
                continue;
            }
            
            // just add one new entry definition
            $definitions[-1] = $this->new_entry_definition(-1);            
            $elements = $this->group_entries_definition($definitions);
            // and break b/c one is what we want
            break;
        }
                
        return $elements;
    }

    /**
     * Entry definition is not supposed to be called but just in case
     */
    protected function entry_definition($fielddefinitions) {
        return array();
    }
    
    /**
     *
     */
    protected function get_return_url() {
        $redirecturl = null;
        
        if (!empty($this->view->param6)) {
            if ($this->view->param6 == self::RETURN_NEW) {
                $redirecturl = new moodle_url($this->_baseurl, array('sesskey' => sesskey(), 'new' => 1));
            } else if ($this->view->param6 == self::RETURN_CALLER) {
                $params = array('d' => $this->_df->id());
                if ($ret = optional_param('ret', '', PARAM_SEQUENCE)) {
                    list($params['view'], $params['filter']) = explode(',', $ret);
                }   
                $redirecturl = new moodle_url("/mod/dataform/{$this->_df->pagefile()}.php", $params);
            }
        }
        return $redirecturl;
    }
    
    /**
     *
     */
    protected function get_response_for_submission() {
        $response = file_rewrite_pluginfile_urls($this->view->eparam7,
                                                'pluginfile.php',
                                                $this->_df->context->id,
                                                'mod_dataform',
                                                'view',
                                                $this->id(). '2');
        if (!$response) {
            $response = get_string('responsedefault', 'dataformview_editon');
        }
        return $response;
    }
    
    /**
     *
     */
    protected function get_response_timeout() {
        if (!empty($this->view->param5)) {
            return (int) $this->view->param5;
        } else {
            return -1;
        }
    }
    
    /**
     *
     */
    public function get_submit_label() {
        if (!empty($this->view->param4)) {
            return s($this->view->param4);
        } else {
            return get_string('submit');
        }
    }
    
    /**
     *
     */
    public function show_cancel() {
        if (!empty($this->view->param8)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     *
     */
    public function show_save_continue() {
        if (!empty($this->view->param9)) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     *
     */
    protected function get_entries_form_type() {
        return 'editon';
    }
}
