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
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once $CFG->libdir.'/formslib.php';

/**
 *
 */
class dataformview_entries_form extends moodleform {

    function definition() {

        $view = $this->_customdata['view'];
        $mform =& $this->_form;

        // buttons
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();

        // entries
        //-------------------------------------------------------------------------------
        $view->definition_to_form($mform);

        // buttons again
        //-------------------------------------------------------------------------------
        $this->add_action_buttons();
    }

    /**
     *
     */
    function add_action_buttons($cancel = true, $submit = null) {
        $mform = &$this->_form;
        
        static $i = 0;
        $i++;

        $arr = array();
        $submitlabel = $submit ? $submit : get_string('savechanges');
        $arr[] = &$mform->createElement('submit', "submitbutton$i", $submitlabel);
        if ($this->add_action_save_continue()) {
            $arr[] = &$mform->createElement('submit', "submitreturnbutton$i", get_string('savecontinue', 'dataform'));
        }
        if ($cancel) {
            $arr[] = &$mform->createElement('cancel', "cancel$i");
        }
        $mform->addGroup($arr, 'buttonarr', null, ' ', false);
    }

    /**
     *
     */
    protected function add_action_save_continue() {
        return false;
    }

    /**
     *
     */
    public function html() {
        return $this->_form->toHtml();
    }

    /**
     *
     */
    function validation($data, $files) {
        global $CFG;

        if (!$errors = parent::validation($data, $files)) {

            $errors = array();
            
            // field validations
            $view = $this->_customdata['view'];
            $patterns = $view->get__patterns('field');
            $fields = $view->get_view_fields();
            $entryids = explode(',', $this->_customdata['update']);

            foreach ($entryids as $eid) {
                // validate all fields for this entry
                foreach ($fields as $fid => $field) {
                    // captcha check
                    if ($field->type() == 'captcha') {
                        if (!empty($CFG->recaptchapublickey) and !empty($CFG->recaptchaprivatekey)) {
                            $mform = $this->_form;
                            $values = $mform->_submitValues;
                            if (!empty($values['recaptcha_challenge_field'])) {
                                $formfield = "field_{$fid}_$eid";
                                $captchaelement = $mform->getElement($formfield); 
                                $challenge = $values['recaptcha_challenge_field'];
                                $response = $values['recaptcha_response_field'];
                                if (true !== ($result = $captchaelement->verify($challenge, $response))) {
                                    $errors[$formfield] = $result;
                                }
                            } else {
                                $errors[$formfield] = get_string('missingrecaptchachallengefield');
                            }
                        }                
                           
                    } else if ($err = $field->validate($eid, $patterns[$fid], (object) $data)) {
                        $errors = array_merge($errors, $err);
                    }
                }
            }
        }

        return $errors;
    }
}
