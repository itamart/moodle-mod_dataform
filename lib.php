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
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

/**
 * MOD FUNCTIONS WHICH ARE CALLED FROM OUTSIDE THE MODULE
 */

defined('MOODLE_INTERNAL') or die;

/**
 * Adds an instance of a dataform
 *
 * @global object
 * @param object $data
 * @return $int
 */
function dataform_add_instance($data) {
    global $CFG, $DB;

    $data->timemodified = time();

    if (empty($data->grade)) {
        $data->grade = 0;
        $data->grademethod = 0;
    }

    if ($CFG->dataform_maxentries) {
        $data->maxentries = $CFG->dataform_maxentries;
    }

    if (!$data->id = $DB->insert_record('dataform', $data)) {
        return false;
    }

    dataform_grade_item_update($data);
    return $data->id;
}

/**
 * updates an instance of a data
 *
 * @global object
 * @param object $data
 * @return bool
 */
function dataform_update_instance($data) {
    global  $DB;

    $data->id = $data->instance;

    $data->timemodified = time();

    if (empty($data->grade)) {
        $data->grade = 0;
        $data->grademethod = 0;
    }

    if (empty($data->notification)) {
        $data->notification = 0;
    }

    if (!$DB->update_record('dataform', $data)) {
        return false;
    }

    dataform_update_grades($data);

    return true;
}

/**
 * deletes an instance of a data
 *
 * @global object
 * @param int $id
 * @return bool
 */
function dataform_delete_instance($id) {
    global $DB;

    if (!$data = $DB->get_record('dataform', array('id'=>$id))) {
        return false;
    }

    $cm = get_coursemodule_from_instance('dataform', $data->id);
    $context = context_module::instance($cm->id);

    // files
    $fs = get_file_storage();
    $fs->delete_area_files($context->id, 'mod_dataform');

    // get all the content in this dataform
    $sql = "SELECT e.id FROM {dataform_entries} e WHERE e.dataid = ?";
    $DB->delete_records_select('dataform_contents', "entryid IN ($sql)", array($id));

    // delete fields views filters entries
    $DB->delete_records('dataform_fields', array('dataid'=>$id));
    $DB->delete_records('dataform_views', array('dataid'=>$id));
    $DB->delete_records('dataform_filters', array('dataid'=>$id));
    $DB->delete_records('dataform_entries', array('dataid'=>$id));

    // Delete the instance itself
    $result = $DB->delete_records('dataform', array('id'=>$id));

    // cleanup gradebook
    dataform_grade_item_delete($data);

    return $result;
}

/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 */
function dataform_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $module_pagetype = array('mod-dataform-*'=>get_string('page-mod-dataform-x', 'dataform'));
    return $module_pagetype;
}

//------------------------------------------------------------
// RESET
//------------------------------------------------------------

/**
 * prints the form elements that control
 * whether the course reset functionality affects the data.
 *
 * @param $mform form passed by reference
 */
function dataform_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'dataformheader', get_string('modulenameplural', 'dataform'));
    $mform->addElement('checkbox', 'reset_dataform_data', get_string('entriesdeleteall','dataform'));

    $mform->addElement('checkbox', 'reset_dataform_notenrolled', get_string('deletenotenrolled', 'dataform'));
    $mform->disabledIf('reset_dataform_notenrolled', 'reset_dataform_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_dataform_ratings', 'reset_dataform_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_dataform_comments', 'reset_dataform_data', 'checked');
}

/**
 * Course reset form defaults.
 * @return array
 */
function dataform_reset_course_form_defaults($course) {
    return array('reset_dataform_data'=>0, 'reset_dataform_ratings'=>1, 'reset_dataform_comments'=>1, 'reset_dataform_notenrolled'=>0);
}

/**
 * Removes all grades from gradebook
 *
 * @global object
 * @global object
 * @param int $courseid
 * @param string $type optional type
 */
function dataform_reset_gradebook($courseid, $type='') {
    global $DB;

    $sql = "SELECT d.*, cm.idnumber as cmidnumber, d.course as courseid
              FROM {dataform} d, {course_modules} cm, {modules} m
             WHERE m.name='dataform' AND m.id=cm.module AND cm.instance=d.id AND d.course=?";

    if ($dataforms = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($dataforms as $dataform) {
            dataform_grade_item_update($dataform, 'reset');
        }
    }
}

/**
 * Actual implementation of the rest coures functionality, delete all the
 * data responses for course $data->courseid.
 *
 * @global object
 * @global object
 * @param object $data the data submitted from the reset course.
 * @return array status array
 */
function dataform_reset_userdata($data) {
    global $CFG, $DB;

    require_once($CFG->libdir.'/filelib.php');
    require_once($CFG->dirroot.'/rating/lib.php');

    $componentstr = get_string('modulenameplural', 'dataform');
    $status = array();

    $allrecordssql = "SELECT e.id
                        FROM {dataform_entries} e
                             INNER JOIN {dataform} d ON e.dataid = d.id
                       WHERE d.course = ?";

    $alldatassql = "SELECT d.id
                      FROM {dataform} d
                     WHERE d.course=?";

    $rm = new rating_manager();
    $ratingdeloptions = new stdClass;
    $ratingdeloptions->component = 'mod_dataform';
    $ratingdeloptions->ratingarea = 'entry';

    // delete entries if requested
    if (!empty($data->reset_dataform_data)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='entry'", array($data->courseid));
        $DB->delete_records_select('dataform_contents', "entryid IN ($allrecordssql)", array($data->courseid));
        $DB->delete_records_select('dataform_entries', "dataid IN ($alldatassql)", array($data->courseid));

        if ($datas = $DB->get_records_sql($alldatassql, array($data->courseid))) {
            foreach ($datas as $dataid=>$unused) {
                fulldelete("$CFG->dataroot/$data->courseid/moddata/dataform/$dataid");

                if (!$cm = get_coursemodule_from_instance('dataform', $dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);

                $ratingdeloptions->contextid = $datacontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            dataform_reset_gradebook($data->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('entriesdeleteall', 'dataform'), 'error'=>false);
    }

    // remove entries by users not enrolled into course
    if (!empty($data->reset_dataform_notenrolled)) {
        $recordssql = "SELECT e.id, e.userid, e.dataid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {dataform_entries} e
                              INNER JOIN {dataform} d ON e.dataid = d.id
                              LEFT OUTER JOIN {user} u ON e.userid = u.id
                        WHERE d.course = ? AND e.userid > 0";

        $course_context = context_course::instance($data->courseid);
        $notenrolled = array();
        $fields = array();
        $rs = $DB->get_recordset_sql($recordssql, array($data->courseid));
        foreach ($rs as $record) {
            if (array_key_exists($record->userid, $notenrolled) or !$record->userexists or $record->userdeleted
              or !is_enrolled($course_context, $record->userid)) {
                //delete ratings
                if (!$cm = get_coursemodule_from_instance('dataform', $record->dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);
                $ratingdeloptions->contextid = $datacontext->id;
                $ratingdeloptions->itemid = $record->id;
                $rm->delete_ratings($ratingdeloptions);

                $DB->delete_records('comments', array('itemid'=>$record->id, 'commentarea'=>'entry'));
                $DB->delete_records('dataform_contents', array('entryid'=>$record->id));
                $DB->delete_records('dataform_entries', array('id'=>$record->id));
                // HACK: this is ugly - the entryid should be before the fieldid!
                if (!array_key_exists($record->dataid, $fields)) {
                    if ($fs = $DB->get_records('dataform_fields', array('dataid'=>$record->dataid))) {
                        $fields[$record->dataid] = array_keys($fs);
                    } else {
                        $fields[$record->dataid] = array();
                    }
                }
                foreach($fields[$record->dataid] as $fieldid) {
                    fulldelete("$CFG->dataroot/$data->courseid/moddata/dataform/$record->dataid/$fieldid/$record->id");
                }
                $notenrolled[$record->userid] = true;
            }
            rs_close($rs);
            $status[] = array('component'=>$componentstr, 'item'=>get_string('deletenotenrolled', 'dataform'), 'error'=>false);
        }
    }

    // remove all ratings
    if (!empty($data->reset_dataform_ratings)) {
        if ($datas = $DB->get_records_sql($alldatassql, array($data->courseid))) {
            foreach ($datas as $dataid=>$unused) {
                if (!$cm = get_coursemodule_from_instance('dataform', $dataid)) {
                    continue;
                }
                $datacontext = context_module::instance($cm->id);

                $ratingdeloptions->contextid = $datacontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            dataform_reset_gradebook($data->courseid);
        }

        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallratings'), 'error'=>false);
    }

    // remove all comments
    if (!empty($data->reset_dataform_comments)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='entry'", array($data->courseid));
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallcomments'), 'error'=>false);
    }

    // updating dates - shift may be negative too
    if ($data->timeshift) {
        shift_course_mod_dates('dataform', array('timeavailable', 'timedue'), $data->timeshift, $data->courseid);
        $status[] = array('component'=>$componentstr, 'item'=>get_string('datechanged'), 'error'=>false);
    }

    return $status;
}

/**
 * Returns all other caps used in module
 *
 * @return array
 */
function dataform_get_extra_capabilities() {
    return array('moodle/site:accessallgroups',
                'moodle/site:viewfullnames',
                'moodle/rating:view',
                'moodle/rating:viewany',
                'moodle/rating:viewall',
                'moodle/rating:rate',
                'moodle/comment:view',
                'moodle/comment:post',
                'moodle/comment:delete');
}

/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function dataform_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
        case FEATURE_MOD_INTRO:               return true;
        case FEATURE_SHOW_DESCRIPTION:        return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        //case FEATURE_GRADE_HAS_GRADE:         return true;
        //case FEATURE_ADVANCED_GRADING:        return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;

        default: return null;
    }
}

/**
 * Lists all browsable file areas
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @return array
 */
function dataform_get_file_areas($course, $cm, $context) {
    $areas = array();
    return $areas;
}

/**
 * Serves the dataform attachments. Implements needed access control ;-)
 *
 * @param object $course
 * @param object $cm
 * @param object $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @return bool false if file not found, does not return if found - justsend the file
 */
function mod_dataform_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload) {
    global $CFG, $DB, $USER;

    // FIELD CONTENT files
    if ($filearea === 'content' and $context->contextlevel == CONTEXT_MODULE) {

        $contentid = (int)array_shift($args);

        if (!$content = $DB->get_record('dataform_contents', array('id'=>$contentid))) {
            return false;
        }

        if (!$field = $DB->get_record('dataform_fields', array('id'=>$content->fieldid))) {
            return false;
        }

        // nanogong ugly hack
        if ($field->type != 'nanogong') {
            if (empty($USER->id)) {
                return false;
            }
        
            require_course_login($course, true, $cm);
        }
        

        if (!$entry = $DB->get_record('dataform_entries', array('id'=>$content->entryid))) {
            return false;
        }

        if (!$dataform = $DB->get_record('dataform', array('id'=>$field->dataid))) {
            return false;
        }

        if ($dataform->id != $cm->instance) {
            // hacker attempt - context does not match the contentid
            return false;
        }

        //check if approved
        if ($dataform->approval and !has_capability('mod/dataform:approve', $context) and !$entry->approved and $USER->id != $entry->userid) {
            return false;
        }

        // group access
        if ($entry->groupid) {
            $groupmode = groups_get_activity_groupmode($cm, $course);
            if ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context)) {
                if (!groups_is_member($record->groupid)) {
                    return false;
                }
            }
        }

        // Separate participants
        $groupmode = isset($groupmode) ? $groupmode : groups_get_activity_groupmode($cm, $course);
        if ($groupmode == -1) {
            if (empty($USER->id)) {
                return false;
            }
            if ($USER->id != $entry->userid and !has_capability('mod/dataform:manageentries', $context)) {
                return false;
            }
        }

        // TODO
        //require_once("field/$field->type/field_class.php");
        //$fieldclass = "dataformfield_$field->type";
        //if (!$fieldclass::file_ok($relativepath)) {
        //    return false;
        //}

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_dataform/content/$contentid/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }
        // finally send the file
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

    // VIEW TEMPLATE files
    if (strpos($filearea, 'view') !== false and $context->contextlevel == CONTEXT_MODULE) {
        require_course_login($course, true, $cm);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_dataform/$filearea/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

    // PDF VIEW files
    $viewpdfareas = array('view_pdfframe', 'view_pdfwmark', 'view_pdfcert');
    if (in_array($filearea, $viewpdfareas) and $context->contextlevel == CONTEXT_MODULE) {
        require_course_login($course, true, $cm);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_dataform/$filearea/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

    // PRESET files
    if (($filearea === 'course_presets' or $filearea === 'site_presets')) {
//                and $context->contextlevel == CONTEXT_MODULE) {
        require_course_login($course, true, $cm);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_dataform/$filearea/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

    if (($filearea === 'js' or $filearea === 'css')) {
//                and $context->contextlevel == CONTEXT_MODULE) {
        require_course_login($course, true, $cm);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_dataform/$filearea/$relativepath";

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

    if (strpos($filearea, 'actor-') === 0 and $context->contextlevel == CONTEXT_MODULE) {

        require_course_login($course, true, $cm);

        $itemid = (int)array_shift($args);

        $relativepath = implode('/', $args);
        $fullpath = "/$context->id/mod_dataform/$filearea/$itemid/$relativepath";

        //require_once("field/$field->type/field_class.php");
        //$fieldclass = "dataformfield_$field->type";
        //if (!$fieldclass::file_ok($relativepath)) {
        //    return false;
        //}

        $fs = get_file_storage();
        if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
            return false;
        }

        // finally send the file
        send_stored_file($file, 0, 0, true); // download MUST be forced - security!
    }

    return false;
}

/**
 *
 */
function dataform_extend_navigation($navigation, $course, $module, $cm) {
}

/**
 * Adds module specific settings to the settings block
 *
 * @param settings_navigation $settings The settings navigation object
 * @param navigation_node $datanode The node to add module settings to
 */
function dataform_extend_settings_navigation(settings_navigation $settings, navigation_node $dfnode) {
    global $PAGE, $USER;
    
    $templatesmanager = has_capability('mod/dataform:managetemplates', $PAGE->cm->context);
    $entriesmanager = has_capability('mod/dataform:manageentries', $PAGE->cm->context);

    // delete
    if ($templatesmanager) {
        $dfnode->add(get_string('renew', 'dataform'), new moodle_url('/mod/dataform/view.php', array('id' => $PAGE->cm->id, 'renew' => 1, 'sesskey' => sesskey())));    
        $dfnode->add(get_string('delete'), new moodle_url('/course/mod.php', array('delete' => $PAGE->cm->id, 'sesskey' => sesskey())));    
    }

    // index
    $dfnode->add(get_string('index', 'dataform'), new moodle_url('/mod/dataform/index.php', array('id' => $PAGE->course->id)));    

    // notifications
    if (isloggedin() and !isguestuser()) {
        $dfnode->add(get_string('messaging', 'message'), new moodle_url('/message/edit.php', array('id' => $USER->id, 'course' => $PAGE->course->id, 'context' => $PAGE->context->id)));    
    }
    
    // manage
    if ($templatesmanager or $entriesmanager) {
        $manage = $dfnode->add(get_string('manage', 'dataform'));
        if ($templatesmanager) {
            $manage->add(get_string('views', 'dataform'), new moodle_url('/mod/dataform/view/index.php', array('id' => $PAGE->cm->id)));
            $manage->add(get_string('fields', 'dataform'), new moodle_url('/mod/dataform/field/index.php', array('id' => $PAGE->cm->id)));
            $manage->add(get_string('filters', 'dataform'), new moodle_url('/mod/dataform/filter/index.php', array('id' => $PAGE->cm->id)));
            $manage->add(get_string('rules', 'dataform'), new moodle_url('/mod/dataform/rule/index.php', array('id' => $PAGE->cm->id)));
            $manage->add(get_string('tools', 'dataform'), new moodle_url('/mod/dataform/tool/index.php', array('id' => $PAGE->cm->id)));
            $manage->add(get_string('jsinclude', 'dataform'), new moodle_url('/mod/dataform/js.php', array('id' => $PAGE->cm->id, 'jsedit' => 1)));
            $manage->add(get_string('cssinclude', 'dataform'), new moodle_url('/mod/dataform/css.php', array('id' => $PAGE->cm->id, 'cssedit' => 1)));
            $manage->add(get_string('presets', 'dataform'), new moodle_url('/mod/dataform/preset/index.php', array('id' => $PAGE->cm->id)));
        }
        $manage->add(get_string('import', 'dataform'), new moodle_url('/mod/dataform/import.php', array('id' => $PAGE->cm->id)));
    }

}

//------------------------------------------------------------
// Info
//------------------------------------------------------------

/**
 * returns a list of participants of this dataform
 */
function dataform_get_participants($dataid) {
    global $DB;

    $params = array('dataid' => $dataid);

    $sql = "SELECT DISTINCT u.id 
              FROM {user} u,
                   {dataform_entries} e
             WHERE e.dataid = :dataid AND
                   u.id = e.userid";
    $entries = $DB->get_records_sql($sql, $params);

    $sql = "SELECT DISTINCT u.id 
              FROM {user} u,
                   {dataform_entries} e,
                   {comments} c
             WHERE e.dataid = ? AND
                   u.id = e.userid AND
                   e.id = c.itemid AND
                   c.commentarea = 'entry'";
    $comments = $DB->get_records_sql($sql, $params);

    $sql = "SELECT DISTINCT u.id 
              FROM {user} u,
                   {dataform_entries} e,
                   {ratings} r
             WHERE e.dataid = ? AND
                   u.id = e.userid AND
                   e.id = r.itemid AND
                   r.component = 'mod_dataform' AND
                   (r.ratingarea = 'entry' OR
                   r.ratingarea = 'activity')";
    $ratings = $DB->get_records_sql($sql, $params);

    $participants = array();

    if ($entries) {
        foreach ($entries as $entry) {
            $participants[$entry->id] = $entry;
        }
    }
    if ($comments) {
        foreach ($comments as $comment) {
            $participants[$comment->id] = $comment;
        }
    }
    if ($ratings) {
        foreach ($ratings as $rating) {
            $participants[$rating->id] = $rating;
        }
    }
    return $participants;
}

/**
 * returns a summary of dataform activity of this user
 */
function dataform_user_outline($course, $user, $mod, $data) {
    global $DB, $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    $sqlparams = array('dataid' => $data->id, 'userid' => $user->id);
    if ($countrecords = $DB->count_records('dataform_entries', $sqlparams)) {
        $result = new object();
        $result->info = get_string('entriescount', 'dataform', $countrecords);
        $lastrecordset = $DB->get_records(
            'dataform_entries',
            $sqlparams,
            'timemodified DESC',
            'id,timemodified',
            0,
            1
        );
        $lastrecord = reset($lastrecordset);
        $result->time = $lastrecord->timemodified;
        if ($grade) {
            $result->info .= ', ' . get_string('grade') . ': ' . $grade->str_long_grade;
        }
        return $result;
    } else if ($grade) {
        $result = new object();
        $result->info = get_string('grade') . ': ' . $grade->str_long_grade;
        $result->time = $grade->dategraded;
        return $result;
    }
    return NULL;
}

/**
 * TODO Prints all the records uploaded by this user
 */
function dataform_user_complete($course, $user, $mod, $data) {
    global $DB, $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo '<p>'.get_string('grade').': '.$grade->str_long_grade.'</p>';
        if ($grade->str_feedback) {
            echo '<p>'.get_string('feedback').': '.$grade->str_feedback.'</p>';
        }
    }
    $sqlparams = array('dataid' => $data->id, 'userid' => $user->id);
    if ($countrecords = $DB->count_records('dataform_entries', $sqlparams)) {
        // TODO get the default view add a filter for user only and display
    }
}

//------------------------------------------------------------
// Participantion Reports
//------------------------------------------------------------

/**
 */
function dataform_get_view_actions() {
    return array('view');
}

/**
 */
function dataform_get_post_actions() {
    return array('add','update','record delete');
}

//------------------------------------------------------------
// COMMENTS
//------------------------------------------------------------

/**
 * Running addtional permission check on plugin, for example, plugins
 * may have switch to turn on/off comments option, this callback will
 * affect UI display, not like pluginname_comment_validate only throw
 * exceptions.
 * Capability check has been done in comment->check_permissions(), we
 * don't need to do it again here.
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return array
 */
function dataform_comment_permissions($comment_param) {
    global $CFG;

    //require_once("$CFG->field/_comment/field_class.php");
    //$comment = new dataformfield__comment($comment_param->cm->instance);
    //return $comment->permissions($comment_param);
    return array('post'=>true, 'view'=>true);
}

/**
 * Validate comment parameter before perform other comments actions
 *
 * @param stdClass $comment_param {
 *              context  => context the context object
 *              courseid => int course id
 *              cm       => stdClass course module object
 *              commentarea => string comment area
 *              itemid      => int itemid
 * }
 * @return boolean
 */
function dataform_comment_validate($comment_param) {
    global $CFG;

    require_once("field/_comment/field_class.php");
    $comment = new dataformfield__comment($comment_param->cm->instance);
    return $comment->validation($comment_param);
}

/**
 *
 */
function dataform_comment_add($newcomment, $comment_param) {
    $df = new dataform($comment_param->cm->instance);
    $eventdata = (object) array('items' => $newcomment);
    $df->events_trigger("commentadded", $eventdata);
}

//------------------------------------------------------------
// Grading
//------------------------------------------------------------

/**
 * Return rating related permissions
 *
 * @param string $contextid the context id
 * @param string $component the component to get rating permissions for
 * @param string $ratingarea the rating area to get permissions for
 * @return array an associative array of the user's rating permissions
 */
function dataform_rating_permissions($contextid, $component, $ratingarea) {
    $context = context::instance_by_id($contextid, MUST_EXIST);
    if ($component == 'mod_dataform' and ($ratingarea == 'entry' or $ratingarea == 'activity')) {
        return array(
            'view'    => has_capability('mod/dataform:ratingsview',$context),
            'viewany' => has_capability('mod/dataform:ratingsviewany',$context),
            'viewall' => has_capability('mod/dataform:ratingsviewall',$context),
            'rate'    => has_capability('mod/dataform:rate',$context)
        );
    }
    return null;
}

/**
 * Validates a submitted rating
 * @param array $params submitted data
 *            context => object the context in which the rated items exists [required]
 *            ratingarea => string 'entry' or 'activity' [required]
 *            itemid => int the ID of the object being rated
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [required]
 * @return boolean true if the rating is valid. Will throw rating_exception if not
 */
function dataform_rating_validate($params) {
    require_once("mod_class.php");
    require_once("field/_rating/field_class.php");
    $df = new dataform(null, $params['context']->instanceid);
    $rating = $df->get_field_from_id(dataformfield__rating::_RATING);
    return $rating->validation($params);
}

/**
 * Return grade for given user or all users.
 * @return array array of grades, false if none
 */
function dataform_get_user_grades($data, $userid = 0) {
    global $CFG;

    require_once("$CFG->dirroot/rating/lib.php");

    $options = new object();
    $options->component = 'mod_dataform';
    if ($data->grade and !$data->grademethod) {
        $options->ratingarea = 'activity';
        $options->aggregationmethod = RATING_AGGREGATE_MAXIMUM;

        $options->itemtable = 'user';
        $options->itemtableusercolumn = 'id';

    } else {
        $options->ratingarea = 'entry';
        $options->aggregationmethod = $data->grademethod;

        $options->itemtable = 'dataform_entries';
        $options->itemtableusercolumn = 'userid';

    }
    $options->modulename = 'dataform';
    $options->moduleid   = $data->id;
    $options->userid = $userid;
    $options->scaleid = $data->grade;
    
    $rm = new rating_manager();
    return $rm->get_user_grades($options);
}

/**
 * Update grades by firing grade_updated event
 * @param object $data null means all databases
 * @param int $userid specific user only, 0 mean all
 * @param bool $nullifnone
 * @param array $grades
 */
function dataform_update_grades($data=null, $userid=0, $nullifnone=true, $grades=null) {
    global $CFG, $DB;
    require_once("$CFG->libdir/gradelib.php");

    if ($data != null) {
        if ($data->grade) {
            if ($grades or $grades = dataform_get_user_grades($data, $userid)) {
                dataform_grade_item_update($data, $grades);

            } else if ($userid and $nullifnone) {
                $grade = new object();
                $grade->userid   = $userid;
                $grade->rawgrade = NULL;
                dataform_grade_item_update($data, $grade);

            } else {
                dataform_grade_item_update($data);
            }
        } else {
            dataform_grade_item_delete($data);
        }
    }
}

/**
 * Update all grades in gradebook.
 *
 * @global object
 */
function dataform_upgrade_grades() {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {dataform} d, {course_modules} cm, {modules} m
             WHERE m.name='dataform' AND m.id=cm.module AND cm.instance=d.id";
    $count = $DB->count_records_sql($sql);

    $sql = "SELECT d.*, cm.idnumber AS cmidnumber, d.course AS courseid
              FROM {dataform} d, {course_modules} cm, {modules} m
             WHERE m.name='dataform' AND m.id=cm.module AND cm.instance=d.id";
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        // too much debug output
        $pbar = new progress_bar('dataupgradegrades', 500, true);
        $i=0;
        foreach ($rs as $data) {
            $i++;
            upgrade_set_timeout(60*5); // set up timeout, may also abort execution
            dataform_update_grades($data, 0, false);
            $pbar->update($i, $count, "Updating Dataform grades ($i/$count).");
        }
    }
    $rs->close();
}

/**
 * Update/create grade item for given dataform
 * @param object $data object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function dataform_grade_item_update($data, $grades=NULL) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $params = array(
        'itemname'=>$data->name,
        'idnumber'=>$data->cmidnumber
    );

    if (!$data->grade) {
        $params['gradetype'] = GRADE_TYPE_NONE;

    } else if ($data->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $data->grade;
        $params['grademin']  = 0;

    } else if ($data->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$data->grade;
    }

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    return grade_update('mod/dataform', $data->course, 'mod', 'dataform', $data->id, 0, $grades, $params);
}

/**
 * Delete grade item for given data
 * @param object $data object
 * @return object grade_item
 */
function dataform_grade_item_delete($data) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    return grade_update('mod/dataform', $data->course, 'mod', 'dataform', $data->id, 0, NULL, array('deleted'=>1));
}


// NOTIFICATIONS //

/**
 * Function to be run periodically according to the moodle cron
 * Finds all entries that have yet to be mailed out, and mails them
 * out to designated recipients
 *
 * @global object
 * @global object
 * @global object
 * @uses CONTEXT_MODULE
 * @uses CONTEXT_COURSE
 * @uses SITEID
 * @uses FORMAT_PLAIN
 * @return void
 */
function dataform_cron_TODO() {
    global $CFG, $USER, $DB;

    $site = get_site();

    // all users that are subscribed to any post that needs sending
    $users = array();

    // status arrays
    $mailcount  = array();
    $errorcount = array();

    // caches
    $discussions     = array();
    $forums          = array();
    $courses         = array();
    $coursemodules   = array();
    $subscribedusers = array();


    // Posts older than 2 days will not be mailed.  This is to avoid the problem where
    // cron has not been running for a long time, and then suddenly people are flooded
    // with mail from the past few weeks or months
    $timenow   = time();
    $endtime   = $timenow - $CFG->maxeditingtime;
    $starttime = $endtime - 48 * 3600;   // Two days earlier

    if ($posts = forum_get_unmailed_posts($starttime, $endtime, $timenow)) {
        // Mark them all now as being mailed.  It's unlikely but possible there
        // might be an error later so that a post is NOT actually mailed out,
        // but since mail isn't crucial, we can accept this risk.  Doing it now
        // prevents the risk of duplicated mails, which is a worse problem.

        if (!forum_mark_old_posts_as_mailed($endtime)) {
            mtrace('Errors occurred while trying to mark some posts as being mailed.');
            return false;  // Don't continue trying to mail them, in case we are in a cron loop
        }

        // checking post validity, and adding users to loop through later
        foreach ($posts as $pid => $post) {

            $discussionid = $post->discussion;
            if (!isset($discussions[$discussionid])) {
                if ($discussion = $DB->get_record('forum_discussions', array('id'=> $post->discussion))) {
                    $discussions[$discussionid] = $discussion;
                } else {
                    mtrace('Could not find discussion '.$discussionid);
                    unset($posts[$pid]);
                    continue;
                }
            }
            $forumid = $discussions[$discussionid]->forum;
            if (!isset($forums[$forumid])) {
                if ($forum = $DB->get_record('forum', array('id' => $forumid))) {
                    $forums[$forumid] = $forum;
                } else {
                    mtrace('Could not find forum '.$forumid);
                    unset($posts[$pid]);
                    continue;
                }
            }
            $courseid = $forums[$forumid]->course;
            if (!isset($courses[$courseid])) {
                if ($course = $DB->get_record('course', array('id' => $courseid))) {
                    $courses[$courseid] = $course;
                } else {
                    mtrace('Could not find course '.$courseid);
                    unset($posts[$pid]);
                    continue;
                }
            }
            if (!isset($coursemodules[$forumid])) {
                if ($cm = get_coursemodule_from_instance('forum', $forumid, $courseid)) {
                    $coursemodules[$forumid] = $cm;
                } else {
                    mtrace('Could not find course module for forum '.$forumid);
                    unset($posts[$pid]);
                    continue;
                }
            }


            // caching subscribed users of each forum
            if (!isset($subscribedusers[$forumid])) {
                $modcontext = context_module::instance($coursemodules[$forumid]->id);
                if ($subusers = forum_subscribed_users($courses[$courseid], $forums[$forumid], 0, $modcontext, "u.*")) {
                    foreach ($subusers as $postuser) {
                        unset($postuser->description); // not necessary
                        // this user is subscribed to this forum
                        $subscribedusers[$forumid][$postuser->id] = $postuser->id;
                        // this user is a user we have to process later
                        $users[$postuser->id] = $postuser;
                    }
                    unset($subusers); // release memory
                }
            }

            $mailcount[$pid] = 0;
            $errorcount[$pid] = 0;
        }
    }

    if ($users && $posts) {

        $urlinfo = parse_url($CFG->wwwroot);
        $hostname = $urlinfo['host'];

        foreach ($users as $userto) {

            @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

            // set this so that the capabilities are cached, and environment matches receiving user
            cron_setup_user($userto);

            mtrace('Processing user '.$userto->id);

            // init caches
            $userto->viewfullnames = array();
            $userto->canpost       = array();
            $userto->markposts     = array();

            // reset the caches
            foreach ($coursemodules as $forumid=>$unused) {
                $coursemodules[$forumid]->cache       = new stdClass();
                $coursemodules[$forumid]->cache->caps = array();
                unset($coursemodules[$forumid]->uservisible);
            }

            foreach ($posts as $pid => $post) {

                // Set up the environment for the post, discussion, forum, course
                $discussion = $discussions[$post->discussion];
                $forum      = $forums[$discussion->forum];
                $course     = $courses[$forum->course];
                $cm         =& $coursemodules[$forum->id];

                // Do some checks  to see if we can bail out now
                // Only active enrolled users are in the list of subscribers
                if (!isset($subscribedusers[$forum->id][$userto->id])) {
                    continue; // user does not subscribe to this forum
                }

                // Don't send email if the forum is Q&A and the user has not posted
                if ($forum->type == 'qanda' && !forum_get_user_posted_time($discussion->id, $userto->id)) {
                    mtrace('Did not email '.$userto->id.' because user has not posted in discussion');
                    continue;
                }

                // Get info about the sending user
                if (array_key_exists($post->userid, $users)) { // we might know him/her already
                    $userfrom = $users[$post->userid];
                } else if ($userfrom = $DB->get_record('user', array('id' => $post->userid))) {
                    unset($userfrom->description); // not necessary
                    $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                } else {
                    mtrace('Could not find user '.$post->userid);
                    continue;
                }

                //if we want to check that userto and userfrom are not the same person this is probably the spot to do it

                // setup global $COURSE properly - needed for roles and languages
                cron_setup_user($userto, $course);

                // Fill caches
                if (!isset($userto->viewfullnames[$forum->id])) {
                    $modcontext = context_module::instance($cm->id);
                    $userto->viewfullnames[$forum->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                }
                if (!isset($userto->canpost[$discussion->id])) {
                    $modcontext = context_module::instance($cm->id);
                    $userto->canpost[$discussion->id] = forum_user_can_post($forum, $discussion, $userto, $cm, $course, $modcontext);
                }
                if (!isset($userfrom->groups[$forum->id])) {
                    if (!isset($userfrom->groups)) {
                        $userfrom->groups = array();
                        $users[$userfrom->id]->groups = array();
                    }
                    $userfrom->groups[$forum->id] = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
                    $users[$userfrom->id]->groups[$forum->id] = $userfrom->groups[$forum->id];
                }

                // Make sure groups allow this user to see this email
                if ($discussion->groupid > 0 and $groupmode = groups_get_activity_groupmode($cm, $course)) {   // Groups are being used
                    if (!groups_group_exists($discussion->groupid)) { // Can't find group
                        continue;                           // Be safe and don't send it to anyone
                    }

                    if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $modcontext)) {
                        // do not send posts from other groups when in SEPARATEGROUPS or VISIBLEGROUPS
                        continue;
                    }
                }

                // Make sure we're allowed to see it...
                if (!forum_user_can_see_post($forum, $discussion, $post, NULL, $cm)) {
                    mtrace('user '.$userto->id. ' can not see '.$post->id);
                    continue;
                }

                // OK so we need to send the email.

                // Does the user want this post in a digest?  If so postpone it for now.
                if ($userto->maildigest > 0) {
                    // This user wants the mails to be in digest form
                    $queue = new stdClass();
                    $queue->userid       = $userto->id;
                    $queue->discussionid = $discussion->id;
                    $queue->postid       = $post->id;
                    $queue->timemodified = $post->created;
                    $DB->insert_record('forum_queue', $queue);
                    continue;
                }


                // Prepare to actually send the post now, and build up the content

                $cleanforumname = str_replace('"', "'", strip_tags(format_string($forum->name)));

                $userfrom->customheaders = array (  // Headers to make emails easier to track
                           'Precedence: Bulk',
                           'List-Id: "'.$cleanforumname.'" <moodleforum'.$forum->id.'@'.$hostname.'>',
                           'List-Help: '.$CFG->wwwroot.'/mod/forum/view.php?f='.$forum->id,
                           'Message-ID: <moodlepost'.$post->id.'@'.$hostname.'>',
                           'X-Course-Id: '.$course->id,
                           'X-Course-Name: '.format_string($course->fullname, true)
                );

                if ($post->parent) {  // This post is a reply, so add headers for threading (see MDL-22551)
                    $userfrom->customheaders[] = 'In-Reply-To: <moodlepost'.$post->parent.'@'.$hostname.'>';
                    $userfrom->customheaders[] = 'References: <moodlepost'.$post->parent.'@'.$hostname.'>';
                }

                $shortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));

                $postsubject = "$shortname: ".format_string($post->subject,true);
                $posttext = forum_make_mail_text($course, $cm, $forum, $discussion, $post, $userfrom, $userto);
                $posthtml = forum_make_mail_html($course, $cm, $forum, $discussion, $post, $userfrom, $userto);

                // Send the post now!

                mtrace('Sending ', '');

                $eventdata = new stdClass();
                $eventdata->component        = 'mod_forum';
                $eventdata->name             = 'posts';
                $eventdata->userfrom         = $userfrom;
                $eventdata->userto           = $userto;
                $eventdata->subject          = $postsubject;
                $eventdata->fullmessage      = $posttext;
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml  = $posthtml;
                $eventdata->notification = 1;

                $smallmessagestrings = new stdClass();
                $smallmessagestrings->user = fullname($userfrom);
                $smallmessagestrings->forumname = "$shortname: ".format_string($forum->name,true).": ".$discussion->name;
                $smallmessagestrings->message = $post->message;
                //make sure strings are in message recipients language
                $eventdata->smallmessage = get_string_manager()->get_string('smallmessage', 'forum', $smallmessagestrings, $userto->lang);

                $eventdata->contexturl = "{$CFG->wwwroot}/mod/forum/discuss.php?d={$discussion->id}#p{$post->id}";
                $eventdata->contexturlname = $discussion->name;

                $mailresult = message_send($eventdata);
                if (!$mailresult){
                    mtrace("Error: mod/forum/lib.php forum_cron(): Could not send out mail for id $post->id to user $userto->id".
                         " ($userto->email) .. not trying again.");
                    add_to_log($course->id, 'forum', 'mail error', "discuss.php?d=$discussion->id#p$post->id",
                               substr(format_string($post->subject,true),0,30), $cm->id, $userto->id);
                    $errorcount[$post->id]++;
                } else {
                    $mailcount[$post->id]++;

                // Mark post as read if forum_usermarksread is set off
                    if (!$CFG->forum_usermarksread) {
                        $userto->markposts[$post->id] = $post->id;
                    }
                }

                mtrace('post '.$post->id. ': '.$post->subject);
            }

            // mark processed posts as read
            forum_tp_mark_posts_read($userto, $userto->markposts);
        }
    }

    if ($posts) {
        foreach ($posts as $post) {
            mtrace($mailcount[$post->id]." users were sent post $post->id, '$post->subject'");
            if ($errorcount[$post->id]) {
                $DB->set_field("forum_posts", "mailed", "2", array("id" => "$post->id"));
            }
        }
    }

    // release some memory
    unset($subscribedusers);
    unset($mailcount);
    unset($errorcount);

    cron_setup_user();

    $sitetimezone = $CFG->timezone;

    // Now see if there are any digest mails waiting to be sent, and if we should send them

    mtrace('Starting digest processing...');

    @set_time_limit(300); // terminate if not able to fetch all digests in 5 minutes

    if (!isset($CFG->digestmailtimelast)) {    // To catch the first time
        set_config('digestmailtimelast', 0);
    }

    $timenow = time();
    $digesttime = usergetmidnight($timenow, $sitetimezone) + ($CFG->digestmailtime * 3600);

    // Delete any really old ones (normally there shouldn't be any)
    $weekago = $timenow - (7 * 24 * 3600);
    $DB->delete_records_select('forum_queue', "timemodified < ?", array($weekago));
    mtrace ('Cleaned old digest records');

    if ($CFG->digestmailtimelast < $digesttime and $timenow > $digesttime) {

        mtrace('Sending forum digests: '.userdate($timenow, '', $sitetimezone));

        $digestposts_rs = $DB->get_recordset_select('forum_queue', "timemodified < ?", array($digesttime));

        if ($digestposts_rs->valid()) {

            // We have work to do
            $usermailcount = 0;

            //caches - reuse the those filled before too
            $discussionposts = array();
            $userdiscussions = array();

            foreach ($digestposts_rs as $digestpost) {
                if (!isset($users[$digestpost->userid])) {
                    if ($user = $DB->get_record('user', array('id' => $digestpost->userid))) {
                        $users[$digestpost->userid] = $user;
                    } else {
                        continue;
                    }
                }
                $postuser = $users[$digestpost->userid];

                if (!isset($posts[$digestpost->postid])) {
                    if ($post = $DB->get_record('forum_posts', array('id' => $digestpost->postid))) {
                        $posts[$digestpost->postid] = $post;
                    } else {
                        continue;
                    }
                }
                $discussionid = $digestpost->discussionid;
                if (!isset($discussions[$discussionid])) {
                    if ($discussion = $DB->get_record('forum_discussions', array('id' => $discussionid))) {
                        $discussions[$discussionid] = $discussion;
                    } else {
                        continue;
                    }
                }
                $forumid = $discussions[$discussionid]->forum;
                if (!isset($forums[$forumid])) {
                    if ($forum = $DB->get_record('forum', array('id' => $forumid))) {
                        $forums[$forumid] = $forum;
                    } else {
                        continue;
                    }
                }

                $courseid = $forums[$forumid]->course;
                if (!isset($courses[$courseid])) {
                    if ($course = $DB->get_record('course', array('id' => $courseid))) {
                        $courses[$courseid] = $course;
                    } else {
                        continue;
                    }
                }

                if (!isset($coursemodules[$forumid])) {
                    if ($cm = get_coursemodule_from_instance('forum', $forumid, $courseid)) {
                        $coursemodules[$forumid] = $cm;
                    } else {
                        continue;
                    }
                }
                $userdiscussions[$digestpost->userid][$digestpost->discussionid] = $digestpost->discussionid;
                $discussionposts[$digestpost->discussionid][$digestpost->postid] = $digestpost->postid;
            }
            $digestposts_rs->close(); /// Finished iteration, let's close the resultset

            // Data collected, start sending out emails to each user
            foreach ($userdiscussions as $userid => $thesediscussions) {

                @set_time_limit(120); // terminate if processing of any account takes longer than 2 minutes

                cron_setup_user();

                mtrace(get_string('processingdigest', 'forum', $userid), '... ');

                // First of all delete all the queue entries for this user
                $DB->delete_records_select('forum_queue', "userid = ? AND timemodified < ?", array($userid, $digesttime));
                $userto = $users[$userid];

                // Override the language and timezone of the "current" user, so that
                // mail is customised for the receiver.
                cron_setup_user($userto);

                // init caches
                $userto->viewfullnames = array();
                $userto->canpost       = array();
                $userto->markposts     = array();

                $postsubject = get_string('digestmailsubject', 'forum', format_string($site->shortname, true));

                $headerdata = new stdClass();
                $headerdata->sitename = format_string($site->fullname, true);
                $headerdata->userprefs = $CFG->wwwroot.'/user/edit.php?id='.$userid.'&amp;course='.$site->id;

                $posttext = get_string('digestmailheader', 'forum', $headerdata)."\n\n";
                $headerdata->userprefs = '<a target="_blank" href="'.$headerdata->userprefs.'">'.get_string('digestmailprefs', 'forum').'</a>';

                $posthtml = "<head>";
/*                foreach ($CFG->stylesheets as $stylesheet) {
                    //TODO: MDL-21120
                    $posthtml .= '<link rel="stylesheet" type="text/css" href="'.$stylesheet.'" />'."\n";
                }*/
                $posthtml .= "</head>\n<body id=\"email\">\n";
                $posthtml .= '<p>'.get_string('digestmailheader', 'forum', $headerdata).'</p><br /><hr size="1" noshade="noshade" />';

                foreach ($thesediscussions as $discussionid) {

                    @set_time_limit(120);   // to be reset for each post

                    $discussion = $discussions[$discussionid];
                    $forum      = $forums[$discussion->forum];
                    $course     = $courses[$forum->course];
                    $cm         = $coursemodules[$forum->id];

                    //override language
                    cron_setup_user($userto, $course);

                    // Fill caches
                    if (!isset($userto->viewfullnames[$forum->id])) {
                        $modcontext = context_module::instance($cm->id);
                        $userto->viewfullnames[$forum->id] = has_capability('moodle/site:viewfullnames', $modcontext);
                    }
                    if (!isset($userto->canpost[$discussion->id])) {
                        $modcontext = context_module::instance($cm->id);
                        $userto->canpost[$discussion->id] = forum_user_can_post($forum, $discussion, $userto, $cm, $course, $modcontext);
                    }

                    $strforums      = get_string('forums', 'forum');
                    $canunsubscribe = ! forum_is_forcesubscribed($forum);
                    $canreply       = $userto->canpost[$discussion->id];
                    $shortname = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));

                    $posttext .= "\n \n";
                    $posttext .= '=====================================================================';
                    $posttext .= "\n \n";
                    $posttext .= "$shortname -> $strforums -> ".format_string($forum->name,true);
                    if ($discussion->name != $forum->name) {
                        $posttext  .= " -> ".format_string($discussion->name,true);
                    }
                    $posttext .= "\n";

                    $posthtml .= "<p><font face=\"sans-serif\">".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/course/view.php?id=$course->id\">$shortname</a> -> ".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/forum/index.php?id=$course->id\">$strforums</a> -> ".
                    "<a target=\"_blank\" href=\"$CFG->wwwroot/mod/forum/view.php?f=$forum->id\">".format_string($forum->name,true)."</a>";
                    if ($discussion->name == $forum->name) {
                        $posthtml .= "</font></p>";
                    } else {
                        $posthtml .= " -> <a target=\"_blank\" href=\"$CFG->wwwroot/mod/forum/discuss.php?d=$discussion->id\">".format_string($discussion->name,true)."</a></font></p>";
                    }
                    $posthtml .= '<p>';

                    $postsarray = $discussionposts[$discussionid];
                    sort($postsarray);

                    foreach ($postsarray as $postid) {
                        $post = $posts[$postid];

                        if (array_key_exists($post->userid, $users)) { // we might know him/her already
                            $userfrom = $users[$post->userid];
                        } else if ($userfrom = $DB->get_record('user', array('id' => $post->userid))) {
                            $users[$userfrom->id] = $userfrom; // fetch only once, we can add it to user list, it will be skipped anyway
                        } else {
                            mtrace('Could not find user '.$post->userid);
                            continue;
                        }

                        if (!isset($userfrom->groups[$forum->id])) {
                            if (!isset($userfrom->groups)) {
                                $userfrom->groups = array();
                                $users[$userfrom->id]->groups = array();
                            }
                            $userfrom->groups[$forum->id] = groups_get_all_groups($course->id, $userfrom->id, $cm->groupingid);
                            $users[$userfrom->id]->groups[$forum->id] = $userfrom->groups[$forum->id];
                        }

                        $userfrom->customheaders = array ("Precedence: Bulk");

                        if ($userto->maildigest == 2) {
                            // Subjects only
                            $by = new stdClass();
                            $by->name = fullname($userfrom);
                            $by->date = userdate($post->modified);
                            $posttext .= "\n".format_string($post->subject,true).' '.get_string("bynameondate", "forum", $by);
                            $posttext .= "\n---------------------------------------------------------------------";

                            $by->name = "<a target=\"_blank\" href=\"$CFG->wwwroot/user/view.php?id=$userfrom->id&amp;course=$course->id\">$by->name</a>";
                            $posthtml .= '<div><a target="_blank" href="'.$CFG->wwwroot.'/mod/forum/discuss.php?d='.$discussion->id.'#p'.$post->id.'">'.format_string($post->subject,true).'</a> '.get_string("bynameondate", "forum", $by).'</div>';

                        } else {
                            // The full treatment
                            $posttext .= forum_make_mail_text($course, $cm, $forum, $discussion, $post, $userfrom, $userto, true);
                            $posthtml .= forum_make_mail_post($course, $cm, $forum, $discussion, $post, $userfrom, $userto, false, $canreply, true, false);

                        // Create an array of postid's for this user to mark as read.
                            if (!$CFG->forum_usermarksread) {
                                $userto->markposts[$post->id] = $post->id;
                            }
                        }
                    }
                    if ($canunsubscribe) {
                        $posthtml .= "\n<div class='mdl-right'><font size=\"1\"><a href=\"$CFG->wwwroot/mod/forum/subscribe.php?id=$forum->id\">".get_string("unsubscribe", "forum")."</a></font></div>";
                    } else {
                        $posthtml .= "\n<div class='mdl-right'><font size=\"1\">".get_string("everyoneissubscribed", "forum")."</font></div>";
                    }
                    $posthtml .= '<hr size="1" noshade="noshade" /></p>';
                }
                $posthtml .= '</body>';

                if (empty($userto->mailformat) || $userto->mailformat != 1) {
                    // This user DOESN'T want to receive HTML
                    $posthtml = '';
                }

                $attachment = $attachname='';
                $usetrueaddress = true;
                //directly email forum digests rather than sending them via messaging
                $mailresult = email_to_user($userto, $site->shortname, $postsubject, $posttext, $posthtml, $attachment, $attachname, $usetrueaddress, $CFG->forum_replytouser);

                if (!$mailresult) {
                    mtrace("ERROR!");
                    echo "Error: mod/forum/cron.php: Could not send out digest mail to user $userto->id ($userto->email)... not trying again.\n";
                    add_to_log($course->id, 'forum', 'mail digest error', '', '', $cm->id, $userto->id);
                } else {
                    mtrace("success.");
                    $usermailcount++;

                    // Mark post as read if forum_usermarksread is set off
                    forum_tp_mark_posts_read($userto, $userto->markposts);
                }
            }
        }
    /// We have finishied all digest emails, update $CFG->digestmailtimelast
        set_config('digestmailtimelast', $timenow);
    }

    cron_setup_user();

    if (!empty($usermailcount)) {
        mtrace(get_string('digestsentusers', 'forum', $usermailcount));
    }

    if (!empty($CFG->forum_lastreadclean)) {
        $timenow = time();
        if ($CFG->forum_lastreadclean + (24*3600) < $timenow) {
            set_config('forum_lastreadclean', $timenow);
            mtrace('Removing old forum read tracking info...');
            forum_tp_clean_read_records();
        }
    } else {
        set_config('forum_lastreadclean', time());
    }


    return true;
}
