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
 * @copyright 2012 Itamar Tzadok 
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("$CFG->dirroot/mod/dataform/view/view_class.php");
require_once("$CFG->libdir/pdflib.php");

class dataform_view_pdf extends dataform_view_base {

    const EXPORT_ALL = 'all';
    const EXPORT_PAGE = 'page';
    const PAGE_BREAK = '<div class="pdfpagebreak"></div>';

    protected $type = 'pdf';
    protected $_editors = array('section', 'param2', 'param3', 'param4');
    protected $_vieweditors = array('section', 'param2');
    protected $_settings = null;
    protected $_tmpfiles = null;

    /**
     *
     */
    public function __construct($df = 0, $view = 0) {       
        parent::__construct($df, $view);
        if (!empty($this->view->param1)) {
            $this->_settings = unserialize($this->view->param1);
        } else {
            $this->_settings = (object) array(
                'orientation' => '',
                'unit' => 'mm',
                'format' => 'LETTER',
                'destination' => 'I',
                'transparency' => 0.5,
                'header' => (object) array(
                    'enabled' => false,
                    'margintop' => 0,
                    'marginleft' => 10,
                ),
                'footer' => (object) array(
                    'enabled' => false,
                    'margin' => 10,
                ),
                'margins' => (object) array(
                    'left' => 15,
                    'top' => 27,
                    'right' => -1,
                    'keep' => false,
                ),
                'pagebreak' => 'auto',
                'protection' => (object) array(
                    'permissions' => array('print', 'copy'),
                    'user_pass' => '',
                    'owner_pass' => null,
                    'mode' => 0,
                    //'pubkeys' => null                    )
                ),
                'signature' => (object) array(
                    'password' => '',
                    'type' => 1,
                    'info' => array(
                        'Name' => '',
                        'Location' => '',
                        'Reason' => '',
                        'ContactInfo' => '',
                    )
                )       
            );
        }
    }

    /**
     * process any view specific actions
     */
    public function process_data() {
        global $CFG;

        // proces pdf export request
        if ($exportpdf = optional_param('exportpdf','', PARAM_ALPHA)) {
            $this->process_export($exportpdf);
        }

        // Do standard view processing
        return parent::process_data();
    }

    /**
     *
     */
    public function process_export($range = self::EXPORT_PAGE) {
        global $CFG;
      
        $settings = $this->_settings;
        $this->_tmpfiles = array();

        // Generate the pdf
        $pdf = new dfpdf($settings);
        
        // Set margins
        $pdf->SetMargins($settings->margins->left, $settings->margins->top, $settings->margins->right);
        
        // Set header
        if (!empty($settings->header->enabled)) {
            $pdf->setHeaderMargin($settings->header->margintop);
            $this->set_header($pdf);
        } else {
            $pdf->setPrintHeader(false);
        }
        // Set footer
        if (!empty($settings->footer->enabled)) {
            $pdf->setFooterMargin($settings->footer->margin);
            //$this->set_footer($pdf);
        } else {
            $pdf->setPrintFooter(false);
        }
        
        //Protection
        $protection = $settings->protection;
        $pdf->SetProtection(
            $protection->permissions,
            $protection->user_pass,
            $protection->owner_pass,
            $protection->mode
            //$protection->pubkeys
        ); 

        // Set document signature
        $this->set_signature($pdf);
            
        // Paging
        if (empty($settings->pagebreak)) {
            $pdf->SetAutoPageBreak(false, 0);
        }
/*        
        // Set the content
        if ($range == self::EXPORT_ALL) {
            $entries = new dataform_entries($this->_df, $this);
            $options = array();
            // Set a filter to take it all
            $filter = $this->get_filter();
            $filter->perpage = 0;
            $options['filter'] = $filter;
            // do we need ratings?
            if ($ratingoptions = $this->is_rating()) {
                $options['ratings'] = $ratingoptions;
            }
            // do we need comments?
            
            // Get the entries
            $entries->set_content($options);
            $this->_entries->set_content(array('entriesset' => $entries));
        } else {
            $this->set_content();
        }
*/
        $this->set_content();
        
        $content = array();
        if ($settings->pagebreak == 'entry') {
            $entries = $this->_entries->entries();
            foreach ($entries as $eid => $entry) {
                $entriesset = new object;
                $entriesset->max = 1;
                $entriesset->found = 1;
                $entriesset->entries = array($eid => $entry);
                $this->_entries->set_content(array('entriesset' => $entriesset));
                $pages = explode(self::PAGEBREAK, $this->display(array('tohtml' => true, 'controls' => false, 'entryactions' => false)));
                $content += $pages;
            }
        } else {
            $content = explode(self::PAGE_BREAK, $this->display(array('tohtml' => true, 'controls' => false, 'entryactions' => false)));
        }


        foreach ($content as $pagecontent) {
        
            $pdf->AddPage();

            // Set frame
            $this->set_frame($pdf);
            
            // Set watermark
            $this->set_watermark($pdf);
            
            $pagecontent = $this->process_content_images($pagecontent);        
            $pdf->writeHTML($pagecontent);
        }
       
        // Send the pdf
        $pdf->Output('doc.pdf', $settings->destination);

        // Clean up temp files
        if ($this->_tmpfiles) {
            foreach ($this->_tmpfiles as $filepath) {
                unlink($filepath);
            }
        }

        exit;
    }

    /**
     *
     */
    public function get_pdf_settings() {
        return $this->_settings;
    }
    
    /**
     *
     */
    public static function get_permission_options() {
        return array(
            'print' => get_string('perm_print', 'dataformview_pdf'),
            'modify' => get_string('perm_modify', 'dataformview_pdf'),
            'copy' => get_string('perm_copy', 'dataformview_pdf'),
            'fill-forms' => get_string('perm_fill-forms', 'dataformview_pdf'),
            'extract' => get_string('perm_extract', 'dataformview_pdf'),
            'assemble' => get_string('perm_assemble', 'dataformview_pdf'),
            'print-high' => get_string('perm_print-high', 'dataformview_pdf'),
            //'owner' => get_string('perm_owner', 'dataformview_pdf'),
        );
    }

    /**
     * Overridden to process pdf specific area files
     */
    public function from_form($data) {
        $data = parent::from_form($data);
              
        // Save pdf specific template files
        $contextid = $this->_df->context->id;
        $imageoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('image'));
        $certoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('.crt'));
        
        // Pdf frame
        file_save_draft_area_files($data->pdfframe, $contextid, 'mod_dataform', 'view_pdfframe', $this->id(), $imageoptions);
        // Pdf watermark
        file_save_draft_area_files($data->pdfwmark, $contextid, 'mod_dataform', 'view_pdfwmark', $this->id(), $imageoptions);
        // Pdf cert
        file_save_draft_area_files($data->pdfcert, $contextid, 'mod_dataform', 'view_pdfcert', $this->id(), $certoptions);

        return $data;
    }

    /**
     * Overridden to process pdf specific area files
     */
    public function to_form() {
        $data = parent::to_form();
      
        // Save pdf specific template files
        $contextid = $this->_df->context->id;
        $imageoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('image'));
        $certoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('.crt'));

        // Pdf frame
        $draftitemid = file_get_submitted_draft_itemid('pdfframe');
        file_prepare_draft_area($draftitemid, $contextid, 'mod_dataform', 'view_pdfframe', $this->id(), $imageoptions);
        $data->pdfframe = $draftitemid;

        // Pdf watermark
        $draftitemid = file_get_submitted_draft_itemid('pdfwmark');
        file_prepare_draft_area($draftitemid, $contextid, 'mod_dataform', 'view_pdfwmark', $this->id(), $imageoptions);
        $data->pdfwmark = $draftitemid;

        // Pdf certification
        $draftitemid = file_get_submitted_draft_itemid('pdfcert');
        file_prepare_draft_area($draftitemid, $contextid, 'mod_dataform', 'view_cert', $this->id(), $certoptions);
        $data->pdfcert = $draftitemid;

        return $data;
    }


    /**
     * Returns a fieldset of view options
     */
    public function generate_default_view() {
        // get all the fields
        if (!$fields = $this->_df->get_fields()) {
            return; // you shouldn't get that far if there are no user fields
        }

        // set views and filters menus and quick search
        $table = new html_table();
        $table->attributes['align'] = 'center';
        $table->attributes['cellpadding'] = '2';
        // first row: menus
        $row1 = new html_table_row();
        $viewsmenu = new html_table_cell('##viewsmenu##');
        $seperator = new html_table_cell('     ');
        $filtersmenu = new html_table_cell('##filtersmenu##');
        $quicksearch = new html_table_cell('##quicksearch##');
        $quickperpage = new html_table_cell('##quickperpage##');
        $row1->cells = array($viewsmenu, $seperator, $filtersmenu, $quicksearch, $quickperpage);
        foreach ($row1->cells as $cell) {
            $cell->style = 'border:0 none;';
        }
        // second row: add entries 
        $row2 = new html_table_row();
        $addentries = new html_table_cell('##addnewentry##     ##export:all##');
        $addentries->colspan = 5;
        $row2->cells = array($addentries);
        foreach ($row2->cells as $cell) {
            $cell->style = 'border:0 none;';
        }        
        // third row: paging bar
        $row3 = new html_table_row();
        $pagingbar = new html_table_cell('##pagingbar##');
        $pagingbar->colspan = 5;
        $row3->cells = array($pagingbar);
        foreach ($row3->cells as $cell) {
            $cell->style = 'border:0 none;';
        }
        // construct the table
        $table->data = array($row1, $row2, $row3);
        $sectiondefault = html_writer::table($table);
        $this->view->esection = html_writer::tag('div', $sectiondefault, array('class' => 'mdl-align'));


        // set content
        $table = new html_table();
        $table->attributes['align'] = 'center';
        $table->attributes['cellpadding'] = '2';
        // fields
        foreach ($fields as $field) {
            if ($field->field->id > 0) {
                $name = new html_table_cell($field->name(). ':');
                $name->style = 'text-align:right;';
                $content = new html_table_cell("[[{$field->name()}]]");
                $row = new html_table_row();
                $row->cells = array($name, $content);
                $table->data[] = $row;
            }
        }
        // actions
        $row = new html_table_row();
        $actions = new html_table_cell('##edit##  ##delete##');
        $actions->colspan = 2;
        $row->cells = array($actions);
        $table->data[] = $row;
        // construct the table
        $entrydefault = html_writer::table($table);
        $this->view->eparam2 = html_writer::tag('div', $entrydefault, array('class' => 'entry'));
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;
        
        $elements = array();


        // flatten the set to a list of elements
        foreach ($entriesset as $entry_definitions) {
            $elements = array_merge($elements, $entry_definitions);
        }

        // Add group heading 
        $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
        if ($name) {
            array_unshift($elements, array('html', $OUTPUT->heading($name, 3, 'main')));
        }
        // Wrap with entriesview
        array_unshift($elements, array('html', html_writer::start_tag('div', array('class' => 'entriesview'))));
        array_push($elements, array('html', html_writer::end_tag('div')));

        return $elements;
    }

    /**
     *
     */
    protected function entry_definition($fielddefinitions) {
        $elements = array();
        
        // split the entry template to tags and html
        $tags = array_keys($fielddefinitions);
        $parts = $this->split_tags($tags, $this->view->eparam2);
        
        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $fielddefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = array('html', $part);
            }
        }

        return $elements;      
    }

    /**
     *
     */
    protected function new_entry_definition($entryid = -1) {
        $elements = array();
        
        // get patterns definitions
        $fields = $this->_df->get_fields();
        $tags = array();
        $patterndefinitions = array();
        $entry = new object;
        foreach ($this->_tags['field'] as $fieldid => $patterns) {
            $field = $fields[$fieldid];
            $entry->id = $entryid;
            $options = array('edit' => true, 'manage' => true);
            if ($fielddefinitions = $field->get_definitions($patterns, $entry, $options)) {
                $patterndefinitions = array_merge($patterndefinitions, $fielddefinitions);
            }
            $tags = array_merge($tags, $patterns);
        }            
            
        // split the entry template to tags and html
        $parts = $this->split_tags($tags, $this->view->eparam2);

        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $patterndefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = array('html', $part);
            }
        }
        
        return $elements;
    }

    /**
     *
     */
    protected  function set_frame($pdf) {
        // Add to pdf frame image if any
        $fs = get_file_storage();
        if ($frame = $fs->get_area_files($this->_df->context->id, 'mod_dataform', 'view_pdfframe', $this->id(), '', false)) {
            $frame = reset($frame);

            $tmpdir = make_temp_directory('');
            $filename = $frame->get_filename();
            $filepath = $tmpdir. "files/$filename";
            if ($frame->copy_content_to($filepath)) {
                $pdf->Image($filepath,
                    '', // $x = '',
                    '', // $y = '',
                    0, // $w = 0,
                    0, // $h = 0,
                    '', // $type = '',
                    '', // $link = '',
                    '', // $align = '',
                    false, // $resize = false,
                    300, // $dpi = 300,
                    '', // $palign = '',
                    false, // $ismask = false,
                    false, // $imgmask = false,
                    0, // $border = 0,
                    false, // $fitbox = false,
                    false, // $hidden = false,
                    true // $fitonpage = false,                    
                );
            }
            unlink($filepath);
        }
    }

    /**
     *
     */
    protected  function set_watermark($pdf) {
        // Add to pdf watermark image if any
        $fs = get_file_storage();
        if ($wmark = $fs->get_area_files($this->_df->context->id, 'mod_dataform', 'view_pdfwmark', $this->id(), '', false)) {
            $wmark = reset($wmark);

            $tmpdir = make_temp_directory('');
            $filename = $wmark->get_filename();
            $filepath = $tmpdir. "files/$filename";
            if ($wmark->copy_content_to($filepath)) {
                list($wmarkwidth,$wmarkheight,) = array_values($wmark->get_imageinfo());
                // TODO 25.4 in Inch (assuming unit in mm) and 72 dpi by default when image dims not specified
                $wmarkwidthmm = $wmarkwidth*25.4/72;
                $wmarkheightmm = $wmarkheight*25.4/72;
                $pagedim = $pdf->getPageDimensions();
                $centerx = ($pagedim['wk']-$wmarkwidthmm)/2;
                $centery = ($pagedim['hk']-$wmarkheightmm)/2;

                $pdf->SetAlpha($this->_settings->transparency);
                $pdf->Image($filepath,
                    $centerx, // $x = '',
                    $centery // $y = '',
                );
                $pdf->SetAlpha(1);
            }
            unlink($filepath);
        }
    }
       
    /**
     *
     */
    protected  function set_signature($pdf) {
        $fs = get_file_storage();
        if ($cert = $fs->get_area_files($this->_df->context->id, 'mod_dataform', 'view_pdfcert', $this->id(), '', false)) {
            $cert = reset($cert);

            $tmpdir = make_temp_directory('');
            $filename = $cert->get_filename();
            $filepath = $tmpdir. "files/$filename";
            if ($cert->copy_content_to($filepath)) {
                $signsettings = $this->_settings->signature;
                $pdf->setSignature("file://$filepath", "file://$filepath", $signsettings->password, '', $signsettings->type, $signsettings->info);
            }
            unlink($filepath);
        }
    }
    
    /**
     *
     */
    protected function set_header($pdf) {
        if (empty($this->view->eparam3)) {
            return;
        }
        
        // Rewrite plugin file urls
        $content = file_rewrite_pluginfile_urls(
            $this->view->eparam3,
            'pluginfile.php',
            $this->_df->context->id,
            'mod_dataform',
            "viewparam3",
            $this->id()
        );

        $content = $this->process_content_images($content);
        $pdf->SetHeaderData('', 0, '', $content);              
    }

    /**
     *
     */
    protected function set_footer($pdf) {
        if (empty($this->view->eparam4)) {
            return;
        }
        
        // Rewrite plugin file urls
        $content = file_rewrite_pluginfile_urls(
            $this->view->eparam4,
            'pluginfile.php',
            $this->_df->context->id,
            'mod_dataform',
            "viewparam4",
            $this->id()
        );

        $content = $this->process_content_images($content);
        //$pdf->SetFooterData('', 0, '', $content);               
    }

    /**
     *
     */
    protected function process_content_images($content) {
        global $CFG;
        
        if (preg_match_all("%$CFG->wwwroot/pluginfile.php(/[^.]+.jpg)%", $content, $matches)) {

            $fs = get_file_storage();
            $tmpdir = make_temp_directory('');
            foreach ($matches[1] as $imagepath) {
                if (!$file = $fs->get_file_by_hash(sha1($imagepath)) or $file->is_directory()) {
                    continue;
                }
                //$filecontent = $file->get_content();
                $tmpdir = make_temp_directory('');
                $filename = $file->get_filename();
                $filepath = $tmpdir. "files/$filename";
                if ($file->copy_content_to($filepath)) {
                    $replacements["$CFG->wwwroot/pluginfile.php$imagepath"] = $filepath;//. $filecontent;
                    $this->_tmpfiles[] = $filepath;
                }
            }
            // Replace content
            $content = str_replace(array_keys($replacements), $replacements, $content);
        }
        return $content;
    }
}

// Extend the TCPDF class to create custom Header and Footer
class dfpdf extends pdf {
    
    protected $_dfsettings;

    public function __construct($settings) {
        parent::__construct($settings->orientation, $settings->unit, $settings->format);
        $this->_dfsettings = $settings;
    }
    
    //Page header
    public function Header() {
        // Adjust X to override left margin
        $x = $this->GetX();
        $this->SetX($this->_dfsettings->header->marginleft);
        $this->writeHtml($this->header_string);
        // Reset X to original
        $this->SetX($x);
    }

    // Page footer
    public function Footer() {
        // Position at 15 mm from bottom
        $this->SetY(-15);
        // Set font
        $this->SetFont('helvetica', 'I', 8);
        // Page number
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}