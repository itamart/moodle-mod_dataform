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
 * @subpackage picture
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once("$CFG->dirroot/mod/dataform/field/file/field_form.php");

class dataformfield_picture_form extends dataformfield_file_form {

    /**
     *
     */
    function field_definition() {
        global $CFG;

        $mform =& $this->_form;

        // pic display dimensions
        $dispdimgrp=array();
        $dispdimgrp[] = &$mform->createElement('text', 'param4', null, array('size'=>'8'));
        $dispdimgrp[] = &$mform->createElement('text', 'param5', null, array('size'=>'8'));
        $dispdimgrp[] = &$mform->createElement('select', 'param6', null, array('px'=>'px','em'=>'em','%'=>'%'));
        $mform->addGroup($dispdimgrp, 'dispdim', get_string('displaydimensions', 'dataformfield_picture'), array('x',''), false);
        $mform->setType('param4', PARAM_INT);
        $mform->setType('param5', PARAM_INT);
        $mform->addGroupRule('dispdim', array('param4' => array(array(null, 'numeric', null, 'client'))));
        $mform->addGroupRule('dispdim', array('param5' => array(array(null, 'numeric', null, 'client'))));
        
        // max pic dimensions (crop if needed)
        $maxpicdimgrp=array();
        $maxpicdimgrp[] = &$mform->createElement('text', 'param7', null, array('size'=>'8'));
        $maxpicdimgrp[] = &$mform->createElement('text', 'param8', null, array('size'=>'8'));
        $mform->addGroup($maxpicdimgrp, 'maxpicdim', get_string('maxdimensions', 'dataformfield_picture'), 'x', false);
        $mform->setType('param7', PARAM_INT);
        $mform->setType('param8', PARAM_INT);
        $mform->addGroupRule('maxpicdim', array('param7' => array(array(null, 'numeric', null, 'client'))));
        $mform->addGroupRule('maxpicdim', array('param8' => array(array(null, 'numeric', null, 'client'))));
        $mform->setDefault('param7', '');
        $mform->setDefault('param8', '');
        
        // thumbnail dimensions (crop if needed)
        $thumbnailgrp=array();
        $thumbnailgrp[] = &$mform->createElement('text', 'param9', null, array('size'=>'8'));
        $thumbnailgrp[] = &$mform->createElement('text', 'param10', null, array('size'=>'8'));
        $mform->addGroup($thumbnailgrp, 'thumbnaildim', get_string('thumbdimensions', 'dataformfield_picture'), 'x', false);
        $mform->setType('param9', PARAM_INT);
        $mform->setType('param10', PARAM_INT);
        $mform->addGroupRule('thumbnaildim', array('param9' => array(array(null, 'numeric', null, 'client'))));
        $mform->addGroupRule('thumbnaildim', array('param10' => array(array(null, 'numeric', null, 'client'))));
        $mform->setDefault('param9', '');
        $mform->setDefault('param10', '');

        parent::field_definition();
        
    }

    /**
     *
     */
    function filetypes_definition() {

        $mform =& $this->_form;

        // accetped types
        $options = array();
        $options['*.jpg,*.gif,*.png'] = get_string('filetypeimage', 'dataform');
        $options['*.jpg'] = get_string('filetypejpg', 'dataform');
        $options['*.gif'] = get_string('filetypegif', 'dataform');
        $options['*.png'] = get_string('filetypepng', 'dataform');
        $mform->addElement('select', 'param3', get_string('filetypes', 'dataform'), $options);

    }
    
}
