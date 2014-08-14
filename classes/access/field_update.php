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
 * mod_dataform access validators.
 *
 * @package    mod_dataform
 * @copyright  2013 Itamar Tzadok {@link http://substantialmethods.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_dataform\access;

defined('MOODLE_INTERNAL') || die();

/**
 * mod_dataform field update permission class.
 *
 * @package    mod_dataform
 * @copyright  2013 Itamar Tzadok {@link http://substantialmethods.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class field_update extends base {

    /**
     * @return bool
     */
    public static function validate($params) {
        $dataformid = $params['dataformid'];
        // Must have fieldid
        if (empty($params['fieldid'])) {
            return false;
        }
        $field = \mod_dataform_field_manager::instance($dataformid)->get_field_by_id($params['fieldid']);

        if (!$field->editable and !has_capability('mod/dataform:manageentries', $field->df->context)) {
            return false;
        }
        return parent::validate($params);
    }

    /**
     * @return null|array
     */
    public static function get_rules(\mod_dataform_access_manager $man, array $params) {
        $fieldid = $params['fieldid'];

        return $man->get_type_rules('field');
    }

    /**
     * @return array
     */
    public static function get_capabilities() {
        return array();
    }
}
