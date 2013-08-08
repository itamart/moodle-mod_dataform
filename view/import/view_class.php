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
 * @subpackage import
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/view/view_class.php");

class dataformview_import extends dataformview_base {

    protected $type = 'import';
    protected $_editors = array('section');
    
    /**
     * redirect to import page
     */
    public function set_page($page = null) {
        redirect(new moodle_url('/mod/dataform/import.php', array('d' => $this->_df->id())));
    }

    /**
     *
     */
    public function process_data() {
        global $CFG;

        $mform = $this->get_import_form();
        
        if ($mform->is_cancelled()) {
            return null;
        
        } else if ($formdata = $mform->get_data()) {            

            $data = new object;
            $data->eids = array();
            
            $fieldsettings = array();

            // collect field import settings from formdata by field, tag and element
            foreach ($formdata as $name => $value){
                if (strpos($name, 'f_') !== false) {   // assuming only field settings start with f_
                    list(, $fieldid, $tag, $elem) = explode('_', $name);
                    if (!array_key_exists($fieldid, $fieldsettings)) {
                        $fieldsettings[$fieldid] = array();
                    } else if (!array_key_exists($tag, $fieldsettings[$fieldid])) {
                        $fieldsettings[$fieldid][$tag] = array();
                    }
                    $fieldsettings[$fieldid][$tag][$elem] = $value;
                }
            }
            
            // process csv if any
            if ($this->view->param2) {
                
                if (!empty($formdata->csvtext)) { // upload from text
                    $csvcontent = $formdata->csvtext;
                } else { // upload from file
                    $csvcontent = $mform->get_file_content('importfile');
                }

                $options = array(
                    'delimiter' => $formdata->delimiter,
                    'enclosure' => ($formdata->enclosure ? $formdata->enclosure : ''),
                    'encoding' => $formdata->encoding,
                    'updateexisting' => $formdata->updateexisting,
                    'settings' => $fieldsettings
                );
            
                if (!empty($csvcontent)) {
                    $data = $this->process_csv($data, $csvcontent, $options);
                }
            }

            // process fields' non-csv import
            foreach ($fieldsettings as $fieldid => $importsettings) {
                $field = $this->_df->get_field_from_id($fieldid);
                $field->prepare_import_content($data, $importsettings);
            }
            
            return $this->execute_import($data);
        }
    }

    /**
     *
     */
    public function execute_import($data) {
        if ($data->eids) {
            $this->_entries->process_entries('update', $data->eids, $data, true);               
            return true;
        } else {
            return null;
        }
    }

    /**
     * @param array  $options associative delimiter,enclosure,encoding,updateexisting,settings
     */
    public function process_csv(&$data, $csvcontent, $options = null) {
        global $CFG;
    
        require_once("$CFG->libdir/csvlib.class.php");
        
        @set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);
    
        $iid = csv_import_reader::get_new_iid('moddataform');
        $cir = new csv_import_reader($iid, 'moddataform');
    
        $delimiter = !empty($options['delimiter']) ? $options['delimiter'] : 'comma';
        $enclosure = !empty($options['enclosure']) ? $options['enclosure'] : '';
        $encoding = !empty($options['encoding']) ? $options['encoding'] : 'UTF-8';
        $updateexisting = !empty($options['updateexisting']) ? $options['updateexisting'] : false;
        $fieldsettings = !empty($options['settings']) ? $options['settings'] : array();

        $readcount = $cir->load_csv_content($csvcontent, $encoding, $delimiter);
        if (empty($readcount)) { 
            $data->error = get_string('csvfailed','dataform');
            return $data;
        }
    
        // csv column headers
        if (!$fieldnames = $cir->get_columns()) {
            $data->error = get_string('cannotreadtmpfile','error');
            return $data;
        }

        // process each csv record
        $updateexisting = $updateexisting and !empty($csvfieldnames['Entry']);
        $i = 0;
        $cir->init();
        while ($csvrecord = $cir->next()) {
            $csvrecord = array_combine($fieldnames, $csvrecord);
            // set the entry id
            if ($updateexisting and $csvrecord['Entry'] > 0) {
                $data->eids[$csvrecord['Entry']] = $entryid = $csvrecord['Entry'];
            } else {
                $i--;
                $data->eids[$i] = $entryid = $i;
            }
            // iterate the fields and add their content

            foreach ($fieldsettings as $fieldid => $importsettings) {
                $field = $this->_df->get_field_from_id($fieldid);
                $field->prepare_import_content($data, $importsettings, $csvrecord, $entryid);
            }
        }
        $cir->cleanup(true);
        $cir->close();

        return $data;
    }

    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        // get all the fields
        if (!$fields = $this->_df->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        // fields
        $this->view->param1 = '';
        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $this->view->param1 .= "[[{$field->name()}]]\n";
            }
        }
    }

    /**
     *
     */
    public function get_form() {
        global $CFG;

        $returnurl = new moodle_url('/mod/dataform/import.php', array('d' => $this->_df->id()));

        require_once($CFG->dirroot. '/mod/dataform/view/'. $this->type. '/view_form.php');
        $formclass = 'dataformview_'. $this->type. '_form';
        $actionurl = new moodle_url('/mod/dataform/view/view_edit.php',
                                    array('d' => $this->_df->id(),
                                        'vedit' => $this->id(),
                                        'type' => $this->type,
                                        'returnurl' => $returnurl)); 
        return new $formclass($this, $actionurl);
    }

    /**
     *
     */
    public function get_import_form() {
        global $CFG;
        require_once("$CFG->dirroot/mod/dataform/view/import/import_form.php");

        // hide csv settings
        $custom_data = array();
        if (empty($this->view->param2)) {
            $custom_data['hidecsvsettings'] = true;
            $custom_data['hidecsvinput'] = true;
        }           
        
        $actionurl = new moodle_url('/mod/dataform/import.php',
                                    array('d' => $this->_df->id(),
                                        'vid' => $this->id(),
                                        'import' => 1)); 
        return new dataformview_import_import_form($this, $actionurl, $custom_data);
    }
    
    /**
     *
     */
    protected function set__patterns($data = null) {
        parent::set__patterns($data);
        
        // get patterns from param1
        if ($data) {
            $text = !empty($data->param1) ? ' '. $data->param1 : '';

            if (trim($text)) {
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
