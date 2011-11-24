<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @subpackage view-import
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

require_once("$CFG->dirroot/mod/dataform/view/view_class.php");

class dataform_view_import extends dataform_view_base {

    protected $type = 'import';
    protected $_editors = array('section');
    
    /**
     * default view
     */
    public function generate_default_view() {
        // get all the fields for that database
        if (!$fields = $this->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        // fields to import
        $this->view->param1 = '';
        foreach ($fields as $field) {
            if ($field->id() > 0) {
                $name = new html_table_cell($field->name(). ':');
                $this->view->param1 .= "[[{$field->name()}]]\n";
            }
        }        
    }

    /**
     *
     */
    public function group_entries_definition($entriesset, $name = '') {        
        return null;
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
                require_once("$CFG->libdir/csvlib.class.php");
                
                $entriesupdated = $entriesadded= 0;
                $delimiter = $formdata->delimiter;
                $enclosure = $formdata->enclosure ? $formdata->enclosure : '';
                $encoding = $formdata->encoding;
            
                @set_time_limit(0);
                raise_memory_limit(MEMORY_EXTRA);
            
                $iid = csv_import_reader::get_new_iid('moddataform');
                $cir = new csv_import_reader($iid, 'moddataform');
            
                if (!empty($formdata->csvtext)) { // upload from text
                    $csvcontent = $formdata->csvtext;
                } else { // upload from file
                    $csvcontent = $mform->get_file_content('importfile');
                }

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
                $updateexisting = $formdata->updateexisting and !empty($csvfieldnames['Entry']);
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
            }
            
            // process fields' non-csv import
            foreach ($fieldsettings as $fieldid => $importsettings) {
                $field = $this->_df->get_field_from_id($fieldid);
                $field->prepare_import_content($data, $importsettings);
            }

            if ($data->eids) {
                $this->_entries->process_entries('update', $data->eids, $data, true);               
                return true;
            } else {
                return null;
            }
        }
    }

    /**
     *
     */
    public function get_form() {
        global $CFG;

        $custom_data = array('view' => $this, 'df' => $this->_df);
        $returnurl = new moodle_url('/mod/dataform/import.php', array('d' => $this->_df->id()));

        require_once($CFG->dirroot. '/mod/dataform/view/'. $this->type. '/view_form.php');
        $formclass = 'mod_dataform_view_'. $this->type. '_form';
        $actionurl = new moodle_url('/mod/dataform/view/view_edit.php',
                                    array('d' => $this->_df->id(),
                                        'vedit' => $this->id(),
                                        'type' => $this->type,
                                        'returnurl' => $returnurl)); 
        return new $formclass($actionurl, $custom_data);
    }

    /**
     *
     */
    public function get_import_form() {
        global $CFG;
        require_once("$CFG->dirroot/mod/dataform/view/import/import_form.php");

        $custom_data = array();
        $custom_data['view'] = $this;
        $custom_data['df'] = $this->_df;
        // hide csv settings
        if (empty($this->view->param2)) {
            $custom_data['hidecsvsettings'] = true;
            $custom_data['hidecsvinput'] = true;
        }
            
        
        $actionurl = new moodle_url('/mod/dataform/import.php',
                                    array('d' => $this->_df->id(),
                                        'vid' => $this->id(),
                                        'import' => 1)); 
        return new mod_dataform_view_import_import_form($actionurl, $custom_data);
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
                        if ($patterns = $field->patterns()->search($text)) {
                            $this->_patterns['field'][$fieldid] = $patterns;
                        }
                    }
                }
                
            }
            $this->view->patterns = serialize($this->_patterns);
        }
    }
        
    
}
