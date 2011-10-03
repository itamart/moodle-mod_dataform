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

defined('MOODLE_INTERNAL') or die('Direct access to this script is forbidden.');

require_once ("$CFG->dirroot/course/moodleform_mod.php");

class mod_dataform_mod_form extends moodleform_mod {
    
    protected $_df = null; 

    function definition() {
        global $CFG;
        
        if ($cmid = optional_param('update', 0, PARAM_INT)) {
            require_once($CFG->dirroot. '/mod/dataform/mod_class.php');
            $this->_df = new dataform(0, $cmid);
        }
        
        $mform = &$this->_form;

    // buttons
    //-------------------------------------------------------------------------------
    	$this->add_action_buttons();

    // name and intro
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // name
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->setDefault('name', get_string('modulename', 'dataform'));

        // intro
        $this->add_intro_editor(false, get_string('intro', 'dataform'));

    // timing
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'timinghdr', get_string('timing', 'form'));

        // time available
        $mform->addElement('date_time_selector', 'timeavailable', get_string('dftimeavailable', 'dataform'), array('optional'=>true));
        // time due
        $mform->addElement('date_time_selector', 'timedue', get_string('dftimedue', 'dataform'), array('optional'=>true));
        $mform->disabledIf('timedue', 'interval', 'gt', 0);

        // interval between required entries
        $mform->addElement('duration', 'timeinterval', get_string('dftimeinterval', 'dataform'));
        $mform->disabledIf('timeinterval', 'timeavailable[off]', 'checked');
        $mform->disabledIf('timeinterval', 'timedue[off]');

        // number of intervals
        $mform->addElement('select', 'intervalcount', get_string('dfintervalcount', 'dataform'), array_combine(range(1,100),range(1,100)));
        $mform->setDefault('intervalcount', 1);
        $mform->disabledIf('intervalcount', 'timeavailable[off]', 'checked');
        $mform->disabledIf('intervalcount', 'timedue[off]');
        $mform->disabledIf('intervalcount', 'timeinterval', 'eq', '');

        // allow late
        $mform->addElement('checkbox', 'allowlate', get_string('dflateallow', 'dataform') , get_string('dflateuse', 'dataform'));

    // rss
    //-------------------------------------------------------------------------------
        if($CFG->enablerssfeeds && $CFG->dataform_enablerssfeeds){
            $mform->addElement('header', 'rssshdr', get_string('rss'));

            $mform->addElement('select', 'rssarticles', get_string('numberrssarticles', 'dataform') , $countoptions);
        }

    // grading
    //-------------------------------------------------------------------------------
        $this->standard_grading_coursemodule_elements();

        // rating method
        $grademethods = array(
            0 => get_string('ratingmanual', 'dataform'),
            1 => get_string('ratingsavg', 'dataform'),
            2 => get_string('ratingscount', 'dataform'),
            3 => get_string('ratingsmax', 'dataform'),
            4 => get_string('ratingsmin', 'dataform'),
            5 => get_string('ratingssum', 'dataform')
        );
        $mform->addElement('select', 'grademethod', get_string('gradingmethod', 'dataform'), $grademethods);
        $mform->setDefault('grademethod', 0);
        $mform->disabledIf('grademethod', 'grade', 'eq', 0);
        
    // entry settings
    //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrysettingshdr', get_string('entrysettings', 'dataform'));

        // if there is an admin limit select from dropdown
        if ($CFG->dataform_maxentries > 0) { 
            $maxoptions = (array_combine(range(0, $CFG->dataform_maxentries),range(0, $CFG->dataform_maxentries)));

            // required entries
            $mform->addElement('select', 'entriesrequired', get_string('entriesrequired', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // required entries to view
            $mform->addElement('select', 'entriestoview', get_string('entriestoview', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // max entries
            $mform->addElement('select', 'maxentries', get_string('entriesmax', 'dataform'), $maxoptions);
            $mform->setDefault('maxentries', $CFG->dataform_maxentries);
        
        // no admin limit so enter any number
        } else if ($CFG->dataform_maxentries == -1){ 
            // required entries
            $mform->addElement('text', 'entriesrequired', get_string('entriesrequired', 'dataform'));
            $mform->addRule('entriesrequired', null, 'numeric', null, 'client');
            // required entries to view
            $mform->addElement('text', 'entriestoview', get_string('entriestoview', 'dataform'));
            $mform->addRule('entriestoview', null, 'numeric', null, 'client');
            // max entries
            $mform->addElement('text', 'maxentries', get_string('entriesmax', 'dataform'));
            $mform->addRule('maxentries', null, 'numeric', null, 'client');

        // admin denies non-managers entries
        } else if ($CFG->dataform_maxentries == 0){ 
            $mform->addElement('hidden', 'admindeniesentries', 1);
            // required entries
            $mform->addElement('text', 'entriesrequired', get_string('entriesrequired', 'dataform'));
            $mform->disabledIf('entriesrequired', 'admindeniesentries', 'eq', 1);
            // required entries to view
            $mform->addElement('text', 'entriestoview', get_string('entriestoview', 'dataform'));
            $mform->disabledIf('entriestoview', 'admindeniesentries', 'eq', 1);
            // max entries
            $mform->addElement('text', 'maxentries', get_string('entriesmax', 'dataform'));
            $mform->disabledIf('maxentries', 'admindeniesentries', 'eq', 1);
        }

        // time limit to manage an entry
        $mform->addElement('text', 'timelimit', get_string('entrytimelimit', 'dataform'));
        $mform->setType('timelimit', PARAM_INT);
        $mform->setDefault('timelimit', '');
        $mform->addRule('timelimit', null, 'numeric', null, 'client');

        // approval
        $mform->addElement('selectyesno', 'approval', get_string('requireapproval', 'dataform'));

        // group entries
        $mform->addElement('selectyesno', 'grouped', get_string('groupentries', 'dataform'));
        $mform->disabledIf('grouped', 'groupmode', 'eq', 0);
        $mform->disabledIf('grouped', 'groupmode', 'eq', -1);
        
        // comments
        $mform->addElement('selectyesno', 'comments', get_string('comments', 'dataform'));

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

        if ($this->_df !== null and $viewmenu = $this->_df->get_views(null, true)) {
            $singleoptions = array(0 => get_string('choose')) + $viewmenu;
        } else {
            $singleoptions = array(0 => get_string('choose'));
        }                

        // edit view
        $mform->addElement('select', 'singleedit', get_string('viewforedit', 'dataform'), $singleoptions);
        //$mform->addHelpButton('singleedit', array('viewforedit', get_string('viewforedit', 'dataform'), 'dataform'));

        // single view
        $mform->addElement('select', 'singleview', get_string('viewformore', 'dataform'), $singleoptions);
        //$mform->addHelpButton('singleview', array('viewformore', get_string('viewformore', 'dataform'), 'dataform'));

    // common course elements
    //-------------------------------------------------------------------------------
        $this->standard_coursemodule_elements();

        // add separate participants group option
        //_elements has a numeric index, this code accesses the elements by name
        $groups = &$mform->getElement('groupmode');
        $groups->addOption(get_string('separateparticipants', 'dataform'), -1);

    // buttons
    //-------------------------------------------------------------------------------
    	$this->add_action_buttons();
    }

    /**
     *
     */
    function data_preprocessing(&$default_values){
        if (!empty($default_values->timeinterval)) {
            $default_values->timedue = $default_values->timeinterval * $default_values->intervalcount;
        }

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
            // reset grading
            if ($data->grade = 0) {
                $data->grademethod = 0;
            }
            // set locks
            $lockonapproval = !empty($data->lockonapproval) ? $data->lockonapproval : 0;
            $lockoncomments = !empty($data->lockoncomments) ? $data->lockoncomments : 0;
            $lockonratings = !empty($data->lockonratings) ? $data->lockonratings : 0;

            $data->locks = $lockonapproval | $lockoncomments | $lockonratings;
        }
        return $data;
    }

}
