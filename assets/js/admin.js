jQuery(function($){
    $('#lucd-add-client-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        var $feedback = $('#lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, data, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
            if(response.success){
                $form[0].reset();
            }
        });
    });

    var clientLabels = [], clientMap = {};
    if(lucdAdmin.clients){
        $.each(lucdAdmin.clients, function(i, client){
            clientLabels.push(client.label);
            clientMap[client.label] = client.id;
        });
    }

    function setupClientAutocomplete($context){
        var $input = $context.find('.lucd-project-client');
        if(!$input.length){ return; }
        var $hidden = $context.find('.lucd-project-client-id');
        $input.autocomplete({
            source: clientLabels,
            select: function(event, ui){
                $hidden.val(clientMap[ui.item.value]);
            },
            change: function(event, ui){
                if(!ui.item){
                    $hidden.val('');
                    $(this).val('');
                }
            }
        }).on('input', function(){
            $hidden.val('');
        });
    }

    $(document).on('click', '.lucd-accordion-header', function(){
        var $header = $(this);
        var $content = $header.next('.lucd-accordion-content');
        $content.toggle();
        if($content.data('loaded')){
            return;
        }
        var action = $header.data('action');
        if(!action){
            return;
        }
        $content.html('<span class="spinner is-active"></span>');
        var data = {action: action, nonce: lucdAdmin[$header.data('nonce')]};
        if($header.data('client-id')){
            data.client_id = $header.data('client-id');
        }
        if($header.data('project-id')){
            data.project_id = $header.data('project-id');
        }
        $.post(ajaxurl, data, function(response){
            if(response.success){
                $content.html(response.data).data('loaded', true);
                setupClientAutocomplete($content);
            } else {
                $content.html('<p>'+response.data+'</p>');
            }
        });
    });

    $(document).on('submit', '.lucd-edit-client-form', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, data, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
        });
    });

    $('#lucd-add-project-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var $feedback = $('#lucd-project-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        var label = $form.find('.lucd-project-client').val();
        var clientId = $form.find('.lucd-project-client-id').val();
        if(!clientId || !clientMap[label] || clientMap[label] != clientId){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(lucdAdmin.i18n.selectClient);
            return;
        }
        $.post(ajaxurl, $form.serialize(), function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
            if(response.success){
                $form[0].reset();
            }
        });
    });

    $(document).on('submit', '.lucd-edit-project-form', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        var label = $form.find('.lucd-project-client').val();
        var clientId = $form.find('.lucd-project-client-id').val();
        if(!clientId || !clientMap[label] || clientMap[label] != clientId){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(lucdAdmin.i18n.selectClient);
            return;
        }
        $.post(ajaxurl, data, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
        });
    });

    setupClientAutocomplete($('#lucd-add-project-form'));
});
