if ( get_cookie( 'has_visited' ) === null ) {
    jQuery('#nlpop').show();
    console.log('visa nyhetsbrev popup!');
} else {
    console.log(get_cookie( 'has_visited' ));
    console.log('visa inte nyhetsbrev popup!');
}