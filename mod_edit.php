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

require_once('../../config.php');
require_once($CFG->dirroot. '/mod/dataform/mod_class.php');
require_once $CFG->libdir.'/formslib.php';

/**
 *
 */
class mod_dataform_mod_section_form extends moodleform {

    function definition() {
        global $CFG;

        $df = $this->_customdata['df'];
        $section = $this->_customdata['section'];
        
        $mform =& $this->_form;

        // hidden optional params
        $mform->addElement('hidden', 'd', $df->id());
        $mform->setType('d', PARAM_INT);

        $mform->addElement('hidden', 'section', $section);
        $mform->setType('section', PARAM_ALPHA);

    // entry settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrysettingshdr', get_string('entrysettings', 'dataform'));

        // if there is an admin limit select from dropdown
        if ($CFG->dataform_maxentries) { 
            $maxoptions = (array_combine(range(1, $CFG->dataform_maxentries),range(1, $CFG->dataform_maxentries)));

            // required entries
            $mform->addElement('select', 'entriesrequired', get_string('entriesrequired', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // required entries to view
            $mform->addElement('select', 'entriestoview', get_string('entriestoview', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // max entries
            $mform->addElement('select', 'maxentries', get_string('entriesmax', 'dataform'), $maxoptions);
            $mform->setDefault('maxentries', $CFG->dataform_maxentries);
        
        // no admin limit so enter any number
        } else { 
            // required entries
            $mform->addElement('text', 'entriesrequired', get_string('entriesrequired', 'dataform'));
            $mform->addRule('entriesrequired', null, 'numeric', null, 'client');
            // required entries to view
            $mform->addElement('text', 'entriestoview', get_string('entriestoview', 'dataform'));
            $mform->addRule('entriestoview', null, 'numeric', null, 'client');
            // max entries
            $mform->addElement('text', 'maxentries', get_string('entriesmax', 'dataform'));
            $mform->addRule('maxentries', null, 'numeric', null, 'client');
        }
        //$mform->addHelpButton('entriesrequired', array('entriesrequired', get_string('entriesrequired', 'dataform'), 'dataform'));
        //$mform->addHelpButton('entriestoview', array('entriestoview', get_string('entriestoview', 'dataform'), 'dataform'));
        //$mform->addHelpButton('maxentries', array('entriesmax', get_string('entriesmax', 'dataform'), 'dataform'));

        // time limit to manage an entry
        $mform->addElement('text', 'timelimit', get_string('entrytimelimit', 'dataform'));
        $mform->setType('timelimit', PARAM_INT);
        $mform->setDefault('timelimit', '');
        $mform->addRule('timelimit', null, 'numeric', null, 'client');
        //$mform->addHelpButton('timelimit', array("entrytimelimit", get_string('entrytimelimit', 'dataform'), 'dataform'));

        // approval
        $mform->addElement('selectyesno', 'approval', get_string('requireapproval', 'dataform'));
        //$mform->addHelpButton('approval', array('requireapproval', get_string('requireapproval', 'dataform'), 'dataform'));

        // comments
        $mform->addElement('selectyesno', 'comments', get_string('comments', 'dataform'));
        //$mform->addHelpButton('comments', array('comments', get_string('commentsallow', 'dataform'), 'dataform'));

        // entry rating
        $mform->addElement('modgrade', 'rating', get_string('rating', 'dataform'));
        $mform->setDefault('rating', 0);

        // entry locks
        $locksarray = array();
        $locksarray[] = &$mform->createElement('advcheckbox', 'lockonapproval', null, get_string('entrylockonapproval', 'dataform'), null, array(0,1));
        $locksarray[] = &$mform->createElement('advcheckbox', 'lockoncomments', null, get_string('entrylockoncomments', 'dataform'), null, array(0,2));
        $locksarray[] = &$mform->createElement('advcheckbox', 'lockonratings', null, get_string('entrylockonratings', 'dataform'), null, array(0,4));
        $mform->addGroup($locksarray, 'locksarr', get_string('entrylocks', 'dataform'), '<br />', false);
        //$mform->addHelpButton('locksarr', array('locksarr', get_string('entrylocks', 'dataform'), 'dataform'));

        $viewmenu = array(0 => get_string('choose')) + $df->get_views(null, true);

        // edit view
        $mform->addElement('select', 'singleedit', get_string('viewforedit', 'dataform'), $viewmenu);
        //$mform->addHelpButton('singleedit', array('viewforedit', get_string('viewforedit', 'dataform'), 'dataform'));

        // single view
        $mform->addElement('select', 'singleview', get_string('viewformore', 'dataform'), $viewmenu);
        //$mform->addHelpButton('singleview', array('viewformore', get_string('viewformore', 'dataform'), 'dataform'));

    //-------------------------------------------------------------------------------
        // buttons
        $this->add_action_buttons(true);
    }

    /**
     *
     */
    function data_preprocessing(&$default_values){
        if (!empty($default_values->locks)) {
            $default_values->lockonapproval = $default_values->locks & 1;
            $default_values->lockoncomments = $default_values->locks & 2;
            $default_values->lockonratings = $default_values->locks & 4;
        }
    }

    /**
     *

     */
    function set_data($default_values) {
        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }

    /**
     *
     */
    function get_data($slashed = true) {
        if ($data = parent::get_data($slashed)) {
            // set locks
            $lockonapproval = !empty($data->lockonapproval) ? $data->lockonapproval : 0;
            $lockoncomments = !empty($data->lockoncomments) ? $data->lockoncomments : 0;
            $lockonratings = !empty($data->lockonratings) ? $data->lockonratings : 0;

            $data->locks = $lockonapproval | $lockoncomments | $lockonratings;
        }
        return $data;
    }

}

$urlparams = new object();
$urlparams->d          = required_param('d', PARAM_INT);
$urlparams->section   = optional_param('section', '' ,PARAM_NOTAGS);

// Set a dataform object
$df = new dataform($urlparams->d);

require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('mod_edit', array('urlparams' => $urlparams));

$mform = new mod_dataform_mod_section_form(null, array('df' => $df, 'section' => $urlparams->section));

if ($mform->is_cancelled()){

} else if ($mform->no_submit_button_pressed()) {

// process validated    
} else if ($fromform = $mform->get_data()) { 

    $df->update($fromform);
}

// print header
$df->print_header(array('tab' => $urlparams->section, 'urlparams' => $urlparams));

// display form
$mform->set_data($df->data);
$mform->display($df->data);

$df->print_footer();

