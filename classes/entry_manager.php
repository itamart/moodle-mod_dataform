<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package mod_dataform
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 *
 */
class mod_dataform_entry_manager {

    const SELECT_FIRST_PAGE = 1;
    const SELECT_LAST_PAGE = 2;
    const SELECT_NEXT_PAGE = 3;
    const SELECT_RANDOM_PAGE = 4;
    const SELECT_RANDOM_ENTRIES = 5;

    const COUNT_ALL = 0;
    const COUNT_VIEWABLE = 1;
    const COUNT_FILTERED = 2;
    const COUNT_DISPLAYED = 3;

    const SUBMIT_SAVE = 'save';
    const SUBMIT_SAVE_CONTINUE = 'savecont';
    const SUBMIT_SAVE_NEW = 'savenew';

    /** @var int Id of the Dataform this manager works for. */
    protected $_dataformid;
    /** @var int Id of the Dataformview this manager works for. */
    protected $_viewid;
    /** @var array The list of entries set as content by manager. */
    protected $_entries = null;
    /** @var int Total number of entries in the Dataform this manager works for. */
    protected $_countall = 0;
    /** @var int Number of entries viewable by the user without applying filters. */
    protected $_countviewable = 0;
    /** @var int Number of entries viewable by the user with filters applied. */
    protected $_countfiltered = 0;
    /** @var int Number of page of retrieved entries. */
    protected $_page = 0;
    /** @var array Local sql cache. */
    protected $_sql = array();

    /**
     * Returns and caches (for the current script) if not already, an entries manager for the specified Dataform.
     *
     * @param int Dataform id
     * @return mod_dataform_entry_manager
     */
    public static function instance($dataformid, $viewid) {
        global $DB;

        if (!$dataformid) {
            if (!$viewid or !$dataformid = $DB->get_field('dataform_views', 'dataid', array('id' => $viewid))) {
                throw new moodle_exception('invaliddataform', 'dataform', null, null, "Dataform id: $dataformid");
            }
        }

        if (!$instance = \mod_dataform_instance_store::instance($dataformid, "entry_manager-$viewid")) {
            $instance = new mod_dataform_entry_manager($dataformid, $viewid);
            \mod_dataform_instance_store::register($dataformid, "entry_manager-$viewid", $instance);
        }

        return $instance;
    }

    /**
     * Constructor
     * View or dataform or both, each can be id or object
     */
    public function __construct($dataformid, $viewid = 0) {

        if (empty($dataformid)) {
            throw new coding_exception('Dataform id or object must be passed to entries constructor.');
        }

        $this->_dataformid = $dataformid;
        $this->_viewid = $viewid;
    }

    /**
     * Magic property method
     *
     * Attempts to call a set_$key method if one exists otherwise falls back
     * to simply set the property
     *
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value) {
        if (method_exists($this, 'set_'.$key)) {
            $this->{'set_'.$key}($value);
        }
    }

    /**
     * Magic get method
     *
     * Attempts to call a get_$key method to return the property and ralls over
     * to return the raw property
     *
     * @param str $key
     * @return mixed
     */
    public function __get($key) {
        if (method_exists($this, 'get_'.$key)) {
            return $this->{'get_'.$key}();
        }
        return null;
    }


    /**
     *
     */
    public function set_content(array $options = null) {
        // Load sql from filter.
        if (!empty($options['filter'])) {
            $this->_sql = $options['filter']->get_sql($this->viewid);
            unset($options['filter']);
        }

        if (isset($options['entriesset'])) {
            $entriesset = $options['entriesset'];
        } else {
            $entriesset = $this->fetch_entries($options);
        }

        $this->entries = !empty($entriesset->entries) ? $entriesset->entries : array();
        $this->_page = !empty($entriesset->page) ? $entriesset->page : 0;

        $entriescount = count($this->entries);
        $this->_countviewable = !empty($entriesset->max) ? $entriesset->max : $entriescount;
        $this->_countfiltered = !empty($entriesset->found) ? $entriesset->found : $entriescount;
        $this->_countall = !empty($entriesset->total) ? $entriesset->total : $this->_countviewable;
    }

    /**
     *
     */
    public function fetch_entries(array $options = null) {
        global $DB;

        if (!empty($options['filter'])) {
            $sql = $options['filter']->get_sql($this->viewid);
        } else {
            $sql = $this->sql;
        }

        if (!$sql) {
            return null;
        }

        // Count entries.
        $totalcount = $this->count_entries();
        if (!$sql->countfiltered) {
            $maxcount = $searchcount = $DB->count_records_sql($sql->countmax, $sql->params);
        } else {
            if ($maxcount = $DB->count_records_sql($sql->countmax, $sql->params)) {
                $searchcount = $DB->count_records_sql($sql->countfiltered, $sql->allparams);
            } else {
                $searchcount = 0;
            }
        }

        // Initialize returned object.
        $entries = new stdClass;
        $entries->total = $totalcount;
        $entries->max = $maxcount;
        $entries->found = $searchcount;
        $entries->entries = null;
        $entries->page = 0;

        // Get the entry records.
        if ($searchcount) {
            $perpage = $sql->perpage;
            $selection = $sql->selection;

            if ($perpage and $selection == self::SELECT_RANDOM_ENTRIES) {
                // Get random entries.
                $sqlselect = "SELECT DISTINCT e.id FROM $sql->from WHERE $sql->where";
                $entryids = $DB->get_records_sql($sqlselect, $sql->allparams);
                // Get a random subset of ids.
                $randids = array_rand($entryids, min($perpage, count($entryids)));
                // Get the entries.
                list($insql, $paramids) = $DB->get_in_or_equal($randids);
                $andwhereids = " AND e.id $insql ";
                $sqlselect = "SELECT $sql->what FROM $sql->from WHERE $sql->where $andwhereids";
                $entries->entries = $DB->get_records_sql($sqlselect, $sql->allparams + $paramids);

            } else if ($perpage) {
                // Get page.
                $page = $sql->page;

                // !$filter->groupby and $perpage = $filter->perpage and $filter->selection != self::SELECT_RANDOM_ENTRIES
                $numpages = $searchcount > $perpage ? ceil($searchcount / $perpage) : 1;
                $page = $numpages > 1 ? (int) $page : 0;

                if ($selection) {
                    if ($selection == self::SELECT_FIRST_PAGE) {
                        // First page.
                        $page = 0;
                    } else if ($selection == self::SELECT_LAST_PAGE) {
                        // Last page.
                        $page = $numpages - 1;
                    } else if ($selection == self::SELECT_NEXT_PAGE) {
                        // Next page.
                        $page = ($page % $numpages);
                    } else if ($selection == self::SELECT_RANDOM_PAGE) {
                        // Random page.
                        $page = $numpages > 1 ? rand(0, ($numpages - 1)) : 0;
                    }
                }
                $entries->page = $page;
                $entries->entries = $DB->get_records_sql($sql->select, $sql->allparams, $page * $perpage, $perpage);
            } else {
                // Get everything.
                $entries->entries = $DB->get_records_sql($sql->select, $sql->allparams);
            }

            if (!$entries->entries) {
                // Nothing to do without entries so return.
                return $entries;
            }

            // Access control.
            $accessparams = array('dataformid' => $this->dataformid, 'viewid' => $this->viewid);
            foreach ($entries->entries as $entryid => $entry) {
                if (!mod_dataform\access\entry_view::validate($accessparams + array('entry' => $entry))) {
                    unset($entries->entries[$entryid]);
                    $entries->max--;
                    $entries->found--;
                }
            }

            // Now get the contents if required and add it to the entry objects.
            if ($entries->entries and $sql->dataformcontent) {

                $view = $this->view_manager->get_view_by_id($this->viewid);
                $fields = $view->get_fields();

                // Get the node content of the requested entries.
                list($eids, $eparams) = $DB->get_in_or_equal(array_keys($entries->entries));
                list($fids, $fparams) = $DB->get_in_or_equal($sql->dataformcontent);
                $params = array_merge($eparams, $fparams);
                $contents = $DB->get_records_select('dataform_contents', "entryid {$eids} AND fieldid {$fids}", $params);

                foreach ($contents as $contentid => $content) {
                    $entry = $entries->entries[$content->entryid];
                    $fieldid = $content->fieldid;

                    // TODO: this shouldn't happen so we need to check why
                    // it does and handle properly.
                    if (empty($fields[$fieldid])) {
                        continue;
                    }

                    $varcontentid = "c{$fieldid}_id";
                    $entry->$varcontentid = $contentid;
                    foreach ($fields[$fieldid]->get_content_parts() as $part) {
                        $varpart = "c{$fieldid}_$part";
                        $entry->$varpart = $content->$part;
                    }
                    $entries->entries[$content->entryid] = $entry;
                }
            }
        }

        return $entries;
    }

    /**
     * Counts the number of entries in the activity and returns the count.
     * If filter is provided via options, returns count with filter applied.
     * Otherwise returns total count.
     *
     * @param array $options
     * @return int
     */
    public function count_entries(array $options = null) {
        global $DB;

        $sql = null;

        if (!empty($options['filter'])) {
            if (!$sql = $options['filter']->get_sql($this->viewid)) {
                return 0;
            }
        }

        // Load sql from local cache.
        if (!$sql and !$sql = $this->sql) {
            // No sql so return total count.
            return $DB->count_records('dataform_entries', array('dataid' => $this->dataformid));
        }

        // We have sql to count by.
        $sqlcount = $sql->countfiltered ? $sql->countfiltered : $sql->countmax;
        $params = $sql->allparams;

        return $DB->count_records_sql($sqlcount, $params);
    }

    /**
     * Returns the position of the specified entryid in the list of filtered entries.
     *
     * @param int $entryid
     * @param \mod_dataform\pluginbase\dataformfilter $filter
     * @return int
     */
    public function get_entry_position($entryid, $filter) {
        global $DB;

        if (!$entryid or $entryid < 0) {
            return 0;
        }

        if (!$filter or !$sql = $filter->get_sql($this->viewid)) {
            return 0;
        }

        $sqlselect = "SELECT $sql->what $sql->whatcontent
                      FROM $sql->from
                      WHERE $sql->where $sql->sortorder";

        if ($entries = $DB->get_records_sql($sqlselect, $sql->allparams)) {
            return (int) array_search($entryid, array_keys($entries));
        }

        return 0;
    }

    /**
     * Returns number of entries according to specified scope.
     * COUNT_ALL: Total number of entries in the activity.
     * COUNT_VIEWABLE: viewable by the user without search filters.
     * COUNT_FILTERED: viewable by the user with search filters applied (if any).
     * COUNT_DISPLAYED: actually fetched (filtered + paging if any).
     *
     * @param string $scope COUNT_ALL|COUNT_FILTERED|COUNT_DISPLAYED
     * @return int
     */
    public function get_count($scope = self::COUNT_ALL) {
        if ($scope == self::COUNT_ALL) {
            return $this->_countall;
        }
        if ($scope == self::COUNT_VIEWABLE) {
            return $this->_countviewable;
        }
        if ($scope == self::COUNT_FILTERED) {
            return $this->_countfiltered;
        }
        if ($scope == self::COUNT_DISPLAYED and $this->entries) {
            return count($this->entries);
        }
        return 0;
    }

    /**
     * Returns specific entry by id from fetched entries.
     *
     * @return null|stdClass
     */
    public function get_entry_by_id($entryid) {
        if (!empty($this->entries[$entryid])) {
            return $this->entries[$entryid];
        }
        return null;
    }

    /**
     * Retrieves stored files which are embedded in the current content
     *  set_content must have been called
     *
     * @return array of stored files
     */
    public function get_embedded_files(array $fids) {

        $df = mod_dataform_dataform::instance($this->dataformid);

        $files = array();
        if (!empty($fids) and !empty($this->entries)) {
            $fs = get_file_storage();
            foreach ($this->entries as $entry) {
                foreach ($fids as $fieldid) {
                    // Get the content id of the requested field.
                    $contentid = isset($entry->{"c{$fieldid}_id"}) ? $entry->{"c{$fieldid}_id"} : null;
                    // The field may not hold any content.
                    if ($contentid) {
                        // Retrieve the files (no dirs) from file area.
                        $files = array_merge($files, $fs->get_area_files(
                            $df->context->id,
                            'mod_dataform',
                            'content',
                            $contentid,
                            'sortorder, itemid, filepath, filename',
                            false
                        ));
                    }
                }
            }
        }

        return $files;
    }

    /**
     * @return array notification string, list of processed ids
     */
    public function process_entries($action, $eids, $data = null, $confirmed = false) {
        global $DB, $USER, $OUTPUT, $PAGE;

        $entries = $this->get_entries_for_processing($eids, $action);

        // No entries scenario.
        if (empty($entries)) {
            return array(get_string("entrynoneforaction", 'dataform'), '');
        }

        // Require confirmation scenario.
        if (!$confirmed) {
            $yesparams = array($action => implode(',', array_keys($entries)), 'sesskey' => sesskey(), 'confirmed' => true);
            $yesurl = new moodle_url($PAGE->url, $yesparams);
            // Print a confirmation page.
            echo $OUTPUT->header();
            echo $OUTPUT->confirm(get_string("entriesconfirm$action", 'dataform', count($entries)), $yesurl, $PAGE->url);
            echo $OUTPUT->footer();
            exit(0);
        }

        // Process requested entries.
        switch ($action) {
            case 'update':
                $addorupdate = (min(array_keys($entries)) < 0 ? 'added' : 'updated');
                $processed = $this->process_update_entries($entries, $data);
                $strnotify = "entries$addorupdate";
                break;

            case 'duplicate':
                $processed = $this->process_duplicate_entries($entries);
                $strnotify = 'entriesduplicated';
                break;

            case 'delete':
                $processed = $this->process_delete_entries($entries);
                $strnotify = 'entriesdeleted';
                break;

            default:
                $processed = array();
                $strnotify = '';
        }

        $df = mod_dataform_dataform::instance($this->dataformid);

        if ($processed) {
            $strnotify = get_string($strnotify, 'dataform', count($processed));
        } else {
            $strnotify = get_string($strnotify, 'dataform', get_string('no'));
        }

        return array($strnotify, array_keys($processed));
    }

    /**
     *
     * @return null|array
     */
    protected function get_entries_for_processing($eids, $action) {
        global $DB, $USER;

        if (empty($eids)) {
            return null;
        }

        $df = mod_dataform_dataform::instance($this->dataformid);
        $entries = array();

        if (!is_array($eids)) {
            if ($eids < 0) {
                // Adding new entries.
                $eids = array_reverse(range($eids, -1));
            } else {
                // Updating existing entries.
                $eids = explode(',', $eids);
            }
        }

        // Adding or updating entries.
        if ($action == 'update') {
            // Prepare the entries to process.
            foreach ($eids as $ind => $eid) {
                if ($eid > 0) {
                    // Existing entry from view.
                    if (isset($this->entries[$eid])) {
                        $entries[$eid] = $this->entries[$eid];
                        unset($eids[$ind]);
                    }

                } else {
                    if ($eid < 0) {
                        // New entry.
                        $entry = new stdClass;
                        $entry->id = 0;
                        $entry->groupid = $df->currentgroup;
                        $entry->userid = $USER->id;
                        $entries[$eid] = $entry;
                    }
                    unset($eids[$ind]);
                }
            }
        }

        // All other types of processing must refer to specific entry ids.
        if ($eids) {
            list($inids, $params) = $DB->get_in_or_equal($eids);
            $params[] = $df->id;
            $ents = $DB->get_records_select('dataform_entries', " id $inids AND dataid = ? ", $params);
            if ($ents) {
                $entries = $entries + $ents;
            }
        }

        return $entries;
    }

    /**
     * Delete entries of all or a specified user.
     * Shortcut for processing delete request.
     *
     * @param int $userid User id or null for all users
     * @return void
     */
    public function delete_entries($userid = null) {
        global $DB;

        $params = array('dataid' => $this->dataformid);
        if ($userid !== null) {
            $params['userid'] = $userid;
        }
        // Get the ids.
        if ($entries = $DB->get_records('dataform_entries', $params, '', 'id')) {
            $eids = array_keys($entries);
            $this->process_entries('delete', $eids, null, true);
        }
    }

    /**
     * Add/update entries
     *
     * @param array Array of entries to process
     * @param stdClass Entries data
     * @return array Array of processed entries
     */
    protected function process_update_entries($entries, $data) {
        if (empty($entries) or is_null($data)) {
            return array();
        }

        $df = mod_dataform_dataform::instance($this->dataformid);
        $accessman = mod_dataform_access_manager::instance($this->dataformid);

        // Check permissions.
        foreach ($entries as $entryid => $entry) {
            $entrydo = ($entryid < 0 ? 'mod_dataform\access\entry_add' : 'mod_dataform\access\entry_update');
            $accessparams = array('dataformid' => $this->dataformid, 'viewid' => $this->viewid);
            if (!$entrydo::validate($accessparams + array('entry' => $entry))) {
                unset($entries[$entryid]);
            }
        }

        // In case none remain for processing.
        if (empty($entries)) {
            return array();
        }

        $processed = array();

        // First parse the data to collate content in an array for each recognized field.
        $contents = array_fill_keys(array_keys($entries), array('info' => array(), 'fields' => array()));
        $calculations = array();
        $fields = $this->field_manager->get_fields();
        $savetype = '';

        // Iterate the data and extract entry and fields content.
        foreach ($data as $name => $value) {
            // Which submit type.
            if (strpos($name, 'submitbutton') === 0) {
                list(, $savetype, ) = explode('_', $name);
                continue;
            }

            if (strpos($name, 'entry_') === 0) {
                // Entry info
                // Assuming only entry info names start with entry_.
                list(, $entryid, $var) = explode('_', $name);
                $contents[$entryid]['info'][$var] = $value;

            } else if (strpos($name, 'field_') === 0) {
                // Assuming only field names contain field_.
                list(, $fieldid, $entryid) = explode('_', $name);
                if (!empty($fields[$fieldid])) {
                    $field = $fields[$fieldid];
                } else if ($field = $this->field_manager->get_field_by_id($fieldid)) {
                    $fields[$fieldid] = $field;
                } else {
                    continue;
                }

                // Entry content.
                if (!array_key_exists($fieldid, $contents[$entryid]['fields'])) {
                    $contents[$entryid]['fields'][$fieldid] = $field->get_content_from_data($entryid, $data);
                }
            }
        }

        // Now update entry and contents.
        $savenew = (strpos($savetype, 'savenew') === 0);
        foreach ($entries as $eid => $entry) {
            if ($savenew) {
                $entry->id = 0;
                unset($entry->timecreated);
                unset($entry->timemodified);
            }

            if ($entry->id = $this->update_entry($entry, $contents[$eid]['info'])) {
                // $eid should be different from $entryid only in new entries.
                foreach ($contents[$eid]['fields'] as $fieldid => $content) {
                    $fields[$fieldid]->update_content($entry, $content, $savenew);
                }

                // Trigger the entry event.
                if ($eid != $entry->id) {
                    $entryevent = '\mod_dataform\event\entry_created';
                } else {
                    $entryevent = '\mod_dataform\event\entry_updated';
                }

                $eventparams = array(
                    'objectid' => $entry->id,
                    'context' => $df->context,
                    'relateduserid' => $entry->userid,
                    'other' => array(
                        'dataid' => $this->dataformid,
                        'viewid' => $this->viewid,
                        'entryid' => $entry->id,
                    )
                );
                $event = $entryevent::create($eventparams);
                $event->add_record_snapshot('dataform_entries', $entry);
                $event->trigger();

                // Update calculated grades if applicable.
                $df->grade_manager->update_calculated_grades($entry);

                $processed[$entry->id] = $entry;
            }
        }
        return $processed;
    }

    /**
     * Duplicate entries
     *
     * @param array Array of entries to process
     * @return array Array of processed entries
     */
    protected function process_duplicate_entries($entries) {
        global $DB, $USER;

        if (empty($entries)) {
            return array();
        }

        $df = mod_dataform_dataform::instance($this->dataformid);

        $processed = array();
        $accessparams = array('dataformid' => $this->dataformid, 'viewid' => $this->viewid);
        foreach ($entries as $entryid => $entry) {
            // Can user add more entries?
            if (!mod_dataform\access\entry_add::validate($accessparams + array('entry' => $entry))) {
                return $processed;
            }

            // Add a duplicated entry and content.
            $newentry = clone($entry);
            $newentry->id = -1;
            $newentry->userid = $USER->id;
            $newentry->groupid = $df->currentgroup;
            $newentry->timecreated = $newentry->timemodified = time();

            $newentry->id = $this->update_entry($newentry);

            $fields = $this->field_manager->get_fields();
            foreach ($fields as $field) {
                $field->duplicate_content($entry, $newentry);
            }
            $processed[$newentry->id] = $newentry;

            // Trigger the entry event.
            $eventparams = array(
                'objectid' => $entry->id,
                'context' => $df->context,
                'relateduserid' => $entry->userid,
                'other' => array(
                    'dataid' => $this->dataformid,
                    'viewid' => $this->viewid,
                    'entryid' => $entry->id,
                )
            );
            $event = \mod_dataform\event\entry_created::create($eventparams);
            $event->add_record_snapshot('dataform_entries', $entry);
            $event->trigger();

            // Update calculated grades if applicable.
            $df->grade_manager->update_calculated_grades($entry);
        }
        return $processed;
    }

    /**
     * Delete entries
     *
     * @param array Array of entries to process
     * @return array Array of processed entries
     */
    protected function process_delete_entries($entries) {
        global $DB;

        if (empty($entries)) {
            return array();
        }

        $df = mod_dataform_dataform::instance($this->dataformid);

        $processed = array();
        $accessparams = array('dataformid' => $this->dataformid, 'viewid' => $this->viewid);
        foreach ($entries as $entry) {
            // Check permissions.
            if (!mod_dataform\access\entry_delete::validate($accessparams + array('entry' => $entry))) {
                continue;
            }

            $fields = $this->field_manager->get_fields();
            foreach ($fields as $field) {
                $field->delete_content($entry->id);
            }

            $DB->delete_records('dataform_entries', array('id' => $entry->id));
            $processed[$entry->id] = $entry;

            // Trigger event.
            $eventparams = array(
                'objectid' => $entry->id,
                'context' => $df->context,
                'relateduserid' => $entry->userid,
                'other' => array(
                    'dataid' => $this->dataformid,
                    'viewid' => $this->viewid,
                    'entryid' => $entry->id,
                )
            );
            $event = \mod_dataform\event\entry_deleted::create($eventparams);
            $event->add_record_snapshot('dataform_entries', $entry);
            $event->trigger();

            // Update calculated grades if applicable.
            $df->grade_manager->update_calculated_grades($entry);
        }

        return $processed;
    }

    /**
     * Adding/updating entry record.
     * Assumes that permissions check has already been done.
     *
     * @param stdClass entry to add/update
     * @param stdClass data to update entry with
     * @param bool whether to update entry time
     * @return int|bool entry id if success, false otherwise
     */
    protected function update_entry(&$entry, $data = null, $updatetime = true) {
        global $CFG, $DB, $USER;

        $df = mod_dataform_dataform::instance($this->dataformid);

        if ($data) {
            foreach ($data as $key => $value) {
                if ($key == 'name') {
                    $entry->userid = $value;
                } else {
                    $entry->{$key} = $value;
                }
            }

            // Don't update time later if set from data.
            if (isset($data['timemodified'])) {
                $updatetime = false;
            }

            // TODO Entry group sanity checks on $data['groupid'].
        }

        // Update existing entry (only authenticated users).
        if ($entry->id > 0) {
            if ($updatetime) {
                $entry->timemodified = time();
            }

            if ($DB->update_record('dataform_entries', $entry)) {
                return $entry->id;
            } else {
                return false;
            }
        }

        // Add new entry (authenticated or anonymous (if enabled))
        // Identify non-logged-in users (in anonymous entries) as guests.
        $currentuserid = empty($USER->id) ? $CFG->siteguest : $USER->id;
        $entryuserid = !empty($entry->userid) ? $entry->userid : $currentuserid;

        $entry->dataid = $df->id;
        $entry->type = !isset($entry->type) ? '' : $entry->type;
        $entry->userid = $df->grouped ? 0 : $entryuserid;
        $entry->groupid = !isset($entry->groupid) ? $df->currentgroup : $entry->groupid;
        $entry->timecreated = !isset($entry->timecreated) ? time() : $entry->timecreated;
        $entry->timemodified = !isset($entry->timemodified) ? time() : $entry->timemodified;
        $entry->state = !empty($entry->state) ? $entry->state : 0;

        // Adjust the entry type if needed.
        $entrytype = '';
        if (!$entry->type) {
            $view = $this->view_manager->get_view_by_id($this->viewid);
            if ($view->entrytype) {
                $entry->type = $view->entrytype;
            }
        }

        $entry->id = $DB->insert_record('dataform_entries', $entry);

        return $entry->id;
    }

    // GETTERS.
    /**
     * Returns the manager's dataformid.
     *
     * @return int
     */
    public function get_dataformid() {
        return $this->_dataformid;
    }

    /**
     * Returns the manager's viewid.
     *
     * @return int
     */
    public function get_viewid() {
        return $this->_viewid;
    }

    /**
     * Returns the entries set.
     *
     * @return stdClass
     */
    public function get_entries() {
        return $this->_entries;
    }

    /**
     * Returns the page of the entries set.
     *
     * @return stdClass
     */
    public function get_page() {
        return $this->_page;
    }

    /**
     * Returns the sql query.
     *
     * @return stdClass
     */
    public function get_sql() {
        return $this->_sql;
    }

    /**
     * Returns the view manager of the Dataform this mannager works for.
     *
     * @return mod_dataform_view_manager
     */
    public function get_view_manager() {
        return mod_dataform_view_manager::instance($this->dataformid);
    }

    /**
     * Returns the field manager of the Dataform this mannager works for.
     *
     * @return mod_dataform_field_manager
     */
    public function get_field_manager() {
        return mod_dataform_field_manager::instance($this->dataformid);
    }

    /**
     *
     */
    public function set_entries($value) {
        $this->_entries = $value;
    }

}
