/**
 * @package dataformfield
 * @subpackage dataformview
 * @copyright  2013 Itamar Tzadok
 */

/**
 * Dataformview field overlay
 */
M.dataformfield_dataformview_overlay = {};

M.dataformfield_dataformview_overlay.init = function(Y, options) {
    YUI().use('panel', 'resize-plugin', 'dd-plugin', 'transition', function (Y) {

        var dataformviews = Y.all('.dataformfield-dataformview.overlay');
        
        // Create the panel for each field.
        dataformviews.each(function (dfview) {
            var viewBtn = dfview.one('button');
            var panelSource = dfview.one('.panelContent');
            var panel = new Y.Panel({
                srcNode      : panelSource,
                headerContent: 'Dataform view',
                width        : 600,
                height       : 400,
                zIndex       : 1000,
                centered     : true,
                modal        : true,
                visible      : false,
                render       : true,
                plugins      : [Y.Plugin.Drag,Y.Plugin.Resize]
            });
            panelSource.removeClass('hide');
            // When the View is pressed, show the modal form.
            viewBtn.on('click', function (e) {
                panel.show();
            });
        });
    });
};