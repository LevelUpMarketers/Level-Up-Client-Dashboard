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

    $( document ).on( 'click', '.lucd-card', function() {
        var section = $( this ).data( 'section' );
        var $navItem = $( '.lucd-nav-button[data-section="' + section + '"]' ).closest( '.lucd-nav-item' );
        loadSection( section, $navItem );
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
        var formData = new FormData( this );
        formData.append( 'action', 'lucd_save_profile' );
        $.ajax( {
            url: lucdDashboard.ajaxUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function( response ) {
                if ( response.success ) {
                    var $section = $form.closest( '.lucd-nav-item' );
                    $form.hide();
                    loadSection( 'profile', $section );
                } else {
                    alert( response.data );
                }
            }
        } );
    } );

    $( document ).on( 'change', '#company_logo', function() {
        var file = this.files[0];
        if ( file ) {
            var reader = new FileReader();
            reader.onload = function( e ) {
                $( '#company_logo_preview' ).css( 'background-image', 'url(' + e.target.result + ')' ).show();
            };
            reader.readAsDataURL( file );
        }
    } );

    $( document ).on( 'input', '#mailing_postcode, #company_postcode', function(){
        var val = $( this ).val().replace(/[^0-9-]/g, '').slice(0,10);
        $( this ).val( val );
    });

    var $default = $( '.lucd-nav-button[data-section="overview"]' ).closest( '.lucd-nav-item' );
    if ( $default.length ) {
        loadSection( 'overview', $default );
    }
} );
