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

class dataformview_pdf extends dataformview_base {

    const EXPORT_ALL = 'all';
    const EXPORT_PAGE = 'page';
    const EXPORT_ENTRY = 'entry';
    const PAGE_BREAK = '<div class="pdfpagebreak"></div>';

    protected $type = 'pdf';
    protected $_editors = array('section', 'param2', 'param3', 'param4');
    protected $_vieweditors = array('section', 'param2', 'param3', 'param4');
    protected $_settings = null;
    protected $_tmpfiles = null;

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
     *
     */
    public function __construct($df = 0, $view = 0) {       
        parent::__construct($df, $view);

        if (!empty($this->view->param1)) {
            $settings = unserialize($this->view->param1);
        }    
        
        $this->_settings = (object) array(
            'docname' => !empty($settings->docname) ? $settings->docname : '',
            'orientation' => !empty($settings->orientation) ? $settings->orientation : '',
            'unit' => !empty($settings->unit) ? $settings->unit : 'mm',
            'format' => !empty($settings->format) ? $settings->format : 'LETTER',
            'destination' => !empty($settings->destination) ? $settings->destination : 'I',
            'transparency' => !empty($settings->transparency) ? $settings->transparency : 0.5,
            'pagebreak' => !empty($settings->pagebreak) ? $settings->pagebreak : 'auto',
            'toc' => (object) array(
                'page' => !empty($settings->toc->page) ? $settings->toc->page : '',
                'name' => !empty($settings->toc->name) ? $settings->toc->name : '',
                'title' => !empty($settings->toc->title) ? $settings->toc->title : '',
                'template' => !empty($settings->toc->template) ? $settings->toc->template : '',
            ),
            'header' => (object) array(
                'enabled' => !empty($settings->header->enabled) ? $settings->header->enabled : false,
                'margintop' => !empty($settings->header->margintop) ? $settings->header->margintop : 0,
                'marginleft' => !empty($settings->header->marginleft) ? $settings->header->marginleft : 10,
            ),
            'footer' => (object) array(
                'text' => !empty($this->view->eparam4) ? $this->view->eparam4 : '',
                'enabled' => !empty($settings->footer->enabled) ? $settings->footer->enabled : false,
                'margin' => !empty($settings->footer->margin) ? $settings->footer->margin : 10,
            ),
            'margins' => (object) array(
                'left' => !empty($settings->margins->left) ? $settings->margins->left : 15,
                'top' => !empty($settings->margins->top) ? $settings->margins->top : 27,
                'right' => !empty($settings->margins->right) ? $settings->margins->right : -1,
                'keep' => !empty($settings->margins->keep) ? $settings->margins->keep : false,
            ),
            'protection' => (object) array(
                'permissions' => !empty($settings->protection->permissions) ? $settings->protection->permissions : array('print', 'copy'),
                'user_pass' => !empty($settings->protection->user_pass) ? $settings->protection->user_pass : '',
                'owner_pass' => !empty($settings->protection->owner_pass) ? $settings->protection->owner_pass : null,
                'mode' => !empty($settings->protection->mode) ? $settings->protection->mode : 0,
                //'pubkeys' => null                    )
            ),
            'signature' => (object) array(
                'password' => !empty($settings->signature->password) ? $settings->signature->password : '',
                'type' => !empty($settings->signature->type) ? $settings->signature->type : 1,
                'info' => array(
                    'Name' => !empty($settings->signature->info->Name) ? $settings->signature->info->Name : '',
                    'Location' => !empty($settings->signature->info->Location) ? $settings->signature->info->Location : '',
                    'Reason' => !empty($settings->signature->info->Reason) ? $settings->signature->info->Reason : '',
                    'ContactInfo' => !empty($settings->signature->info->ContactInfo) ? $settings->signature->info->ContactInfo : '',
                )
            )       
        );
    }

    /**
     * process any view specific actions
     */
    public function process_data() {
        global $CFG;

        // proces pdf export request
        if (optional_param('pdfexportall', 0, PARAM_INT)) {
            $this->process_export(self::EXPORT_ALL);
        } else if (optional_param('pdfexportpage', 0, PARAM_INT)) {
            $this->process_export(self::EXPORT_PAGE);
        } else if ($exportentry = optional_param('pdfexportentry', 0, PARAM_INT)) {
            $this->process_export($exportentry);
        }

        // Do standard view processing
        return parent::process_data();
    }

    /**
     *
     */
    public function process_export($export = self::EXPORT_PAGE) {
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

        // Set the content
        if ($export == self::EXPORT_ALL) {
            $this->_filter->perpage = 0;
        } else if ($export == self::EXPORT_PAGE) {
            // Nothing to change in filter
        } else if ($export) {
            // Specific entry requested
            $this->_filter->eids = $export;
        }

        $this->set_content();

        // Exit if no entries
        if (!$this->_entries->entries()) {
            return;
        }
        
        $content = array();
        if ($settings->pagebreak == 'entry') {
            $entries = $this->_entries->entries();
            foreach ($entries as $eid => $entry) {
                $entriesset = new object;
                $entriesset->max = 1;
                $entriesset->found = 1;
                $entriesset->entries = array($eid => $entry);
                $this->_entries->set_content(array('entriesset' => $entriesset));
                $pages = explode(self::PAGE_BREAK, $this->display(array('export' => true, 'tohtml' => true, 'controls' => false, 'entryactions' => false)));
                $content = array_merge($content,$pages);
            }
        } else {
            $content = explode(self::PAGE_BREAK, $this->display(array('export' => true, 'tohtml' => true, 'controls' => false, 'entryactions' => false)));
        }


        foreach ($content as $pagecontent) {
        
            $pdf->AddPage();

            // Set page bookmarks
            $pagecontent = $this->set_page_bookmarks($pdf, $pagecontent);

            // Set frame
            $this->set_frame($pdf);
            
            // Set watermark
            $this->set_watermark($pdf);
            
            $pagecontent = $this->process_content_images($pagecontent);
            $this->write_html($pdf, $pagecontent);
        }
       
        // Set TOC
        if (!empty($settings->toc->page)) {
            $pdf->addTOCPage();
            if (!empty($settings->toc->title)) {
                $pdf->writeHTML($settings->toc->title);
            }
            
            if (empty($settings->toc->template)) {
                $pdf->addTOC($settings->toc->page, '', '.', $settings->toc->name);
            } else {
                $templates = explode("\n", $settings->toc->template);
                $pdf->addHTMLTOC($settings->toc->page, $settings->toc->name, $templates);
            }
            $pdf->endTOCPage();
        }

        // Send the pdf
        $documentname = optional_param('docname', $this->get_documentname($settings->docname), PARAM_TEXT);
        $destination = optional_param('dest', $settings->destination, PARAM_ALPHA);
        $pdf->Output("$documentname.pdf", $destination);

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
    public function to_form($data = null) {
        $data = parent::to_form($data);
      
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
     * Override parent to remove pdf bookmark tags
     */
    public function display(array $options = array()) {
        // For export just return the parent
        if (!empty($options['export'])) {
            return parent::display($options);
        }
        // For display we need to clean up the bookmark patterns    
        if (!empty($options['tohtml'])) {
            $displaycontent = parent::display($options);
            // Remove the bookmark patterns
            $displaycontent = preg_replace("%#@PDF-[G]*BM:\d+:[^@]*@#%", '', $displaycontent);
        
            return $displaycontent;
        } else {
            $options['tohtml'] = true;
            $displaycontent = parent::display($options);
            // Remove the bookmark patterns
            $displaycontent = preg_replace("%#@PDF-[G]*BM:\d+:[^@]*@#%", '', $displaycontent);
        
            echo $displaycontent;            
        }
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
    protected function set_page_bookmarks($pdf, $pagecontent) {
        $settings = $this->_settings;
        static $bookmarkgroup = '';
        
        // Find all patterns ##PDFBM:d:any text##
        if (preg_match_all("%#@PDF-[G]*BM:\d+:[^@]*@#%", $pagecontent, $matches)) {
            if (!empty($settings->toc->page)) {
                // Get the array of templates
                $templates = explode("\n", $settings->toc->template);
                
                // Add a bookmark for each pattern
                foreach ($matches[0] as $bookmark) {
                    $bookmark = trim($bookmark, '#@');
                    list($bmtype, $bmlevel, $bmtext) = explode(':', $bookmark, 3);
                    
                    // Must have a template for the TOC level
                    if (empty($templates[$bmlevel])) {
                        continue;;
                    }
                    
                    // Add a group bookmark only if new
                    if ($bmtype == 'PDF-GBM') {
                        if ($bmtext != $bookmarkgroup) {
                            $pdf->Bookmark($bmtext, $bmlevel);
                            $bookmarkgroup = $bmtext;
                        }
                    } else {
                        $pdf->Bookmark($bmtext, $bmlevel);
                    }
                }                   
             }
            // remove patterns from page content
            $pagecontent = str_replace($matches[0], '', $pagecontent);
        }
        return $pagecontent;
    }
    
    /**
     *
     */
    protected function set_frame($pdf) {
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
    protected function set_watermark($pdf) {
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
    protected function set_signature($pdf) {
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
        // Add the Dataform css to content
        if ($this->_df->data->css) {
            $style = html_writer::tag('style', $this->_df->data->css, array('type' => 'text/css'));
            $content = $style. $content;
        }

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
        $pdf->SetFooterData('', 0, '', $content);               
    }

    /**
     *
     */
    protected function process_content_images($content) {
        global $CFG;

        $replacements = array();
        $tmpdir = make_temp_directory('files');
            
        // Does not support theme images (until we find a way to process them)

        // Process pluginfile images
        $imagetypes = get_string('imagetypes', 'dataformview_pdf');
        if (preg_match_all("%$CFG->wwwroot/pluginfile.php(/[^.]+.($imagetypes))%", $content, $matches)) {
            $replacements = array();
            
            $fs = get_file_storage();
            foreach ($matches[1] as $imagepath) {
                if (!$file = $fs->get_file_by_hash(sha1($imagepath)) or $file->is_directory()) {
                    continue;
                }
                $filename = $file->get_filename();
                $filepath = "$tmpdir/$filename";
                if ($file->copy_content_to($filepath)) {
                    $replacements["$CFG->wwwroot/pluginfile.php$imagepath"] = $filepath;
                    $this->_tmpfiles[] = $filepath;
                }
            }
        }
        // Replace content
        if ($replacements) {
            $content = str_replace(array_keys($replacements), $replacements, $content);
        }
        return $content;
    }

    /**
     *
     */
    protected function write_html($pdf, $content) {
        
        // Add the Dataform css to content
        if ($this->_df->data->css) {
            $style = html_writer::tag('style', $this->_df->data->css, array('type' => 'text/css'));
            $content = $style. $content;
        }
        
        $pdf->writeHTML($content);
    }
    
    /**
     *
     */
    protected function get_documentname($namepattern) {
        $docname = 'doc';

        
        return "$docname.pdf";
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
        if (!empty($this->header_string)) {
            $text = $this->set_page_numbers($this->header_string);
            $this->writeHtml($text);
        }
        // Reset X to original
        $this->SetX($x);
    }

    // Page footer
    public function Footer() {
        if (!empty($this->_dfsettings->footer->text)) {
            $text = $this->set_page_numbers($this->_dfsettings->footer->text);
            $this->writeHtml($text);
        }
    }
    
    protected function set_page_numbers($text) {
        $replacements = array(
            '##pagenumber##' => $this->getAliasNumPage(),
            '##totalpages##' => $this->getAliasNbPages(),
        );
        $text = str_replace(array_keys($replacements), $replacements, $text);
        return $text;
    }
}