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
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */
defined('MOODLE_INTERNAL') or die;

require_once ("$CFG->dirroot/course/moodleform_mod.php");
require_once($CFG->dirroot. '/mod/dataform/mod_class.php');

class mod_dataform_mod_form extends moodleform_mod {
    
    protected $_df = null; 

    function definition() {
        global $CFG;
        
        if ($cmid = optional_param('update', 0, PARAM_INT)) {
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
        $mform->addElement('header', 'gradinghdr', get_string('rating', 'rating'));

        // entry rating
        $mform->addElement('modgrade', 'grade', get_string('grade'));
        $mform->setDefault('grade', 0);

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
        
        // entry rating
        $mform->addElement('modgrade', 'rating', get_string('rating', 'dataform'));
        $mform->setDefault('rating', 0);

        // Notifications
        //-------------------------------------------------------------------------------
        // Types
        $mform->addElement('header', 'notificationshdr', get_string('notifications'));
        $grp=array();
        foreach (dataform::get_notification_types() as $type => $key) {
            $grp[] = &$mform->createElement('advcheckbox', $type, null, get_string("messageprovider:dataform_$type", 'dataform'), null, array(0,$key));
        }
        $mform->addGroup($grp, 'notificationgrp', get_string('notificationenable', 'dataform'), html_writer::empty_tag('br'), false);
        // Format
        $options = array(
            FORMAT_HTML => get_string('formathtml'),
            FORMAT_HTML => get_string('formatplain'),
        );
        $mform->addElement('select', 'notificationformat', get_string('format'), $options);
        
        // entry settings
        //-------------------------------------------------------------------------------
        $mform->addElement('header', 'entrysettingshdr', get_string('entrysettings', 'dataform'));

        if ($CFG->dataform_maxentries > 0) { 
            // Admin limit, select from dropdown
            $maxoptions = (array_combine(range(0, $CFG->dataform_maxentries),range(0, $CFG->dataform_maxentries)));

            // required entries
            $mform->addElement('select', 'entriesrequired', get_string('entriesrequired', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // required entries to view
            $mform->addElement('select', 'entriestoview', get_string('entriestoview', 'dataform'), array(0=>get_string('none')) + $maxoptions);
            // max entries
            $mform->addElement('select', 'maxentries', get_string('entriesmax', 'dataform'), $maxoptions);
            $mform->setDefault('maxentries', $CFG->dataform_maxentries);
        
        } else {
            // No limit or no entries
            $admindeniesentries = (int) !$CFG->dataform_maxentries; 
            $mform->addElement('hidden', 'admindeniesentries', $admindeniesentries);
            $mform->setType('admindeniesentries', PARAM_INT);

            // required entries
            $mform->addElement('text', 'entriesrequired', get_string('entriesrequired', 'dataform'));
            $mform->setDefault('entriesrequired', 0);
            $mform->addRule('entriesrequired', null, 'numeric', null, 'client');
            $mform->setType('entriesrequired', PARAM_INT);
            $mform->disabledIf('entriesrequired', 'admindeniesentries', 'eq', 1);

            // required entries to view
            $mform->addElement('text', 'entriestoview', get_string('entriestoview', 'dataform'));
            $mform->setDefault('entriestoview', 0);
            $mform->addRule('entriestoview', null, 'numeric', null, 'client');
            $mform->setType('entriestoview', PARAM_INT);
            $mform->disabledIf('entriestoview', 'admindeniesentries', 'eq', 1);

            // max entries
            $mform->addElement('text', 'maxentries', get_string('entriesmax', 'dataform'));
            $mform->setDefault('maxentries', -1);
            $mform->addRule('maxentries', null, 'numeric', null, 'client');
            $mform->setType('maxentries', PARAM_INT);
            $mform->disabledIf('maxentries', 'admindeniesentries', 'eq', 1);

        }

        // anonymous entries
        if ($CFG->dataform_anonymous) { 
            $mform->addElement('selectyesno', 'anonymous', get_string('entriesanonymous', 'dataform'));
            $mform->setDefault('anonymous', 0);
        }
        
        // group entries
        $mform->addElement('selectyesno', 'grouped', get_string('groupentries', 'dataform'));
        $mform->disabledIf('grouped', 'groupmode', 'eq', 0);
        $mform->disabledIf('grouped', 'groupmode', 'eq', -1);
        
        // time limit to manage an entry
        $mform->addElement('text', 'timelimit', get_string('entrytimelimit', 'dataform'));
        $mform->setType('timelimit', PARAM_INT);
        $mform->setDefault('timelimit', -1);
        $mform->addRule('timelimit', null, 'numeric', null, 'client');

        $mform->addElement('selectyesno', 'approval', get_string('requireapproval', 'dataform'));
        
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
    function data_preprocessing(&$data){
        if (!empty($data->notification)) {
            $notification = $data->notification;
            foreach (dataform::get_notification_types() as $type => $key) {
                $data->$type = $notification & $key;
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
            if (!empty($data->timeinterval)) {
                $data->timedue = $data->timeavailable + ($data->timeinterval * $data->intervalcount);
            }
            // Set notification
            $data->notification = 0;
            foreach (dataform::get_notification_types() as $type => $key) {
                if (!empty($data->$type)) {
                    $data->notification = $data->notification | $key;
                }
            }
        }
        return $data;
    }

}
