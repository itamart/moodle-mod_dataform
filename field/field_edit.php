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

require_once('../../../config.php');
require_once("$CFG->dirroot/mod/dataform/mod_class.php");

$urlparams = new object();
$urlparams->d          = required_param('d', PARAM_INT);    // dataform ID

$urlparams->type       = optional_param('type','' ,PARAM_ALPHA);   // type of a field to edit
$urlparams->id        = optional_param('id',0 ,PARAM_INT);       // field id to edit

// Set a dataform object
$df = new dataform($urlparams->d);

require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('field/field_edit', array('urlparams' => $urlparams));

if ($urlparams->id) {
    $field = $df->get_field_from_id($urlparams->id, true); // force get
} else if ($urlparams->type) {
    $field = $df->get_field($urlparams->type);
}

$mform = $field->get_form();

if ($mform->is_cancelled()){
    redirect(new moodle_url('/mod/dataform/fields.php', array('d' => $df->id())));

// no submit buttons: reset to default, switch editor    
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
        add_to_log($df->course->id, 'dataform', 'fields update', 'fields.php?d='. $df->id(). '&amp;id=', $urlparams->id, $df->cm->id);
    }

    if ($data->submitbutton == get_string('savecontinue', 'dataform')) {
        // go back to form
    } else {
        redirect(new moodle_url('/mod/dataform/fields.php', array('d' => $df->id())));
    }
}

// print header
$df->print_header(array('tab' => 'fields', 'nonotifications' => true, 'urlparams' => $urlparams));

// display form
$mform = $field->get_form();
$mform->set_data($field->to_form());
$mform->display();

$df->print_footer();
