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
 * An adhoc task for cron.
 *
 * @package mod_dataform
 * @copyright 2018 Itamar Tzadok <itamar.tzadok@substantialmethods.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_dataform\task;

class grade_update extends \core\task\adhoc_task {

    /**
     * Updates activity grade for the specified user and specified dataform.
     * With userid = 0 updates grades for all users.
     * With dataformid = 0 updates grades in all dataforms.
     */
    public function execute() {
        global $DB;

        $customdata = $this->get_custom_data();

        if (!empty($customdata->dataformid)) {
            $dataformids = [$customdata->dataformid];
        } else {
            $dataformids = $DB->get_records_menu('dataform', [], '', 'id, id AS did');
        }

        if (!$dataformids) {
            return;
        }

        $userid = !empty($customdata->userid) ? $customdata->userid : 0;

        $gradeduser = $userid ? "user $userid" : 'all users';

        // Execute grade update.
        foreach ($dataformids as $did) {
            $df = \mod_dataform_dataform::instance($did);
            if (!$error = $df->grade_manager->update_grades($userid)) {
                $resultstr = 'Updated';
            } else {
                $resultstr = 'Failed to update';
            }

            if (!PHPUNIT_TEST) {
                mtrace(".... $resultstr grades in dataform '$df->name' ($did) for $gradeduser.");
            }
        }
    }

}
