jQuery( function( $ ) {
    function loadSection( section, $navItem ) {
        $.post(
            lucdDashboard.ajaxUrl,
            {
                action: 'lucd_load_section',
                section: section,
                nonce: lucdDashboard.nonce
            },
            function( response ) {
                if ( ! response.success ) {
                    return;
                }

                if ( window.matchMedia( '(max-width: 768px)' ).matches ) {
                    var $container = $navItem.find( '.lucd-mobile-content' );
                    $container.stop( true, true ).fadeOut( 200, function() {
                        $container.html( response.data ).fadeIn( 200 );
                    } );
                } else {
                    var $content = $( '#lucd-content' );
                    $content.stop( true, true ).fadeOut( 200, function() {
                        $content.html( response.data ).fadeIn( 200 );
                    } );
                }
            }
        );
    }

    $( '.lucd-nav' ).on( 'click', '.lucd-nav-button', function( e ) {
        e.preventDefault();
        var section = $( this ).data( 'section' );
        var $item   = $( this ).closest( '.lucd-nav-item' );
        loadSection( section, $item );
    } );

    $( document ).on( 'click', '.lucd-edit-profile', function() {
        var $view = $( this ).closest( '.lucd-profile-view' );
        $view.hide();
        $view.next( '.lucd-profile-edit' ).show();
    } );

    $( document ).on( 'click', '.lucd-cancel-edit', function() {
        var $form = $( this ).closest( '.lucd-profile-edit' );
        $form.hide();
        $form.prev( '.lucd-profile-view' ).show();
    } );

    $( document ).on( 'submit', '.lucd-profile-edit', function( e ) {
        e.preventDefault();
        var $form = $( this );
        var data  = $form.serialize();
        data += '&action=lucd_save_profile';
        $.post( lucdDashboard.ajaxUrl, data, function( response ) {
            if ( response.success ) {
                var $section = $form.closest( '.lucd-nav-item' );
                $form.hide();
                loadSection( 'profile', $section );
            } else {
                alert( response.data );
            }
        } );
    } );
} );
