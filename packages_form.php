<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
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

require_once "$CFG->libdir/formslib.php";

/**
 *
 */
class mod_dataform_packages_form extends moodleform {

    function definition() {
        global $COURSE;

        $mform = &$this->_form;

        $mform->addElement('header', 'packageshdr', get_string('packageadd', 'dataform'));
        // package source
        $grp = array();
        $grp[] = &$mform->createElement('radio', 'package_source', null, get_string('packagefromdataform', 'dataform'), 'current');
        $grp[] = &$mform->createElement('radio', 'package_source', null, get_string('packagefromfile', 'dataform'), 'file');
        $mform->addGroup($grp, 'psourcegrp', null, '<br />', false);
        $mform->setDefault('package_source', 'current');
        
        // upload file
        $options = array('subdirs' => 0,
                            'maxbytes' => $COURSE->maxbytes,
                            'maxfiles' => 1,
                            'accepted_types' => array('*.zip','*.mbz'));
        $mform->addElement('filepicker', 'uploadfile', null, null, $options);
        $mform->disabledIf('uploadfile', 'package_source', 'neq', 'file');

        $mform->addElement('html', '<br /><div class="mdl-align">');
        $mform->addElement('submit', 'add', '    '. get_string('add'). '    ');
        $mform->addElement('html', '</div>');
    }

}
