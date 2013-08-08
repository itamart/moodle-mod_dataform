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
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

require_once('../../../config.php');
require_once('../mod_class.php');
require_once("$CFG->libdir/tablelib.php");

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);            // course module id
$urlparams->fid = optional_param('fid', 0 , PARAM_INT);          // update field id

// fields list actions
$urlparams->new        = optional_param('new', 0, PARAM_ALPHA);     // type of the new field
$urlparams->delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of fields to delete
$urlparams->duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of fields to duplicate
$urlparams->visible    = optional_param('visible', 0, PARAM_INT);     // id of field to hide/(show to owner)/show to all
$urlparams->editable    = optional_param('editable', 0, PARAM_INT);     // id of field to set editing

$urlparams->confirmed    = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('field/index', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/field/index.php', array('id' => $df->cm->id)));

// DATA PROCESSING
// Duplicate requested fields
if ($urlparams->duplicate and confirm_sesskey()) {
    $df->process_fields('duplicate', $urlparams->duplicate, $urlparams->confirmed);
// Delete requested fields
} else if ($urlparams->delete and confirm_sesskey()) {
    $df->process_fields('delete', $urlparams->delete, $urlparams->confirmed);
// Set field visibility
} else if ($urlparams->visible and confirm_sesskey()) {
    $df->process_fields('visible', $urlparams->visible, true);    // confirmed by default
// Set field editability
} else if ($urlparams->editable and confirm_sesskey()) {
    $df->process_fields('editable', $urlparams->editable, true);    // confirmed by default
}

// any notifications
if (!$fields = $df->get_user_defined_fields(true, flexible_table::get_sort_for_table('dataformfieldsindex'. $df->id()))) {
    $df->notifications['bad'][] = get_string('fieldnoneindataform','dataform');  // nothing in dataform
}

// print header
$df->print_header(array('tab' => 'fields', 'urlparams' => $urlparams));

// Display the field form jump list
$directories = get_list_of_plugins('mod/dataform/field/');
$menufield = array();

foreach ($directories as $directory){
    if ($directory[0] != '_') {
        // Get name from language files
        $menufield[$directory] = get_string('pluginname',"dataformfield_$directory");
    }
}
//sort in alphabetical order
asort($menufield);

$popupurl = new moodle_url('/mod/dataform/field/field_edit.php', array('d' => $df->id(), 'sesskey' => sesskey()));
$fieldselect = new single_select($popupurl, 'type', $menufield, null, array(''=>'choosedots'), 'fieldform');
$fieldselect->set_label(get_string('fieldadd','dataform'). '&nbsp;');
$br = html_writer::empty_tag('br');
echo html_writer::tag('div', $br. $OUTPUT->render($fieldselect). $br, array('class'=>'fieldadd mdl-align'));
//echo $OUTPUT->help_icon('fieldadd', 'dataform');

// if there are user fields print admin style list of them
if ($fields) {

    $editbaseurl = '/mod/dataform/field/field_edit.php';
    $actionbaseurl = '/mod/dataform/field/index.php';
    $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());

    $stredit = get_string('edit');
    $strduplicate =  get_string('duplicate');
    $strdelete = get_string('delete');
    $strhide = get_string('hide');
    $strshow = get_string('show');
    $strlock = get_string('lock', 'dataform');
    $strunlock = get_string('unlock', 'dataform');

    // The default value of the type attr of a button is submit, so set it to button so that
    // it doesn't submit the form
    $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'field\'&#44;this.checked)'));
    $multiactionurl = new moodle_url($actionbaseurl, $linkparams);
    $multidelete = html_writer::tag(
        'button', 
        $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
        array('type' => 'button', 'name' => 'multidelete', 'onclick' => 'bulk_action(\'field\'&#44; \''. $multiactionurl->out(false). '\'&#44; \'delete\')')
    );
    $multiduplicate = html_writer::tag(
        'button', 
        $OUTPUT->pix_icon('t/copy', get_string('multiduplicate', 'dataform')), 
        array('type' => 'button', 'name' => 'multiduplicate', 'onclick' => 'bulk_action(\'field\'&#44; \''. $multiactionurl->out(false). '\'&#44; \'duplicate\')')
    );

    // table headers
    $headers = array(
        'name' => get_string('name'),
        'type' => get_string('type', 'dataform'),
        'description' => get_string('description'),
        'visible' => get_string('visible'),
        'edits' => get_string('fieldeditable', 'dataform'),
        'edit' => $stredit,
        'duplicate' => $multiduplicate,
        'delete' => $multidelete,
        'selectallnone' => $selectallnone,
    );

    $table = new flexible_table('dataformfieldsindex'. $df->id());
    $table->define_baseurl(new moodle_url('/mod/dataform/field/index.php', array('d' => $df->id())));
    $table->define_columns(array_keys($headers));
    $table->define_headers(array_values($headers));

    // Column sorting
    $table->sortable(true);
    $table->no_sorting('description');
    $table->no_sorting('edit');
    $table->no_sorting('duplicate');
    $table->no_sorting('delete');
    $table->no_sorting('selectallnone');

    // Column styles
    $table->set_attribute('class', 'generaltable generalbox boxaligncenter boxwidthwide');
    $table->column_style('visible', 'text-align', 'center');
    $table->column_style('edits', 'text-align', 'center');
    $table->column_style('edit', 'text-align', 'center');
    $table->column_style('duplicate', 'text-align', 'center');
    $table->column_style('delete', 'text-align', 'center');

    $table->setup();

    foreach ($fields as $fieldid => $field) {
        // Skip internal fields
        if ($field::is_internal()) {
            continue;
        }
        
        $fieldname = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('fid' => $fieldid)), $field->name());
        $fieldedit = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('fid' => $fieldid)), $OUTPUT->pix_icon('t/edit', $stredit));
        $fieldduplicate = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('duplicate' => $fieldid)), $OUTPUT->pix_icon('t/copy', $strduplicate));
        $fielddelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $fieldid)), $OUTPUT->pix_icon('t/delete', $strdelete));
        $fieldselector = html_writer::checkbox("fieldselector", $fieldid, false);

        $fieldtype = $field->image(). '&nbsp;'. $field->typename();
        $fielddescription = shorten_text($field->field->description, 30);

        // visible
        if ($visible = $field->field->visible) {
            $visibleicon = $OUTPUT->pix_icon('t/hide', $strhide);
            $visibleicon = ($visible == 1 ? "($visibleicon)" : $visibleicon);
        } else {
           $visibleicon = $OUTPUT->pix_icon('t/show', $strshow);
        }
        $fieldvisible = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('visible' => $fieldid)), $visibleicon);

        // Editable
        if ($editable = $field->field->edits) {
            $editableicon = $OUTPUT->pix_icon('t/lock', $strlock);
        } else {
           $editableicon = $OUTPUT->pix_icon('t/unlock', $strunlock);
        }
        $fieldeditable = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('editable' => $fieldid)), $editableicon);

        $table->add_data(array(
            $fieldname,
            $fieldtype,
            $fielddescription,
            $fieldvisible,
            $fieldeditable,
            $fieldedit,
            $fieldduplicate,
            $fielddelete,
            $fieldselector
        ));
    }

    $table->finish_output();
}

$df->print_footer();
