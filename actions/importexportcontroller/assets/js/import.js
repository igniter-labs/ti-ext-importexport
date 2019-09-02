/*
 * Import controller behavior Script.
 */
+function ($) { "use strict";

    var ImportBehavior = function() {

        this.processImport = function () {
            var $form = $('#importFileColumns').closest('form')

            $form.request('onImport', {
                success: function(data) {
                    $('#importContainer').html(data.result)
                    $(document).trigger('render')
                }
            })
        }
    }

    $.ti.importBehavior = new ImportBehavior;
}(window.jQuery);