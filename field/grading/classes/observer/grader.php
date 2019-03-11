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
 * A dataformfield grading observer.
 *
 * @package    dataformfield_grading
 * @copyright  2018 Itamar Tzadok <itamar@substantialmethods.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace dataformfield_grading\observer;

defined('MOODLE_INTERNAL') || die();

class grader {

    /**
     * Returns grader observers for Dataform events.
     *
     * @return array
     */
    public static function observers() {
        $observers = array();

        $observers[] = array(
            'eventname'   => '\mod_dataform\event\entry_created',
            'callback'    => '\dataformfield_grading\observer\grader::update_grades',
        );

        $observers[] = array(
            'eventname'   => '\mod_dataform\event\entry_updated',
            'callback'    => '\dataformfield_grading\observer\grader::update_grades',
        );

        $observers[] = array(
            'eventname'   => '\mod_dataform\event\entry_deleted',
            'callback'    => '\dataformfield_grading\observer\grader::update_grades',
        );

        return $observers;
    }

    /**
     * Updates activity grades.
     *
     * @return void
     */
    public static function update_grades(\core\event\base $event) {
        global $DB;

        $dataformid = $event->other['dataid'];
        $entryuserid = $event->relateduserid;
        $entryid = $event->objectid;

        // If the target activity doesn't have instance of the field,
        // nothing to do.
        $params = ['dataid' => $dataformid, 'type' => 'grading'];
        if (!$fields = $DB->get_records('dataform_fields', $params)) {
            return;
        }
        
        // Get the fields content (last update) for the target entry.
        list($fids, $params) = $DB->get_in_or_equal(array_keys($fields));
        $params[] = $entryuserid;
        $select = "fieldid {$fids} AND entryid = ?";
        $lastupdates = $DB->get_records_select_menu('dataform_contents', $select, $params, '', 'fieldid,content');
        
        // We need to check if any of the instances allows grading.
        $doupdate = false;
        foreach ($fields as $fieldid => $field) {
            if (!$config = $field->param1) {
                continue;
            }

            $config = json_decode($config);
            
            // If only once and already updated, nothing to do.
            if (empty($config->multiupdate) and !empty($lastupdates[$fieldid])) {
                continue;
            }
            
            // If no events selected, nothing to do.
            if (empty($config->events)) {
                continue;
            }

            // If current event selected, we need to update.
            $eventname = str_replace('\\mod_dataform\\event\\', '', $event->eventname);
            if (in_array($eventname, $config->events)) {
                $doupdate = true;
                break;
            }                   
        }
        
        if ($doupdate) {
            // Create the task instance
            $gu = new \mod_dataform\task\grade_update;
            // add custom data
            $gu->set_custom_data(array(
               'dataformid' => $dataformid,
               'userid' => $entryuserid,
            ));

            // queue it
            \core\task\manager::queue_adhoc_task($gu);
        }
    }

}
