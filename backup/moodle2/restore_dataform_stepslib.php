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
 * @copyright 2010 Eloy Lafuente (stronk7) {@link http://stronk7.com}
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
 * Define all the restore steps that will be used by the restore_dataform_activity_task
 */

/**
 * Structure step to restore one dataform activity
 */
class restore_dataform_activity_structure_step extends restore_activity_structure_step {

    /**
     *
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('dataform', '/activity/dataform');
        $paths[] = new restore_path_element('dataform_field', '/activity/dataform/fields/field');
        $paths[] = new restore_path_element('dataform_filter', '/activity/dataform/filters/filter');
        $paths[] = new restore_path_element('dataform_view', '/activity/dataform/views/view');
        if ($userinfo) {
            $paths[] = new restore_path_element('dataform_entry', '/activity/dataform/entries/entry');
            $paths[] = new restore_path_element('dataform_content', '/activity/dataform/entries/entry/contents/content');
            $paths[] = new restore_path_element('dataform_rating', '/activity/dataform/entries/entry/ratings/rating');
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
            // restoring into existing activity so delete the extra record in course_modules
            //$imposedmoduleid = $this->task->get_moduleid();
            //$DB->delete_records('course_modules', array('id' => $imposedmoduleid));

            // reset task module id and mapping
            //$actualmoduleid = $DB->get_field('course_modules', 'id', array('instance' => $newitemid));
            //$this->task->set_moduleid($actualmoduleid);
            $this->set_mapping('course_module', $this->task->get_old_moduleid(), $this->task->get_moduleid());

            // reset task context id and mapping
            //$ctxid = get_context_instance(CONTEXT_MODULE, $actualmoduleid)->id;
            //$this->task->set_contextid($ctxid);
            $this->set_mapping('context', $this->task->get_old_contextid(), $this->task->get_contextid());

        } else {
            // Save activity id in task
            $this->task->set_activityid($newitemid); 
            // Apply the id to course_sections->instanceid
            $DB->set_field('course_modules', 'instance', $newitemid, array('id' => $this->task->get_moduleid()));
        }
        // Do the mapping for modulename, preparing it for files by oldcontext
        $oldid = $this->task->get_old_activityid();
        $this->set_mapping('dataform', $oldid, $newitemid, true);
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
    protected function process_dataform_entry($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->dataid = $this->get_new_parentid('dataform');

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $data->userid = $this->get_mappingid('user', $data->userid);
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
        global $DB;

        $data = (object)$data;

        // Cannot use ratings API, cause, it's missing the ability to specify times (modified/created)
        $data->contextid = $this->task->get_contextid();
        $data->itemid    = $this->get_new_parentid('dataform_entry');
        if ($data->scaleid < 0) { // scale found, get mapping
            $data->scaleid = -($this->get_mappingid('scale', abs($data->scaleid)));
        }
        $data->rating = $data->value;
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // We need to check that component and ratingarea are both set here.
        if (empty($data->component)) {
            $data->component = 'mod_dataform';
        }
        if (empty($data->ratingarea)) {
            $data->ratingarea = 'entry';
        }

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

        // TODO Add package related files, matching by itemname (data_content)
        //$this->add_related_files('mod_dataform', 'course_packages', 'dataform');

        $dataformnewid = $this->get_new_parentid('dataform');
        
        // default view
        if ($defaultview = $DB->get_field('dataform', 'defaultview', array('id' => $dataformnewid))) {
            if ($defaultview = $this->get_mappingid('dataform_view', $defaultview)) {
                $DB->set_field('dataform', 'defaultview', $defaultview, array('id' => $dataformnewid));
            }
        }

        // default sort
        $updatedf = false;
        if ($defaultsort = $DB->get_field('dataform', 'defaultsort', array('id' => $dataformnewid))) {
            $defaultsort = unserialize($defaultsort);
            $sortfields = array();
            foreach ($defaultsort as $sortfield => $sortdir) {
                if ($sortfield > 0) {
                    $sortfields[$this->get_mappingid('dataform_field', $sortfield)] = $sortdir;
                    $updatedf = true;
                } else {
                    $sortfields[$sortfield] = $sortdir;
                }
            }
            if ($updatedf) {
                $defaultsort = serialize($sortfields);
                $DB->set_field('dataform', 'defaultsort', $defaultsort, array('id' => $dataformnewid));
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

    }
}
