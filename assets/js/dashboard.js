jQuery( function( $ ) {
    var tooltipOffset = 12;
    var $tooltip      = $( '<div class="lucd-tooltip" role="tooltip"></div>' ).appendTo( 'body' ).hide();

    function hideTooltip() {
        $tooltip.hide().text( '' );
    }

    function positionTooltip( event ) {
        $tooltip.css( {
            top: event.pageY + tooltipOffset,
            left: event.pageX + tooltipOffset
        } );
    }

    function applyFieldTruncation( $context ) {
        var $scopes  = $context && $context.length ? $context : $( document );
        var $targets = $();

        $scopes.each( function() {
            var $scope = $( this );

            if ( $scope.is( '.lucd-field-value[data-full-text]' ) ) {
                $targets = $targets.add( $scope );
            }

            $targets = $targets.add( $scope.find( '.lucd-field-value[data-full-text]' ) );
        } );

        if ( ! $targets.length ) {
            return;
        }

        $targets.each( function() {
            var $value   = $( this );
            var fullText = $value.data( 'full-text' );
            var element  = this;

            if ( ! $value.is( ':visible' ) ) {
                return;
            }

            var wasTrunc = $value.hasClass( 'lucd-truncated' );

            $value.removeClass( 'lucd-truncated' ).removeAttr( 'aria-label' );

            if ( ! fullText ) {
                if ( wasTrunc ) {
                    hideTooltip();
                }
                return;
            }

            var widthOverflow  = element.scrollWidth - Math.ceil( $value.innerWidth() );
            var heightOverflow = element.scrollHeight - Math.ceil( $value.innerHeight() );
            var isTruncated    = widthOverflow > 1 || heightOverflow > 1;

            if ( isTruncated ) {
                $value.addClass( 'lucd-truncated' ).attr( 'aria-label', fullText );
            } else if ( wasTrunc ) {
                hideTooltip();
            }
        } );
    }

    function renderSection( $container, html ) {
        if ( ! $container.length ) {
            return;
        }

        hideTooltip();

        if ( ! $container.is( ':visible' ) ) {
            $container.html( html ).fadeIn( 200, function() {
                applyFieldTruncation( $container );
            } );
            return;
        }

        $container.stop( true, true ).fadeOut( 200, function() {
            $container.html( html ).fadeIn( 200, function() {
                applyFieldTruncation( $container );
            } );
        } );
    }

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
                    renderSection( $container, response.data );
                } else {
                    var $content = $( '#lucd-content' );
                    renderSection( $content, response.data );
                }
            }
        );
    }

    $( document ).on( 'mouseenter', '.lucd-field-value.lucd-truncated', function( e ) {
        var text = $( this ).data( 'full-text' );
        if ( ! text ) {
            return;
        }
        $tooltip.text( text );
        positionTooltip( e );
        $tooltip.show();
    } );

    $( document ).on( 'mousemove', '.lucd-field-value.lucd-truncated', function( e ) {
        if ( ! $tooltip.is( ':visible' ) ) {
            return;
        }
        positionTooltip( e );
    } );

    $( document ).on( 'mouseleave', '.lucd-field-value.lucd-truncated', hideTooltip );
    $( document ).on( 'touchstart', hideTooltip );
    $( window ).on( 'scroll', hideTooltip );

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
        hideTooltip();
        var $view = $( this ).closest( '.lucd-profile-view' );
        $view.hide();
        $view.next( '.lucd-profile-edit' ).show();
    } );

    $( document ).on( 'click', '.lucd-cancel-edit', function() {
        hideTooltip();
        var $form = $( this ).closest( '.lucd-profile-edit' );
        $form.hide();
        var $view = $form.prev( '.lucd-profile-view' );
        $view.show();
        applyFieldTruncation( $view );
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
                    hideTooltip();
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

    $( document ).on( 'click', '.lucd-accordion-header', function() {
        $( this ).next( '.lucd-accordion-content' ).slideToggle();
    } );

    $( document ).on( 'input', '#mailing_postcode, #company_postcode', function(){
        var val = $( this ).val().replace(/[^0-9-]/g, '').slice(0,10);
        $( this ).val( val );
    });

    var resizeTimer;
    $( window ).on( 'resize', function() {
        clearTimeout( resizeTimer );
        resizeTimer = setTimeout( function() {
            hideTooltip();
            applyFieldTruncation( $( '#lucd-dashboard' ) );
        }, 150 );
    } );

    var $default = $( '.lucd-nav-button[data-section="overview"]' ).closest( '.lucd-nav-item' );
    if ( $default.length ) {
        loadSection( 'overview', $default );
    }
} );
