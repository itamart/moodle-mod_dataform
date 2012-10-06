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

/**
 * Define all the restore steps that will be used by the restore_dataform_activity_task
 */

/**
 * Structure step to restore one dataform activity
 */
class restore_dataform_activity_structure_step extends restore_activity_structure_step {

    protected $groupmode = 0;
    
    /**
     *
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo'); // restore content and user info (requires the backup users)

        
        $paths[] = new restore_path_element('dataform', '/activity/dataform');
        $paths[] = new restore_path_element('dataform_module', '/activity/dataform/module');
        $paths[] = new restore_path_element('dataform_field', '/activity/dataform/fields/field');
        $paths[] = new restore_path_element('dataform_filter', '/activity/dataform/filters/filter');
        $paths[] = new restore_path_element('dataform_view', '/activity/dataform/views/view');
        $paths[] = new restore_path_element('dataform_rule', '/activity/dataform/rules/rule');

        if ($userinfo) {
            $paths[] = new restore_path_element('dataform_entry', '/activity/dataform/entries/entry');
            $paths[] = new restore_path_element('dataform_content', '/activity/dataform/entries/entry/contents/content');
            $paths[] = new restore_path_element('dataform_rating', '/activity/dataform/entries/entry/ratings/rating');
            $paths[] = new restore_path_element('dataform_grade', '/activity/dataform/grades/grade');
/*
            $paths[] = new restore_path_element('dataform_comment', '/activity/dataform/entries/entry/comments/comment');
            $paths[] = new restore_path_element('dataform_gradecomment', '/activity/dataform/gradecomments/gradecomment');
*/
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    /**
     *
     */
    protected function process_dataform($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $data->timeavailable = $this->apply_date_offset($data->timeavailable);
        $data->timedue = $this->apply_date_offset($data->timedue);

        if ($data->grade < 0) { // scale found, get mapping
            $data->grade = -($this->get_mappingid('scale', abs($data->grade)));
        }

        if ($data->rating < 0) { // scale found, get mapping
            $data->rating = -($this->get_mappingid('scale', abs($data->rating)));
        }

        $newitemid = $this->task->get_activityid();
        
        if ($newitemid) { 
            $data->id = $newitemid;
            $DB->update_record('dataform', $data);
        } else {
            // insert the dataform record
            $newitemid = $DB->insert_record('dataform', $data);
        }
        $this->apply_activity_instance($newitemid);
    }

    /**
     * This must be invoked immediately after creating/updating the "module" activity record
     * and will adjust the new activity id (the instance) in various places
     * Overriding the parent method to handle restoring into the activity
     */
    protected function apply_activity_instance($newitemid) {
        global $DB;

        if ($newitemid == $this->task->get_activityid()) {
            // remap task module id
            $this->set_mapping('course_module', $this->task->get_old_moduleid(), $this->task->get_moduleid());
            // remap task context id
            $this->set_mapping('context', $this->task->get_old_contextid(), $this->task->get_contextid());
        } else {
            // Save activity id in task
            $this->task->set_activityid($newitemid); 
            // Apply the id to course_modules->instance
            $DB->set_field('course_modules', 'instance', $newitemid, array('id' => $this->task->get_moduleid()));
        }
        // Do the mapping for modulename, preparing it for files by oldcontext
        $oldid = $this->task->get_old_activityid();
        $this->set_mapping('dataform', $oldid, $newitemid, true);
    }

    /**
     *
     */
    protected function process_dataform_module($data) {
        global $DB;

        $data = (object)$data;
        // Adjust groupmode in course_modules->groupmode
        if (isset($data->groupmode)) {
            $DB->set_field('course_modules', 'groupmode', $data->groupmode, array('id' => $this->task->get_moduleid()));
        }
    }

    /**
     *
     */
    protected function process_dataform_field($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dataid = $this->get_new_parentid('dataform');

        // insert the dataform_fields record
        $newitemid = $DB->insert_record('dataform_fields', $data);
        $this->set_mapping('dataform_field', $oldid, $newitemid, true); // files by this item id
    }

    /**
     *
     */
    protected function process_dataform_filter($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dataid = $this->get_new_parentid('dataform');

        // adjust groupby field id
        if ($data->groupby > 0) {
            $data->groupby = $this->get_mappingid('dataform_field', $data->groupby);
        }
                    
        // adjust customsort field ids
        if ($data->customsort) {
            $customsort = unserialize($data->customsort);
            $sortfields = array();
            foreach ($customsort as $sortfield => $sortdir) {
                if ($sortfield > 0) {
                    $sortfields[$this->get_mappingid('dataform_field', $sortfield)] = $sortdir;
                } else {
                    $sortfields[$sortfield] = $sortdir;
                }
            }
            $data->customsort = serialize($sortfields);
        }
                        
        // adjust customsearch field ids
        if ($data->customsearch) {
            $customsearch = unserialize($data->customsearch);
            $searchfields = array();
            foreach ($customsearch as $searchfield => $options) {
                if ($searchfield > 0) {
                    $searchfields[$this->get_mappingid('dataform_field', $searchfield)] = $options;
                } else {
                    $searchfields[$searchfield] = $options;
                }
            }
            $data->customsearch = serialize($searchfields);
        }
        
        // insert the dataform_filters record
        $newitemid = $DB->insert_record('dataform_filters', $data);
        $this->set_mapping('dataform_filter', $oldid, $newitemid, false); // no files associated
    }

    /**
     *
     */
    protected function process_dataform_view($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dataid = $this->get_new_parentid('dataform');

        // adjust groupby field id
        if ($data->groupby > 0) {
            $data->groupby = $this->get_mappingid('dataform_field', $data->groupby);
        }

        // adjust view filter id
        if ($data->filter) {
            $data->filter = $this->get_mappingid('dataform_filter', $data->filter);
        }

        // adjust pattern field ids
        if ($data->patterns) {
            $patterns = unserialize($data->patterns);
            $newpatterns = array('view' => $patterns['view'], 'field' => array());
            foreach ($patterns['field'] as $fieldid => $tags) {
                if ($fieldid > 0) {
                    $newpatterns['field'][$this->get_mappingid('dataform_field', $fieldid)] = $tags;
                } else {
                    $newpatterns['field'][$fieldid] = $tags;
                }
            }
            $data->patterns = serialize($newpatterns);
        }
        
        // insert the dataform_views record
        $newitemid = $DB->insert_record('dataform_views', $data);
        $this->set_mapping('dataform_view', $oldid, $newitemid, true); // files by this item id
    }

    /**
     *
     */
    protected function process_dataform_rule($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dataid = $this->get_new_parentid('dataform');

        // insert the dataform_fields record
        $newitemid = $DB->insert_record('dataform_rules', $data);
        $this->set_mapping('dataform_rule', $oldid, $newitemid, false); // no files
    }

    /**
     *
     */
    protected function process_dataform_entry($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dataid = $this->get_new_parentid('dataform');

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        if ($userid = $this->task->get_ownerid()) {
            $data->userid = $userid;
        } else {
            $data->userid = $this->get_mappingid('user', $data->userid);
        }
        $data->groupid = $this->get_mappingid('group', $data->groupid);

        // insert the dataform_entries record
        $newitemid = $DB->insert_record('dataform_entries', $data);
        $this->set_mapping('dataform_entry', $oldid, $newitemid, false); // no files associated
    }

    /**
     *
     */
    protected function process_dataform_content($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->fieldid = $this->get_mappingid('dataform_field', $data->fieldid);
        $data->entryid = $this->get_new_parentid('dataform_entry');

        // insert the data_content record
        $newitemid = $DB->insert_record('dataform_contents', $data);
        $this->set_mapping('dataform_content', $oldid, $newitemid, true); // files by this item id
    }

    /**
     *
     */
    protected function process_dataform_rating($data) {
        $data = (object)$data;
        $data->itemid = $this->get_new_parentid('dataform_entry');
        $this->process_this_rating($data);        
    }

    /**
     *
     */
    protected function process_dataform_grade($data) {
        $data = (object)$data;
        $data->itemid = $this->get_mappingid('user', $data->itemid);
        $this->process_this_rating($data);        
    }

    /**
     *
     */
    protected function process_this_rating($data) {
        global $DB;
        $data = (object)$data;

        $data->contextid = $this->task->get_contextid();
        if ($data->scaleid < 0) { // scale found, get mapping
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('rating', $data);
    }
    
    /**
     *
     */
    protected function after_execute() {
        global $DB;
        // Add data related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_dataform', 'intro', null);

        // Add content related files, matching by item id (dataform_content)
        $this->add_related_files('mod_dataform', 'content', 'dataform_content');

        // Add content related files, matching by item id (dataform_view)
        // TODO it's not quite item id; need to add folders there
        $this->add_related_files('mod_dataform', 'view', 'dataform_view');

        // TODO Add preset related files, matching by itemname (data_content)
        //$this->add_related_files('mod_dataform', 'course_presets', 'dataform');

        $dataformnewid = $this->get_new_parentid('dataform');
        
        // default view
        if ($defaultview = $DB->get_field('dataform', 'defaultview', array('id' => $dataformnewid))) {
            if ($defaultview = $this->get_mappingid('dataform_view', $defaultview)) {
                $DB->set_field('dataform', 'defaultview', $defaultview, array('id' => $dataformnewid));
            }
        }

        // default filter
        if ($defaultfilter = $DB->get_field('dataform', 'defaultfilter', array('id' => $dataformnewid))) {
            if ($defaultfilter = $this->get_mappingid('dataform_filter', $defaultfilter)) {
                $DB->set_field('dataform', 'defaultfilter', $defaultfilter, array('id' => $dataformnewid));
            }
        }

        // single edit view
        if ($singleedit = $DB->get_field('dataform', 'singleedit', array('id' => $dataformnewid))) {
            if ($singleedit = $this->get_mappingid('dataform_view', $singleedit)) {
                $DB->set_field('dataform', 'singleedit', $singleedit, array('id' => $dataformnewid));
            }
        }

        // single view
        if ($singleview = $DB->get_field('dataform', 'singleview', array('id' => $dataformnewid))) {
            if ($singleview = $this->get_mappingid('dataform_view', $singleview)) {
                $DB->set_field('dataform', 'singleview', $singleview, array('id' => $dataformnewid));
            }
        }

        // Update group mode if the original was set to internal mode
        


        // Update id of userinfo fields if needed
        // TODO can we condition this on restore to new site?
        if ($userinfofields = $DB->get_records('dataform_fields', array('dataid' => $dataformnewid, 'type' => 'userinfo'), '', 'id,param1,param2')) {
            foreach ($userinfofields as $fieldid => $uifield) {
                $infoid = $DB->get_field('user_info_field', 'id', array('shortname' => $uifield->param2));
                if ($infoid != (int) $uifield->param1) {
                    $DB->set_field('dataform_fields', 'param1', $infoid, array('id' => $fieldid));
                }
            }
        }

    }
}
