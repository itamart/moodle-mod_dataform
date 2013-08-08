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
 */
require_once('../../config.php');
require_once('mod_class.php');

$urlparams = new object();

$urlparams->d          = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id          = optional_param('id', 0, PARAM_INT);             // module id
$urlparams->vid         = optional_param('vid', 0, PARAM_INT);       // view id
$urlparams->vedit         = optional_param('vedit', 0, PARAM_INT); // view id to edit
$urlparams->import         = optional_param('import', 0, PARAM_INT); // import

// views list actions
$urlparams->reset     = optional_param('reset', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to delete
$urlparams->delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to delete
$urlparams->duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to duplicate

$urlparams->confirmed    = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);

// require capability
if (!has_capability('mod/dataform:manageentries', $df->context)
            or !has_capability('mod/dataform:managetemplates', $df->context)) {
    throw new required_capability_exception($df->context, 'mod/dataform:manageentries', 'nopermissions', '');
}

$df->set_page('import', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/import.php', array('id' => $df->cm->id)));

// DATA PROCESSING
// import
if ($urlparams->vid and confirm_sesskey()) {
    $view = $df->get_view_from_id($urlparams->vid);

    // process import
    if ($urlparams->import and confirm_sesskey()) {
        if ($view->process_data()) {
            redirect(new moodle_url('/mod/dataform/view.php', array('d' => $urlparams->d)));
        } else {
            // proceed to display list of import views
        }

    // or display form
    } else {
        // print header
        $df->print_header(array('tab' => 'import', 'urlparams' => $urlparams));
        $mform = $view->get_import_form();
        $mform->set_data(null);
        $mform->display();
        $df->print_footer();
        die;
    }
}

// view actions
if ($urlparams->duplicate and confirm_sesskey()) {  // Duplicate any requested views
    $df->process_views('duplicate', $urlparams->duplicate, true);

} else if ($urlparams->reset and confirm_sesskey()) { // Reset to default any requested views
    $df->process_views('reset', $urlparams->reset, true);

} else if ($urlparams->delete and confirm_sesskey()) { // Delete any requested views
    $df->process_views('delete', $urlparams->delete, true);
}

// any notifications?
$df->notifications['bad']['defaultview'] = '';
$df->notifications['bad']['getstartedviews'] = '';
if (!$views = $df->get_views_by_type('import', true)) {
    $df->notifications['bad'][] = get_string('importnoneindataform','dataform');  // nothing in database
}

// print header
$df->print_header(array('tab' => 'import', 'urlparams' => $urlparams));

// print add import link
$addimporturl = new moodle_url('/mod/dataform/view/view_edit.php',
                            array('d' => $df->id(), 'type' => 'import', 'sesskey' => sesskey()));
$addimportlink = html_writer::link($addimporturl, get_string('importadd','dataform'));
$br = html_writer::empty_tag('br'); 
echo html_writer::tag('div', $addimportlink. $br. $br, array('class'=>'mdl-align'));

// if there are import views print admin style list of them
if ($views) {

    $viewbaseurl = '/mod/dataform/import.php';
    $editbaseurl = '/mod/dataform/view/view_edit.php';
    $actionbaseurl = '/mod/dataform/import.php';
    $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());
                        
    // table headings
    $strviews = get_string('views', 'dataform');
    $strdescription = get_string('description');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $strreset =  get_string('reset');
    $strduplicate =  get_string('duplicate');

    $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'view\'&#44;this.checked)'));
    $multidelete = html_writer::tag('button', 
                                $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
                                array('name' => 'multidelete',
                                        'onclick' => 'bulk_action(\'view\'&#44; \''. htmlspecialchars_decode(new moodle_url($actionbaseurl, $linkparams)). '\'&#44; \'delete\')'));
    
    $table = new html_table();
    $table->head = array($strviews,
                        $strdescription,
                        $stredit,
                        $strduplicate,
                        $strreset,
                        $multidelete,
                        $selectallnone);
    $table->align = array('left',
                        'left',
                        'center',
                        'center',
                        'center',
                        'center',
                        'center');
    $table->wrap = array(false,
                        false,
                        false,
                        false,
                        false,
                        false,
                        false);
    $table->attributes['align'] = 'center';
    
    foreach ($views as $viewid => $view) {
        
        $viewname = html_writer::link(new moodle_url($viewbaseurl, $linkparams + array('vid' => $viewid)), $view->name());
        $viewdescription = shorten_text($view->view->description, 30);
        $viewedit = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('vedit' => $viewid)),
                        $OUTPUT->pix_icon('t/edit', $stredit));
        $viewduplicate = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('duplicate' => $viewid)),
                        $OUTPUT->pix_icon('t/copy', $strduplicate));
        $viewreset = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('reset' => $viewid)),
                        $OUTPUT->pix_icon('t/reload', $strreset));
        $viewdelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $viewid)),
                        $OUTPUT->pix_icon('t/delete', $strdelete));
        $viewselector = html_writer::checkbox('viewselector', $viewid, false);

        $table->data[] = array(
            $viewname,
            $viewdescription,
            $viewedit,
            $viewduplicate,
            $viewreset,
            $viewdelete,
            $viewselector
       );
    }
    echo html_writer::table($table);
}

$df->print_footer();

