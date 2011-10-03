<?php

/**
 * This file is part of the Dataform module for Moodle - http://moodle.org/.
 *
 * @package mod-dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 *
 * Moodle is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Moodle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Moodle. If not, see <http://www.gnu.org/licenses/>.
 */

//
// This function provides automatic linking to dataform contents of text
// fields where these fields have autolink enabled.
//
// Original code by Williams, Stronk7, Martin D.
// Modified for data module by Vy-Shane SF.

function dataform_filter($courseid, $text) {
    global $CFG, $DB;

    static $nothingtodo;
    static $contentlist;

    if (!empty($nothingtodo)) {   // We've been here in this page already
        return $text;
    }

    // if we don't have a courseid, we can't run the query, so
    if (empty($courseid)) {
        return $text;
    }

    // Create a list of all the resources to search for. It may be cached already.
    if (empty($contentlist)) {
        // We look for text field contents only, and only if the field has
        // autolink enabled (param1).
        $sql = 'SELECT dc.id AS contentid, ' .
               'de.id AS entryid, ' .
               'dc.content AS content, ' .
               'd.id AS dataid ' .
                    'FROM {dataform} d, ' .
                        '{dataform_fields} df, ' .
                        '{dataform_entries} de, ' .
                        '{dataform_contents} dc ' .
                        "WHERE (d.course = ? or d.course = '".SITEID."')" .
                        'AND d.id = df.dataid ' .
                        'AND df.id = dc.fieldid ' .
                        'AND d.id = de.dataid ' .
                        'AND de.id = dc.entryid ' .
                        "AND df.type = 'text' " .
                        "AND " . $DB->sql_compare_text('df.param1', 1) . " = '1'";

        if (!$datacontents = $DB->get_records_sql($sql, array($courseid))) {
            return $text;
        }

        $contentlist = array();

        foreach ($datacontents as $datacontent) {
            $currentcontent = trim($datacontent->content);
            $strippedcontent = strip_tags($currentcontent);

            if (!empty($strippedcontent)) {
                $contentlist[] = new filterobject(
                                        $currentcontent,
                                        '<a class="dataform autolink" title="'.
                                        $strippedcontent.'" href="'.
                                        $CFG->wwwroot.'/mod/dataform/view.php?d='. $datacontent->dataid .
                                        '&amp;eid='. $datacontent->entryid .'">',
                                        '</a>', false, true);
            }
        } // End foreach
    }
    return  filter_phrases($text, $contentlist);  // Look for all these links in the text
}
