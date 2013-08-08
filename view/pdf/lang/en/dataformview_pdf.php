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
 * @package dataformview
 * @subpackage pdf
 * @copyright Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'PDF';

$string['image'] = 'Image';
// PDF Settings
$string['pdfsettings'] = 'PDF settings';
$string['docname'] = 'Document name';
$string['docname_help'] = 'A Pattern for the name of the downloaded PDF document. The name pattern can consist of any of the field patterns included in the view.';
$string['format'] = 'Format';
$string['orientation'] = 'Orientation';
$string['auto'] = 'Auto';
$string['portrait'] = 'Portrait';
$string['landscape'] = 'Landscape';
$string['landscape'] = 'Landscape';
$string['LETTER'] = 'LETTER';
$string['A4'] = 'A4';
$string['destination'] = 'Destination';
$string['dest_D'] = 'Send to download';
$string['dest_I'] = 'Send to browser';
$string['dest_F'] = 'Save to server file';
$string['dest_FI'] = 'Save to server file and send to browser';
$string['dest_FD'] = 'Save to server file and send to browser and force download';
$string['unit'] = 'Unit';
$string['unit_mm'] = 'Millimeter';
$string['unit_pt'] = 'Point';
$string['unit_cm'] = 'Centimeter';
$string['unit_in'] = 'Inch';
// PDF Frame
$string['pdfframe'] = 'PDF Frame';
$string['pdfframe_help'] = 'PDF Frame';
// PDF Watermark
$string['pdfwmark'] = 'PDF Watermark';
$string['pdfwmark_help'] = 'PDF Watermark';
$string['transparency'] = 'Watermark transparency';
$string['transparency_help'] = 'Watermark transparency';
// PDF TOC
$string['pdftoc'] = 'PDF TOC';
$string['tocpage'] = 'TOC Page';
$string['tocpage_help'] = 'Page number (e.g. 1). The page where the TOC should be displayed. 
By default (page number empty or 0) the TOC is not displayed.
<p>Page bookmarks can be added in the repeatd entry section with tags in the format: #@bookmark-type:bookmark-level:bookmark-text@#</p>
<p><b>Bookmark type:</b> PDF-GBM for group bookmark, PDF-BM for regular bookmark. Group bookmarks display only the first occurence 
in a sequence of the same bookmark-text.<br />
<b>Bookmark-level:</b> 0 - n. If the html TOC template is used, 
it must contain a template definition for each bookmark level that is specified in the content.<br />
<b>Bookmark-text:</b> The text that will be displayed for the bookmark in the TOC. 
Field tags can be used to extract the text from the entry content.</p>
<p>Example:</p>
<p>#@PDF-GBM:0:##author:name##@#<br />
#@PDF-BM:1:[[Date masked]]@#</p>
';
$string['tocname'] = 'TOC Name';
$string['tocname_help'] = 'TOC Name';
$string['toctitle'] = 'TOC Title';
$string['toctitle_help'] = 'Html for the TOC title that is displayed above the table of content. 
For example, &lt;h1&gt;TABLE OF CONTENT&lt;/h1&gt; will display the TOC page with the title \'TABLE OF CONTENT\' 
styled as heading1.';
$string['toctmpl'] = 'TOC Template';
$string['toctmpl_help'] = 'The TOC Template specifies the html for displaying the bookmarks in the table of content. 
The #TOC_DESCRIPTION# and #TOC_PAGE_NUMBER# must be enclosed in span tags.
<p>For example:</p>
<p>&lt;div&gt;&lt;span style="color:#FF0000;"&gt;#TOC_DESCRIPTION#&lt;/span&gt; ... &lt;span&gt;#TOC_PAGE_NUMBER#&lt;/span&gt;&lt;/div&gt;<br />
&lt;div&gt;&lt;span style="color:#00FF00;"&gt;#TOC_DESCRIPTION#&lt;/span&gt; ... &lt;span&gt;#TOC_PAGE_NUMBER#&lt;/span&gt;&lt;/div&gt;</p>
<p>By this simple template the pdf file will display the TOC with the first bookmark level in red and the second in green. 
Page numbers will be displayed separated from the bookmark descriptions by ellipsis.</p>
<p>Leave empty to display a default non-html TOC.</p>';
$string['pdfsignature'] = 'Digital signature';
$string['certification'] = 'Certification';
$string['certpassword'] = 'Password';
$string['certtype'] = 'Permissions';
$string['certinfoname'] = 'Info: name';
$string['certinfoloc'] = 'Info: location';
$string['certinforeason'] = 'Info: reason';
$string['certinfocontact'] = 'Info: contact';
$string['certperm2'] = 'Filling in forms, instantiating page templates, signing';
$string['certperm3'] = 'Filling in forms, instantiating page templates, signing, annotation management';
// Protection
$string['protmode0'] = 'RSA 40 bit';
$string['protmode1'] = 'RSA 128 bit';
$string['protmode2'] = 'AES 128 bit';
$string['protmode3'] = 'AES 256 bit';
$string['pdfprotection'] = 'PDF Protection';
$string['protuserpass'] = 'User password';
$string['protownerpass'] = 'Owner password';
$string['protmode'] = 'Mode';
$string['protperms'] = 'Permissions denied';
// Permissions
$string['perm_print'] = 'Print the document';
$string['perm_modify'] = 'Modify content';
$string['perm_copy'] = 'Copy/extract content';
$string['perm_annot-forms'] = 'Manage annotations and forms';
$string['perm_fill-forms'] = 'Fill in forms';
$string['perm_extract'] = 'Extract content';
$string['perm_assemble'] = 'Assemble';
$string['perm_print-high'] = 'Print high level';
$string['perm_owner'] = 'Manage protection (inverted logic - only for public-key)';
$string['pdfheader'] = 'PDF Header';
$string['pdffooter'] = 'PDF Footer';

$string['enabled'] = 'Enabled';
$string['margin'] = 'Margin';
$string['pdfmargins'] = 'PDF Margins and paging';
$string['marginleft'] = 'Left';
$string['margintop'] = 'Top';
$string['marginright'] = 'Right';
$string['marginkeep'] = 'Keep';
$string['pagebreak'] = 'Page break';
$string['imagetypes'] = 'jpg|jpeg|gif|png|tif';