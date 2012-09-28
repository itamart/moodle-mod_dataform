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
 * @package preset
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Preset manager class
 */
class dataform_preset_manager {

    const PRESET_COURSEAREA = 'course_presets';
    const PRESET_SITEAREA = 'site_presets';
    const PRESET_SITECONTEXT = SYSCONTEXTID;

    protected $_df;

    /**
     * constructor
     */
    public function __construct($df) {
        $this->_df = $df;
    }

    /**
     * Returns an array of the shared presets (in moodledata) the user is allowed to access
     * @param in $presetarea  PRESET_COURSEAREA/PRESET_SITEAREA
     */
    public function get_user_presets($presetarea) {
        global $USER;

        $presets = array();
        $course_context = context_course::instance($this->_df->course->id);

        $fs = get_file_storage();
        if ($presetarea == 'course_presets') {
            $files = $fs->get_area_files($course_context->id, 'mod_dataform', $presetarea);
        } else if ($presetarea == 'site_presets') {
            $files = $fs->get_area_files(self::PRESET_SITECONTEXT, 'mod_dataform', $presetarea);
        }
        $canviewall = has_capability('mod/dataform:presetsviewall', $this->_df->context);
        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file->is_directory() || ($file->get_userid() != $USER->id and !$canviewall)) {
                    continue;
                }
                $preset = new object;
                $preset->contextid = $file->get_contextid();
                $preset->path = $file->get_filepath();
                $preset->name = $file->get_filename();
                $preset->shortname = pathinfo($preset->name, PATHINFO_FILENAME);
                $preset->userid = $file->get_userid();
                $preset->itemid = $file->get_itemid();
                $preset->id = $file->get_id();
                $presets[] = $preset;
            }
        }

        return $presets;
    }

    /**
     *
     */
    public function print_presets_list($localpresets, $sharedpresets) {
        global $CFG, $OUTPUT;
        
        $targetpage = '/mod/dataform/preset/index.php';
        if ($localpresets or $sharedpresets) {

            $linkparams = array('d' => $this->_df->id(), 'sesskey' => sesskey());
            $actionurl = htmlspecialchars_decode(new moodle_url($targetpage, $linkparams));
            
            // prepare to make file links
            require_once("$CFG->libdir/filelib.php");

            /// table headings
            $strname = get_string('name');
            $strdescription = get_string('description');
            $strscreenshot = get_string('screenshot');
            $strapply = get_string('presetapply', 'dataform');
            $strmap = get_string('presetmap', 'dataform');
            $strdownload = get_string('download', 'dataform');
            $strdelete = get_string('delete');
            $strshare = get_string('presetshare', 'dataform');

            $selectallnone = html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'preset\'&#44;this.checked)'));
            
            $multidownload = html_writer::tag('button', $OUTPUT->pix_icon('t/download', get_string('multidownload', 'dataform')), array('name' => 'multidownload', 'onclick' => 'bulk_action(\'preset\'&#44; \''. $actionurl. '\'&#44; \'download\')'));
            
            $multidelete = html_writer::tag('button', $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')), array('name' => 'multidelete', 'onclick' => 'bulk_action(\'preset\'&#44; \''. $actionurl. '\'&#44; \'delete\')'));
            
            $multishare = html_writer::tag('button', $OUTPUT->pix_icon('i/group', get_string('multishare', 'dataform')), array('name' => 'multishare', 'onclick' => 'bulk_action(\'preset\'&#44; \''. $actionurl. '\'&#44; \'share\')'));

            $table = new html_table();
            $table->head = array($strname, $strdescription, $strscreenshot, $strapply, $multidownload, $multishare, $multidelete, $selectallnone);
            $table->align = array('left', 'left', 'center', 'center', 'center', 'center', 'center', 'center');
            $table->wrap = array(false, false, false, false, false, false, false, false);
            $table->attributes['align'] = 'center';

            // print local presets
            if ($localpresets) {
                // headingg
                $lpheadingcell = new html_table_cell();
                $lpheadingcell->text = html_writer::tag('h4', get_string('presetavailableincourse', 'dataform'));
                $lpheadingcell->colspan = 9;
                
                $lpheadingrow = new html_table_row();
                $lpheadingrow->cells[] = $lpheadingcell;

                $table->data[] = $lpheadingrow;

                foreach ($localpresets as $preset) {

                    $presetname = $preset->shortname;
                    $presetdescription = '';
                    $presetscreenshot = '';
                    //if ($preset->screenshot) {
                    //    $presetscreenshot = '<img width="150" class="presetscreenshot" src="'. $preset->screenshot. '" alt="'. get_string('screenshot'). '" />';
                    //}
                    $presetapply = html_writer::link(new moodle_url($targetpage, $linkparams + array('apply' => $preset->id)),
                                    $OUTPUT->pix_icon('t/switch_whole', $strapply));
                    //$presetapplymap = html_writer::link(new moodle_url($targetpage, $linkparams + array('applymap' => $preset->id)),
                    //                $OUTPUT->pix_icon('t/switch_plus', $strapply));
                    $presetdownload = html_writer::link(
                        moodle_url::make_file_url("/pluginfile.php", "/$preset->contextid/mod_dataform/course_presets/$preset->itemid/$preset->name"),
                        $OUTPUT->pix_icon('t/download', $strdownload)
                    );
                    $presetshare = '';
                    if (has_capability('mod/dataform:presetsviewall', $this->_df->context)) {
                        $presetshare = html_writer::link(new moodle_url($targetpage, $linkparams + array('share' => $preset->id)),
                                    $OUTPUT->pix_icon('i/group', $strshare));
                    }
                    $presetdelete = html_writer::link(new moodle_url($targetpage, $linkparams + array('delete' => $preset->id)),
                                    $OUTPUT->pix_icon('t/delete', $strdelete));
                    $presetselector = html_writer::checkbox("presetselector", $preset->id, false);

                    $table->data[] = array(
                        $presetname,
                        $presetdescription,
                        $presetscreenshot,
                        $presetapply,
                        $presetdownload,
                        $presetshare,
                        $presetdelete,
                        $presetselector
                   );
                }
                
            }

            // print shared presets
            if ($sharedpresets) {
                // heading
                $lpheadingcell = new html_table_cell();
                $lpheadingcell->text = html_writer::tag('h4', get_string('presetavailableinsite', 'dataform'));
                $lpheadingcell->colspan = 9;
                
                $lpheadingrow = new html_table_row();
                $lpheadingrow->cells[] = $lpheadingcell;

                $table->data[] = $lpheadingrow;
                
                $linkparams['area'] = self::PRESET_SITEAREA;

                foreach ($sharedpresets as $preset) {

                    $presetname = $preset->shortname;
                    $presetdescription = '';
                    $presetscreenshot = '';
                    $presetapply = html_writer::link(new moodle_url($targetpage, $linkparams + array('apply' => $preset->id)), $OUTPUT->pix_icon('t/switch_whole', $strapply));
                    //$presetapplymap = html_writer::link(new moodle_url($targetpage, $linkparams + array('applymap' => $preset->id)), $OUTPUT->pix_icon('t/switch_plus', $strapply));
                    $presetdownload = html_writer::link(
                        moodle_url::make_file_url("/pluginfile.php", "/$preset->contextid/mod_dataform/site_presets/$preset->itemid/$preset->name"),
                        $OUTPUT->pix_icon('t/download', $strdownload)
                    );
                    $presetshare = '';
                    $presetdelete = '';
                    if (has_capability('mod/dataform:managepresets', $this->_df->context)) {            
                        $presetdelete = html_writer::link(new moodle_url($targetpage, $linkparams + array('delete' => $preset->id)), $OUTPUT->pix_icon('t/delete', $strdelete));
                    }                
                    $presetselector = html_writer::checkbox("presetselector", $preset->id, false);

                    $table->data[] = array(
                        $presetname,
                        $presetdescription,
                        $presetscreenshot,
                        $presetapply,
                        $presetdownload,
                        $presetshare,
                        $presetdelete,
                        $presetselector
                   );
                }
            }
            
            echo html_writer::table($table);
            echo html_writer::empty_tag('br');           
        }
    }

    /**
     *
     */
    public function print_preset_form() {
        echo html_writer::start_tag('div', array('style' => 'width:80%;margin:auto;'));
        $mform = new mod_dataform_preset_form(new moodle_url('/mod/dataform/preset/index.php', array('d' => $this->_df->id(), 'sesskey' => sesskey(), 'add' => 1)));
        $mform->set_data(null);
        $mform->display();
        echo html_writer::end_tag('div');
    }

    /**
     *
     */
    public function process_presets($params) {
        global $CFG;
        
        $mform = new mod_dataform_preset_form(new moodle_url('mod/dataform/preset/index.php', array('d' => $this->_df->id(), 'sesskey' => sesskey(), 'add' => 1)));
        // add presets
        if ($data = $mform->get_data()) { 
            // preset this dataform
            if ($data->preset_source == 'current') {
                $this->create_preset_from_backup($data->preset_data);

            // upload presets
            } else if ($data->preset_source == 'file') {
                $this->create_preset_from_upload($data->uploadfile);
            }
        // apply a preset
        } else if ($params->apply and confirm_sesskey()) {
            $this->apply_preset($params->apply, $params->torestorer);
            // rebuild course cache to show new dataform name on the course page
            rebuild_course_cache($this->_df->course->id);
            
        // download (bulk in zip)
        } else if ($params->download and confirm_sesskey()) {
            $this->download_presets($params->download);

        // share presets
        } else if ($params->share and confirm_sesskey()) {
            $this->share_presets($params->share);

        // delete presets
        } else if ($params->delete and confirm_sesskey()) {
            $this->delete_presets($params->delete);
        }
    }

    /**
     *
     */
    public function create_preset_from_backup($userdata) {
        global $CFG, $USER, $SESSION;
        
        require_once("$CFG->dirroot/backup/util/includes/backup_includes.php");
        
        $users = 0;
        $anon = 0;
        switch ($userdata) {
            case 'dataanon':
                $anon = 1;
            case 'data':
                $users = 1;
        }
        
        // store preset settings in $SESSION
        $SESSION->{"dataform_{$this->_df->cm->id}_preset"} = "$users $anon";

        $bc = new backup_controller(backup::TYPE_1ACTIVITY, $this->_df->cm->id, backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id);

        // clear preset settings from $SESSION
        unset($SESSION->{"dataform_{$this->_df->cm->id}_preset"});

        // set users and anon in plan
        $bc->get_plan()->get_setting('users')->set_value($users);        
        $bc->get_plan()->get_setting('anonymize')->set_value($anon);
        $bc->set_status(backup::STATUS_AWAITING);

        $bc->execute_plan();
        $bc->destroy();
        
        $fs = get_file_storage();
        if ($users and !$anon) {
            $contextid = $this->_df->context->id;
            $files = $fs->get_area_files($contextid, 'backup', 'activity', 0, 'timemodified', false);
        } else {
            $usercontext = context_user::instance($USER->id);
            $contextid = $usercontext->id;
            $files = $fs->get_area_files($contextid, 'user', 'backup', 0, 'timemodified', false);
        }
        if (!empty($files)) {
            $course_context = context_course::instance($this->_df->course->id);
            foreach ($files as $file) {
                if ($file->get_contextid() != $contextid) {
                    continue;
                }
                $preset = new object;
                $preset->contextid = $course_context->id;
                $preset->component = 'mod_dataform';
                $preset->filearea = self::PRESET_COURSEAREA;
                $preset->filepath = '/';
                $preset->filename = clean_filename(str_replace(' ', '_', $this->_df->data->name).
                                    '-dataform-preset-'.
                                    gmdate("Ymd_Hi"). '-'.
                                    str_replace(' ', '-', get_string("preset$userdata", 'dataform')). '.mbz');

                $fs->create_file_from_storedfile($preset, $file);
                $file->delete();
                return true;
            }
        }
        return false;
    }

    /**
     *
     */
    public function create_preset_from_upload($draftid) {
        global $USER;

        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        if ($files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'sortorder', false)) {
            $file = reset($files);
            $course_context = context_course::instance($this->_df->course->id);
            $preset = new object;
            $preset->contextid = $course_context->id;
            $preset->component = 'mod_dataform';
            $preset->filearea = self::PRESET_COURSEAREA;
            $preset->filepath = '/';
            
            $ext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);            
            if ($ext == 'mbz') {
                $preset->filename = $file->get_filename();
                $fs->create_file_from_storedfile($preset, $file);
            } else if ($ext == 'zip') {
                // extract files to the draft area
                $zipper = get_file_packer('application/zip');
                $file->extract_to_storage($zipper, $usercontext->id, 'user', 'draft', $draftid, '/');
                $file->delete();

                if ($files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftid, 'sortorder', false)) {
                    foreach ($files as $file) {
                        $ext = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
                        if ($ext == 'mbz') {
                            $preset->filename = $file->get_filename();
                            $fs->create_file_from_storedfile($preset, $file);
                        }
                    }
                }
            }
            $fs->delete_area_files($usercontext->id, 'user', 'draft', $draftid);
            return true;
        }
        return false;
    }

    /**
     *
     */
    public function apply_preset($userpreset, $torestorer = true) {
        global $DB, $CFG, $USER;
        
        // extract the backup file to the temp folder
        $folder = 'tmp-'. $this->_df->context->id. '-'. time();
        $backuptempdir = make_temp_directory("backup/$folder");
        $zipper = get_file_packer('application/zip');
        $fs = get_file_storage();
        $file = $fs->get_file_by_id($userpreset);
        $file->extract_to_pathname($zipper, $backuptempdir);           
        
        require_once("$CFG->dirroot/backup/util/includes/restore_includes.php");

        // anonymous users cleanup
        $DB->delete_records_select('user', $DB->sql_like('firstname', '?'), array('%anonfirstname%'));
        
        $transaction = $DB->start_delegated_transaction();
        $rc = new restore_controller($folder,
                                    $this->_df->course->id,
                                    backup::INTERACTIVE_NO,
                                    backup::MODE_GENERAL,
                                    $USER->id,
                                    backup::TARGET_CURRENT_ADDING);

        $rc->execute_precheck();

        // get the dataform restore activity task
        $tasks = $rc->get_plan()->get_tasks();
        $dataformtask = null;
        foreach ($tasks as &$task) {
            if ($task instanceof restore_dataform_activity_task) {
                $dataformtask = &$task;
                break;
            }
        }

        if ($dataformtask) {
            $dataformtask->set_activityid($this->_df->id());
            $dataformtask->set_moduleid($this->_df->cm->id);
            $dataformtask->set_contextid($this->_df->context->id);
            if ($torestorer) {
                $dataformtask->set_ownerid($USER->id);
            }

            $rc->set_status(backup::STATUS_AWAITING);
            $rc->execute_plan();
            
            $transaction->allow_commit();
            // rc cleanup
            $rc->destroy();
            // anonymous users cleanup
            $DB->delete_records_select('user', $DB->sql_like('firstname', '?'), array('%anonfirstname%'));
            
            redirect(new moodle_url('/mod/dataform/view.php', array('d' => $this->_df->id())));        
        } else {
            $rc->destroy();
        }        
    }

    /**
     *
     */
    public function download_presets($presetids) {
        global $CFG;
        
        if (headers_sent()) {
            throw new moodle_exception('headerssent');
        }

        if (!$pids = explode(',', $presetids)) {
            return false;
        }

        $presets = array();
        $fs = get_file_storage();

        // try first course area
        $course_context = context_course::instance($this->_df->course->id);
        $contextid = $course_context->id;

        if ($files = $fs->get_area_files($contextid, 'mod_dataform', self::PRESET_COURSEAREA)) {
            foreach ($files as $file) {
                if (empty($pids)) break;
                
                if (!$file->is_directory()) {
                    $key = array_search($file->get_id(), $pids);
                    if ($key !== false) {
                        $presets[$file->get_filename()] = $file;
                        unset($pids[$key]);
                    }
                }
            }
        }

        // try site area
        if (!empty($pids)) {
            if ($files = $fs->get_area_files(self::PRESET_SITECONTEXT, 'mod_dataform', self::PRESET_SITEAREA)) {
                foreach ($files as $file) {
                    if (empty($pids)) break;
                    
                    if (!$file->is_directory()) {
                        $key = array_search($file->get_id(), $pids);
                        if ($key !== false) {
                            $presets[$file->get_filename()] = $file;
                            unset($pids[$key]);
                        }
                    }
                }
            }            
        }

        $downloaddir = make_temp_directory('download');
        $filename = 'presets.zip';
        $downloadfile = "$downloaddir/$filename";
        
        $zipper = get_file_packer('application/zip');
        $zipper->archive_to_pathname($presets, $downloadfile);

        header("Content-Type: application/download\n");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header('Expires: 0');
        header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
        header('Pragma: public');
        $downloadhandler = fopen($downloadfile, 'rb');
        print fread($downloadhandler, filesize($downloadfile));
        fclose($downloadhandler);
        unlink($downloadfile);
        exit(0);
    }

    /**
     *
     */
    public function share_presets($presetids) {
        global $CFG, $USER;

        if (!has_capability('mod/dataform:presetsviewall', $this->_df->context)) {
            return false;
        }
                    
        $fs = get_file_storage();
        $filerecord = new object;
        $filerecord->contextid = self::PRESET_SITECONTEXT;
        $filerecord->component = 'mod_dataform';
        $filerecord->filearea = self::PRESET_SITEAREA;
        $filerecord->filepath = '/';

        foreach (explode(',', $presetids) as $pid) {
            $fs->create_file_from_storedfile($filerecord, $pid);
        }
        return true;
    }

    /**
     *
     */
    public function delete_presets($presetids) {
        if (!$pids = explode(',', $presetids)) {
            return false;
        }
        
        if (!has_capability('mod/dataform:managepresets', $this->_df->context)) {
            return false;
        }
                    
        $fs = get_file_storage();

        // try first course area
        $course_context = context_course::instance($this->_df->course->id);
        $contextid = $course_context->id;

        if ($files = $fs->get_area_files($contextid, 'mod_dataform', self::PRESET_COURSEAREA)) {
            foreach ($files as $file) {
                if (empty($pids)) break;
                
                if (!$file->is_directory()) {
                    $key = array_search($file->get_id(), $pids);
                    if ($key !== false) {
                        $file->delete();
                        unset($pids[$key]);
                    }
                }
            }
        }

        // try site area
        if (!empty($pids)) {
            if ($files = $fs->get_area_files(self::PRESET_SITECONTEXT, 'mod_dataform', self::PRESET_SITEAREA)) {
                foreach ($files as $file) {
                    if (empty($pids)) break;
                    
                    if (!$file->is_directory()) {
                        $key = array_search($file->get_id(), $pids);
                        if ($key !== false) {
                            $file->delete();
                            unset($pids[$key]);
                        }
                    }
                }
            }            
        }
        return true;        
    }

}
