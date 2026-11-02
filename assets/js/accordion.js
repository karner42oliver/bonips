/**
 * Accordion
 * @since 0.1
	* @version 1.1
 */
jQuery(function($) {
	var active_box = false;
	if ( typeof boniPS !== 'undefined' && boniPS.active != '-1' ) {
		active_box = parseInt( boniPS.active, 10 );
	}

	var $accordion = $( '#accordion' );
	if ( ! $accordion.length ) return;

	if ( typeof $.fn.accordion === 'function' ) {
		$accordion.accordion({ collapsible: true, header: 'h4', heightStyle: 'content', active: active_box });
		return;
	}

	var $headers = $accordion.children( 'h4' );
	var $panels  = $headers.next( 'div' );

	$headers.attr( 'tabindex', '0' ).css( 'cursor', 'pointer' );
	$panels.hide();

	if ( active_box !== false && $panels.eq( active_box ).length ) {
		$headers.eq( active_box ).addClass( 'ui-state-active' );
		$panels.eq( active_box ).show();
	}

	$headers.on( 'click keydown', function( event ) {

		if ( event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ' ) return;

		event.preventDefault();

		var $header = $( this );
		var $panel  = $header.next( 'div' );
		var is_open = $panel.is( ':visible' );

		$headers.removeClass( 'ui-state-active' );
		$panels.stop( true, true ).slideUp( 120 );

		if ( ! is_open ) {
			$header.addClass( 'ui-state-active' );
			$panel.stop( true, true ).slideDown( 120 );
		}

	});
});