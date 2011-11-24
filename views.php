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

require_once('../../config.php');
require_once('mod_class.php');

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);           // course module id
$urlparams->vedit = optional_param('vedit', 0, PARAM_INT);     // view id to edit

// views list actions
$urlparams->default    = optional_param('default', 0, PARAM_INT);  // id of view to default
$urlparams->visible    = optional_param('visible', 0, PARAM_SEQUENCE);     // ids (comma delimited) of views to hide/(show)/show
$urlparams->hide       = optional_param('hide', 0, PARAM_SEQUENCE);     // ids (comma delimited) of views to hide
$urlparams->reset     = optional_param('reset', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to delete
$urlparams->delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to delete
$urlparams->duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to duplicate
// TODO
$urlparams->setfilter     = optional_param('setfilter', 0, PARAM_INT);  // id of view to filter

$urlparams->confirmed    = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('views', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/views.php', array('id' => $df->cm->id)));

// DATA PROCESSING
if ($urlparams->duplicate and confirm_sesskey()) {  // Duplicate any requested views
    $df->process_views('duplicate', $urlparams->duplicate, $urlparams->confirmed);

} else if ($urlparams->reset and confirm_sesskey()) { // Reset to default any requested views
    $df->process_views('reset', $urlparams->reset, true);

} else if ($urlparams->delete and confirm_sesskey()) { // Delete any requested views
    $df->process_views('delete', $urlparams->delete, $urlparams->confirmed);

} else if ($urlparams->visible and confirm_sesskey()) {    // set view's visibility
    $df->process_views('visible', $urlparams->visible, true);    // confirmed by default

} else if ($urlparams->hide and confirm_sesskey()) {  // hide any requested views
    $df->process_views('hide', $urlparams->hide, true);

} else if ($urlparams->default and confirm_sesskey()) {  // set view to default
    $df->process_views('default', $urlparams->default, true);    // confirmed by default

} else if ($urlparams->setfilter and confirm_sesskey()) {  // set view to default
    $df->process_views('filter', $urlparams->setfilter, true);    // confirmed by default

}

// any notifications?
$df->notifications['bad']['defaultview'] = '';
if (!$views = $df->get_views(null, false, true)) {
    $df->notifications['bad']['getstartedviews'] = get_string('viewnoneindataform','dataform');  // nothing in database
} else if (empty($df->data->defaultview)) {
    $df->notifications['bad']['defaultview'] = get_string('viewnodefault','dataform', '');
}

// print header
$df->print_header(array('tab' => 'views', 'urlparams' => $urlparams));

// Display the view form jump list
$directories = get_list_of_plugins('mod/dataform/view/');
$menuview = array();

foreach ($directories as $directory){
    if ($directory[0] != '_') {
        $menuview[$directory] = get_string('pluginname',"dataformview_$directory");    //get from language files
    }
}
asort($menuview);    //sort in alphabetical order

$br = html_writer::empty_tag('br');
$popupurl = $CFG->wwwroot.'/mod/dataform/view/view_edit.php?d='. $df->id().'&amp;sesskey='. sesskey();
$viewselect = new single_select(new moodle_url($popupurl), 'type', $menuview, null, array(''=>'choosedots'), 'viewform');
$viewselect->set_label(get_string('viewadd','dataform'). '&nbsp;');
echo html_writer::tag('div', $br. $OUTPUT->render($viewselect). $br, array('class'=>'fieldadd mdl-align'));

// if there are views print admin style list of them
if ($views) {

    $viewbaseurl = '/mod/dataform/view.php';
    $editbaseurl = '/mod/dataform/view/view_edit.php';
    $actionbaseurl = '/mod/dataform/views.php';
    $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());
                        
    /// table headings
    $strviews = get_string('views', 'dataform');
    $strtype = get_string('type', 'dataform');
    $strdescription = get_string('description');
    $strvisible = get_string('visible');
    $strdefault = get_string('default');
    $strfilter = get_string('filter', 'dataform');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $strduplicate =  get_string('duplicate');

    $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'view\'&#44;this.checked)'));
    $multidelete = html_writer::tag('button', 
                                $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
                                array('name' => 'multidelete',
                                        'onclick' => 'bulk_action(\'view\'&#44; \''. htmlspecialchars_decode(new moodle_url($actionbaseurl, $linkparams)). '\'&#44; \'delete\')'));

    $strhide = get_string('hide');
    $strshow = get_string('show');
    $strreset =  get_string('reset');
    
    $filtersmenu = $df->get_filters(null, true);
        
    $table = new html_table();
    $table->head = array($strviews, $strtype, $strdescription, $strvisible, $strdefault, $strfilter, $stredit, $strduplicate, $strreset, $multidelete, $selectallnone);
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false, false, false, false);
    $table->attributes['align'] = 'center';
    
    foreach ($views as $viewid => $view) {
        
        $viewname = html_writer::link(new moodle_url($viewbaseurl, array('d' => $df->id(), 'view' => $viewid)), $view->name());
        $viewtype = $view->typename();
        $viewdescription = shorten_text($view->view->description, 30);
        $viewedit = html_writer::link(new moodle_url($editbaseurl, $linkparams + array('vedit' => $viewid)),
                        $OUTPUT->pix_icon('t/edit', $stredit));
        $viewduplicate = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('duplicate' => $viewid)),
                        $OUTPUT->pix_icon('t/copy', $strduplicate));
        $viewreset = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('reset' => $viewid)),
                        $OUTPUT->pix_icon('t/reload', $strreset));
        $viewdelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $viewid)),
                        $OUTPUT->pix_icon('t/delete', $strdelete));
        $viewselector = html_writer::checkbox("viewselector", $viewid, false);

        // visible
        if ($visibile = $view->view->visible) {
            $visibleicon = $OUTPUT->pix_icon('t/hide', $strhide);
            $visibleicon = $visibile == 1 ? "($visibleicon)" : $visibleicon;
        } else {
           $visibleicon = $OUTPUT->pix_icon('t/show', $strshow);
        }
        $visible = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('visible' => $viewid)), $visibleicon);

        // default view
        if ($viewid == $df->data->defaultview) {
            $defaultview = $OUTPUT->pix_icon('t/clear', $strdefault);
        } else {
            $defaultview = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('default' => $viewid)), get_string('choose'));
        }
        
        // view filter
        // TODO
        if ($view->filter() !== false) {
            if (!empty($filtersmenu)) {
                if ($view->filter() and !in_array($view->filter(), array_keys($filtersmenu))) {
                    $viewfilter = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array(setfilter => $viewid, 'fid' => 0)),
                                $OUTPUT->pix_icon('i/risk_xss', $strreset));
                } else {
                    $viewfilter = html_writer::select($filtersmenu, '', $view->filter(), array('' => 'choosedots'), array('onchange' => 'location.href=\'views.php?d='. $df->id(). '&amp;setfilter='. $viewid. '&amp;fid=\'+this.selectedIndex+\'&amp;sesskey='.sesskey().'\''));
                }
            } else {
                $viewfilter = get_string('filtersnonedefined', 'dataform');
            }
        } else {
            $viewfilter = '-';
        }
        
        $table->data[] = array(
            $viewname,
            $viewtype,
            $viewdescription,
            $visible,
            $defaultview,
            $viewfilter,
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

