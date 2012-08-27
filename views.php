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
 * @package mod-dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once('mod_class.php');

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);           // course module id
$urlparams->vedit = optional_param('vedit', 0, PARAM_INT);     // view id to edit

// views list actions
$urlparams->default    = optional_param('default', 0, PARAM_INT);  // id of view to default
$urlparams->singleedit = optional_param('singleedit', 0, PARAM_INT);  // id of view to single edit
$urlparams->singlemore = optional_param('singlemore', 0, PARAM_INT);  // id of view to single more
$urlparams->visible    = optional_param('visible', 0, PARAM_INT);     // id of view to hide/(show)/show
$urlparams->reset     = optional_param('reset', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to delete
$urlparams->delete     = optional_param('delete', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to delete
$urlparams->duplicate  = optional_param('duplicate', 0, PARAM_SEQUENCE);   // ids (comma delimited) of views to duplicate
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

} else if ($urlparams->default and confirm_sesskey()) {  // set view to default
    $df->process_views('default', $urlparams->default, true);    // confirmed by default

} else if ($urlparams->singleedit and confirm_sesskey()) {  // set view to single edit
    if ($urlparams->singleedit == -1) {
        $df->set_single_edit_view();    // reset
    } else {
        $df->set_single_edit_view($urlparams->singleedit); 
    }

} else if ($urlparams->singlemore and confirm_sesskey()) {  // set view to single more
    if ($urlparams->singlemore == -1) {
        $df->set_single_more_view();    // reset
    } else {
        $df->set_single_more_view($urlparams->singlemore); 
    }

} else if ($urlparams->setfilter and confirm_sesskey()) {  // re/set view filter
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
    $strdefault = get_string('defaultview', 'dataform');
    $strsingleedit = get_string('singleedit', 'dataform');
    $strsinglemore = get_string('singlemore', 'dataform');
    $strfilter = get_string('filter', 'dataform');
    $stredit = get_string('edit');
    $strdelete = get_string('delete');
    $strduplicate =  get_string('duplicate');
    $strchoose = get_string('choose');

    $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'view\'&#44;this.checked)'));
    $multideleteurl = new moodle_url($actionbaseurl, $linkparams);
    $multidelete = html_writer::tag(
        'button', 
        $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
        array('name' => 'multidelete', 'onclick' => 'bulk_action(\'view\'&#44; \''. $multideleteurl->out(false). '\'&#44; \'delete\')'));

    $strhide = get_string('hide');
    $strshow = get_string('show');
    $strreset =  get_string('reset');
    
    $filtersmenu = $df->get_filter_manager()->get_filters(null, true);
        
    $table = new html_table();
    $table->head = array($strviews, $strtype, $strdescription, $strvisible, $strdefault, $strsingleedit, $strsinglemore, $strfilter, $stredit, $strduplicate, $strreset, $multidelete, $selectallnone);
    $table->align = array('left', 'left', 'left', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false, false, false, false, false, false);
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
        if ($visible = $view->view->visible) {
            $visibleicon = $OUTPUT->pix_icon('t/hide', $strhide);
            $visibleicon = $visible == 1 ? "($visibleicon)" : $visibleicon;
        } else {
           $visibleicon = $OUTPUT->pix_icon('t/show', $strshow);
        }
        $viewvisible = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('visible' => $viewid)), $visibleicon);

        // default view
        if ($viewid == $df->data->defaultview) {
            $defaultview = $OUTPUT->pix_icon('t/clear', '');
        } else {
            $defaultview = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('default' => $viewid)), $OUTPUT->pix_icon('t/switch_whole', $strchoose));
        }
        
        // single edit view
        if ($viewid == $df->data->singleedit) {
            $singleedit = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('singleedit' => -1)), $OUTPUT->pix_icon('t/clear', ''));
        } else {
            $singleedit = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('singleedit' => $viewid)), $OUTPUT->pix_icon('t/switch_whole', $strchoose));
        }
        
        // single more view
        if ($viewid == $df->data->singleview) {
            $singlemore = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('singlemore' => -1)), $OUTPUT->pix_icon('t/clear', ''));
        } else {
            $singlemore = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('singlemore' => $viewid)), $OUTPUT->pix_icon('t/switch_whole', $strchoose));
        }
        
        // TODO view filter
        if (!empty($filtersmenu)) {
            $viewfilterid = $view->view->filter;
            if ($viewfilterid and !in_array($viewfilterid, array_keys($filtersmenu))) {
                $viewfilter = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('setfilter' => $viewid, 'fid' => -1)), $OUTPUT->pix_icon('i/risk_xss', $strreset));
            
            } else {
                if ($viewfilterid) {
                    $selected = $viewfilterid;
                    $options = array(-1 => '* '. get_string('reset')) + $filtersmenu;
                } else {
                    $selected = '';
                    $options = $filtersmenu;
                }
                
                $selecturl = new moodle_url($actionbaseurl, $linkparams + array('setfilter' => $viewid));
                $viewselect = new single_select($selecturl, 'fid', $options, $selected, array('' => 'choosedots'));

                $viewfilter = $OUTPUT->render($viewselect);
            }
        } else {
            $viewfilter = get_string('filtersnonedefined', 'dataform');
        }
        
        $table->data[] = array(
            $viewname,
            $viewtype,
            $viewdescription,
            $viewvisible,
            $defaultview,
            $singleedit,
            $singlemore,
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

