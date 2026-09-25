import { notificationState } from './state.js';
import { escapeHtml, formatDate, renderPagination } from './shared.js';

function publishUnreadCount(count) {
    const unreadCount = Math.max(0, Number(count || 0));
    const label = unreadCount > 99 ? '99+' : String(unreadCount);
    $('[data-whatsapp-unread]').toggleClass('d-none', unreadCount === 0).text(label).attr('aria-label', unreadCount+' mensajes de WhatsApp sin leer');
    $('#notifications-whatsapp-unread-tab-badge').toggleClass('d-none', unreadCount === 0).text(label);
    $(document).trigger('notifications:whatsapp-unread-updated', [unreadCount]);
}

export function loadUnreadCount() {
    PostMethodFunction('/admin/notifications/whatsapp/unread-count', {}, null, function(response) {
        publishUnreadCount(response.unread_count || 0);
    }, null);
}

function handleRealtimeEvent(payload) {
    if (notificationState.activeChannel !== 'whatsapp') return;
    const eventData = payload && payload.message ? payload.message : (payload || {});
    const conversationId = eventData.conversation_id;
    notificationState.whatsappPagination.page = 1;
    loadConversations();
    if (!notificationState.whatsappConversationId || !conversationId || String(notificationState.whatsappConversationId) === String(conversationId)) {
        refreshConversation();
    }
}

function bindRealtime(pusher) {
    if (!pusher || notificationState.whatsappPusherChannel) return;
    const channel = pusher.subscribe('opzio-channel-whatsapp');
    const handler = function(payload) {
        handleRealtimeEvent(payload);
    };
    channel.bind('opzio-event-message', handler);
    channel.bind('opzio-event-status', handler);
    notificationState.whatsappPusherChannel = channel;
    notificationState.whatsappPusherHandler = handler;
    notificationState.whatsappPusherWaiting = false;
}

export function enableRealtime() {
    if (notificationState.whatsappPusherChannel) return;
    if (window.opzioPusher) {
        bindRealtime(window.opzioPusher);
        return;
    }
    if (notificationState.whatsappPusherWaiting) return;
    notificationState.whatsappPusherWaiting = true;
    $(document).one('opzio:pusher-ready.notificationsWhatsapp', function(event, pusher) {
        bindRealtime(pusher);
    });
}

export function disableRealtime() {
    $(document).off('opzio:pusher-ready.notificationsWhatsapp');
    notificationState.whatsappPusherWaiting = false;
    const channel = notificationState.whatsappPusherChannel;
    const handler = notificationState.whatsappPusherHandler;
    if (!channel) return;
    channel.unbind('opzio-event-message', handler);
    channel.unbind('opzio-event-status', handler);
    if (window.opzioPusher) window.opzioPusher.unsubscribe('opzio-channel-whatsapp');
    notificationState.whatsappPusherChannel = null;
    notificationState.whatsappPusherHandler = null;
}

function conversationInitial(conversation) {
    const name = String(conversation.display_name || conversation.phone || 'W').trim();
    return escapeHtml(name.charAt(0).toUpperCase() || 'W');
}

function renderConversations(response) {
    const conversations = response.conversations || [];
    if (conversations.length === 0) {
        $('#notifications-whatsapp-conversation-list').html('<div class="notifications-empty">No hay conversaciones</div>');
    } else {
        let html = '';
        conversations.forEach(function(conversation) {
            const activeClass = String(notificationState.whatsappConversationId) === String(conversation.id) ? ' is-active' : '';
            const unreadClass = Number(conversation.unread_count) > 0 ? ' is-unread' : '';
            const unread = Number(conversation.unread_count) > 0 ? '<span class="notifications-whatsapp-unread-count">'+(Number(conversation.unread_count) > 99 ? '99+' : conversation.unread_count)+'</span>' : '';
            html += '<button type="button" class="notifications-whatsapp-conversation'+activeClass+unreadClass+'" data-id="'+escapeHtml(conversation.id)+'">';
            html += '<span class="notifications-whatsapp-avatar notifications-whatsapp-conversation-avatar">'+conversationInitial(conversation)+'</span>';
            html += '<span class="notifications-whatsapp-conversation-main">';
            html += '<span class="notifications-whatsapp-conversation-name">'+escapeHtml(conversation.display_name || conversation.phone)+'</span>';
            html += '<span class="notifications-whatsapp-conversation-phone">'+escapeHtml(conversation.phone || '')+'</span>';
            html += '<span class="notifications-whatsapp-conversation-preview">'+escapeHtml(conversation.last_message_preview || 'Sin mensajes')+'</span>';
            html += '</span><span class="notifications-whatsapp-conversation-meta">';
            html += '<span class="notifications-whatsapp-conversation-time">'+escapeHtml(formatDate(conversation.last_message_at_local || conversation.last_message_at))+'</span>'+unread+'</span></button>';
        });
        $('#notifications-whatsapp-conversation-list').html(html);
    }
    notificationState.whatsappPagination = response.pagination || notificationState.whatsappPagination;
    renderPagination('#notifications-whatsapp-pagination', notificationState.whatsappPagination, function(page) {
        notificationState.whatsappPagination.page = page;
        loadConversations();
    });
    loadUnreadCount();
}

export function loadConversations() {
    PostMethodFunction('/admin/notifications/whatsapp/conversations', {
        pagination: JSON.stringify(notificationState.whatsappPagination),
        search: $('#notifications-whatsapp-search').val() || '',
        unread_only: $('#notifications-whatsapp-unread-only').is(':checked') ? '1' : '0',
    }, null, renderConversations, null);
}

function renderMessageBody(body) {
    return escapeHtml(body || '').replace(/\r?\n/g, '<br>');
}

function safeMediaUrl(item) {
    const viewUrl = String(item && item.view_url || '').trim();
    if (/^https?:\/\//i.test(viewUrl)) return viewUrl;
    const sourceUrl = String(item && item.url || '').trim();
    return /^https:\/\//i.test(sourceUrl) ? sourceUrl : '';
}

function mediaLabel(item, contentType) {
    if (contentType.indexOf('image/') === 0) return 'Imagen';
    if (contentType.indexOf('video/') === 0) return 'Video';
    if (contentType.indexOf('audio/') === 0) return 'Audio';
    if (contentType === 'application/pdf') return 'Documento PDF';
    return String(item && (item.filename || item.name || item.content_type) || 'Archivo adjunto');
}

function renderMediaItem(item) {
    const mediaUrl = safeMediaUrl(item);
    if (!mediaUrl) return '';
    const contentType = String(item && item.content_type || '').toLowerCase();
    const label = mediaLabel(item, contentType);
    const escapedUrl = escapeHtml(mediaUrl);
    const escapedLabel = escapeHtml(label);

    if (contentType.indexOf('image/') === 0) {
        return '<a class="notifications-whatsapp-media-preview" href="'+escapedUrl+'" target="_blank" rel="noopener noreferrer" aria-label="Ver '+escapedLabel+'"><img src="'+escapedUrl+'" alt="'+escapedLabel+'" loading="lazy"></a>';
    }
    if (contentType.indexOf('video/') === 0) {
        return '<video class="notifications-whatsapp-media-video" controls preload="metadata"><source src="'+escapedUrl+'" type="'+escapeHtml(contentType)+'">'+escapedLabel+'</video>';
    }
    if (contentType.indexOf('audio/') === 0) {
        return '<audio class="notifications-whatsapp-media-audio" controls preload="metadata"><source src="'+escapedUrl+'" type="'+escapeHtml(contentType)+'">'+escapedLabel+'</audio>';
    }

    const icon = contentType === 'application/pdf' ? 'fa-file-pdf' : 'fa-paperclip';
    return '<a class="notifications-whatsapp-attachment" href="'+escapedUrl+'" target="_blank" rel="noopener noreferrer"><i class="fa-solid '+icon+'" aria-hidden="true"></i><span>'+escapedLabel+'</span></a>';
}

function messageStatusIcon(message) {
    if (message.is_inbound) return '';
    if (message.status === 'failed' || message.status === 'undelivered') return '<i class="fa-solid fa-circle-exclamation" title="'+escapeHtml(message.status_label || 'Fallido')+'"></i>';
    const isRead = message.status === 'read' || message.status === 'delivered';
    return '<i class="fa-solid fa-check-double'+(isRead ? ' is-read' : '')+'" title="'+escapeHtml(message.status_label || '')+'"></i>';
}

function templateMessageBody(message) {
    const template = (notificationState.whatsappTemplates || []).find(function(item) {
        return String(item.sid) === String(message.content_sid);
    });
    if (!template) return '';
    const content = templateContent(template);
    const values = message.content_variables && typeof message.content_variables === 'object' ? message.content_variables : {};
    return renderTemplateText(content.body, values, template);
}

function messageDisplayBody(message) {
    const storedBody = String(message.body || '');
    if (!message.content_sid) return storedBody;
    if (storedBody && !/^Plantilla\s+/i.test(storedBody)) return storedBody;
    return templateMessageBody(message) || 'Mensaje de WhatsApp enviado';
}

function renderMessages(messages) {
    if (!messages || messages.length === 0) {
        $('#notifications-whatsapp-messages').html('<div class="notifications-empty">No hay mensajes en esta conversacion</div>');
        return;
    }
    let html = '';
    messages.forEach(function(message) {
        const directionClass = message.is_inbound ? ' is-inbound' : ' is-outbound';
        const displayBody = messageDisplayBody(message);
        const body = displayBody ? '<div class="notifications-whatsapp-message-body">'+renderMessageBody(displayBody)+'</div>' : '';
        let media = '';
        (message.media || []).forEach(function(item) {
            media += renderMediaItem(item);
        });
        if (media) media = '<div class="notifications-whatsapp-message-media">'+media+'</div>';
        html += '<article class="notifications-whatsapp-message'+directionClass+'">'+body+media+'<div class="notifications-whatsapp-message-meta"><span>'+escapeHtml(formatDate(message.created_at_local || message.created_at))+'</span>'+messageStatusIcon(message)+'</div></article>';
    });
    $('#notifications-whatsapp-messages').html(html);
    const container = document.getElementById('notifications-whatsapp-messages');
    if (container) container.scrollTop = container.scrollHeight;
}

function setWindowState(conversation) {
    const isOpen = Boolean(conversation && conversation.window_open);
    const windowLabel = isOpen ? 'Ventana abierta' : 'Ventana cerrada';
    $('#notifications-whatsapp-chat-window').text(windowLabel).toggleClass('is-closed', !isOpen);
    $('#notifications-whatsapp-window-note').text(isOpen ? 'Puedes enviar texto libre durante la ventana de atencion.' : 'Fuera de la ventana de 24 horas debes usar una plantilla aprobada.').toggleClass('is-closed', !isOpen);
}

function renderChat(response) {
    const conversation = response.conversation || {};
    notificationState.whatsappConversation = conversation;
    $('#notifications-whatsapp-chat-empty').addClass('d-none');
    $('#notifications-whatsapp-chat-content').removeClass('d-none');
    $('#notifications-whatsapp-chat-avatar').text(String(conversation.display_name || conversation.phone || 'W').charAt(0).toUpperCase());
    $('#notifications-whatsapp-chat-name').text(conversation.display_name || conversation.phone || 'Conversacion');
    $('#notifications-whatsapp-chat-phone').text(conversation.phone || '');
    setWindowState(conversation);
    renderMessages(response.messages || []);
}

function markConversationRead(id) {
    PostMethodFunction('/admin/notifications/whatsapp/conversation/read', {id: id}, null, function(response) {
        publishUnreadCount(response.unread_count || 0);
        loadConversations();
    }, null);
}

function loadConversationById(id) {
    notificationState.whatsappConversationId = id;
    $('#notifications-whatsapp-layout').addClass('is-chat-open');
    $('#notifications-whatsapp-messages').html('<div class="notifications-empty">Cargando...</div>');
    PostMethodFunction('/admin/notifications/whatsapp/conversation', {id: id}, null, function(response) {
        renderChat(response);
        markConversationRead(id);
    }, null);
}

export function openConversation() {
    loadConversationById($(this).attr('data-id'));
}

export function refreshConversation() {
    if (!notificationState.whatsappConversationId) return;
    PostMethodFunction('/admin/notifications/whatsapp/conversation', {id: notificationState.whatsappConversationId}, null, renderChat, null);
}

export function closeConversation() {
    notificationState.whatsappConversationId = null;
    notificationState.whatsappConversation = null;
    $('#notifications-whatsapp-layout').removeClass('is-chat-open');
    $('#notifications-whatsapp-chat-content').addClass('d-none');
    $('#notifications-whatsapp-chat-empty').removeClass('d-none');
    loadConversations();
}

export function renderWhatsappClientOptions() {
    const select = $('#notifications-whatsapp-client');
    if (select.length === 0) return;
    const selected = select.val();
    let html = '<option value="">Seleccionar cliente</option>';
    (notificationState.clients || []).forEach(function(client) {
        const name = String(client.name || '')+(client.lastname ? ' '+client.lastname : '');
        html += '<option value="'+escapeHtml(client.id)+'" data-phone="'+escapeHtml(client.phone || '')+'">'+escapeHtml(name || client.phone || 'Cliente')+'</option>';
    });
    select.html(html).val(selected || '');
}

export function toggleNewConversation() {
    renderWhatsappClientOptions();
    $('#notifications-whatsapp-start-form').toggleClass('d-none');
}

export function closeNewConversation() {
    $('#notifications-whatsapp-start-form').addClass('d-none');
}

export function selectClient() {
    const option = $(this).find('option:selected');
    if (option.attr('data-phone')) $('#notifications-whatsapp-phone').val(option.attr('data-phone'));
}

export function startConversation(event) {
    event.preventDefault();
    PostMethodFunction('/admin/notifications/whatsapp/conversations/start', {
        client_id: $('#notifications-whatsapp-client').val() || '',
        phone: $('#notifications-whatsapp-phone').val() || '',
        display_name: $('#notifications-whatsapp-display-name').val() || '',
    }, null, function(response) {
        closeNewConversation();
        notificationState.whatsappConversationId = response.conversation.id;
        loadConversations();
        loadConversationById(response.conversation.id);
    }, null);
}

export function openContactConversation(contact) {
    const phone = contact && (contact.phone || (contact.type === 'phone' ? contact.value : ''));
    if (!phone) {
        window.alertWarning?.('El contacto no tiene un telefono valido para WhatsApp.');
        return;
    }
    PostMethodFunction('/admin/notifications/whatsapp/conversations/start', {
        client_id: contact.client_id || '',
        phone,
        display_name: contact.name || '',
    }, null, function(response) {
        notificationState.whatsappConversationId = response.conversation.id;
        loadConversations();
        loadConversationById(response.conversation.id);
    }, null);
}

function renderTemplateOptions() {
    const select = $('#notifications-whatsapp-template');
    const selected = select.val();
    let html = '<option value="">Mensaje libre</option>';
    (notificationState.whatsappTemplates || []).forEach(function(template) {
        const approval = template.approval && template.approval.status ? ' - '+template.approval.status : '';
        html += '<option value="'+escapeHtml(template.sid)+'">'+escapeHtml((template.friendly_name || template.sid)+approval)+'</option>';
    });
    select.html(html).val(selected || '');
}

function selectedTemplate() {
    const sid = $('#notifications-whatsapp-template').val() || '';
    return (notificationState.whatsappTemplates || []).find(function(template) {
        return String(template.sid) === String(sid);
    }) || null;
}

function templateContent(template) {
    const types = template && template.types ? template.types : {};
    const candidates = [
        ['twilio/text', function(content) { return content.body; }],
        ['twilio/media', function(content) { return content.body; }],
        ['twilio/quick-reply', function(content) { return content.body; }],
        ['twilio/call-to-action', function(content) { return content.body; }],
        ['whatsapp/card', function(content) { return [content.body, content.footer].filter(Boolean).join('\n'); }],
        ['twilio/card', function(content) { return [content.title, content.subtitle].filter(Boolean).join('\n'); }],
        ['whatsapp/flows', function(content) { return content.body; }],
    ];

    for (let index = 0; index < candidates.length; index += 1) {
        const type = candidates[index][0];
        const content = types[type];
        if (!content) continue;
        const body = String(candidates[index][1](content) || '').trim();
        if (body !== '') return {type: type, body: body};
    }

    return {type: 'Contenido enriquecido', body: 'Esta plantilla contiene contenido enriquecido.'};
}

function templateVariableNames(template, body) {
    const names = [];
    const addName = function(value) {
        const name = String(value || '').trim();
        if (name !== '' && !names.includes(name)) names.push(name);
    };
    const matcher = /\{\{\s*([^{}]+?)\s*\}\}/g;
    let match = matcher.exec(body || '');
    while (match) {
        addName(match[1]);
        match = matcher.exec(body || '');
    }
    Object.keys(template && template.variables ? template.variables : {}).forEach(addName);
    return names;
}

function templateSample(template, name) {
    const variables = template && template.variables ? template.variables : {};
    return variables[name] == null ? '' : String(variables[name]);
}

function collectTemplateVariables() {
    const values = {};
    $('#notifications-whatsapp-template-variables-form input[data-template-variable]').each(function() {
        const name = String($(this).attr('data-template-variable') || '').trim();
        const value = String($(this).val() || '').trim();
        if (name !== '' && value !== '') values[name] = value;
    });
    return values;
}

function renderTemplatePreviewBody(body, values, template) {
    const samples = {};
    templateVariableNames(template, body).forEach(function(name) {
        samples[name] = templateSample(template, name);
    });

    return escapeHtml(body || '').replace(/\{\{\s*([^{}]+?)\s*\}\}/g, function(match, rawName) {
        const name = String(rawName || '').trim();
        const value = Object.prototype.hasOwnProperty.call(values, name) ? values[name] : samples[name];
        const hasValue = value != null && String(value).trim() !== '';
        const className = Object.prototype.hasOwnProperty.call(values, name) ? 'is-filled' : 'is-example';
        return '<mark class="notifications-whatsapp-template-token '+className+'">'+escapeHtml(hasValue ? value : match)+'</mark>';
    });
}

function renderTemplateText(body, values, template) {
    const samples = {};
    templateVariableNames(template, body).forEach(function(name) {
        samples[name] = templateSample(template, name);
    });

    return String(body || '').replace(/\{\{\s*([^{}]+?)\s*\}\}/g, function(match, rawName) {
        const name = String(rawName || '').trim();
        const value = Object.prototype.hasOwnProperty.call(values, name) ? values[name] : samples[name];
        return value != null && String(value).trim() !== '' ? String(value) : match;
    });
}

function renderTemplateVariableFields(template, names) {
    const container = $('#notifications-whatsapp-template-variables-form');
    if (names.length === 0) {
        container.html('<span class="notifications-whatsapp-template-no-variables">Esta plantilla no requiere variables.</span>');
        return;
    }

    let html = '<div class="notifications-whatsapp-template-variables-heading"><strong>Variables a usar</strong><span>Completa los valores antes de enviar.</span></div><div class="notifications-whatsapp-template-variable-grid">';
    names.forEach(function(name) {
        const sample = templateSample(template, name);
        html += '<label class="notifications-whatsapp-template-variable"><span>{{'+escapeHtml(name)+'}}</span><input type="text" class="form-control" data-template-variable="'+escapeHtml(name)+'" placeholder="'+escapeHtml(sample || 'Valor')+'" aria-label="Valor para la variable '+escapeHtml(name)+'"></label>';
    });
    html += '</div>';
    container.html(html);
}

function updateTemplatePreview() {
    const template = selectedTemplate();
    if (!template) return;
    const content = templateContent(template);
    const values = collectTemplateVariables();
    $('#notifications-whatsapp-template-preview-body').html(renderTemplatePreviewBody(content.body, values, template));
    $('#notifications-whatsapp-variables').val(JSON.stringify(values));
}

function renderSelectedTemplate() {
    const template = selectedTemplate();
    const body = $('#notifications-whatsapp-body');
    if (!template) {
        $('#notifications-whatsapp-template-preview').addClass('d-none');
        $('#notifications-whatsapp-template-preview-name').empty();
        $('#notifications-whatsapp-template-variables-form').empty();
        $('#notifications-whatsapp-variables').val('{}');
        body.prop('disabled', false).attr('placeholder', 'Escribe un mensaje');
        return;
    }

    const content = templateContent(template);
    const names = templateVariableNames(template, content.body);
    $('#notifications-whatsapp-template-preview-name').text((template.friendly_name || template.sid)+' · '+content.type);
    renderTemplateVariableFields(template, names);
    $('#notifications-whatsapp-template-preview').removeClass('d-none');
    body.val('').prop('disabled', true).attr('placeholder', 'El mensaje se construye con la plantilla seleccionada');
    updateTemplatePreview();
}

export function selectTemplate() {
    renderSelectedTemplate();
}

export function updateTemplateVariables() {
    updateTemplatePreview();
}

export function loadTemplates() {
    PostMethodFunction('/admin/notifications/whatsapp/templates', {}, null, function(response) {
        notificationState.whatsappTemplates = response.templates || [];
        renderTemplateOptions();
        renderSelectedTemplate();
        renderTemplateList();
        if (notificationState.whatsappConversationId) refreshConversation();
    }, null);
}

function renderTemplateList() {
    const templates = notificationState.whatsappTemplates || [];
    if (templates.length === 0) {
        $('#notifications-whatsapp-template-list').html('<div class="notifications-empty">No hay plantillas disponibles</div>');
        return;
    }
    let html = '';
    templates.forEach(function(template) {
        const approval = template.approval || {};
        const status = approval.status || 'Sin enviar';
        html += '<article class="notifications-whatsapp-template-item"><div><strong>'+escapeHtml(template.friendly_name || template.sid)+'</strong><span>'+escapeHtml(template.sid || '')+' · '+escapeHtml(template.language || '')+' · '+escapeHtml(status)+'</span></div><div class="notifications-whatsapp-template-item-actions">';
        html += '<button type="button" class="btn btn-link notifications-action notifications-whatsapp-template-edit" data-sid="'+escapeHtml(template.sid)+'" title="Editar" aria-label="Editar plantilla"><i class="fa-solid fa-pen"></i></button>';
        html += '<button type="button" class="btn btn-link notifications-action notifications-whatsapp-template-submit" data-sid="'+escapeHtml(template.sid)+'" title="Enviar a aprobacion" aria-label="Enviar plantilla a aprobacion"><i class="fa-solid fa-paper-plane"></i></button>';
        html += '<button type="button" class="btn btn-link notifications-action notifications-whatsapp-template-delete" data-sid="'+escapeHtml(template.sid)+'" title="Eliminar" aria-label="Eliminar plantilla"><i class="fa-solid fa-trash"></i></button></div></article>';
    });
    $('#notifications-whatsapp-template-list').html(html);
}

function templateText(template) {
    const types = template && template.types ? template.types : {};
    return types['twilio/text'] && types['twilio/text'].body ? types['twilio/text'].body : '';
}

export function resetTemplateForm() {
    const form = document.getElementById('notifications-whatsapp-template-form');
    if (form) form.reset();
    $('#notifications-whatsapp-template-sid').val('');
    $('#notifications-whatsapp-template-language').val('es');
    $('#notifications-whatsapp-template-form-title').text('Nueva plantilla');
    $('#notifications-whatsapp-template-category').val('UTILITY');
}

export function openTemplates() {
    resetTemplateForm();
    $('#notifications-whatsapp-templates-modal').removeClass('d-none');
    $('body').addClass('notifications-modal-open');
    loadTemplates();
}

export function closeTemplates() {
    $('#notifications-whatsapp-templates-modal').addClass('d-none');
    if ($('#notifications-compose-modal').hasClass('d-none') && $('#notifications-email-view-modal').hasClass('d-none')) $('body').removeClass('notifications-modal-open');
}

export function editTemplate() {
    const sid = $(this).attr('data-sid');
    const template = (notificationState.whatsappTemplates || []).find(function(item) { return item.sid === sid; });
    if (!template) return;
    $('#notifications-whatsapp-template-sid').val(template.sid || '');
    $('#notifications-whatsapp-template-name').val(template.friendly_name || '');
    $('#notifications-whatsapp-template-language').val(template.language || 'es');
    $('#notifications-whatsapp-template-body').val(templateText(template));
    $('#notifications-whatsapp-template-variables').val(template.variables ? JSON.stringify(template.variables) : '');
    $('#notifications-whatsapp-template-form-title').text('Editar plantilla');
}

export function saveTemplate(event) {
    event.preventDefault();
    const sid = $('#notifications-whatsapp-template-sid').val();
    const body = $('#notifications-whatsapp-template-body').val() || '';
    const variables = $('#notifications-whatsapp-template-variables').val() || '';
    const payload = {
        friendly_name: $('#notifications-whatsapp-template-name').val() || '',
        language: $('#notifications-whatsapp-template-language').val() || 'es',
        body: body,
        variables: variables,
        types: JSON.stringify({'twilio/text': {body: body}}),
    };
    const url = sid ? '/admin/notifications/whatsapp/templates/update' : '/admin/notifications/whatsapp/templates/add';
    if (sid) payload.sid = sid;
    PostMethodFunction(url, payload, null, function(response) {
        alertSuccess(response.message || 'Plantilla guardada');
        resetTemplateForm();
        loadTemplates();
    }, null);
}

export function deleteTemplate() {
    const sid = $(this).attr('data-sid');
    if (typeof window.Swal?.fire !== 'function') return;
    window.Swal.fire({
        title: 'Eliminar plantilla',
        text: 'La eliminacion se ejecutara en Twilio.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Eliminar',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    }).then(function(result) {
        if (!result.isConfirmed) return;
        PostMethodFunction('/admin/notifications/whatsapp/templates/delete', {sid: sid}, null, function(response) {
            alertSuccess(response.message || 'Plantilla eliminada');
            loadTemplates();
        }, null);
    });
}

export function submitTemplate() {
    const sid = $(this).attr('data-sid');
    const template = (notificationState.whatsappTemplates || []).find(function(item) { return item.sid === sid; }) || {};
    const fallbackName = String(template.friendly_name || 'opzio_template').toLowerCase().replace(/[^a-z0-9_]+/g, '_').replace(/^_+|_+$/g, '').slice(0, 100) || 'opzio_template';
    PostMethodFunction('/admin/notifications/whatsapp/templates/submit', {
        sid: sid,
        name: (template.approval && template.approval.name) || fallbackName,
        category: $('#notifications-whatsapp-template-category').val() || 'UTILITY',
    }, null, function(response) {
        alertSuccess(response.message || 'Plantilla enviada a aprobacion');
        loadTemplates();
    }, null);
}

export function sendMessage(event) {
    event.preventDefault();
    if (!notificationState.whatsappConversationId) return;
    const templateSid = $('#notifications-whatsapp-template').val() || '';
    let body = $('#notifications-whatsapp-body').val() || '';
    let variables = $('#notifications-whatsapp-variables').val() || '{}';
    let displayBody = '';
    if (templateSid) {
        const template = selectedTemplate();
        const content = templateContent(template);
        const values = collectTemplateVariables();
        const missing = templateVariableNames(template, content.body).filter(function(name) {
            return !Object.prototype.hasOwnProperty.call(values, name);
        });
        if (missing.length > 0) {
            window.alertWarning?.('Completa las variables: '+missing.map(function(name) { return '{{'+name+'}}'; }).join(', '));
            return;
        }
        variables = JSON.stringify(values);
        displayBody = renderTemplateText(content.body, values, template);
        body = '';
    }
    $('#notifications-whatsapp-send').prop('disabled', true);
    PostMethodFunction('/admin/notifications/whatsapp/message', {
        conversation_id: notificationState.whatsappConversationId,
        body: body,
        display_body: displayBody,
        content_sid: templateSid,
        content_variables: variables,
    }, null, function(response) {
        $('#notifications-whatsapp-send').prop('disabled', false);
        $('#notifications-whatsapp-body').val('');
        $('#notifications-whatsapp-variables').val('{}');
        refreshConversation();
        loadConversations();
    }, function() {
        $('#notifications-whatsapp-send').prop('disabled', false);
    });
}

export function initializeWhatsapp() {
    if (notificationState.whatsappPolling) return;
    notificationState.whatsappPolling = true;
    $(document).on('notifications:clients-loaded', renderWhatsappClientOptions);
    if (notificationState.activeChannel === 'whatsapp') enableRealtime();
    window.setInterval(function() {
        if (notificationState.activeChannel !== 'whatsapp') return;
        loadConversations();
        if (notificationState.whatsappConversationId) refreshConversation();
    }, 30000);
}