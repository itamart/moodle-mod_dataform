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
 * @subpackage entryauthor
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class dataformfield_entryauthor_entryauthor extends \mod_dataform\pluginbase\dataformfield_internal {

    const INTERNALID = -2;

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
            'type' => 'entryauthor',
            'name' => get_string('fieldname', 'dataformfield_entryauthor'),
            'description' => '',
            'visible' => 2,
            'editable' => -1,
            'internalname' => ''
        );
        return $field;
    }

    /**
     * Overrides {@link dataformfield::prepare_import_content()} to set import of entry::userid.
     *
     * @return stdClass
     */
    public function prepare_import_content($data, $importsettings, $csvrecord = null, $entryid = 0) {
        global $DB;

        $csvname = '';

        // Author id
        if (!empty($importsettings['id'])) {
            $setting = $importsettings['id'];
            if (!empty($setting['name'])) {
                $csvname = $setting['name'];

                if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
                    $data->{"entry_{$entryid}_userid"} = $csvrecord[$csvname];
                    return $data;
                }
            }
        }

        // Author username
        if (!empty($importsettings['username'])) {
            $setting = $importsettings['username'];
            if (!empty($setting['name'])) {
                $csvname = $setting['name'];

                if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
                    if ($userid = $DB->get_field('user', 'id', array('username' => $csvrecord[$csvname]))) {
                        $data->{"entry_{$entryid}_userid"} = $userid;
                        return $data;
                    }
                }
            }
        }

        // Author idnumber
        if (!empty($importsettings['idnumber'])) {
            $setting = $importsettings['idnumber'];
            if (!empty($setting['name'])) {
                $csvname = $setting['name'];

                if (isset($csvrecord[$csvname]) and $csvrecord[$csvname] !== '') {
                    if ($userid = $DB->get_field('user', 'id', array('idnumber' => $csvrecord[$csvname]))) {
                        $data->{"entry_{$entryid}_userid"} = $userid;
                    }
                }
            }
        }
        return $data;
    }

    /**
     *
     */
    public function get_sort_sql($element = null) {
        if ($element == 'name') {
            $element = 'id';
        }
        return parent::get_sort_sql($element);
    }

    /**
     *
     */
    public function get_search_sql($search) {
        global $USER;

        // Set search current user entries entries
        if ($search[0] == 'currentuser') {
            $search[0] = 'id';
            $search[3] = $USER->id;
            if ($search[1] == '' and $search[2] == '') {
                // IS EMPTY == NOT equal
                $search[1] = 'NOT';
                $search[2] = '=';
            } else if ($search[1] == 'NOT' and $search[2] == '') {
                // NOT EMPTY == IS equal
                $search[1] = '';
                $search[2] = '=';
            } else {
                // No other settings for this element should be processed
                return null;
            }
        }

        return parent::get_search_sql($search);
    }

    /**
     * Returns the field alias for sql queries.
     *
     * @param string The field element to query
     * @return string
     */
    protected function get_sql_alias($element = null) {
        return 'u';
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
            "$fieldid,firstname" => get_string('userfirstname', 'dataformfield_entryauthor'),
            "$fieldid,lastname" => get_string('userlastname', 'dataformfield_entryauthor'),
            "$fieldid,username" => get_string('userusername', 'dataformfield_entryauthor'),
            "$fieldid,idnumber" => get_string('useridnumber', 'dataformfield_entryauthor'),
        );
    }

    /**
     * Return array of search options menu as
     * $fieldid,element => name, for the filter form.
     *
     * @return null|array
     */
    public function get_search_options_menu() {
        $fieldid = $this->id;
        $currentuser = array("$fieldid,currentuser" => get_string('currentuser', 'dataformfield_entryauthor'));
        return array_merge($currentuser, $this->get_sort_options_menu());
    }

    /**
     * @return string SQL fragment.
     */
    public function get_search_from_sql() {
        return " JOIN {user} u ON u.id = e.userid ";
    }

    /**
     * Returns an array of distinct content of the field.
     *
     * @param string $element
     * @param int $sortdir Sort direction 0|1 ASC|DESC
     * @return array
     */
    public function get_distinct_content($element, $sortdir = 0) {
        global $CFG, $DB;

        $sortdir = $sortdir ? 'DESC' : 'ASC';
        $contentfull = $this->get_sort_sql();
        $sql = "SELECT DISTINCT $contentfull
                FROM {user} u
                    JOIN {dataform_entries} e ON u.id = e.userid
                WHERE e.dataid = ? AND  $contentfull IS NOT NULL
                ORDER BY $contentfull $sortdir";

        $distinctvalues = array();
        if ($options = $DB->get_records_sql($sql, array($this->df->id))) {
            if ($this->internalname == 'name') {
                $internalname = 'id';
            } else {
                $internalname = $this->internalname;
            }
            foreach ($options as $data) {
                $value = $data->{$internalname};
                if ($value === '') {
                    continue;
                }
                $distinctvalues[] = $value;
            }
        }
        return $distinctvalues;
    }
}
