if ( get_cookie( 'has_visited' ) === 'undefined' ) {
    jQuery('.newsletter-popup').css({'display': 'block'});
    console.log('visa nyhetsbrev popup!');
} else {
    console.log('visa inte nyhetsbrev popup!');
}