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
 * @package dataformfield
 * @subpackage dataformview
 * @copyright 2013 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['pluginname'] = 'Dataform view';
$string['embedded'] = 'Embedded';
$string['missingview'] = 'You must select a view or clear the dataform selection';
$string['nodataforms'] = 'No dataforms are found in this course';
$string['noviews'] = 'No views are found for the selected dataform';
$string['dataform'] = 'Dataform';
$string['dataform_help'] = 'Choose the Dataform whose view you want to display. You can select any Dataform in this site for which you have \'managetemplates\' capability.';
$string['view'] = 'View';
$string['view_help'] = 'Choose the view you want to display from the selectd Dataform.';
$string['filter'] = 'Filter';
$string['filter_help'] = 'Choose a filter of the selected Dataform. This filter will be applied to the selected view before displaying the view.';
$string['css'] = 'Custom css';
$string['css_help'] = 'Custom css';
$string['customsort'] = 'Custom sort';
$string['customsort_help'] = 'You can add custom sort criteria that would be applied to the displayed view. Each criterion is a comma separated [[field name]],ASC/DESC pair. One criterion per line. The pattern must be included in the selected view template. For example, assuming the patterns ##author:firstname## and [[Count]] are included in the selected view template, by the following criteria the view will display the entries sorted by the author first name in ascending order and counts in descending order:
<br />##author:firstname##,ASC
<br />[[Count]],DESC';
$string['customsearch'] = 'Custom search';
$string['customsearch_help'] = 'You can add custom search criteria that would be applied to the displayed view. Each criterion is a comma separated AND/OR,remote field pattern,0/1,operator,local field pattern/value. One criterion per line. The pattern must be included in the selected view template. For example, assuming the patterns ##author:firstname## is included in the selected view template, by the following criteria the view will display only entries by the author first name:
AND,##author:firstname##,0,=,##author:firstname##';
$string['filterby'] = 'Filter by';
$string['filterby_help'] = 'Select Entry author to include in the displayed view only entries by the author of the hosting entry.
<br />Select Entry group to include in the displayed view only entries by members of the hosting entry author\'s group.';
$string['entryauthor'] = 'Entry author';
$string['entrygroup'] = 'Entry group';
$string['viewbutton'] = 'View';