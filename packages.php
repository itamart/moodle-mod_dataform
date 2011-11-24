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
require_once('mod_class.php');
require_once('packages_form.php');

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);            // course module id

// packages list actions
$urlparams->apply =     optional_param('apply', 0, PARAM_INT);  // path of package to apply
//$urlparams->applymap =  optional_param('applymap', 0, PARAM_INT);  // path of package to apply with mapping
$urlparams->map =       optional_param('map', 0, PARAM_BOOL);  // map new package fields to old fields
$urlparams->delete =    optional_param('delete', '', PARAM_SEQUENCE);   // ids of packages to delete
$urlparams->share =     optional_param('share', '', PARAM_SEQUENCE);     // ids of packages to share
$urlparams->plugin =    optional_param('plugin', 0, PARAM_INT);     // path of package to share
$urlparams->path =      optional_param('path', '', PARAM_PATH);
$urlparams->download =     optional_param('download', '', PARAM_SEQUENCE);     // ids of packages to download in one zip

$urlparams->confirmed = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);
require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('packages', array('modjs' => true, 'urlparams' => $urlparams));

// activate navigation node
navigation_node::override_active_url(new moodle_url('/mod/dataform/packages.php', array('id' => $df->cm->id)));

// DATA PROCESSING
$mform = new mod_dataform_packages_form(new moodle_url('/mod/dataform/packages.php',
                                                        array('d' => $df->id(), 'sesskey' => sesskey(), 'add' => 1)));
// add packages
if ($data = $mform->get_data()) { 
    // package this dataform
    if ($data->package_source == 'current') {
        require_once("$CFG->dirroot/backup/util/includes/backup_includes.php");
        
        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $df->cm->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);
        $bc->execute_plan();
        $df->create_package_from_backup();

    // upload packages
    } else if ($data->package_source == 'file') {
        $df->create_package_from_upload($data->uploadfile);
    }
// apply a package
} else if ($urlparams->apply and confirm_sesskey()) {    // apply package
    $df->apply_package($urlparams->apply, $urlparams->path);
    
// download (bulk in zip)
} else if ($urlparams->download and confirm_sesskey()) {
    $df->download_packages($urlparams->download);

// share packages
} else if ($urlparams->share and confirm_sesskey()) {  // share selected packages
    $df->share_packages($urlparams->share);

// delete packages
} else if ($urlparams->delete and confirm_sesskey()) { // delete selected packages
    $df->delete_packages($urlparams->delete);

// TODO
} else if ($urlparams->plugin and confirm_sesskey()) {  // plug in selected packages
    //$df->plug_in_packages($urlparams->plugin);
}

$pluginpackages = $df->get_plugin_packages();
$localpackages = $df->get_user_packages(dataform::PACKAGE_COURSEAREA);
$sharedpackages = $df->get_user_packages(dataform::PACKAGE_SITEAREA);

// any notifications
$df->notifications['bad']['getstartedpackages'] = '';
if (!$pluginpackages and !$localpackages and !$sharedpackages) {
    $df->notifications['bad']['getstartedpackages'] = get_string('packagenoneavailable','dataform');  // nothing in dataform
    $linktofields = html_writer::link(new moodle_url('fields.php', array('d' => $df->id())), get_string('fields', 'dataform'));
    $linktoviews = html_writer::link(new moodle_url('views.php', array('d' => $df->id())), get_string('views', 'dataform'));
    $df->notifications['bad']['getstartedfields'] = get_string('getstartedfields','dataform', $linktofields);
    $df->notifications['bad']['getstartedviews'] = get_string('getstartedviews','dataform', $linktoviews);
}

// print header
$df->print_header(array('tab' => 'packages', 'urlparams' => $urlparams));

// print the add form
echo html_writer::start_tag('div', array('style' => 'width:70%;float:right'));
echo html_writer::start_tag('div', array('style' => 'width:60%'));
$mform->set_data(null);
$mform->display();
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// if there are packages print admin style list of them
if ($localpackages or $pluginpackages or $sharedpackages) {

    $actionbaseurl = '/mod/dataform/packages.php';
    $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());
    $actionurl = htmlspecialchars_decode(new moodle_url($actionbaseurl, $linkparams));
    
    // prepare to make file links
    require_once($CFG->libdir.'/filelib.php');

    /// table headings
    $strname = get_string('name');
    $strdescription = get_string('description');
    $strscreenshot = get_string('screenshot');
    $strapply = get_string('packageapply', 'dataform');
    $strmap = get_string('packagemap', 'dataform');
    $strdownload = get_string('download', 'dataform');
    $strdelete = get_string('delete');
    $strshare = get_string('packageshare', 'dataform');
    //$strplugin = get_string('packageplugin', 'dataform');

    $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'package\'&#44;this.checked)'));
    $multidownload = html_writer::tag('button', 
                                $OUTPUT->pix_icon('t/download', get_string('multidownload', 'dataform')), 
                                array('name' => 'multidownload', 'onclick' => 'bulk_action(\'package\'&#44; \''. $actionurl. '\'&#44; \'download\')'));
    $multidelete = html_writer::tag('button', 
                                $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), 
                                array('name' => 'multidelete', 'onclick' => 'bulk_action(\'package\'&#44; \''. $actionurl. '\'&#44; \'delete\')'));
    $multishare = html_writer::tag('button', 
                                $OUTPUT->pix_icon('i/group', get_string('multishare', 'dataform')), 
                                array('name' => 'multishare', 'onclick' => 'bulk_action(\'package\'&#44; \''. $actionurl. '\'&#44; \'share\')'));

    $table = new html_table();
    $table->head = array($strname, $strdescription, $strscreenshot, $strapply, $multidownload, $multishare, $multidelete, $selectallnone);
    $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false);
    $table->attributes['align'] = 'center';

    // print local packages
    if ($localpackages) {
        // headingg
        $lpheadingcell = new html_table_cell();
        $lpheadingcell->text = html_writer::tag('h4', get_string('packageavailableincourse', 'dataform'));
        $lpheadingcell->colspan = 9;
        
        $lpheadingrow = new html_table_row();
        $lpheadingrow->cells[] = $lpheadingcell;

        $table->data[] = $lpheadingrow;

        foreach ($localpackages as $package) {

            $packagename = $package->shortname;
            $packagedescription = '';
            $packagescreenshot = '';
            //if ($package->screenshot) {
            //    $packagescreenshot = '<img width="150" class="packagescreenshot" src="'. $package->screenshot. '" alt="'. get_string('screenshot'). '" />';
            //}
            $packageapply = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('apply' => $package->id)),
                            $OUTPUT->pix_icon('t/switch_whole', $strapply));
            //$packageapplymap = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('applymap' => $package->id)),
            //                $OUTPUT->pix_icon('t/switch_plus', $strapply));
            $packagedownload = html_writer::link(new moodle_url("/pluginfile.php/$package->contextid/mod_dataform/course_packages/$package->itemid/$package->name"),
                            $OUTPUT->pix_icon('t/download', $strdownload));
            $packageshare = '';
            if (has_capability('mod/dataform:packagesviewall', $df->context)) {
                $packageshare = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('share' => $package->id)),
                            $OUTPUT->pix_icon('i/group', $strshare));
            }
            //$packageplugin = '';
            //if (has_capability('mod/dataform:managepackages', $df->context)) {            
            //    $packageplugin = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('plugin' => $package->id)),
            //                    $OUTPUT->pix_icon('i/admin', $strplugin));
            //}
            $packagedelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $package->id)),
                            $OUTPUT->pix_icon('t/delete', $strdelete));
            $packageselector = html_writer::checkbox("packageselector", $package->id, false);

            $table->data[] = array(
                $packagename,
                $packagedescription,
                $packagescreenshot,
                $packageapply,
                $packagedownload,
                $packageshare,
                $packagedelete,
                $packageselector
           );
        }
        
    }

    // print plugin packages
    if ($pluginpackages or $sharedpackages) {
        // headingg
        $lpheadingcell = new html_table_cell();
        $lpheadingcell->text = html_writer::tag('h4', get_string('packageavailableinsite', 'dataform'));
        $lpheadingcell->colspan = 9;
        
        $lpheadingrow = new html_table_row();
        $lpheadingrow->cells[] = $lpheadingcell;

        $table->data[] = $lpheadingrow;
        
        foreach ($pluginpackages  as $package) {

            $packagename = $package->shortname;
            $packagedescription = '';
            $packagescreenshot = '';
            $packageapply = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('path' => $package->name, 'apply' => -1)),
                            $OUTPUT->pix_icon('t/switch_whole', $strapply));
            //$packageapplymap = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('plugin' => 1, 'applymap' => $package->name)),
            //                $OUTPUT->pix_icon('t/switch_plus', $strapply));
            $packagedownload = '';
            $packageshare = '';
            $packagedelete = '';
            //if (has_capability('mod/dataform:managepackages', $df->context)) {            
            //    $packagedelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('path' => $package->name, 'delete' => -1)),
            //                    $OUTPUT->pix_icon('t/delete', $strdelete));
            //}                
            $packageselector = '';

            $table->data[] = array(
                $packagename,
                $packagedescription,
                $packagescreenshot,
                $packageapply,
                $packagedownload,
                $packageshare,
                $packagedelete,
                $packageselector
           );
        }
        
        $linkparams['area'] = dataform::PACKAGE_SITEAREA;

        foreach ($sharedpackages as $package) {

            $packagename = $package->shortname;
            $packagedescription = '';
            $packagescreenshot = '';
            $packageapply = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('apply' => $package->id)),
                            $OUTPUT->pix_icon('t/switch_whole', $strapply));
            //$packageapplymap = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('applymap' => $package->id)),
            //                $OUTPUT->pix_icon('t/switch_plus', $strapply));
            $packagedownload = html_writer::link(new moodle_url("/pluginfile.php/$package->contextid/mod_dataform/site_packages/$package->itemid/$package->name"),
                            $OUTPUT->pix_icon('t/download', $strdownload));
            $packageshare = '';
            //$packageplugin = '';
            //if (has_capability('mod/dataform:managepackages', $df->context)) {            


            //    $packageplugin = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('plugin' => $package->id)),
            //                    $OUTPUT->pix_icon('i/admin', $strplugin));
            //}
            $packagedelete = '';
            if (has_capability('mod/dataform:managepackages', $df->context)) {            
                $packagedelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $package->id)),
                                $OUTPUT->pix_icon('t/delete', $strdelete));
            }                
            $packageselector = html_writer::checkbox("packageselector", $package->id, false);

            $table->data[] = array(
                $packagename,
                $packagedescription,
                $packagescreenshot,
                $packageapply,
                $packagedownload,
                $packageshare,
                $packagedelete,
                $packageselector
           );
        }
    }
    
    echo html_writer::table($table);
    echo html_writer::empty_tag('br');
    
}

$df->print_footer();
