/**
 * @package dataformfield
 * @subpackage checkbox
 * @copyright  2013 Itamar Tzadok
 */

/**
 * Select registrations for multiactions
 */
M.dataformfield_checkbox_required = {};

M.dataformfield_checkbox_required.init = function(Y, options) {
    YUI().use('node', function (Y) {
        var fieldname = options.fieldname;
        var selected = options.selected;
        var err_message = options.message;
        
        if (!Y.one('#fgroup_id_'+fieldname+'_grp')) {
            return;
        }
        
        Y.one('#fgroup_id_'+fieldname+'_grp').on('click', function (e) {
            if (e.target.get('className') === 'checkboxgroup'+fieldname+'_selected') {
                var empty = true;
                Y.all('.checkboxgroup'+fieldname+'_selected').each(function(check) {
                    if (check.get('checked')) {
                        empty = false;
                    }
                });
                Y.all('#fgroup_id_'+fieldname+'_grp .fgrouplabel label span').remove();
                if (empty) {
                    Y.one('#fgroup_id_'+fieldname+'_grp .fgrouplabel label').append('<span class="error">'+err_message+'</span>');
                }
            }
        });
        
        Y.all('#fgroup_id_'+fieldname+'_grp .fgrouplabel label span').remove();
        if (!selected) {
            Y.one('#fgroup_id_'+fieldname+'_grp .fgrouplabel label').append('<span class="error">'+err_message+'</span>');
        }
    });
};