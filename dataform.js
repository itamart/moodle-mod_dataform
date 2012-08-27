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
 * @package mod-dataform
 * @copyright 2011 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * The Dataform has been developed as an enhanced counterpart
 * of Moodle's Database activity module (1.9.11+ (20110323)).
 * To the extent that Dataform code corresponds to Database code,
 * certain copyrights on the Database module may obtain.
 */

/**
 * insert the field tags into the textarea.
 * Used when editing a dataform view
 */
function insert_field_tags(selectlist, editorname) {
    var value = selectlist.options[selectlist.selectedIndex].value;
    editorid = 'id_'+editorname;

    // textarea displayed and tinyMCE hidden
    if (document.getElementById(editorid).style.display != 'none') {
        editor = document.getElementById(editorid);
        switch (value){
            case '9':
                insertAtCursor(editor, "\t");
                break;

            case '10':               
                insertAtCursor(editor, "\n");
                break;

            default:
                insertAtCursor(editor, value);
        }

    // tinyMCE displayed
    } else {
        tinyMCE.execInstanceCommand(editorid, 'mceInsertContent', false, value);
    }
}

/**
 * select antries for multiactions
 * Used when editing dataform entries
 */
function select_allnone(elem, checked) {
    var selectors = document.getElementsByName(elem + 'selector');
    for (var i = 0; i < selectors.length; i++) {
        selectors[i].checked = checked;
    }
}

/**
 * construct url for multiactions
 * Used when editing dataform entries
 */
function bulk_action(elem, url, action, defaultval) {
    var selected = [];
    var selectors = document.getElementsByName(elem +'selector');
    for (var i = 0; i < selectors.length; i++) {
        if (selectors[i].checked == true) {
            selected.push(selectors[i].value);
        }
    }

    // send selected entries to processing
    if (selected.length) {
        location.href = url + '&' + action + '=' + selected.join(',');

    // if no entries selected but there is default, send it
    } else if (defaultval) {
        location.href = url + '&' + action + '=' + defaultval;
    }
}

/**
 * hiding/displaying advanced search form when viewing
 */
function showHideAdvSearch(checked) {
    var divs = document.getElementsByTagName('div');
    for(i=0;i<divs.length;i++) {
        if(divs[i].id.match('dataform_adv_form')) {
            if(checked) {
                divs[i].style.display = 'inline';
            }
            else {
                divs[i].style.display = 'none';
            }
        }
        else if (divs[i].id.match('reg_search')) {
            if (!checked) {
                divs[i].style.display = 'inline';
            }
            else {
                divs[i].style.display = 'none';
            }
        }
    }
}

/**
 * wordcount bar
 */

M.dataform_wordcount_bar = {pb: null};

M.dataform_wordcount_bar.callback = function(obj) {
    if (typeof tinyMCE == 'undefined') {
        // For normal textareas
		editor = document.getElementById('id_'+obj.pbid+'_editor');
        //insertAtCursor(editor, value);
    } else {
        editor = tinyMCE.get('id_'+obj.pbid+'_editor');

        var text = editor.getContent().replace(/<[^>]+>/gi,'');
        text = text.replace(/\s+/gi,' ');
        var words = text.split(' ').length;
        document.getElementById('id_'+obj.pbid+'_wordcount_value').innerHTML = words;
        obj.pb.set('value', words);

        editor.onKeyUp.add(function(editor, e) {
                                    var text = editor.getContent().replace(/<[^>]+>/gi,'');
                                    text = text.replace(/\s+/gi,' ');
                                    var words = text.split(' ').length;
                                    document.getElementById('id_'+obj.pbid+'_wordcount_value').innerHTML = words;
                                    obj.pb.set('value', words);
                        });
    }
};

M.dataform_wordcount_bar.init = function(Y, options) {
    var Dom = YAHOO.util.Dom; 
    
    this.pbid = options['identifier'];
    this.pb = new YAHOO.widget.ProgressBar();
    this.pb.set('width', '300px');
    this.pb.set('anim', false);
    this.pb.set('minValue', Number(options['minValue']));
    this.pb.set('maxValue', Number(options['maxValue']));
    this.pb.set('value', Number(options['value']));
    
    this.pb.render('id_'+this.pbid+'_wordcount_pb');
    Dom.get('id_'+this.pbid+'_wordcount_value').innerHTML = options['value'];
    
    //var anim = this.pb.get('anim');
    //anim.duration = 1;
    //anim.method = YAHOO.util.Easing.easeNone;
    
    //this.pb.on('progress', function(value){
    //    Dom.get('id_'+this.pbid+'_wordcount_value').innerHTML = value;
    //});
    
    this.pb.on('valueChange', function(oArgs){
        Dom.get('id_'+this.pbid+'_wordcount_value').innerHTML = oArgs.newValue;
    });
    
    Y.later(1000, M.dataform_wordcount_bar, M.dataform_wordcount_bar.callback, this);
}


M.dataform_filepicker = {};


M.dataform_filepicker.callback = function(params) {
    var html = '<a href="'+params['url']+'">'+params['file']+'</a>';
    document.getElementById('file_info_'+params['client_id']).innerHTML = html;
};

/**
 * This fucntion is called for each file picker on page.
 */
M.dataform_filepicker.init = function(Y, options) {
    options.formcallback = M.dataform_filepicker.callback;
    if (!M.core_filepicker.instances[options.client_id]) {
        M.core_filepicker.init(Y, options);
    }
    Y.on('click', function(e, client_id) {
        e.preventDefault();
        M.core_filepicker.instances[client_id].show();
    }, '#filepicker-button-'+options.client_id, null, options.client_id);

    var item = document.getElementById('nonjs-filepicker-'+options.client_id);
    if (item) {
        item.parentNode.removeChild(item);
    }
    item = document.getElementById('filepicker-wrapper-'+options.client_id);
    if (item) {
        item.style.display = '';
    }
};

M.dataform_urlpicker = {};

M.dataform_urlpicker.init = function(Y, options) {
    options.formcallback = M.dataform_urlpicker.callback;
    if (!M.core_filepicker.instances[options.client_id]) {
        M.core_filepicker.init(Y, options);
    }
    Y.on('click', function(e, client_id) {
        e.preventDefault();
        M.core_filepicker.instances[client_id].show();
    }, '#id_filepicker-button-'+options.client_id, null, options.client_id);

};

M.dataform_urlpicker.callback = function (params) {
    document.getElementById('id_field_url_'+params.client_id).value = params.url;
};

M.dataform_imagepicker = {};

M.dataform_imagepicker.callback = function(params) {
	if (params['url'] == '') {
		var html = params['file'];
	} else {
		var html = '<a href="'+params['url']+'"><img src="'+params['url']+'" style="max-width:50px !important;" /> '+params['file']+'</a>';
	}
    document.getElementById('file_info_'+params['client_id']).innerHTML = html;
};

/**
 * This fucntion is called for each file picker on page.
 */
M.dataform_imagepicker.init = function(Y, options) {
    options.formcallback = M.dataform_imagepicker.callback;
    if (!M.core_filepicker.instances[options.client_id]) {
        M.core_filepicker.init(Y, options);
    }
    Y.on('click', function(e, client_id) {
        e.preventDefault();
        M.core_filepicker.instances[client_id].show();
    }, '#filepicker-button-'+options.client_id, null, options.client_id);

    var item = document.getElementById('nonjs-filepicker-'+options.client_id);
    if (item) {
        item.parentNode.removeChild(item);
    }
    item = document.getElementById('filepicker-wrapper-'+options.client_id);
    if (item) {
        item.style.display = '';
		M.dataform_imagepicker.callback(options);
    }
};
