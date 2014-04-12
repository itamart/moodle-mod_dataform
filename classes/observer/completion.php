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
 * The mod_dataform dataform notification observer.
 *
 * @package    mod_dataform
 * @copyright  2014 Itamar Tzadok <itamar@substantialmethods.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dataform\observer;

defined('MOODLE_INTERNAL') || die();

class completion {

    /**
     * Returns completion observers for Dataform completion conditions.
     *
     * @return array
     */
    public static function observers() {
        $observers = array();

        $observers[] = array(
            'eventname'   => '\mod_dataform\event\entry_created',
            'callback'    => '\mod_dataform\observer\completion::update',
        );
        
        $observers[] = array(
            'eventname'   => '\mod_dataform\event\entry_deleted',
            'callback'    => '\mod_dataform\observer\completion::update',
        );
        
        return $observers;
    }

    /**
     * Updates activity completion status.
     *
     * @return void
     */
    public static function update(\core\event\base $event) {
        global $DB;
        
        $dataformid = $event->other['dataid'];        
        $entryuserid = $event->relateduserid;        

        $df = \mod_dataform_dataform::instance($dataformid);
        
        $completion = new \completion_info($df->course);
        if ($completion->is_enabled($df->cm) != COMPLETION_TRACKING_AUTOMATIC) {
            return;
        }

        if ($df->completionentries) {
            $completion->update_state($df->cm, COMPLETION_UNKNOWN, $entryuserid);
        }
    }

}
