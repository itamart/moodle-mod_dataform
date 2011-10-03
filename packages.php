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

$urlparams = new object();

$urlparams->d = optional_param('d', 0, PARAM_INT);             // dataform id
$urlparams->id = optional_param('id', 0, PARAM_INT);            // course module id

$urlparams->new = optional_param('new', 0, PARAM_INT);   // save current package

// packages list actions
$urlparams->apply =     optional_param('apply', 0, PARAM_INT);  // path of package to apply
$urlparams->applymap =  optional_param('applymap', 0, PARAM_INT);  // path of package to apply with mapping
$urlparams->map =       optional_param('map', 0, PARAM_BOOL);  // map new package fields to old fields
$urlparams->delete =    optional_param('delete', 0, PARAM_INT);   // path of package to delete
$urlparams->share =     optional_param('share', 0, PARAM_INT);     // path of package to share
$urlparams->plugin =    optional_param('plugin', 0, PARAM_INT);     // path of package to share
$urlparams->path =      optional_param('path', '', PARAM_PATH);
$urlparams->area =      optional_param('area', dataform::PACKAGE_COURSEAREA, PARAM_ALPHAEXT);

$urlparams->confirmed = optional_param('confirmed', 0, PARAM_INT);

// Set a dataform object
$df = new dataform($urlparams->d, $urlparams->id);

require_capability('mod/dataform:managetemplates', $df->context);

$df->set_page('packages', array('urlparams' => $urlparams));

// DATA PROCESSING
if ($forminput = data_submitted() and confirm_sesskey()) {
}

if ($urlparams->new and confirm_sesskey()) {    // save current package
    
    require_once("$CFG->dirroot/backup/util/includes/backup_includes.php");
    
    $bc = new backup_controller(backup::TYPE_1ACTIVITY, $df->cm->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);
    $bc->execute_plan();
    $df->create_package_from_backup();

} else if ($urlparams->apply and confirm_sesskey()) {    // apply package

    // extract the backup file to the temp folder
    $folder = $df->context->id. '-'. time();
    $tempdir = "temp/backup/$folder";
    $backuptempdir = make_upload_directory($tempdir);
    $zipper = get_file_packer('application/zip');
    if (!empty($urlparams->path)) { // plugin package
        $packagepath = "$CFG->dirroot/mod/dataform/package/{$urlparams->path}";
        $zipper->extract_to_pathname($packagepath, $backuptempdir);
    } else {
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($urlparams->apply);
        $file->extract_to_pathname($zipper, $backuptempdir);           
    }
    
    require_once("$CFG->dirroot/backup/util/includes/restore_includes.php");
    
    $transaction = $DB->start_delegated_transaction();
    $rs = new restore_controller($folder, $df->course->id, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id, backup::TARGET_CURRENT_ADDING);

    // get the dataform restore activity task
    $tasks = $rs->get_plan()->get_tasks();
    $dataformtask = null;
    foreach ($tasks as $key => $task) {
        if ($task instanceof restore_dataform_activity_task) {
            $dataformtask = $task;
            break;
        }
    }

    if ($dataformtask) {
        $dataformtask->set_activityid($df->id());
        $dataformtask->set_moduleid($df->cm->id);
        $dataformtask->set_contextid($df->context->id);

        $rs->execute_precheck();

        $rs->execute_plan();
        
        $transaction->allow_commit();
        redirect(new moodle_url('/mod/dataform/view.php', array('d' => $df->id())));        
    }

/*
} else if ($urlparams->applymap and confirm_sesskey()) {

    $df->restore($urlparams->applymap, true);    // apply selected package with mapping
    
    // mapping prints mapping form so finish the page and exit
    echo $OUTPUT->footer();
    exit(0);
*/
} else if ($urlparams->share and confirm_sesskey()) {  // share selected packages
    $df->share_packages($urlparams->share);

//} else if ($urlparams->plugin and confirm_sesskey()) {  // plug in selected packages
//    $df->plug_in_packages($urlparams->plugin);

} else if ($urlparams->delete and confirm_sesskey()) { // delete selected packages
//    if (!empty($urlparams->path)) { // plugin package
//        $df->plug_in_packages($urlparams->path, true);
//    } else {
        $df->delete_packages($urlparams->area, $urlparams->delete);
//    }
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

// TODO
$br = html_writer::empty_tag('br');
// save current dataform to local package form
echo html_writer::tag('div',
                        $br.
                        html_writer::link(new moodle_url('packages.php', array('d' => $df->id(), 'sesskey' => sesskey(), 'new' => 1)), get_string('packageaddfromdataform','dataform')).
                        $br.$br.$br.$br.
                        '',
                        array('class' => 'mdl-align fieldadd'));
// upload package file
//echo '<a href="javascript:void(0);" onclick="return openpopup(\'/files/index.php?id='. $df->course->id. '&amp;wdir=/moddata/dataform/packages&amp;choose=uploadpackage.file\', \'coursefiles\', \'menubar=0,location=0,scrollbars,resizable,width=750,height=500\', 0);">'. get_string('packageaddfromfile', 'dataform'). '</a> (<a href="packages.php?d='. $df->id().'">'. get_string('packagerefreshlist', 'dataform'). '</a>)';
//helpbutton('packages', get_string('packageaddfromfile','dataform'), 'dataform');
//echo '</div>';
//echo '<br />';

// if there are packages print admin style list of them
if ($localpackages or $pluginpackages or $sharedpackages) {

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
    $strselectallnone = html_writer::checkbox('', '', false, '', array('onclick' => 
                                                '"inps=document.getElementsByTagName(\'input\');'.
                                                'for (var i=0;i<inps.length;i++) {'.
                                                    'if (inps[i].type==\'checkbox\' && inps[i].name.search(\'selector_\')!=-1){'.
                                                        'inps[i].checked=this.checked;'.
                                                    '}'.
                                                '}"'));

    $table = new html_table();
    $table->head = array($strname, $strdescription, $strscreenshot, $strapply, $strapply. html_writer::empty_tag('br'). $strmap, $strdownload, $strshare, $strdelete, $strselectallnone);
    $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'center', 'center');
    $table->wrap = array(false, false, false, false, false, false, false, false, false);
    $table->attributes['align'] = 'center';

    $actionbaseurl = '/mod/dataform/packages.php';
    $linkparams = array('d' => $df->id(), 'sesskey' => sesskey());

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

            $packagename = $package->name;
            $packagedescription = '';
            $packagescreenshot = '';
            //if ($package->screenshot) {
            //    $packagescreenshot = '<img width="150" class="packagescreenshot" src="'. $package->screenshot. '" alt="'. get_string('screenshot'). '" />';
            //}
            $packageapply = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('apply' => $package->id)),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/switch_whole'), 'class' => "iconsmall", 'alt' => $strapply, 'title' => $strapply)));
            //$packageapplymap = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('applymap' => $package->id)),
            //                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/switch_plus'), 'class' => "iconsmall", 'alt' => $strapply, 'title' => $strapply)));
            $packageapplymap = '';
            $packagedownload = html_writer::link(new moodle_url("/pluginfile.php/$package->contextid/mod_dataform/course_packages/$package->itemid/$package->name"),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/backup'), 'class' => "iconsmall", 'alt' => $strdownload, 'title' => $strdownload)));
            $packageshare = '';
            if (has_capability('mod/dataform:packagesviewall', $df->context)) {
                $packageshare = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('share' => $package->id)),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/group'), 'class' => "iconsmall", 'alt' => $strshare, 'title' => $strshare)));
            }
            //$packageplugin = '';
            //if (has_capability('mod/dataform:managepackages', $df->context)) {            
            //    $packageplugin = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('plugin' => $package->id)),
            //                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/admin'), 'class' => "iconsmall", 'alt' => $strplugin, 'title' => $strplugin)));
            //}
            $packagedelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $package->id)),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'class' => "iconsmall", 'alt' => $strdelete, 'title' => $strdelete)));
            $packageselector = html_writer::checkbox("selector_$package->itemid", $package->itemid, false);

            $table->data[] = array(
                $packagename,
                $packagedescription,
                $packagescreenshot,
                $packageapply,
                $packageapplymap,
                $packagedownload,
                $packageshare,
                //$packageplugin,
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

            $packagename = $package->name;
            $packagedescription = '';
            $packagescreenshot = '';
            $packageapply = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('path' => $package->name, 'apply' => -1)),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/switch_whole'), 'class' => "iconsmall", 'alt' => $strapply, 'title' => $strapply)));
            //$packageapplymap = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('plugin' => 1, 'applymap' => $package->name)),
            //                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/switch_plus'), 'class' => "iconsmall", 'alt' => $strapply, 'title' => $strapply)));
            $packageapplymap = '';
            $packagedownload = '';
            $packageshare = '';
            //$packageplugin = '';
            $packagedelete = '';
            //if (has_capability('mod/dataform:managepackages', $df->context)) {            
            //    $packagedelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('path' => $package->name, 'delete' => -1)),
            //                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'class' => "iconsmall", 'alt' => $strdelete, 'title' => $strdelete)));
            //}                
            $packageselector = '';

            $table->data[] = array(
                $packagename,
                $packagedescription,
                $packagescreenshot,
                $packageapply,
                $packageapplymap,
                $packagedownload,
                $packageshare,
                //$packageplugin,
                $packagedelete,
                $packageselector
           );
        }
        
        $linkparams['area'] = dataform::PACKAGE_SITEAREA;

        foreach ($sharedpackages as $package) {

            $packagename = $package->name;
            $packagedescription = '';
            $packagescreenshot = '';
            $packageapply = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('apply' => $package->id)),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/switch_whole'), 'class' => "iconsmall", 'alt' => $strapply, 'title' => $strapply)));
            //$packageapplymap = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('applymap' => $package->id)),
            //                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/switch_plus'), 'class' => "iconsmall", 'alt' => $strapply, 'title' => $strapply)));
            $packageapplymap = '';
            $packagedownload = html_writer::link(new moodle_url("/pluginfile.php/$package->contextid/mod_dataform/site_packages/$package->itemid/$package->name"),
                            html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/backup'), 'class' => "iconsmall", 'alt' => $strdownload, 'title' => $strdownload)));
            $packageshare = '';
            //$packageplugin = '';
            //if (has_capability('mod/dataform:managepackages', $df->context)) {            


            //    $packageplugin = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('plugin' => $package->id)),
            //                    html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('i/admin'), 'class' => "iconsmall", 'alt' => $strplugin, 'title' => $strplugin)));
            //}
            $packagedelete = '';
            if (has_capability('mod/dataform:managepackages', $df->context)) {            
                $packagedelete = html_writer::link(new moodle_url($actionbaseurl, $linkparams + array('delete' => $package->id)),
                                html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('t/delete'), 'class' => "iconsmall", 'alt' => $strdelete, 'title' => $strdelete)));
            }                
            $packageselector = html_writer::checkbox("selector_$package->id", $package->id, false);

            $table->data[] = array(
                $packagename,
                $packagedescription,
                $packagescreenshot,
                $packageapply,
                $packageapplymap,
                $packagedownload,
                $packageshare,
                //$packageplugin,
                $packagedelete,
                $packageselector
           );
        }
    }
    
    echo html_writer::table($table);
    echo html_writer::empty_tag('br');
    
}

$df->print_footer();
