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
 * @subpackage pdf
 * @copyright 2012 Itamar Tzadok 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/view_form.php");

class mod_dataform_view_pdf_form extends mod_dataform_view_base_form {

    /**
     *
     */
    function view_definition_after_gps() {
        global $COURSE;

        $view = $this->_customdata['view'];
        $editoroptions = $view->editors();
        $editorattr = array('cols' => 40, 'rows' => 12);

        $mform =& $this->_form;

        // repeated entry (param2)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('viewlistbody', 'dataform'));

        $mform->addElement('editor', 'eparam2_editor', '', $editorattr, $editoroptions['param2']);
        $mform->setDefault("eparam2_editor[format]", FORMAT_PLAIN);
        $this->add_tags_selector('eparam2_editor', 'field');
        $this->add_tags_selector('eparam2_editor', 'character');        

        // PDF settings (param1)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('pdfsettings', 'dataformview_pdf'));

        // Orientation: Portrait, Landscape
        $options = array(
            '' => get_string('auto', 'dataformview_pdf'),
            'P' => get_string('portrait', 'dataformview_pdf'),
            'L' => get_string('landscape', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'orientation', get_string('orientation', 'dataformview_pdf'), $options);

        // Unit
        $options = array(
            'mm' => get_string('unit_mm', 'dataformview_pdf'),
            'pt' => get_string('unit_pt', 'dataformview_pdf'),
            'cm' => get_string('unit_cm', 'dataformview_pdf'),
            'in' => get_string('unit_in', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'unit', get_string('unit', 'dataformview_pdf'), $options);

        // Format
        $options = array(
            'A4' => get_string('A4', 'dataformview_pdf'),
            'LETTER' => get_string('LETTER', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'format', get_string('format', 'dataformview_pdf'), $options);

        // Destination
        $options = array(
            'D' => get_string('dest_D', 'dataformview_pdf'),
            'I' => get_string('dest_I', 'dataformview_pdf'),
            //'F' => get_string('dest_F', 'dataformview_pdf'),
            //'FI' => get_string('dest_FI', 'dataformview_pdf'),
            //'FD' => get_string('dest_FD', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'destination', get_string('destination', 'dataformview_pdf'), $options);
       
        $fileoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('image'));

        // Frame
        $mform->addElement('filemanager', 'pdfframe', get_string('frame', 'dataformview_pdf'), null, $fileoptions);
            
        // Watermark
        $mform->addElement('filemanager', 'pdfwmark', get_string('watermark', 'dataformview_pdf'), null, $fileoptions);

        // Watermark Transparency
        $transunits = range(0, 1, 0.1);
        $options = array_combine($transunits, $transunits);
        $mform->addElement('select', 'transparency', get_string('transparency', 'dataformview_pdf'), $options);
        
        // Digital Signature (param3)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('pdfprotection', 'dataformview_pdf'));

        // Permissions
        $perms = $view::get_permission_options();
        foreach ($perms as $perm => $label) {
            $elemgrp[] = &$mform->createElement('advcheckbox', "perm_$perm", null, $label, null, array('', $perm));
        }
        $mform->addGroup($elemgrp, "perms_grp", get_string('protperms', 'dataformview_pdf'), '<br />', false);
        
        // User Password
        $mform->addElement('text', 'protuserpass', get_string('protuserpass', 'dataformview_pdf'));

        // Owner Password
        $mform->addElement('text', 'protownerpass', get_string('protownerpass', 'dataformview_pdf'));

        // Mode
        $options = array(
            '' => get_string('choosedots'),
            0 => get_string('protmode0', 'dataformview_pdf'),
            1 => get_string('protmode1', 'dataformview_pdf'),
            2 => get_string('protmode2', 'dataformview_pdf'),
            3 => get_string('protmode3', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'protmode', get_string('protmode', 'dataformview_pdf'), $options);

        // Pub keys
        // ...

        // Digital Signature (param3)
        //-------------------------------------------------------------------------------
        $mform->addElement('header', '', get_string('pdfsignature', 'dataformview_pdf'));

        // Certification
        $fileoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('.crt'));
        $mform->addElement('filemanager', 'pdfcert', get_string('certification', 'dataformview_pdf'), null, $fileoptions);
        
        // Password
        $mform->addElement('text', 'certpassword', get_string('certpassword', 'dataformview_pdf'));
        // Type
        $options = array(
            1 => get_string('none'),
            2 => get_string('certperm2', 'dataformview_pdf'),
            3 => get_string('certperm3', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'certtype', get_string('certtype', 'dataformview_pdf'), $options);
        // Info
        $mform->addElement('text', 'certinfoname', get_string('certinfoname', 'dataformview_pdf'));
        $mform->addElement('text', 'certinfoloc', get_string('certinfoloc', 'dataformview_pdf'));
        $mform->addElement('text', 'certinforeason', get_string('certinforeason', 'dataformview_pdf'));
        $mform->addElement('text', 'certinfocontact', get_string('certinfocontact', 'dataformview_pdf'));


    }


    /**
     *
     */
    function data_preprocessing(&$data){
        parent::data_preprocessing($data);

        $view = $this->_customdata['view'];

        // Pdf settings
        if ($settings = $view->get_pdf_settings()) {
            foreach ($settings as $name => $value) {
                if ($name == 'protection') {
                    $protection = $value;
                    $perms = $view::get_permission_options();
                    foreach ($perms as $perm => $unused) {
                        if (in_array($perm, $protection->permissions)) {
                            $var = "perm_$perm";
                            $data->$var = $perm;
                        }
                    }
                    $data->protuserpass = $protection->user_pass;
                    $data->protownerpass = $protection->owner_pass;
                    $data->protmode = $protection->mode;
                    //$data->pubkeys = $protection->pubkeys;
                } else if ($name == 'signature') {
                    $signsettings = $value;
                    $data->certpassword = $signsettings->password;
                    $data->certtype = $signsettings->type;
                    $data->certinfoname = $signsettings->info['Name'];
                    $data->certinfoloc = $signsettings->info['Location'];
                    $data->certinforeason = $signsettings->info['Reason'];
                    $data->certinfocontact = $signsettings->info['ContactInfo'];
                } else {
                    $data->$name = $value;
                }
            }
        }
    }

    /**
     *
     */
    function set_data($data) {
        $this->data_preprocessing($data);
        parent::set_data($data);
    }

    /**
     *
     */
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            $view = $this->_customdata['view'];

            // Pdf settings
            if ($settings = $view->get_pdf_settings()) {

                foreach ($settings as $name => $value) {
                    if ($name == 'protection') {
                        $protection = $value;
                        $protection->permissions = array();
                        $perms = $view::get_permission_options();
                        foreach ($perms as $perm => $unused) {
                            $var = "perm_$perm";
                            if (!empty($data->$var)) {
                                $protection->permissions[] = $perm;
                            }
                        }
                        $protection->user_pass = $data->protuserpass;
                        $protection->owner_pass = $data->protownerpass;
                        $protection->mode = $data->protmode;
                        //$data->pubkeys = $protection->pubkeys;
                        
                        $settings->protection = $protection;
                    } else if ($name == 'signature') {
                        $signsettings = $value;
                        $signsettings->password = $data->certpassword;
                        $signsettings->type = $data->certtype;
                        $signsettings->info['Name'] = $data->certinfoname;
                        $signsettings->info['Location'] = $data->certinfoloc;
                        $signsettings->info['Reason'] = $data->certinforeason;
                        $signsettings->info['ContactInfo'] = $data->certinfocontact;

                        $settings->signature = $signsettings;
                    } else if (isset($data->$name)) {
                        $settings->$name = $data->$name;
                    }
                }

                $data->param1 = serialize($settings);
            }
            
            return $data;
        }
    }

    
}
