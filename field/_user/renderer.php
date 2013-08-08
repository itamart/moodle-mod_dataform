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
 * @subpackage _user
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

require_once("$CFG->dirroot/mod/dataform/field/renderer.php");

/**
 *
 */
class dataformfield__user_renderer extends dataformfield_renderer {

    /**
     * 
     */
    protected function replacements(array $tags = null, $entry = null, array $options = null) {
        $field = $this->_field;
        $fieldname = $field->get('internalname');
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        // no edit mode
        $replacements = array();

        // edit author name
        if ($fieldname == 'name') {
            // two tags are possible
            foreach ($tags as $tag) {
                if ($tag == "##author:edit##" and $edit and has_capability('mod/dataform:manageentries', $field->df()->context)) {
                    $replacements[$tag] = array('', array(array($this,'display_edit'), array($entry)));
                } else {
                    $replacements[$tag] = array('html', $this->{"display_$fieldname"}($entry));
                }
            }

        // if not picture there is only one possible tag so no check
        } else if ($fieldname != 'picture') {
            $replacements["##author:{$fieldname}##"] = array('html', $this->{"display_$fieldname"}($entry));

        // for picture switch on $tags
        } else {
            foreach ($tags as $tag) {
                if ($tag == "##author:picturelarge##") {
                    $replacements[$tag] = array('html', $this->{"display_$fieldname"}($entry, true));
                } else {
                    $replacements[$tag] = array('html', $this->{"display_$fieldname"}($entry));
                }                
            }    
        }

        return $replacements;
    }

    /**
     * 
     */
    public function display_edit(&$mform, $entry, array $options = null) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            $entry->firstname =  $USER->firstname;
            $entry->lastname =  $USER->lastname;
            $entry->userid =  $USER->id;
        }

        $field = $this->_field;
        $fieldid = $field->id();
        $entryid = $entry->id;
        $fieldname = "field_{$fieldid}_{$entryid}";

        $selected = $entry->userid;

        static $usersmenu = null;
        if (is_null($usersmenu)) {
            $users = $field->df()->get_gradebook_users();
            // add a supervisor's id
            if (!in_array($entry->userid, array_keys($users))) {
                $user = new object;
                $user->id = $entry->userid;
                $user->firstname = $entry->firstname;
                $user->lastname = $entry->lastname;
                $users[$entry->userid] = $user;
            }           
        }

        $usermenu = array();
        foreach ($users as $userid => $user) {
            $usermenu[$userid] = $user->firstname. ' '. $user->lastname;
        }
        $mform->addElement('select', $fieldname, null, $usermenu);
        $mform->setDefault($fieldname, $selected);
    }

   /**
     * 
     */
    public function display_name($entry) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            $entry->firstname =  $USER->firstname;
            $entry->lastname =  $USER->lastname;
            $entry->userid =  $USER->id;
        }

        $df = $this->_field->df();
        return html_writer::link(new moodle_url('/user/view.php', array('id' => $entry->userid, 'course' => $df->course->id)), fullname($entry));
    }

    /**
     * 
     */
    public function display_firstname($entry) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            return $USER->firstname;
        } else {
            return $entry->firstname;
        }
    }

    /**
     * 
     */
    public function display_lastname($entry) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            return $USER->lastname;
        } else {
            return $entry->lastname;
        }
    }

    /**
     * 
     */
    public function display_username($entry) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            return $USER->username;
        } else {
            return $entry->username;
        }
    }

    /**
     * 
     */
    public function display_id($entry) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            return $USER->id;
        } else {
            return $entry->userid;
        }
    }

    /**
     * 
     */
    public function display_idnumber($entry) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            return $USER->idnumber;
        } else {
            return $entry->idnumber;
        }
    }

    /**
     * 
     */
    public function display_picture($entry, $large = false) {
        global $USER, $OUTPUT;
        
        if ($entry->id < 0) { // new entry
            $user = $USER;
        } else {
            $user = new object();
            foreach (explode(',', user_picture::fields()) as $userfield) {
                if ($userfield == 'id') {
                    $user->id = $entry->uid;
                } else {
                    $user->{$userfield} = $entry->{$userfield};
                }
            }
        }

        $pictureparams = array('courseid' => $this->_field->df()->course->id);
        if ($large) {
            $pictureparams['size'] = 100;
        }
        return $OUTPUT->user_picture($user, $pictureparams);
    }

    /**
     * 
     */
    public function display_email($entry) {
        global $USER;
        
        if ($entry->id < 0) { // new entry
            return $USER->email;
        } else {
            return $entry->email;
        }
    }

    /**
     * Array of patterns this field supports 
     */
    protected function patterns() {
        $fieldinternalname = $this->_field->get('internalname');
        $cat = get_string('authorinfo', 'dataform');

        $patterns = array();
        $patterns["##author:{$fieldinternalname}##"] = array(true, $cat);
        // for user name add edit tag
        if ($fieldinternalname == 'name') {
            $patterns["##author:edit##"] = array(false, $cat);
        }
        // for user picture add the large picture
        if ($fieldinternalname == 'picture') {
            $patterns["##author:picturelarge##"] = array(true, $cat);
        }

        return $patterns; 
    }
}
