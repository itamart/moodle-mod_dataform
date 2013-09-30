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
 * This file is part of the Dataform module for Moodle - http://moodle.org/. 
 *
 * @package dataformview
 * @subpackage csv
 * @copyright 2012 Itamar Tzadok 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/csvlib.class.php");

/**
 *
 */
class dataformview_csv_import_form extends moodleform {
    protected $_view;

    public function __construct($view, $action = null, $customdata = null, $method = 'post', $target = '', $attributes = null, $editable = true) {
        $this->_view = $view;
        
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable);       
    }
    
    /**
     *
     */
    public function html() {
        return $this->_form->toHtml();
    }


    function definition() {

        $view = $this->_view;
        $fieldsettings = empty($this->_customdata['hidefieldsettings']) ? true : false;

        $mform = &$this->_form;

        // action buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('import', 'dataform'));
        
        // field settings
        //-------------------------------------------------------------------------------
        $this->field_settings();
        
        // csv settings
        //-------------------------------------------------------------------------------
        $this->csv_settings();
        
        // action buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('import', 'dataform'));
    }

    /**
     *
     */
    protected function field_settings() {
        $view = $this->_view;
        $df = $view->get_df();
        $mform = &$this->_form;

        $mform->addElement('header', 'fieldsettingshdr', get_string('fieldsimportsettings', 'dataformview_import'));
        $columns = $view->get_columns();
        foreach ($columns as $column) {
            list($pattern, $header,) = $column;
            $patternname = trim($pattern,'[#]');
            $header = $header ? $header : $patternname;

            if (!$fieldid = $view->get_pattern_fieldid($pattern)) {
                continue;
            }

            if (!$field = $df->get_field_from_id($fieldid)) {
                continue;
            }
            
            $name = "f_{$fieldid}_$patternname";

            $grp = array();
            $grp[] = &$mform->createElement('text', "{$name}_name", null, array('size'=>'16'));                   
            list($elements, $labels) = $field->renderer()->get_pattern_import_settings($mform, $pattern);
            $grp = $grp + $elements;
            $mform->addGroup($grp, "grp$patternname", $patternname, $labels, false);
                                
            $mform->setType("{$name}_name", PARAM_NOTAGS);
            $mform->setDefault("{$name}_name", $header);
        }
    }    

    /**
     *
     */
    protected function csv_settings() {
        $view = $this->_view;
        $mform = &$this->_form;

        $mform->addElement('header', 'csvsettingshdr', get_string('csvsettings', 'dataform'));

        // delimiter
        $delimiters = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'dataform'), $delimiters);
        $mform->setDefault('delimiter', $view->get_delimiter());

        // enclosure
        $mform->addElement('text', 'enclosure', get_string('csvenclosure', 'dataform'), array('size'=>'10'));
        $mform->setType('enclosure', PARAM_NOTAGS);
        $mform->setDefault('enclosure', $view->get_enclosure());

        // encoding
        $choices = textlib::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'grades'), $choices);
        $mform->setDefault('encoding', $view->get_encoding());

        // upload file
        $mform->addElement('filepicker', 'importfile', get_string('uploadfile', 'dataformview_import'));
        
        // upload text
        $mform->addElement('textarea', 'csvtext', get_string('uploadtext', 'dataformview_import'), array('wrap' => 'virtual', 'rows' => '5', 'style' => 'width:100%;'));
        
        // update existing entries
        $mform->addElement('selectyesno', 'updateexisting', get_string('updateexisting', 'dataformview_import'));
        
        // edit after import
        //$mform->addElement('selectyesno', 'editafter', get_string('importeditimported', 'dataformview_import'));
    }

}
