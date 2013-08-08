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
 * @subpackage csv
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/view/aligned/view_class.php");

class dataformview_csv extends dataformview_aligned {

    const EXPORT_ALL = 'all';
    const EXPORT_PAGE = 'page';

    protected $type = 'csv';
    
    protected $_output = 'csv';
    protected $_delimiter = 'comma';
    protected $_enclosure = '';
    protected $_encoding = 'UTF-8';

    protected $_showimportform = false;
    
    /**
     *
     */
    public function __construct($df = 0, $view = 0) {       
        parent::__construct($df, $view);
        if (!empty($this->view->param3)) {
            $this->_output = $this->view->param3;
        }
        if (!empty($this->view->param1)) {
            list($this->_delimiter, $this->_enclosure, $this->_encoding) = explode(',', $this->view->param1);
        }
    }

    /**
     * process any view specific actions
     */
    public function process_data() {
        global $CFG;

        // proces csv export request
        if ($exportcsv = optional_param('exportcsv','', PARAM_ALPHA)) {
            $this->process_export($exportcsv);
        }
        
        // proces csv import request
        if ($importcsv = optional_param('importcsv',0, PARAM_INT)) {
            $this->process_import();
        }
        
    }

    /**
     * Overridden to show import form without entries
     */
    public function display(array $params = array()) {
        global $OUTPUT;

        if ($this->_showimportform) {
            
            $mform = $this->get_import_form();
        
            $tohtml = isset($params['tohtml']) ? $params['tohtml'] : false;
            // print view
            $viewname = 'dataformview-'. str_replace(' ', '_', $this->name());
            if ($tohtml) {
                return html_writer::tag('div', $mform->html(), array('class' => $viewname));
            } else {
                echo html_writer::start_tag('div', array('class' => $viewname));
                $mform->display();
                echo html_writer::end_tag('div');
            }
        
        } else {
            return parent::display($params);
        }    
    }

    /**
     *
     */
    public function process_export($range = self::EXPORT_PAGE) {
        global $CFG;

        require_once($CFG->libdir . '/csvlib.class.php');

        if (!$csvcontent = $this->get_csv_content($range)) {
            return;
        }
        $dataformname = $this->_df->name();
        $delimiter = csv_import_reader::get_delimiter($this->_delimiter);
        $filename = clean_filename("{$dataformname}-export");
        $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
        $filename .= clean_filename("-{$this->_delimiter}_separated");
        $filename .= '.'. $this->_output;

        $patterns = array("\n");
        $adjustments = array('');
        if ($this->_enclosure) {
            $patterns[] = $this->_enclosure;
            $adjustments[] = '&#' . ord($this->_enclosure) . ';';
        } else {
            $patterns[] = $delimiter;
            $adjustments[] = '&#' . ord($delimiter) . ';';
        }
        $returnstr = '';
        foreach($csvcontent as $row) {
            foreach($row as $key => $column) {
                $value = str_replace($patterns, $adjustments, $column);
                $row[$key] = $this->_enclosure. $value. $this->_enclosure;
            }
            $returnstr .= implode($delimiter, $row) . "\n";
        }
        
        // Convert encoding
        $returnstr = mb_convert_encoding($returnstr, $this->_encoding);

        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
        header('Pragma: public');

        echo $returnstr;
        exit;
    }

    /**
     *
     */
    public function get_csv_content($range = self::EXPORT_PAGE) {
        // Set content
        if ($range == self::EXPORT_ALL) {
            $entries = new dataform_entries($this->_df, $this);
            $options = array();
            // Set a filter to take it all
            $filter = $this->get_filter();
            $filter->perpage = 0;
            $options['filter'] = $filter;
            // do we need ratings?
            if ($ratingoptions = $this->is_rating()) {
                $options['ratings'] = $ratingoptions;
            }
            // do we need comments?
            
            // Get the entries
            $entries->set_content($options);
            $exportentries = $entries->entries();
        } else {
            $this->set_content();
            $exportentries = $this->_entries->entries();
        }

        // Compile entries if any
        if (!$exportentries) {
            return null;
        }

        $csvcontent = array();
        
        // Get csv headers from view columns
        $csvheader = array();
        $columns = $this->get_columns();
        foreach ($columns as $column) {
            list($pattern, $header,) = $column;
            $csvheader[] = $header ? $header : trim($pattern,'[#]');
        }
        $csvcontent[] = $csvheader;
        
        // Get the field definitions
        // array(array(pattern => value,...)...)
        foreach ($exportentries as $entryid => $entry) {
            $row = array();
            $definitions = $this->get_field_definitions($entry, array());
            foreach ($definitions as  $definition) {
                if (is_array($definition)) {
                    list(, $value) = $definition;
                    $row[] = $value;
                }
            }
            $csvcontent[] = $row;
        }

        return $csvcontent;
    }

    /**
     *
     */
    public function process_import() {
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
        } else {
            // Set import flag to display the form
            $this->_showimportform = true;
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
    
        $delimiter = !empty($options['delimiter']) ? $options['delimiter'] : $this->_delimiter;
        $enclosure = !empty($options['enclosure']) ? $options['enclosure'] : $this->_enclosure;
        $encoding = !empty($options['encoding']) ? $options['encoding'] : $this->_encoding;
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
     *
     */
    public function get_import_form() {
        global $CFG;
        require_once("$CFG->dirroot/mod/dataform/view/csv/import_form.php");

        $actionurl = new moodle_url($this->_baseurl, array('importcsv' => 1)); 
        return new dataformview_csv_import_form($this, $actionurl);
    }

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
        // second row: export 
        $row2 = new html_table_row();
        $addentries = new html_table_cell('##addnewentries##');
        $addentries->colspan = 5;
        $row2->cells = array($addentries);
        foreach ($row2->cells as $cell) {
            $cell->style = 'border:0 none;';
        }        
        // next row: export import
        $row2a = new html_table_row();
        $addentries = new html_table_cell('##export:all## | ##export:page## | ##import##');
        $addentries->colspan = 5;
        $row2a->cells = array($addentries);
        foreach ($row2a->cells as $cell) {
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
        $table->data = array($row1, $row2, $row2a, $row3);
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
    }    

    /**
     * Overridden to add default headers from patterns
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
                $header = !empty($arr[1]) ? $arr[1] : trim($tag, '[]#');
                $class = !empty($arr[2]) ? $arr[2] : '';

                $definition = array($tag, $header, $class);                
                $this->_columns[] = $definition;            
            }
        }
        return $this->_columns;
    }    
}
