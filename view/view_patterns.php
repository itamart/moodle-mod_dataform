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
 * @copyright 2012 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

/**
 * Base class for view patterns
 */
class dataformview_patterns {

    const PATTERN_SHOW_IN_MENU = 0;
    const PATTERN_CATEGORY = 1;

    protected $_view = null;

    /**
     * Constructor
     */
    public function __construct(&$view) {
        $this->_view = $view;
    }

    /**
     *
     */
    public function search($text) {
        $viewid = $this->_view->view->id;
        
        $found = array();
        // Fixed patterns
        $patterns = array_keys($this->patterns());
        foreach ($patterns as $pattern) {
            if (strpos($text, $pattern) !== false) {
                $found[] = $pattern;
            }
        }

        // Regexp patterns
        if ($patterns = array_keys($this->regexp_patterns())) {
            foreach ($patterns as $pattern) {
                if (preg_match_all("/$pattern/", $text, $matches)) {
                    foreach ($matches[0] as $match) {
                        $found[$match] = $match;
                    }
                }
            }
        }

        return $found;
    }

    /**
     *
     */
    public final function get_menu($showall = false) {
        // the default menu category for views
        $patternsmenu = array();
        foreach ($this->patterns() as $tag => $pattern) {
            if ($showall or $pattern[self::PATTERN_SHOW_IN_MENU]) {
                // which category
                if (!empty($pattern[self::PATTERN_CATEGORY])) {
                    $cat = $pattern[self::PATTERN_CATEGORY];
                } else {
                    $cat = get_string('views', 'dataform');
                }
                // prepare array
                if (!isset($patternsmenu[$cat])) {
                    $patternsmenu[$cat] = array($cat => array());
                }
                // add tag
                $patternsmenu[$cat][$cat][$tag] = $tag;
            }
        }
        return $patternsmenu;
    }

    /**
     *
     */
    public function get_replacements($tags = null, $entry = null, array $options = array()) {
        global $CFG, $OUTPUT;
        $view = $this->_view;
        $viewname = $view->name();
        
        $info = array_keys($this->info_patterns());
        $ref = array_keys($this->ref_patterns());
        $userpref = array_keys($this->userpref_patterns());
        $actions = array_keys($this->action_patterns());
        $paging = array_keys($this->paging_patterns());
        $paging = array_keys($this->paging_patterns());
        
        $options['filter'] = $view->get_filter();
        $options['baseurl'] = new moodle_url($view->get_baseurl(), array('sesskey' => sesskey()));
        $edit = !empty($options['edit']) ? $options['edit'] : false;

        $replacements = array();

        foreach ($tags as $tag) {
            if (in_array($tag, $info)) {
                $replacements[$tag] = $this->get_info_replacements($tag, $entry, $options);
            } else if (in_array($tag, $ref)) {
                $replacements[$tag] = $this->get_ref_replacements($tag, $entry, $options);
            } else if (in_array($tag, $userpref)) {
                $replacements[$tag] = $this->get_userpref_replacements($tag, $entry, $options);
            } else if (in_array($tag, $actions)) {
                $replacements[$tag] = $this->get_action_replacements($tag, $entry, $options);
            } else if (in_array($tag, $paging)) {
                $replacements[$tag] = $this->get_paging_replacements($tag, $entry, $options);
            } else if ($this->is_regexp_pattern($tag)) {
                $replacements[$tag] = $this->get_regexp_replacements($tag, $entry, $options);
            }
        }

        return $replacements;
    }

    /**
     *
     */
    protected function get_regexp_replacements($tag, $entry = null, array $options = null) {
        global $OUTPUT;

        $df = $this->_view->get_df();

        static $views = null;
        if ($views === null) {
            $views = $df->get_views();
        }

        if ($views) {
            foreach ($views as $view) {
                $viewname = $view->name();
                if (strpos($tag, "#{{viewlink:$viewname;") === 0) {
                    list(, $linktext, $urlquery, ) = explode(';', $tag);
                    // Pix icon for text
                    if (strpos($linktext, '_pixicon:') === 0) {
                        list(, $icon, $titletext) = explode(':', $linktext);
                        $linktext = $OUTPUT->pix_icon($icon, $titletext);
                    }    
                    // Replace pipes in urlquery with &
                    $urlquery = str_replace('|', '&', $urlquery);
                    return html_writer::link($view->get_baseurl()->out(false). "&$urlquery", $linktext);
                }
                if (strpos($tag, "#{{viewsesslink:$viewname;") === 0) {
                    list(, $linktext, $urlquery, ) = explode(';', $tag);
                    // Pix icon for text
                    if (strpos($linktext, '_pixicon:') === 0) {
                        list(, $icon, $titletext) = explode(':', $linktext);
                        $linktext = $OUTPUT->pix_icon($icon, $titletext);
                    }    
                    $urlquery = str_replace('|', '&', $urlquery);
                    $linkparams = array('sesskey' => sesskey());
                    $viewlink = new moodle_url($view->get_baseurl(), $linkparams);
                    return html_writer::link($viewlink->out(false). "&$urlquery", $linktext);
                }
            }
        }

        return '';
    }

    /**
     *
     */
    protected function get_info_replacements($tag, $entry = null, array $options = null) {
        $replacement = '';

        switch ($tag) {
            case '##numentriestotal##':
                $replacement = empty($options['entriescount']) ? 0 : $options['entriescount'];
                break;

            case '##numentriesdisplayed##':
                $replacement = empty($options['entriesfiltercount']) ? 0 : $options['entriesfiltercount'];
                break;
        }
        return $replacement;
    }

    /**
     *
     */
    protected function get_ref_replacements($tag, $entry = null, array $options = null) {
        if ($tag == '##viewsmenu##') {
            return $this->print_views_menu($options, true);
        }

        if ($tag == '##filtersmenu##') {
            return $this->print_filters_menu($options, true);
        }

        // View url
        if ($tag == '##viewurl##') {
            return $this->get_viewurl_replacement();
        }
        
        if (strpos($tag, '##viewurl:') === 0) {
            list(, $viewname) = explode(':', trim($tag, '#'));
            return $this->get_viewurl_replacement($viewname);
        }

        // View content
        if (strpos($tag, '##viewcontent:') === 0) {
            list(, $viewname) = explode(':', trim($tag, '#'));
            return $this->get_viewcontent_replacement($viewname);
        }

        return '';
    }

    /**
     *
     */
    protected function get_userpref_replacements($tag, $entry = null, array $options = null) {
        $view = $this->_view;
        $filter = $view->get_filter();
        
        if (!$view->is_forcing_filter() and (!$filter->id or !empty($options['entriescount']))) {
            switch ($tag) {
                case '##quicksearch##': return $this->print_quick_search($filter, true);
                case '##quickperpage##': return $this->print_quick_perpage($filter, true);
                case '##advancedfilter##': return $this->print_advanced_filter($filter, true);
            }
        }
        return '';
    }

    /**
     *
     */
    protected function get_action_replacements($tag, $entry = null, array $options = null) {
        global $CFG, $OUTPUT;

        $replacement = '';
        
        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = new moodle_url($view->get_baseurl());
        $baseurl->param('sesskey', sesskey());

        $showentryactions = (!empty($options['showentryactions'])
                                or has_capability('mod/dataform:manageentries', $df->context));
        // TODO: move to a view attribute so as to call only once
        // Can this user registered or anonymous add entries
        $usercanaddentries = $view->get_df()->user_can_manage_entry();

        switch ($tag) {
            case '##addnewentry##':
            case '##addnewentries##':
                if (!empty($options['hidenewentry']) or !$usercanaddentries) {
                    break;
                }

                if ($tag == '##addnewentry##') {
                    if (!empty($df->data->singleedit)) {
                        $baseurl->param('view', $df->data->singleedit);
                    }
                    $baseurl->param('new', 1);
                    $label = html_writer::tag('span', get_string('entryaddnew', 'dataform'));
                    $replacement = html_writer::link($baseurl, $label, array('class' => 'addnewentry'));
                } else {
                    $range = range(1, 20);
                    $options = array_combine($range, $range);
                    $select = new single_select(new moodle_url($baseurl), 'new', $options, null, array(0 => get_string('dots', 'dataform')), 'newentries_jump');
                    $select->set_label(get_string('entryaddmultinew','dataform'). '&nbsp;');
                    $replacement = $OUTPUT->render($select);
                }

                break;

            case '##multiduplicate##':
                $replacement =
                    html_writer::empty_tag('input',
                                            array('type' => 'button',
                                                    'name' => 'multiduplicate',
                                                    'value' => get_string('multiduplicate', 'dataform'),
                                                    'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'duplicate\')'));
                break;

            case '##multiduplicate:icon##':
                if ($showentryactions) {
                    $replacement = html_writer::tag('button',
                                $OUTPUT->pix_icon('t/copy', get_string('multiduplicate', 'dataform')),
                                array('type' => 'button',
                                        'name' => 'multiduplicate',
                                        'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'duplicate\')'));
                }
                break;
               
            case '##multiedit##':
                if ($showentryactions) {
                    $replacement = html_writer::empty_tag('input',
                                            array('type' => 'button',
                                                    'name' => 'multiedit',
                                                    'value' => get_string('multiedit', 'dataform'),
                                                    'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'editentries\')'));
                }
                break;

            case '##multiedit:icon##':
                if ($showentryactions) {
                    $replacement = html_writer::tag('button',
                                $OUTPUT->pix_icon('t/edit', get_string('multiedit', 'dataform')),
                                array('type' => 'button',
                                        'name' => 'multiedit',
                                        'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'editentries\')'));
                }
                break;

            case '##multidelete##':
                if ($showentryactions) {
                    $replacement = html_writer::empty_tag('input',
                                            array('type' => 'button',
                                                    'name' => 'multidelete',
                                                    'value' => get_string('multidelete', 'dataform'),
                                                    'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'delete\')'));
                }
                break;

            case '##multidelete:icon##':
                if ($showentryactions) {
                    $replacement = html_writer::tag('button',
                                $OUTPUT->pix_icon('t/delete', get_string('multidelete', 'dataform')),
                                array('type' => 'button',
                                        'name' => 'multidelete',
                                        'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'delete\')'));
                }
                break;

            case '##multiapprove##':
            case '##multiapprove:icon##':
                if ($df->data->approval and has_capability('mod/dataform:approve', $df->context)) {
                    if ($tag == '##multiapprove##') {
                        $replacement =
                            html_writer::empty_tag('input',
                                                    array('type' => 'button',
                                                            'name' => 'multiapprove',
                                                            'value' => get_string('multiapprove', 'dataform'),
                                                            'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'approve\')'));
                    } else {
                        $replacement =
                            html_writer::tag('button',
                                        $OUTPUT->pix_icon('i/tick_green_big', get_string('multiapprove', 'dataform')),
                                        array('type' => 'button',
                                                'name' => 'multiapprove',
                                                'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'approve\')'));
                    }
                }
                break;

            case '##multiexport##':
                $buttonval = get_string('multiexport', 'dataform');
            case '##multiexport:icon##':
                $buttonval = !isset($buttonval) ? $OUTPUT->pix_icon('t/portfolioadd', get_string('multiexport', 'dataform')) : $buttonval;

                if (!empty($CFG->enableportfolios)) {
                    if (!empty($format)) {
                        $baseurl->param('format', $format);
                    }
                    //list(,$ext,) = explode(':', $tag);
                    $replacement =
                        html_writer::tag('button',
                                $buttonval,
                                array('type' => 'button',
                                        'name' => 'multiexport',
                                        'onclick' => 'bulk_action(\'entry\'&#44; \''. $baseurl->out(false). '\'&#44; \'export\'&#44;-1)'));
                }
                break;

            case '##selectallnone##':
                $replacement =
                        html_writer::checkbox(null, null, false, null, array('onclick' => 'select_allnone(\'entry\'&#44;this.checked)'));

                break;
        }

        return $replacement;
    }

    /**
     *
     */
    protected function get_paging_replacements($tag, $entry = null, array $options = null) {
        global $OUTPUT;

        $replacement = '';
        
        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = $view->get_baseurl();

        // typical entry 'more' request. If not single view (1 per page) show return to list instead of paging bar
        if (!empty($filter->eids)) {
            $url = new moodle_url($baseurl);
            // Add page
            if ($filter->page) {
                $url->param('page', $filter->page);
            }
            // Change view to caller
            if ($ret = optional_param('ret', 0, PARAM_INT)) {
                $url->param('view', $ret);
            }
            // Remove eids so that we return to list
            $url->remove_params('eids');
            // Make the link
            $pagingbar = html_writer::link($url->out(false), get_string('viewreturntolist', 'dataform'));

        // typical groupby, one group per page case. show paging bar as per number of groups
        } else if (isset($filter->pagenum)) {
            $pagingbar = new paging_bar($filter->pagenum,
                                        $filter->page,
                                        1,
                                        $baseurl. '&amp;',
                                        'page',
                                        '',
                                        true);
         // standard paging bar case
        } else if (!empty($filter->perpage)
                    and !empty($options['entriescount'])
                    and !empty($options['entriesfiltercount'])
                    and $options['entriescount'] != $options['entriesfiltercount']) {
                    
            $pagingbar = new paging_bar($options['entriesfiltercount'],
                                        $filter->page,
                                        $filter->perpage,
                                        $baseurl. '&amp;',
                                        'page',
                                        '',
                                        true);
        } else {
            $pagingbar = '';
        }

        if ($pagingbar instanceof paging_bar) {
           $replacement = $OUTPUT->render($pagingbar);
        } else {
           $replacement = $pagingbar;
        }
        return $replacement;
    }

    /**
     *
     */
    protected function get_viewurl_replacement($viewname = null) {
        $view = $this->_view;
        
        // Return this view's url
        if ($viewname === null) {
            return $view->get_baseurl()->out(false);
        }

        $df = $this->_view->get_df();        
        static $views = null;
        if ($views === null) {
            $views = array();
            if ($theviews = $df->get_views()) {
                foreach ($theviews as $theview) {
                    $views[$theview->name()] = $theview;
                }
            }
        }
        
        if (!empty($views[$viewname])) {
            return $views[$viewname]->get_baseurl()->out(false);
        }
        return '';
    }
    
    /**
     *
     */
    protected function get_viewcontent_replacement($viewname = null) {
        $df = $this->_view->get_df();        
        static $views = null;
        if ($views === null) {
            $views = array();
            if ($theviews = $df->get_views()) {
                foreach ($theviews as $theview) {
                    $views[$theview->name()] = $theview;
                }
            }
        }
        
        if (!empty($views[$viewname])) {
            // Cannot display current view or else infinite loop
            if ($views[$viewname]->id() == $this->_view->id()) {
                return '';
            }

            $views[$viewname]->set_content();
            return $views[$viewname]->display(array('tohtml' => true));
        }
        return '';
    }
    

    
    /**
     *
     */
    protected function patterns() {
        $patterns = array_merge(
            $this->info_patterns(),
            $this->ref_patterns(),
            $this->userpref_patterns(),
            $this->action_patterns(),
            $this->paging_patterns()
        );
        return $patterns;
    }

    /**
     *
     */
    protected function info_patterns() {
        $cat = get_string('entries', 'dataform');
        $patterns = array(
            '##numentriestotal##' => array(true, $cat),
            '##numentriesdisplayed##' => array(true, $cat),
        );
        return $patterns;
    }

    /**
     *
     */
    protected function ref_patterns() {
        $cat = get_string('reference', 'dataform');
        $patterns = array(
            '##viewurl##' => array(true, $cat),
            '##viewsmenu##' => array(true, $cat),
            '##filtersmenu##' => array(true, $cat),
        );
        
        $df = $this->_view->get_df();

        static $views = null;
        if ($views === null) {
            $views = $df->get_views_menu();
        }
        
        if ($views) {
            foreach ($views as $viewname) {
                $patterns["##viewurl:$viewname##"] = array(false);
                $patterns["##viewcontent:$viewname##"] = array(false);
            }
        }
        
        return $patterns;
    }

    /**
     *
     */
    protected function userpref_patterns() {
        $cat = get_string('userpref', 'dataform');
        $patterns = array(
            '##quicksearch##' => array(true, $cat),
            '##quickperpage##' => array(true, $cat),
            '##advancedfilter##' => array(true, $cat),
        );
        return $patterns;
    }

    /**
     *
     */
    protected function action_patterns() {
        $cat = get_string('generalactions', 'dataform');
        $patterns = array(
            '##addnewentry##' => array(true, $cat),
            '##addnewentries##' => array(true, $cat),
            '##selectallnone##' => array(true, $cat),
            '##multiduplicate##' => array(true, $cat),
            '##multiduplicate:icon##' => array(true, $cat),
            '##multiedit##' => array(true, $cat),
            '##multiedit:icon##' => array(true, $cat),
            '##multidelete##' => array(true, $cat),
            '##multidelete:icon##' => array(true, $cat),
            '##multiapprove##' => array(true, $cat),
            '##multiapprove:icon##' => array(true, $cat),
            '##multiexport##' => array(true, $cat),
            '##multiexport:icon##' => array(true, $cat),
            '##multiimport##' => array(true, $cat),
            '##multiimporty:icon##' => array(true, $cat),
        );
        return $patterns;
    }

    /**
     *
     */
    protected function paging_patterns() {
        $cat = get_string('pagingbar', 'dataform');
        $patterns = array(
            '##pagingbar##' => array(true, $cat),
        );
        return $patterns;
    }


    
    /**
     * TODO Currently not included in the menu
     */
    protected function regexp_patterns() {
        $df = $this->_view->get_df();

        $patterns = array();
        // Get list of views
        if ($views = $df->get_views_menu()) {
            // View link
            $cat = get_string('reference', 'dataform');
            foreach ($views as $viewname) {
                $patterns["#{{viewlink:$viewname;[^;]*;[^;]*;}}#"] = array(true, $cat);
                $patterns["#{{viewsesslink:$viewname;[^;]*;[^;]*;}}#"] = array(true, $cat);
            }
        }
        return $patterns;
    }

    /**
     *
     */
    protected function is_regexp_pattern($pattern) {
        $df = $this->_view->get_df();

        static $views = null;
        if ($views === null) {
            $views = $df->get_views_menu();
        }
        
        if ($views) {
            foreach ($views as $viewname) {
                if (strpos($pattern, "#{{viewlink:$viewname;") === 0) {
                    return true;
                }
                if (strpos($pattern, "#{{viewsesslink:$viewname;") === 0) {
                    return true;
                }
            }
        }
        return false;
    }


    
    /**
     *
     */
    protected function print_views_menu($options, $return = false) {
        global $OUTPUT;
        
        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = $view->get_baseurl();

        $viewjump = '';

        if ($menuviews = $df->get_views_menu() and count($menuviews) > 1) {

            // Display the view form jump list
            $baseurl = $baseurl->out_omit_querystring();
            $baseurlparams = array('d' => $df->id(),
                                    'sesskey' => sesskey(),
                                    'filter' => $filter->id);
            $viewselect = new single_select(new moodle_url($baseurl, $baseurlparams), 'view', $menuviews, $view->id(), array(''=>'choosedots'), 'viewbrowse_jump');
            $viewselect->set_label(get_string('viewcurrent','dataform'). '&nbsp;');
            $viewjump = $OUTPUT->render($viewselect);
        }

        if ($return) {
            return $viewjump;
        } else {
            echo $viewjump;
        }
    }

    /**
     *
     */
    protected function print_filters_menu($options, $return = false) {
        global $OUTPUT;

        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = $view->get_baseurl();

        $filterjump = '';

        if (!$view->is_forcing_filter() and ($filter->id or !empty($options['entriescount']))) {
            $fm = $df->get_filter_manager();
            if (!$menufilters = $fm->get_filters(null, true)) {
                $menufilters = array();
            }
            if ($userfilters = $fm->get_user_filters_menu($view->id())) {
                $menufilters[] = array(get_string('filtermy', 'dataform') => $userfilters);
            }
            
            $baseurl = $baseurl->out_omit_querystring();
            $baseurlparams = array('d' => $df->id(),
                                    'sesskey' => sesskey(),
                                    'view' => $view->id());
            //if ($filter->id) {
            //    $menufilters[0] = get_string('filtercancel', 'dataform');
            //}

            // Display the filter form jump list
            $filterselect = new single_select(new moodle_url($baseurl, $baseurlparams), 'filter', $menufilters, $filter->id, array(''=>'choosedots'), 'filterbrowse_jump');
            $filterselect->set_label(get_string('filtercurrent','dataform'). '&nbsp;');
            $filterjump = $OUTPUT->render($filterselect);
        }
        
        if ($return) {
            return $filterjump;
        } else {
            echo $filterjump;
        }
    }

    /**
     *
     */
    protected function print_quick_search($options, $return = false) {

        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = $view->get_baseurl();

        $quicksearchjump = '';

        $baseurl = $baseurl->out_omit_querystring();
        $baseurlparams = array('d' => $df->id(),
                                'sesskey' => sesskey(),
                                'view' => $view->id(),
                                'filter' => dataform_filter_manager::USER_FILTER_SET);

        if ($filter->id < 0 and $filter->search) {
            $searchvalue = $filter->search;
        } else {
            $searchvalue = '';
        }

        // Display the quick search form
        $label = html_writer::label(get_string('search'), "usersearch");
        $inputfield = html_writer::empty_tag('input', array('type' => 'text',
                                                            'name' => 'usersearch',
                                                            'value' => $searchvalue,
                                                            'size' => 20));

        $button = '';
        //html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('submit')));

        $formparams = '';
        foreach ($baseurlparams as $var => $val) {
            $formparams .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $var, 'value' => $val));
        }

        $attributes = array('method' => 'post',
                            'action' => new moodle_url($baseurl));

        $qsform = html_writer::tag('form', "$formparams&nbsp;$label&nbsp;$inputfield&nbsp;$button", $attributes);

        // and finally one more wrapper with class
        $quicksearchjump = html_writer::tag('div', $qsform, array('class' => 'singleselect'));

        if ($return) {
            return $quicksearchjump;
        } else {
            echo $quicksearchjump;
        }
    }

    /**
     *
     */
    protected function print_quick_perpage($filter, $return = false) {
        global $OUTPUT;

        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = $view->get_baseurl();

        $perpagejump = '';

        $baseurl = $baseurl->out_omit_querystring();
        $baseurlparams = array('d' => $df->id(),
                                'sesskey' => sesskey(),
                                'view' => $view->id(),
                                'filter' => dataform_filter_manager::USER_FILTER_SET);

        if ($filter->id < 0 and $filter->perpage) {
            $perpagevalue = $filter->perpage;
        } else {
            $perpagevalue = 0;
        }

        $perpage = array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10,15=>15,
           20=>20,30=>30,40=>40,50=>50,100=>100,200=>200,300=>300,400=>400,500=>500,1000=>1000);

        // Display the view form jump list
        $select = new single_select(new moodle_url($baseurl, $baseurlparams), 'uperpage', $perpage, $perpagevalue, array(''=>'choosedots'), 'perpage_jump');
        $select->set_label(get_string('filterperpage','dataform'). '&nbsp;');
        $perpagejump = $OUTPUT->render($select);

        if ($return) {
            return $perpagejump;
        } else {
            echo $perpagejump;
        }
    }

    /**
     *
     */
    protected function print_advanced_filter($filter, $return = false) {
        global $OUTPUT;

        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = $view->get_baseurl();

        $fm = $df->get_filter_manager();
        $filterform = $fm->get_advanced_filter_form($filter, $view);
        
        if ($return) {
            return html_writer::tag('div', $filterform->html(), array('class' => 'mdl-left'));
        } else {
            html_writer::start_tag('div', array('class' => 'mdl-left'));
            $filterform->display();
            html_writer::end_tag('div');
        }
    }

}
