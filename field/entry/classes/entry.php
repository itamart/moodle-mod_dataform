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
 * @package dataformfield_entry
 * @copyright 2015 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class dataformfield_entry_entry extends \mod_dataform\pluginbase\dataformfield_internal {

    const INTERNALID = -5;

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
            'type' => 'entry',
            'name' => get_string('fieldname', 'dataformfield_entry'),
            'description' => '',
            'visible' => 2,
            'editable' => -1,
        );
        return $field;
    }

    /**
     * Update the activity entrytypes setting.
     */
    public function update($data) {

        if (!empty($data->entrytypes)) {
            $this->df->update((object) array('entrytypes' => $data->entrytypes));
        }

        return $this->id;
    }

    /**
     * Overrides {@link dataformfield::prepare_import_content()} to set import of entry::userid.
     *
     * @return stdClass
     */
    public function prepare_import_content($data, $importsettings, $csvrecord = null, $entryid = 0) {
        global $DB;

        $csvname = '';

        // Entry id.
        if (!empty($importsettings['id'])) {
            $setting = $importsettings['id'];
            if (!empty($setting['name'])) {
                $csvname = $setting['name'];

                if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
                    $data->{"entry_{$entryid}_id"} = $csvrecord[$csvname];
                    return $data;
                }
            }
        }

        // Entry type.
        if (!empty($importsettings['type'])) {
            $setting = $importsettings['type'];
            if (!empty($setting['name'])) {
                $csvname = $setting['name'];

                if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
                    $data->{"entry_{$entryid}_type"} = $csvrecord[$csvname];
                    return $data;
                }
            }
        }

        return $data;
    }

    /**
     * Returns the field alias for sql queries.
     *
     * @param string The field element to query
     * @return string
     */
    protected function get_sql_alias($element = null) {
        return 'e';
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
        $fieldname = $this->name;
        return array(
            "$fieldid,id" => "$fieldname ". get_string('id', 'dataformfield_entry'),
            "$fieldid,type" => "$fieldname ". get_string('type', 'dataformfield_entry'),
        );
    }

    /**
     *
     */
    public function get_entry_ids_for_element($element) {
        global $DB;

        $conditions = array();
        $params = array();

        $conditions[] = ' dataid = ? ';
        $params[] = $this->dataid;

        if ($element == 'type') {
            $conditions[] = ' type != ? ';
            $params[] = '';
        } else if ($element == 'id') {
            $conditions[] = ' id != ? ';
            $params[] = 0;
        }

        $where = implode(' AND ', $conditions);
        if ($eids = $DB->get_records_select_menu('dataform_entries', $where, $params, '', 'id,id as eid')) {
            return $eids;
        }

        return null;
    }

}
