'use strict';

jQuery(document).ready(function($) {
    var conditions_to_trigger = [];

    $('[data-condition]').each(function(index, el) {
        var condition = $(el).data('condition');

        if ( ! condition ) return;

        var truth = ( condition.indexOf('!') !== 0 ) ? true : false,
            option = ( truth ) ? condition : condition.substring(1),
            option_element = $( '[name="' + option + '"]' );

        if ( ! option_element.length ) return;

        // Conditions to trigger after this
        conditions_to_trigger.push( option );

        // Refresh conditions on change
        option_element.on('change', function(event) {
            var value = $(this).prop('checked');

            if ( value === truth ) {
                $(el).parents('tr').fadeIn(200);
                return;
            }

            $(el).parents('tr').hide();
        });
    });

    // Trigger conditions
    conditions_to_trigger = _.uniq( conditions_to_trigger );
    _.each(conditions_to_trigger, function(element) {
        $( '[name="' + element + '"]' ).trigger('change');
    });
});
