( function( $ ) {
	$(window).load( function() {
		var headerHeight = $( '.site-header' ).outerHeight();

		if ( $(window).width() > 768 ) {
			$( '.site' ).css( 'padding-top', headerHeight );
		}
	});
} )( jQuery );