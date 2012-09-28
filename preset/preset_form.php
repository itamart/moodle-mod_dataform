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
 * @package mod
 * @subpackage dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->libdir/formslib.php");

/**
 *
 */
class mod_dataform_preset_form extends moodleform {

    function definition() {
        global $COURSE;

        $mform = &$this->_form;

        $mform->addElement('header', 'presetshdr', get_string('presetadd', 'dataform'));
        // preset source
        $grp = array();
        $grp[] = &$mform->createElement('radio', 'preset_source', null, get_string('presetfromdataform', 'dataform'), 'current');
        
        $packdata = array(
            'nodata' => get_string('presetnodata', 'dataform'),
            'data' => get_string('presetdata', 'dataform'),
            'dataanon' => get_string('presetdataanon', 'dataform'),
        );
        $grp[] = &$mform->createElement('select', 'preset_data', null, $packdata);
        $grp[] = &$mform->createElement('radio', 'preset_source', null, get_string('presetfromfile', 'dataform'), 'file');
        $mform->addGroup($grp, 'psourcegrp', null, array('  ', '<br />'), false);
        $mform->setDefault('preset_source', 'current');
        
        // upload file
        $options = array('subdirs' => 0,
                            'maxbytes' => $COURSE->maxbytes,
                            'maxfiles' => 1,
                            'accepted_types' => array('*.zip','*.mbz'));
        $mform->addElement('filepicker', 'uploadfile', null, null, $options);
        $mform->disabledIf('uploadfile', 'preset_source', 'neq', 'file');

        $mform->addElement('html', '<br /><div class="mdl-align">');
        $mform->addElement('submit', 'add', '    '. get_string('add'). '    ');
        $mform->addElement('html', '</div>');
    }

}
