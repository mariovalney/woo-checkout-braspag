'use strict';

jQuery( document ).ready( function($) {
    var inputs_to_init = [];

    $( '[data-condition]' ).each( function(index, el) {
        var condition_attr = $( el ).data( 'condition' );

        if ( ! condition_attr ) {
            return;
        }

        var checks              = [],
            conditions          = condition_attr.split( '|' ),
            conditions_length   = conditions.length,
            elements_to_observe = [];

        for (var i = 0; i < conditions_length; i++) {

            var truth = ( conditions[i].indexOf( '!' ) !== 0 ) ? true : false,
            option    = ( truth ) ? conditions[i] : conditions[i].substring( 1 );

            option = option.split( '=', 2 );

            var option_element = $( '[name="' + option[0] + '"]' );

            if ( ! option_element.length ) {
                continue;
            }

            checks.push(
                {
                    element: option_element,
                    truth: truth,
                    value: option[1] || true
                }
            );

            // Elements to trigger
            elements_to_observe.push( option_element );

            // Inputs to trigger after finish
            inputs_to_init.push( option[0] );
        }

        // Refresh conditions on change
        var elements_to_observe_length = elements_to_observe.length;
        for (var j = 0; j < elements_to_observe_length; j++) {

            elements_to_observe[j].on( 'change', function(event) {
                var show = true,
                value    = '';

                var checks_length = checks.length;
                for (var i = 0; i < checks_length; i++) {
                    // Get checked element value
                    value = checks[i].element.val();
                    if ( checks[i].element.prop( 'type' ) === 'checkbox' ) {
                        value = checks[i].element.prop( 'checked' );
                    }

                    // If value is equal and we want this
                    if ( value == checks[i].value && checks[i].truth ) {
                        continue;
                    }

                    // If value is different and we want this
                    if ( value != checks[i].value && ! checks[i].truth ) {
                        continue;
                    }

                    // Nope: we should hide
                    show = false;
                    break;
                }

                if ( show ) {
                    $( el ).parents( 'tr' ).fadeIn( 200 );
                    return;
                }

                $( el ).parents( 'tr' ).hide();
            } );
        }
    } );

    // Trigger conditions
    inputs_to_init = _.uniq( inputs_to_init );
    _.each( inputs_to_init, function(element) {
        $( '[name="' + element + '"]' ).trigger( 'change' );
    } );
} );
