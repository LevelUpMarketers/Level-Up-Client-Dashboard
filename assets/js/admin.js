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
        $context.find('.lucd-project-client, .lucd-ticket-client').each(function(){
            var $input = $(this);
            var $form = $input.closest('form');
            var $hidden = $input.hasClass('lucd-project-client') ? $form.find('.lucd-project-client-id') : $form.find('.lucd-ticket-client-id');
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
        });
    }

    function formatCurrencyInput($input){
        var val = $input.val().replace(/[^0-9.]/g, '');
        if(val){
            var num = parseFloat(val);
            $input.val(num.toLocaleString('en-US', {style: 'currency', currency: 'USD'}));
        } else {
            $input.val('');
        }
    }

    function formatCurrencyFields($context){
        $context.find('.lucd-currency').each(function(){
            formatCurrencyInput($(this));
        });
    }

    function initStatusFields($context){
        $context.find('.lucd-status-select').each(function(){
            $(this).trigger('change');
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
        if($header.data('ticket-id')){
            data.ticket_id = $header.data('ticket-id');
        }
        $.post(ajaxurl, data, function(response){
            if(response.success){
                $content.html(response.data).data('loaded', true);
                setupClientAutocomplete($content);
                formatCurrencyFields($content);
                initStatusFields($content);
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

    $(document).on('click', '.lucd-archive-client', function(e){
        e.preventDefault();
        var $form = $(this).closest('form');
        var clientId = $form.find('input[name="client_id"]').val();
        var nonce = $form.find('#lucd_archive_nonce').val();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, {action: 'lucd_archive_client', client_id: clientId, lucd_archive_nonce: nonce}, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
        });
    });

    $(document).on('click', '.lucd-delete-client', function(e){
        e.preventDefault();
        if(!confirm(lucdAdmin.i18n.confirmDeleteClient)){
            return;
        }
        var $form = $(this).closest('form');
        var clientId = $form.find('input[name="client_id"]').val();
        var nonce = $form.find('#lucd_delete_nonce').val();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, {action: 'lucd_delete_client', client_id: clientId, lucd_delete_nonce: nonce}, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
            if(response.success){
                $form.remove();
            }
        });
    });

    $(document).on('click', '.lucd-archive-project', function(e){
        e.preventDefault();
        var $form = $(this).closest('form');
        var projectId = $form.find('input[name="project_id"]').val();
        var nonce = $form.find('#lucd_archive_project_nonce').val();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, {action: 'lucd_archive_project', project_id: projectId, lucd_archive_project_nonce: nonce}, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
        });
    });

    $(document).on('click', '.lucd-delete-project', function(e){
        e.preventDefault();
        if(!confirm(lucdAdmin.i18n.confirmDeleteProject)){
            return;
        }
        var $form = $(this).closest('form');
        var projectId = $form.find('input[name="project_id"]').val();
        var nonce = $form.find('#lucd_delete_project_nonce').val();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, {action: 'lucd_delete_project', project_id: projectId, lucd_delete_project_nonce: nonce}, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
            if(response.success){
                $form.remove();
            }
        });
    });

    $(document).on('click', '.lucd-archive-ticket', function(e){
        e.preventDefault();
        var $form = $(this).closest('form');
        var ticketId = $form.find('input[name="ticket_id"]').val();
        var nonce = $form.find('#lucd_archive_ticket_nonce').val();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, {action: 'lucd_archive_ticket', ticket_id: ticketId, lucd_archive_ticket_nonce: nonce}, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
        });
    });

    $(document).on('click', '.lucd-delete-ticket', function(e){
        e.preventDefault();
        if(!confirm(lucdAdmin.i18n.confirmDeleteTicket)){
            return;
        }
        var $form = $(this).closest('form');
        var ticketId = $form.find('input[name="ticket_id"]').val();
        var nonce = $form.find('#lucd_delete_ticket_nonce').val();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        $.post(ajaxurl, {action: 'lucd_delete_ticket', ticket_id: ticketId, lucd_delete_ticket_nonce: nonce}, function(response){
            $feedback.find('.spinner').removeClass('is-active');
            $feedback.find('p').text(response.data);
            if(response.success){
                $form.remove();
            }
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

    $('#lucd-add-ticket-form').on('submit', function(e){
        e.preventDefault();
        var $form = $(this);
        var $feedback = $('#lucd-ticket-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        var label = $form.find('.lucd-ticket-client').val();
        var clientId = $form.find('.lucd-ticket-client-id').val();
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

    $(document).on('submit', '.lucd-edit-ticket-form', function(e){
        e.preventDefault();
        var $form = $(this);
        var data = $form.serialize();
        var $feedback = $form.next('.lucd-feedback');
        $feedback.find('p').text('');
        $feedback.find('.spinner').addClass('is-active');
        var label = $form.find('.lucd-ticket-client').val();
        var clientId = $form.find('.lucd-ticket-client-id').val();
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

    $(document).on('blur', '.lucd-currency', function(){
        formatCurrencyInput($(this));
    });

    $(document).on('change', '.lucd-status-select', function(){
        var val = $(this).val();
        $(this).closest('.lucd-field').find('textarea').val(val);
    });

    function updateTicketDuration($form){
        var start = $form.find('.lucd-ticket-start').val();
        var end = $form.find('.lucd-ticket-end').val();
        if(start && end){
            var diff = (new Date(end) - new Date(start)) / 60000;
            if(diff >= 0){
                $form.find('.lucd-ticket-duration').val(Math.round(diff));
            }
        }
    }

    $(document).on('blur', '.lucd-ticket-start, .lucd-ticket-end', function(){
        updateTicketDuration($(this).closest('form'));
    });

    setupClientAutocomplete($('#lucd-add-project-form'));
    setupClientAutocomplete($('#lucd-add-ticket-form'));
    formatCurrencyFields($('#lucd-add-project-form'));
    initStatusFields($('#lucd-add-project-form'));
});
