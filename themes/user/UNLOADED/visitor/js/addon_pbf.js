;(function(global, $){
    //es5 strict mode
    "use strict";

    $('body').on('keyup', 'input[name*="[new_password]"], input[name*="[new_password_confirm]"]', function() {
        $('input[name*="[new_password]"], input[name*="[new_password_confirm]"]').trigger('change');
    });

}(window, jQuery));