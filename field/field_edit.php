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
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once("$CFG->dirroot/mod/dataform/mod_class.php");

$urlparams = new object();
$urlparams->d          = required_param('d', PARAM_INT);    // dataform ID

$urlparams->type       = optional_param('type','' ,PARAM_ALPHA);   // type of a field to edit
$urlparams->fid        = optional_param('fid',0 ,PARAM_INT);       // field id to edit

// Set a dataform object
$df = new dataform($urlparams->d);

$df->set_page('field/field_edit', array('urlparams' => $urlparams));

require_capability('mod/dataform:managetemplates', $df->context);

if ($urlparams->fid) {
    $field = $df->get_field_from_id($urlparams->fid, true); // force get
} else if ($urlparams->type) {
    $field = $df->get_field($urlparams->type);
}

$mform = $field->get_form();

if ($mform->is_cancelled()){
    redirect(new moodle_url('/mod/dataform/field/index.php', array('d' => $df->id())));

// no submit buttons    
} else if ($mform->no_submit_button_pressed()) {

// process validated    
} else if ($data = $mform->get_data()) { 

   // add new field
    if (!$field->id()) {
        $field->insert_field($data);
        add_to_log($df->course->id, 'dataform', 'fields add', 'field_edit.php?d='. $df->id(), '', $df->cm->id);

    // update field
    } else {
        $data->id = $field->id();
        $field->update_field($data);
        add_to_log($df->course->id, 'dataform', 'fields update', 'field/index.php?d='. $df->id(). '&amp;id=', $urlparams->fid, $df->cm->id);
    }

    if ($data->submitbutton != get_string('savecontinue', 'dataform')) {
        redirect(new moodle_url('/mod/dataform/field/index.php', array('d' => $df->id())));
    }
    
    // continue to edit so refresh the form
    $mform = $field->get_form();
}

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/field/index.php', array('id' => $df->cm->id)));

// print header
$df->print_header(array('tab' => 'fields', 'nonotifications' => true, 'urlparams' => $urlparams));

$formheading = $field->id() ? get_string('fieldedit', 'dataform', $field->name()) : get_string('fieldnew', 'dataform', $field->typename());
echo html_writer::tag('h2', format_string($formheading), array('class' => 'mdl-align'));

// display form
$mform->set_data($field->to_form());
$mform->display();

$df->print_footer();
