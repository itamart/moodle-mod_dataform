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
 * certain copyrights on the Database module may obtain, including:
 * @copyright 2005 Martin Dougiamas http://dougiamas.com
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

$urlparams = new object();
$urlparams->d = required_param('d', PARAM_INT);   // dataform id
$urlparams->jsedit = optional_param('jsedit', 0, PARAM_BOOL);   // edit mode

if ($urlparams->jsedit) {
    require_once('mod_class.php');
    require_once $CFG->libdir.'/formslib.php';

    class mod_dataform_js_form extends moodleform {

        function definition() {
            global $CFG;

            $mform = &$this->_form;
            $d = $this->_customdata['d'];

            // hidden optional params
            $mform->addElement('hidden', 'd', $d);
            $mform->setType('d', PARAM_INT);

            $mform->addElement('hidden', 'jsedit', 1);
            $mform->setType('jsedit', PARAM_BOOL);

            // buttons
            //-------------------------------------------------------------------------------
            $this->add_action_buttons(true);

            // js
            //-------------------------------------------------------------------------------
            $mform->addElement('header', 'generalhdr', get_string('headerjs', 'dataform'));

            $attributes = array('wrap' => 'virtual', 'rows' => 15, 'cols' => 80);
            $mform->addElement('textarea', 'js', null, $attributes);

            // buttons
            //-------------------------------------------------------------------------------
            $this->add_action_buttons(true);
        }

    }

    // Set a dataform object
    $df = new dataform($urlparams->d);

    require_capability('mod/dataform:managetemplates', $df->context);

    $df->set_page('js', array('urlparams' => $urlparams));

    $mform = new mod_dataform_js_form(null, array('d' => $df->id())); 
    if ($mform->is_cancelled()) {
    
    } else if ($data = $mform->get_data()){
        $rec = new object();
        $rec->js = $data->js;
        
        $df->update($rec, get_string('jssaved', 'dataform'));
        
        add_to_log($df->course->id, 'dataform', 'js saved', 'js.php?id='. $df->cm->id. '&amp;d='. $df->id(), $df->id(), $df->cm->id);
    }

    $df->print_header(array('tab' => 'js', 'urlparams' => $urlparams));
    $mform->get_data($df->data);
    $mform->display();
    $df->print_footer();

} else {

    defined('NO_MOODLE_COOKIES') or define('NO_MOODLE_COOKIES', true); // session not used here

    $lifetime  = 600;                                   // Seconds to cache this stylesheet
    
    $PAGE->set_url('/mod/dataform/js.php', array('d'=>$urlparams->d));

    if ($data = $DB->get_record('dataform', array('id' => $urlparams->d))) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $lifetime) . ' GMT');
        header('Cache-control: max_age = '. $lifetime);
        header('Pragma: ');
        header('Content-type: text/javascript');  // Correct MIME type

        echo $data->js;
    }
}
