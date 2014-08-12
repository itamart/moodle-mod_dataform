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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod_dataform data generator
 *
 * @package    mod_dataform
 * @category   phpunit
 * @copyright  2012 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Page module PHPUnit data generator class
 *
 * @package    mod_dataform
 * @category   phpunit
 * @copyright  2014 Itamar Tzadok
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_dataform_generator extends testing_module_generator {

    /**
     * Create new dataform module instance
     * @param array|stdClass $record
     * @param array $options (mostly course_module properties)
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Deletes an existing dataform module instance
     *
     * @param int $id Id of dataform instance
     * @return void
     */
    public function delete_instance($id) {
        if ($df = mod_dataform_dataform::instance($id)) {
            $df->delete();
        }
    }

    /**
     * Deletes all dataform instances in course or site if course id not specified.
     *
     * @param int $id Id of course
     * @return void
     */
    public function delete_all_instances($courseid = 0) {
        global $DB;

        $params = array();
        if ($courseid) {
            $params['course'] = $courseid;
        }
        if ($dataforms = $DB->get_records_menu('dataform', $params, '', 'id, id AS did')) {
            foreach ($dataforms as $dataformid) {
                mod_dataform_dataform::instance($dataformid)->delete();
            }
        }
    }

    /**
     * Duplicates a single dataform within a course.
     *
     * This is based on the code from course/modduplicate.php, but reduced for
     * simplicity.
     *
     * @param stdClass $course Course object
     * @param int $cmid Dataform to duplicate
     * @return stdClass The new dataform instance with cmid
     */
    public function duplicate_instance($course, $cmid) {
        global $DB, $USER;

        // Do backup.
        $bc = new backup_controller(
            backup::TYPE_1ACTIVITY,
            $cmid, backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore.
        $rc = new restore_controller(
            $backupid,
            $course->id,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id, backup::TARGET_CURRENT_ADDING
        );
        $rc->execute_precheck();
        $rc->execute_plan();

        // Find cmid.
        $tasks = $rc->get_plan()->get_tasks();
        $cmcontext = context_module::instance($cmid);
        $newcmid = 0;
        $newactivityid = 0;
        foreach ($tasks as $task) {
            if (is_subclass_of($task, 'restore_activity_task')) {
                if ($task->get_old_contextid() == $cmcontext->id) {
                    $newcmid = $task->get_moduleid();
                    $newactivityid = $task->get_activityid();
                    break;
                }
            }
        }
        $rc->destroy();
        if (!$newcmid) {
            throw new coding_exception('Unexpected: failure to find restored cmid');
        }
        if (!$instance = $DB->get_record('dataform', array('id' => $newactivityid))) {
            throw new coding_exception('Unexpected: failure to find restored activityid');
        }
        $instance->cmid = $newcmid;

        // Clear the time limit, otherwise phpunit complains.
        set_time_limit(0);

        return $instance;
    }

    /**
     * Generates a dataform view.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_view($record, array $options = null) {
        $record = (object)(array)$record;
        $df = mod_dataform_dataform::instance($record->dataid);
        $view = $df->view_manager->get_view($record->type);
        $view->generate_default_view();

        // Add data from record.
        foreach ($record as $var => $value) {
            $view->$var = $value;
        }

        // Add the view.
        $view->add($view->data);

        // Set as default if specified
        if (!empty($record->default)) {
            $df->view_manager->process_views('default', $view->id, null, true);
        }
        return $view->data;
    }

    /**
     * Generates a dataform filter.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_filter($record, array $options = null) {
        $record = (object)(array)$record;
        $filter = new \mod_dataform\pluginbase\dataformfilter($record);

        // Append sort options if specified.
        if (!empty($record->sortoptions)) {
            // Convert fieldid sortdir to
            // fieldid => sortdir
            $sorties = array();
            foreach (explode(',', $record->sortoptions) as $sortoption) {
                list($fieldid, $sortdir) = explode(' ', $sortoption);
            }
            $filter->append_sort_options($record->sortoptions);
        }

        // Append search options if specified.
        if (!empty($record->searchoptions)) {
            // Convert AND|OR,fieldid,element,[NOT],operator,value to
            // fieldid => (endor => (element, not, operator, value))
            $searchoptions = array_map(
                function ($a) {
                    list($andor, $fieldid, $element, $isnot, $op, $value) = explode(',', $a);
                    return array($fieldid => array($andor => array(array($element, $isnot, $op, $value))));
                },
                explode(';', $record->searchoptions)
            );

            $searchies = array();
            foreach ($searchoptions as $searchoption) {
                foreach ($searchoption as $fieldid => $andors) {
                    if (empty($searchies[$fieldid])) {
                        $searchies[$fieldid] = $andors;
                    } else {
                        foreach ($andors as $andor => $soptions) {
                            if (empty($searchies[$fieldid][$andor])) {
                                $searchies[$fieldid][$andor] = $soptions;
                            } else {
                                foreach ($soptions as $soption) {
                                    $searchies[$fieldid][$andor][] = $soption;
                                }
                            }
                        }
                    }
                }
            }

            $filter->append_search_options($searchies);
        }
        $filter->update();
        return $filter->instance;
    }

    /**
     * Generates a dataform field.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_field($record, array $options = null) {
        $record = (object)(array)$record;
        $df = mod_dataform_dataform::instance($record->dataid);
        $field = $df->field_manager->get_field($record->type);
        $field->name = $record->name;

        // Params
        for ($i = 1; $i <= 10; $i++) {
            $parami = "param$i";
            if (isset($record->$parami)) {
                $value = $record->$parami;
                // Really ugly hack: make new lines in string.
                $value = str_replace('\r\n', "\n", $value);
                $field->$parami = $value;
            }
        }

        $field->create($field->data);
        return $field->data;
    }

    /**
     * Generates a dataform entry.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_entry($record, array $options = null) {
        global $DB;

        // Convert timecreated and timemodified
        $record['timecreated'] = !empty($record['timecreated']) ? strtotime($record['timecreated']) : 0;
        $record['timemodified'] = !empty($record['timemodified']) ? strtotime($record['timemodified']) : 0;

        $df = \mod_dataform_dataform::instance($record['dataid']);
        $entry = \mod_dataform\pluginbase\dataformentry::blank_instance($df, (object)(array)$record);
        $entry->id = $DB->insert_record('dataform_entries', $entry);

        // Add content
        if ($fields = $df->field_manager->get_fields()) {
            foreach ($fields as $field) {
                if (!empty($record[$field->name])) {
                    $field->update_content($entry, array($record[$field->name]));
                }
            }
        }

        return $entry;
    }

}
