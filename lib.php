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
 * @copyright 2005 Moodle Pty Ltd http://moodle.com
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

/**
 * MOD FUNCTIONS WHICH ARE CALLED FROM OUTSIDE THE MODULE
 */

/**
 * Adds an instance of a dataform
 *
 * @global object
 * @param object $data
 * @return $int
 */
function dataform_add_instance($data) {
    global $CFG, $DB;
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

    dataform_grade_item_update($data);

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
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

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
    $mform->addElement('checkbox', 'reset_data', get_string('entriesdeleteall','dataform'));

    $mform->addElement('checkbox', 'reset_dataform_notenrolled', get_string('deletenotenrolled', 'dataform'));
    $mform->disabledIf('reset_dataform_notenrolled', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_ratings', get_string('deleteallratings'));
    $mform->disabledIf('reset_dataform_ratings', 'reset_data', 'checked');

    $mform->addElement('checkbox', 'reset_dataform_comments', get_string('deleteallcomments'));
    $mform->disabledIf('reset_dataform_comments', 'reset_data', 'checked');
}

/**
 * Course reset form defaults.
 * @return array
 */
function dataform_reset_course_form_defaults($course) {
    return array('reset_data'=>0, 'reset_dataform_ratings'=>1, 'reset_dataform_comments'=>1, 'reset_dataform_notenrolled'=>0);
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

    if ($datas = $DB->get_records_sql($sql, array($courseid))) {
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
    if (!empty($data->reset_data)) {
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='dataform_entry'", array($data->courseid));
        $DB->delete_records_select('dataform_contents', "entryid IN ($allrecordssql)", array($data->courseid));
        $DB->delete_records_select('dataform_entries', "dataid IN ($alldatassql)", array($data->courseid));

        if ($datas = $DB->get_records_sql($alldatassql, array($data->courseid))) {
            foreach ($datas as $dataid=>$unused) {
                fulldelete("$CFG->dataroot/$data->courseid/moddata/dataform/$dataid");

                if (!$cm = get_coursemodule_from_instance('dataform', $dataid)) {
                    continue;
                }
                $datacontext = get_context_instance(CONTEXT_MODULE, $cm->id);

                $ratingdeloptions->contextid = $datacontext->id;
                $rm->delete_ratings($ratingdeloptions);
            }
        }

        if (empty($data->reset_gradebook_grades)) {
            // remove all grades from gradebook
            dataform_reset_gradebook($data->courseid);
        }
        $status[] = array('component'=>$componentstr, 'item'=>get_string('deleteallentries', 'dataform'), 'error'=>false);
    }

    // remove entries by users not enrolled into course
    if (!empty($data->reset_dataform_notenrolled)) {
        $recordssql = "SELECT e.id, e.userid, e.dataid, u.id AS userexists, u.deleted AS userdeleted
                         FROM {dataform_entries} e
                              INNER JOIN {dataform} d ON e.dataid = d.id
                              LEFT OUTER JOIN {user} u ON e.userid = u.id
                        WHERE d.course = ? AND e.userid > 0";

        $course_context = get_context_instance(CONTEXT_COURSE, $data->courseid);
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
                $datacontext = get_context_instance(CONTEXT_MODULE, $cm->id);
                $ratingdeloptions->contextid = $datacontext->id;
                $ratingdeloptions->itemid = $record->id;
                $rm->delete_ratings($ratingdeloptions);

                $DB->delete_records('comments', array('itemid'=>$record->id, 'commentarea'=>'dataform_entry'));
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
                $datacontext = get_context_instance(CONTEXT_MODULE, $cm->id);

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
        $DB->delete_records_select('comments', "itemid IN ($allrecordssql) AND commentarea='dataform_entry'", array($data->courseid));
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
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_GRADE_OUTCOMES:          return true;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_RATE:                    return true;

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

        // TODO
        //require_once("field/$field->type/field_class.php");
        //$fieldclass = "dataform_field_$field->type";
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

    if (($filearea === 'course_packages' or $filearea === 'site_packages')) {
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
        //$fieldclass = "dataform_field_$field->type";
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
function dataform_extend_settings_navigation(settings_navigation $settings, navigation_node $dataformnode) {
    global $PAGE;
    
    if (has_capability('mod/dataform:managetemplates', $PAGE->cm->context)) {
        $dataformnode->add(get_string('delete'),
                            new moodle_url('/course/mod.php', array('delete' => $PAGE->cm->id,
                                                                   'sesskey' => sesskey())));
    
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

    $sql = "SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {dataform_entries} e
             WHERE e.dataid = :dataid AND
                   u.id = e.userid";
    $entries = $DB->get_records_sql($sql, $params);

    $sql = "SELECT DISTINCT u.id, u.id
              FROM {user} u,
                   {dataform_entries} e,
                   {comments} c
             WHERE e.dataid = ? AND
                   u.id = e.userid AND
                   e.id = c.itemid AND
                   c.commentarea = 'dataform_entry'";
    $comments = $DB->get_records_sql($sql, $params);

    $sql = "SELECT DISTINCT u.id, u.id
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
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (empty($grades->items[0]->grades)) {
        $grade = false;
    } else {
        $grade = reset($grades->items[0]->grades);
    }

    if ($countrecords = count_records('dataform_entries', 'dataid', $data->id, 'userid', $user->id)) {
        $result = new object();
        $result->info = get_string('entriescount', 'dataform', $countrecords);
        $lastrecord   = $DB->get_record_sql('SELECT id,timemodified FROM '.$CFG->prefix.'dataform_entries
                                         WHERE dataid = '.$data->id.' AND userid = '.$user->id.'
                                      ORDER BY timemodified DESC', true);
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
 * Prints all the records uploaded by this user
 */
function dataform_user_complete($course, $user, $mod, $data) {
    global $CFG;
    require_once("$CFG->libdir/gradelib.php");

    $grades = grade_get_grades($course->id, 'mod', 'dataform', $data->id, $user->id);
    if (!empty($grades->items[0]->grades)) {
        $grade = reset($grades->items[0]->grades);
        echo '<p>'.get_string('grade').': '.$grade->str_long_grade.'</p>';
        if ($grade->str_feedback) {
            echo '<p>'.get_string('feedback').': '.$grade->str_feedback.'</p>';
        }
    }
    if ($records = $DB->get_records_select('dataform_entries', 'dataid = '.$data->id.' AND userid = '.$user->id,
                                                      'timemodified DESC')) {
        dataform_print_template('singletemplate', $records, $data);
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
    //$comment = new dataform_field__comment($comment_param->cm->instance);
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

    //require_once("field/_comment/field_class.php");
    //$comment = new dataform_field__comment($comment_param->cm->instance);
    //return $comment->validate($comment_param);
    return true;
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
    $context = get_context_instance_by_id($contextid, MUST_EXIST);
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
    global $CFG, $DB, $USER;
    
    return true;

    // Check the component is mod_dataform
    if ($params['component'] != 'mod_dataform') {
        throw new rating_exception('invalidcomponent');
    }

    // you can't rate your own entries unless you can manage ratings
    if (!has_capability('mod/dataform:manageratings', $params['context']) and $params['rateduserid'] == $USER->id) {
        throw new rating_exception('nopermissiontorate');
    }

    $datasql = "SELECT d.* e.grading FROM {dataform} d
                JOIN {dataform_entries} e ON e.dataid = d.id
                WHERE e.id = :itemid";
    $dataparams = array('itemid'=>$params['itemid']);
    if (!$data = $DB->get_record_sql($datasql, $dataparams)) {
        //item doesn't exist
        throw new rating_exception('invaliditemid');
    }

    if ($data->grade or $data->grademethod) {
        require_once("field/_grade/field_class.php");
        $rating = new dataform_field__grade($data);
    } else {
        require_once("field/_rating/field_class.php");
        $rating = new dataform_field__rating($data);
    }
    return $rating->validate($params);
}

/**
 * Return grade for given user or all users.
 * @return array array of grades, false if none
 */
function dataform_get_user_grades($data, $userid=0) {
    global $CFG;

    require_once($CFG->dirroot.'/rating/lib.php');

    $options = new object();
    $options->component = 'mod_dataform';
    if ($data->grade and !$data->grademethod) {
        $options->ratingarea = 'activity';
        $options->aggregationmethod = RATING_AGGREGATE_SUM;
    } else {
        $options->ratingarea = 'entry';
        $options->aggregationmethod = $data->grademethod;
    }
    $options->modulename = 'dataform';
    $options->moduleid   = $data->id;
    $options->userid = $userid;
    $options->scaleid = $data->grade;
    $options->itemtable = 'dataform_entries';
    $options->itemtableusercolumn = 'userid';

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
    require_once($CFG->libdir.'/gradelib.php');

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
                data_grade_item_update($data);
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
 * Update/create grade item for given data
 * @param object $data object with extra cmidnumber
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return object grade_item
 */
function dataform_grade_item_update($data, $grades=NULL) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname'=>$data->name, 'idnumber'=>$data->cmidnumber);

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
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/dataform', $data->course, 'mod', 'dataform', $data->id, 0, NULL, array('deleted'=>1));
}
