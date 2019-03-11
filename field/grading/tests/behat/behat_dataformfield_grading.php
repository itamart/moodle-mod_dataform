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
 * Steps definitions.
 *
 * @package dataformfield_grading
 * @category tests
 * @copyright 2018 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

use Behat\Behat\Context\Step\Given as Given;
use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * Dataform mc field steps definitions.
 *
 * @package dataformfield_mc
 * @category tests
 * @copyright 2017 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_dataformfield_grading extends behat_base {

    /**
     * Creates/updates a grading field.
     *
     * @Given /^the following dataformfield grading exists:$/
     * @param TableNode $data
     */
    public function the_following_dataformfield_grading_exists(TableNode $data) {
        global $DB;

        $datahash = $data->getRowsHash();

        // Get the dataform id.
        $idnumber = $datahash['dataform'];
        if (!$dataformid = $DB->get_field('course_modules', 'instance', array('idnumber' => $idnumber))) {
            throw new Exception('The specified dataform with idnumber "' . $idnumber . '" does not exist');
        }

        $fieldman = \mod_dataform_field_manager::instance($dataformid);

        // Get the field or create it if does not exist.
        $params = array('dataid' => $dataformid, 'name' => $datahash['name']);
        if (!$instance = $DB->get_record('dataform_fields', $params)) {
            $field = $fieldman->add_field('grading');
        } else {
            $field = $fieldman->get_field($instance);
        }

        // Update the field config.
        $config = $field->get_config();

        // Events.
        if (isset($datahash['event'])) {
            $config['events'] = explode(',', $datahash['event']);
        }

        // Multi update.
        if (isset($datahash['multiupdate'])) {
            $config['multiupdate'] = (int) !empty($datahash['multiupdate']);
        }

        // Update the field.
        $field->param1 = json_encode($config);
        $field->update($field->data);
    }
}
