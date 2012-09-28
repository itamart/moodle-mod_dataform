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
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

require_once('../../config.php');

$urlparams = new object;
$urlparams->d = optional_param('d', 0, PARAM_INT);   // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);   // course module id
$urlparams->jsedit = optional_param('jsedit', 0, PARAM_BOOL);   // edit mode

if ($urlparams->jsedit) {
    require_once('mod_class.php');
    require_once $CFG->libdir.'/formslib.php';

    class mod_dataform_js_form extends moodleform {

        function definition() {
            global $CFG, $COURSE;

            $mform = &$this->_form;

            // buttons
            //-------------------------------------------------------------------------------
            $this->add_action_buttons(true);

            // js
            //-------------------------------------------------------------------------------
            $mform->addElement('header', 'generalhdr', get_string('headerjs', 'dataform'));

            // includes
            $attributes = array('wrap' => 'virtual', 'rows' => 5, 'cols' => 60);
            $mform->addElement('textarea', 'jsincludes', get_string('jsincludes', 'dataform'), $attributes);

            // code
            $attributes = array('wrap' => 'virtual', 'rows' => 15, 'cols' => 60);
            $mform->addElement('textarea', 'js', get_string('jscode', 'dataform'), $attributes);

            // uploads
            $options = array(
                'subdirs' => 0,
                'maxbytes' => $COURSE->maxbytes,
                'maxfiles' => 10,
                'accepted_types' => array('*.js')
            );
            $mform->addElement('filemanager', 'jsupload', get_string('jsupload', 'dataform'), null, $options);
            
            // buttons
            //-------------------------------------------------------------------------------
            $this->add_action_buttons(true);
        }

    }

    // Set a dataform object
    $df = new dataform($urlparams->d, $urlparams->id);
    require_capability('mod/dataform:managetemplates', $df->context);

    $df->set_page('js', array('urlparams' => $urlparams));

    // activate navigation node
    navigation_node::override_active_url(new moodle_url('/mod/dataform/js.php', array('id' => $df->cm->id, 'jsedit' => 1)));

    $mform = new mod_dataform_js_form(new moodle_url('/mod/dataform/js.php', array('d' => $df->id(), 'jsedit' => 1))); 

    if ($mform->is_cancelled()) {
    
    } else if ($data = $mform->get_data()){
        $rec = new object();
        $rec->js = $data->js;
        $rec->jsincludes = $data->jsincludes;        
        $df->update($rec, get_string('jssaved', 'dataform'));
        
        // add uploaded files
        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($usercontext->id, 'user', 'draft', $data->jsupload, 'sortorder', false)) {
            $filerec = new object;
            $filerec->contextid = $df->context->id;
            $filerec->component = 'mod_dataform';
            $filerec->filearea = 'js';
            $filerec->filepath = '/';
            
            foreach ($files as $file) {
                $filerec->filename = $file->get_filename();
                $fs->create_file_from_storedfile($filerec, $file);
            }
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $data->jsupload);
        }
        
        add_to_log($df->course->id, 'dataform', 'js saved', 'js.php?id='. $df->cm->id. '&amp;d='. $df->id(), $df->id(), $df->cm->id);
    }

    $df->print_header(array('tab' => 'js', 'urlparams' => $urlparams));

    $options = array(
        'subdirs' => 0,
        'maxbytes' => $COURSE->maxbytes,
        'maxfiles' => 10,
    );
    $draftitemid = file_get_submitted_draft_itemid('jsupload');
    file_prepare_draft_area($draftitemid, $df->context->id, 'mod_dataform', 'js', 0, $options);
    $df->data->jsupload = $draftitemid;

    $mform->set_data($df->data);
    $mform->display();
    $df->print_footer();

} else {

    defined('NO_MOODLE_COOKIES') or define('NO_MOODLE_COOKIES', true); // session not used here

    $lifetime  = 600;                                   // Seconds to cache this stylesheet
    
    $PAGE->set_url('/mod/dataform/js.php', array('d'=>$urlparams->d));

    if ($jsdata = $DB->get_field('dataform', 'js', array('id' => $urlparams->d))) {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
        header('Expires: ' . gmdate("D, d M Y H:i:s", time() + $lifetime) . ' GMT');
        header('Cache-control: max_age = '. $lifetime);
        header('Pragma: ');
        header('Content-type: text/javascript');  // Correct MIME type

        echo $jsdata;
    }
}
