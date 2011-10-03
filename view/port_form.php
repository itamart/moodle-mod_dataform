<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/. 
 *
 * @package mod-dataform
 * @author Itamar Tzadok
 * @copyright 2011 Moodle contributors
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's standard Database activity module. To the extent that the
 * Dataform code corresponds to the Database code (1.9.11+ (20110323)),
 * certain copyrights on certain files may obtain.
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

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden!');

require_once("$CFG->libdir/formslib.php");
require_once("$CFG->libdir/csvlib.class.php");

/**
 *
 */
abstract class mod_dataform_port_form extends moodleform {

    /**
     *
     */
    public function html() {
        return $this->_form->toHtml();
    }


    /**
     *
     */
    protected function add_hidden_fields() {
        $mform = $this->_form;
    
        $mform->addElement('hidden', 'import', 1);
        $mform->setType('import', PARAM_BOOL);

    }

    /**
     *
     */
    protected function port_types($portname, array $types, $default = null) {
        $mform = &$this->_form;

        $mform->addElement('header', 'porttypehdr', get_string("{$portname}type", 'dataform'));

        $typesarray = array();
        foreach ($types as $type) {
            $typesarray[] = &$mform->createElement('radio', 'porttype', null, get_string("porttype$type", 'dataform'), $type);
        }
        $mform->addGroup($typesarray, 'typesarr', null, '<br /> ', false);
        $mform->addRule('typesarr', null, 'required');
        if (!empty($default)) {
            $mform->setDefault('porttype', $default);
        }
    }

    /**
     *
     */
    protected function csv_settings() {
        $mform = &$this->_form;

        $mform->addElement('header', 'csvsettingshdr', get_string('csvsettings', 'dataform'));

        $delimiters = csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter', get_string('csvdelimiter', 'dataform'), $delimiters);
        $mform->setDefault('delimiter', 'comma');

        $mform->addElement('text', 'enclosure', get_string('csvenclosure', 'dataform'), array('size'=>'10'));
        $mform->setDefault('enclosure', '');

        $textlib = textlib_get_instance();
        $choices = $textlib->get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'admin'), $choices);
        $mform->setDefault('encoding', 'UTF-8');
    }    
}

/**
 * Import currently available only from csv
 */
class mod_dataform_import_form extends mod_dataform_port_form {

    function definition() {

        $mform = &$this->_form;

        $this->add_hidden_fields();
        
        $mform->addElement('html', '<table><tr><td valign="top" style="width:30%;">');
        // import types
        //-------------------------------------------------------------------------------
        $this->port_types('import', array('blank', 'csv'), 'blank');
        
        // blank settings
        //-------------------------------------------------------------------------------
        $this->blank_settings();
        
        // csv settings
        //-------------------------------------------------------------------------------
        $this->csv_settings();
        
        $mform->addElement('html', '</td><td valign="top">');

        // import input
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'importinputhdr', get_string('importinput', 'dataform'));
        $mform->addElement('textarea', 'csvtext', get_string('importuploadtext', 'dataform'), array('wrap' => 'virtual', 'rows' => '5', 'cols' => '60'));
        $mform->disabledIf('csvtext', 'porttype', 'neq', 'csv');
        // upload file
        $mform->addElement('filepicker', 'importfile', get_string('file'));
        $mform->disabledIf('csvtext', 'porttype', 'neq', 'csv');
        // update existing entries
        $mform->addElement('selectyesno', 'updateexisting', get_string('importupdateexisting', 'dataform'));
        // edit after import
        $mform->addElement('selectyesno', 'editafter', get_string('importeditimported', 'dataform'));

        $mform->addElement('html', '</td><tr></table>');

        // action buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('import', 'dataform'));
    }

    /**
     *
     */
    protected function blank_settings() {
        $mform = &$this->_form;

        $mform->addElement('header', 'csvsettingshdr', get_string('blanksettings', 'dataform'));

        $mform->addElement('text', 'new', get_string('blankentries', 'dataform'), array('size'=>'10'));
        $mform->setDefault('new', 0);
        $mform->disabledIf('new', 'porttype', 'neq', 'blank');
        $mform->addRule('new', null, 'numeric', null, 'client');
    }    


}

/**
 * Import currently available only from csv
 */
class mod_dataform_export_form extends mod_dataform_port_form {

    function definition() {

        $mform = &$this->_form;

        $this->add_hidden_fields();
        
        // action buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('export', 'dataform'));

        // import types
        //-------------------------------------------------------------------------------
        $this->port_types('export', array('csv', 'xls', 'ods'), 'csv');
                
        // action buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons(true, get_string('export', 'dataform'));
    }
}


