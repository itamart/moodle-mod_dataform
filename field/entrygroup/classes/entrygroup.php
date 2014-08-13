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
 * @package dataformfield
 * @subpackage entrygroup
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class dataformfield_entrygroup_entrygroup extends \mod_dataform\pluginbase\dataformfield_internal {

    const INTERNALID = -3;

    /**
     * Returns instance defaults for for the field
     * (because internal fields do not have DB record).
     *
     * @return null|stdClass
     */
    public static function get_default_data($dfid) {
        $field = (object) array(
            'id' => self::INTERNALID,
            'dataid' => $dfid,
            'type' => 'entrygroup',
            'name' => get_string('fieldname', 'dataformfield_entrygroup'),
            'description' => '',
            'visible' => 2,
            'editable' => -1,
        );
        return $field;
    }

    /**
     * Overrides {@link dataformfield::prepare_import_content()} to set import into entry::groupid.
     *
     * @return stdClass
     */
    public function prepare_import_content($data, $importsettings, $csvrecord = null, $entryid = 0) {
        global $DB;

        $groupid = 0;

        // Group id
        if (!empty($importsettings['id'])) {
            $setting = $importsettings['id'];
            if (!empty($setting['name'])) {
                $csvname = $setting['name'];

                if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
                    $data->{"entry_{$entryid}_groupid"} = $csvrecord[$csvname];
                }
            }
            return $data;
        }

        // Group idnumber
        if (!empty($importsettings['idnumber'])) {
            $setting = $importsettings['idnumber'];
            if (!empty($setting['name'])) {
                $csvname = $setting['name'];

                if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
                    if ($groupid = $DB->get_field('groups', 'id', array('idnumber' => $csvrecord[$csvname]))) {
                        $data->{"entry_{$entryid}_groupid"} = $groupid;
                    }
                }
            }
            return $data;
        }

        return $data;
    }

    /**
     * Return array of sort options menu as
     * $fieldid,element => name, for the filter form.
     *
     *
     * @return null|array
     */
    public function get_sort_options_menu() {
        $fieldid = $this->id;
        return array(
            "$fieldid,idnumber" => get_string('idnumber'),
        );
    }

    /**
     * Returns the field alias for sql queries.
     *
     * @param string The field element to query
     * @return string
     */
    protected function get_sql_alias($element = null) {
        return 'g';
    }

    /**
     * @return string SQL fragment.
     */
    public function get_search_from_sql() {
        return " JOIN {groups} g ON g.id = e.groupid  ";
    }

    /**
     *
     */
    public function get_select_sql() {
        $elements = array(
            'g.idnumber AS groupidnumber',
            'g.name AS groupname',
            'g.hidepicture AS grouphidepic',
            'g.picture AS grouppic',
        );
        $selectsql = implode(',', $elements);
        return " $selectsql ";
    }

    /**
     *
     */
    public function get_sort_from_sql() {
        $sql = " LEFT JOIN {groups} g ON g.id = e.groupid  ";
        return array($sql, null);
    }


}
