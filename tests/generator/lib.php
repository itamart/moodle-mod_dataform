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
        $record = (object) (array) $record;
        $data = \mod_dataform_dataform::get_default_data();

        // Adjust values.
        foreach ($record as $key => $value) {
            if (!property_exists($data, $key)) {
                continue;
            }
            $method = "get_value_$key";
            if (method_exists($this, $method)) {
                $value = $this->$method($value);
            }
            $data->$key = $value;
        }

        // Must have course.
        $data->course = $record->course;

        return parent::create_instance($data, (array) $options);
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
            $cmid,
            backup::FORMAT_MOODLE,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id
        );
        $backupid = $bc->get_backupid();
        $backupbasepath = $bc->get_plan()->get_basepath();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore.
        $rc = new restore_controller(
            $backupid,
            $course->id,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id,
            backup::TARGET_CURRENT_ADDING
        );

        if (!$rc->execute_precheck()) {
            $precheckresults = $rc->get_precheck_results();
            if (is_array($precheckresults) && !empty($precheckresults['errors'])) {
                if (empty($CFG->keeptempdirectoriesonbackup)) {
                    fulldelete($backupbasepath);
                }
            }
        }

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

        if (empty($CFG->keeptempdirectoriesonbackup)) {
            fulldelete($backupbasepath);
        }

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
        $df = new \mod_dataform_dataform($record->dataid);
        $view = $df->view_manager->get_view($record->type);
        $view->generate_default_view();

        // Add data from record.
        foreach ($record as $var => $value) {
            $view->$var = $value;
        }

        // Add the view.
        $view->add($view->data);

        // Set as default if specified.
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
            // fieldid => sortdir.
            $sorties = array();
            foreach (explode(',', $record->sortoptions) as $sortoption) {
                list($fieldid, $sortdir) = explode(' ', $sortoption);
            }
            $filter->append_sort_options($record->sortoptions);
        }

        // Append search options if specified.
        if (!empty($record->searchoptions)) {
            // Convert AND|OR,fieldid,element,[NOT],operator,value to
            // fieldid => (endor => (element, not, operator, value)).
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
        $df = new \mod_dataform_dataform($record->dataid);
        $field = $df->field_manager->get_field($record->type);

        // Add data from record.
        foreach ($record as $var => $value) {
            $field->$var = $value;
        }

        // HACK: make new lines in string.
        $nlconversionfields = array(
            'label',
            'param1',
            'param2',
            'param3',
            'param4',
            'param5',
            'param6',
            'param7',
            'param8',
            'param9',
            'param10',
        );
        foreach ($nlconversionfields as $var) {
            if ($field->$var) {
                $value = str_replace('\\\n', "\n", $field->$var);
                $field->$var = $value;
            }
        }

        $field->create($field->data);
        return $field->data;
    }

    /**
     * Generates a dataform grade item.
     * @param array|stdClass $record
     * @param array $options
     * @return \grade_item generated object
     */
    public function create_grade_item($record, array $options = null) {
        $record = (object)(array)$record;

        $dataformid = $record->dataid;
        $df = \mod_dataform_dataform::instance($dataformid);
        $grademan = $df->grade_manager;

        // Get existing grade items.
        if (!$gradeitems = $grademan->grade_items) {
            $itemnumber = 0;
        } else {
            $itemnumber = count($gradeitems);
        }

        $gradedata = array(
            'grade' => $record->grade
        );
        if (!empty($record->gradeguide)) {
            $gradedata['gradeguide'] = $record->gradeguide;
        }
        if (!empty($record->gradecalc)) {
            $gradedata['gradecalc'] = $record->gradecalc;
        }

        if ($itemnumber == 0) {
            // First item, let's update the dataform to create the grade item.
            $df->update($gradedata);
            // Now update the grade item with the desired name.
            $gradedata['name'] = $record->name;
            $itemparams = $grademan->get_grade_item_params_from_data($gradedata);
            $grademan->update_grade_item($itemnumber, $itemparams);
        } else {
            // Now update the grade item.
            $gradedata['name'] = $record->name;
            $itemparams = $grademan->get_grade_item_params_from_data($gradedata);
            $grademan->update_grade_item($itemnumber, $itemparams);
            $grademan->adjust_dataform_settings($itemnumber, $gradedata);
        }

        $grademan->grade_items = null;
        return $grademan->grade_items[$itemnumber];
    }

    /**
     * Generates a dataform entry.
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass generated object
     */
    public function create_entry($record, array $options = null) {
        global $DB;

        // Convert timecreated and timemodified.
        $record['timecreated'] = !empty($record['timecreated']) ? strtotime($record['timecreated']) : 0;
        $record['timemodified'] = !empty($record['timemodified']) ? strtotime($record['timemodified']) : 0;

        $df = new \mod_dataform_dataform($record['dataid']);
        $entry = \mod_dataform\pluginbase\dataformentry::blank_instance($df, (object)(array)$record);
        $entry->id = $DB->insert_record('dataform_entries', $entry);

        // Add content.
        if ($fields = $df->field_manager->get_fields()) {
            $fieldsbyname = array();
            foreach ($fields as $field) {
                $fieldsbyname[$field->name] = $field;
            }

            foreach ($record as $name => $value) {
                list($fieldname, $contentname) = array_pad(explode('_', $name), 2, '');
                if (array_key_exists($fieldname, $fieldsbyname)) {
                    $values = array($contentname => $value);
                    $fieldsbyname[$fieldname]->update_content($entry, $values);
                }
            }
        }

        return $entry;
    }

    /**
     * Returns timestamp for timeavailable string.
     *
     * @param string $value
     * @return int timestamp
     */
    protected function get_value_timeavailable($value) {
        return (!empty($value) ? strtotime($value) : 0);
    }

    /**
     * Returns timestamp for timedue string.
     *
     * @param string $value
     * @return int timestamp
     */
    protected function get_value_timedue($value) {
        return (!empty($value) ? strtotime($value) : 0);
    }

}
