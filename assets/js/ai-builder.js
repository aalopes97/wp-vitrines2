(function ($) {
    'use strict';

    var messages = [];
    var isBusy = false;
    var streamAbort = null;

    function t(key, fallback) {
        return (vitrineAiData.strings && vitrineAiData.strings[key]) || fallback;
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/'/g, '&#39;');
    }

    function assistantAvatarHtml() {
        if (vitrineAiData.logoUrl) {
            return '<img src="' + escapeAttr(vitrineAiData.logoUrl) + '" alt="BVS" class="vitrine-ai-msg__avatar-img" />';
        }
        return '<span class="dashicons dashicons-superhero"></span>';
    }

    function userAvatarHtml() {
        return '<span class="dashicons dashicons-admin-users"></span>';
    }

    function renderWelcome() {
        var html = '<div class="vitrine-ai-msg vitrine-ai-msg--assistant vitrine-ai-msg--welcome">';
        html += '<div class="vitrine-ai-msg__avatar">' + assistantAvatarHtml() + '</div>';
        html += '<div class="vitrine-ai-msg__body">';
        html += '<strong>' + escapeHtml(t('welcomeTitle', 'Ola! Vamos montar sua vitrine?')) + '</strong>';
        html += '<p>' + escapeHtml(t('welcomeText', '')) + '</p>';
        html += '</div></div>';
        $('#vitrine-ai-messages').html(html);
    }

    function scrollMessages() {
        var box = document.getElementById('vitrine-ai-messages');
        if (box) {
            box.scrollTop = box.scrollHeight;
        }
    }

    function buildSuggestionsHtml(suggestions, loading) {
        if (loading) {
            return '<div class="vitrine-ai-suggestions vitrine-ai-suggestions--loading"><span class="vitrine-ai-suggestions__label">' +
                escapeHtml(t('loadingSuggestions', 'Gerando sugestoes...')) + '</span></div>';
        }
        if (!suggestions || !suggestions.length) {
            return '';
        }

        var html = '<div class="vitrine-ai-suggestions">';
        html += '<span class="vitrine-ai-suggestions__label">' + escapeHtml(t('suggestions', 'Sugestoes')) + '</span>';
        html += '<div class="vitrine-ai-suggestions__list">';
        suggestions.forEach(function (item) {
            html += '<button type="button" class="vitrine-ai-suggestion-chip" data-text="' + escapeAttr(item) + '">' + escapeHtml(item) + '</button>';
        });
        html += '</div></div>';
        return html;
    }

    function renderMessages() {
        var $box = $('#vitrine-ai-messages');
        $box.empty();

        if (!messages.length) {
            renderWelcome();
            $('#vitrine-ai-examples').show();
            return;
        }

        $('#vitrine-ai-examples').hide();

        messages.forEach(function (msg, idx) {
            var isUser = msg.role === 'user';
            var cls = isUser ? 'vitrine-ai-msg--user' : 'vitrine-ai-msg--assistant';
            var label = isUser ? t('you', 'Voce') : t('assistantName', 'Assistente BVS');
            var avatarHtml = isUser ? userAvatarHtml() : assistantAvatarHtml();
            var isLastAssistant = !isUser && idx === messages.length - 1;

            var html = '<div class="vitrine-ai-msg ' + cls + '">';
            html += '<div class="vitrine-ai-msg__avatar">' + avatarHtml + '</div>';
            html += '<div class="vitrine-ai-msg__body">';
            html += '<span class="vitrine-ai-msg__label">' + escapeHtml(label) + '</span>';
            html += '<div class="vitrine-ai-msg__text">' + escapeHtml(msg.content).replace(/\n/g, '<br>') + '</div>';

            if (!isUser && isLastAssistant) {
                if (msg.suggestionsLoading) {
                    html += buildSuggestionsHtml([], true);
                } else if (msg.suggestions && msg.suggestions.length) {
                    html += buildSuggestionsHtml(msg.suggestions, false);
                }
            }

            html += '</div></div>';
            $box.append(html);
        });

        scrollMessages();
    }

    function createStreamingBubble() {
        removeStreamingBubble();

        var html = '<div class="vitrine-ai-msg vitrine-ai-msg--assistant vitrine-ai-msg--streaming" id="vitrine-ai-streaming">';
        html += '<div class="vitrine-ai-msg__avatar">' + assistantAvatarHtml() + '</div>';
        html += '<div class="vitrine-ai-msg__body">';
        html += '<span class="vitrine-ai-msg__label">' + escapeHtml(t('assistantName', 'Assistente BVS')) + '</span>';
        html += '<div class="vitrine-ai-msg__text" id="vitrine-ai-streaming-text"></div>';
        html += '<span class="vitrine-ai-stream-cursor" aria-hidden="true"></span>';
        html += '</div></div>';

        $('#vitrine-ai-messages').append(html);
        $('#vitrine-ai-examples').hide();
        scrollMessages();
    }

    function updateStreamingBubble(text) {
        $('#vitrine-ai-streaming-text').html(escapeHtml(text).replace(/\n/g, '<br>'));
        scrollMessages();
    }

    function removeStreamingBubble() {
        $('#vitrine-ai-streaming').remove();
    }

    function renderExamples() {
        var $wrap = $('#vitrine-ai-examples');
        if (!$wrap.length || !vitrineAiData.examples || !vitrineAiData.examples.length) {
            return;
        }

        var html = '<p class="vitrine-ai-examples__title">Exemplos:</p><div class="vitrine-ai-examples__list">';
        vitrineAiData.examples.forEach(function (example) {
            html += '<button type="button" class="vitrine-ai-example-chip">' + escapeHtml(example) + '</button>';
        });
        html += '</div>';
        $wrap.html(html);
    }

    function setBusy(busy, statusText) {
        isBusy = busy;
        $('#vitrine-ai-send, #vitrine-ai-generate, #vitrine-ai-new-chat').prop('disabled', busy);
        $('#vitrine-ai-input').prop('disabled', busy);

        var $status = $('#vitrine-ai-status');
        if (statusText) {
            $status.text(statusText).prop('hidden', false);
        } else {
            $status.prop('hidden', true).text('');
        }
    }

    function setGeneratingLoading(active) {
        var $overlay = $('#vitrine-ai-loading');
        var $btn = $('#vitrine-ai-generate');
        var $btnLabel = $btn.find('.vitrine-ai-btn-generate__label');

        if (!$btnLabel.length) {
            $btn.wrapInner('<span class="vitrine-ai-btn-generate__label"></span>');
            $btnLabel = $btn.find('.vitrine-ai-btn-generate__label');
        }

        if (active) {
            if (!$btn.data('originalText')) {
                $btn.data('originalText', $btnLabel.text());
            }
            $overlay.prop('hidden', false).attr('aria-busy', 'true');
            $overlay.find('.vitrine-ai-loading__text').text(t('generating', 'Gerando vitrine...'));
            $overlay.find('.vitrine-ai-loading__hint').text(t('generatingHint', 'Montando layout e criando rascunho.'));
            $btn.addClass('is-loading');
            $btnLabel.text(t('generating', 'Gerando vitrine...'));
            return;
        }

        $overlay.prop('hidden', true).attr('aria-busy', 'false');
        $btn.removeClass('is-loading');
        if ($btn.data('originalText')) {
            $btnLabel.text($btn.data('originalText'));
        }
    }

    function addUserMessage(text) {
        text = String(text || '').trim();
        if (!text) {
            return false;
        }
        messages.push({ role: 'user', content: text });
        renderMessages();
        return true;
    }

    function appendAssistantMessage(content, suggestions) {
        messages.push({
            role: 'assistant',
            content: content,
            suggestions: suggestions || [],
            suggestionsLoading: false
        });
        renderMessages();
    }

    function setLastAssistantSuggestions(suggestions, loading) {
        for (var i = messages.length - 1; i >= 0; i--) {
            if (messages[i].role === 'assistant') {
                messages[i].suggestions = suggestions || [];
                messages[i].suggestionsLoading = !!loading;
                break;
            }
        }
        renderMessages();
    }

    function parseSseBuffer(buffer) {
        var events = [];
        var parts = buffer.split('\n\n');
        var remainder = parts.pop() || '';

        parts.forEach(function (chunk) {
            chunk.split('\n').forEach(function (line) {
                line = line.trim();
                if (line.indexOf('data:') === 0) {
                    try {
                        events.push(JSON.parse(line.slice(5).trim()));
                    } catch (e) {
                        /* ignore malformed chunks */
                    }
                }
            });
        });

        return { events: events, remainder: remainder };
    }

    function sendChatMessageStream() {
        var formData = new FormData();
        formData.append('action', 'vitrine_ai_chat_stream');
        formData.append('nonce', vitrineAiData.nonce);
        formData.append('messages', JSON.stringify(messages));

        var controller = new AbortController();
        streamAbort = controller;

        return fetch(vitrineAiData.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            signal: controller.signal
        }).then(function (response) {
            if (!response.ok || !response.body) {
                throw new Error(t('errorChat', 'Erro no chat.'));
            }

            createStreamingBubble();

            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffer = '';
            var fullText = '';
            var handledDone = false;

            function processEvents(events) {
                events.forEach(function (event) {
                    if (event.type === 'delta' && event.content) {
                        fullText += event.content;
                        updateStreamingBubble(fullText);
                    } else if (event.type === 'error') {
                        throw new Error(event.message || t('errorChat', 'Erro no chat.'));
                    } else if (event.type === 'done') {
                        if (event.message) {
                            fullText = event.message;
                            updateStreamingBubble(fullText);
                        }
                        handledDone = true;
                        removeStreamingBubble();
                        appendAssistantMessage(fullText, event.suggestions || []);
                    }
                });
            }

            function pump() {
                return reader.read().then(function (result) {
                    if (result.done) {
                        if (buffer.trim()) {
                            processEvents(parseSseBuffer(buffer + '\n\n').events);
                        }
                        return fullText;
                    }

                    buffer += decoder.decode(result.value, { stream: true });
                    var parsed = parseSseBuffer(buffer);
                    buffer = parsed.remainder;
                    processEvents(parsed.events);

                    return pump();
                });
            }

            return pump().then(function (finalText) {
                removeStreamingBubble();
                if (!handledDone && finalText) {
                    appendAssistantMessage(finalText, []);
                    return loadSuggestions(finalText);
                }
                return finalText;
            });
        }).finally(function () {
            streamAbort = null;
        });
    }

    function sendChatMessageFallback() {
        return $.ajax({
            url: vitrineAiData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'vitrine_ai_chat',
                nonce: vitrineAiData.nonce,
                messages: JSON.stringify(messages)
            }
        }).then(function (res) {
            if (!res.success || !res.data || !res.data.message) {
                var msg = (res.data && res.data.message) ? res.data.message : t('errorChat', 'Erro no chat.');
                throw new Error(msg);
            }
            appendAssistantMessage(res.data.message, res.data.suggestions || []);
            return res.data.message;
        });
    }

    function loadSuggestions(assistantMessage) {
        return $.ajax({
            url: vitrineAiData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'vitrine_ai_suggestions',
                nonce: vitrineAiData.nonce,
                messages: JSON.stringify(messages),
                assistant_message: assistantMessage
            }
        }).then(function (res) {
            if (res.success && res.data && res.data.suggestions) {
                setLastAssistantSuggestions(res.data.suggestions, false);
            } else {
                setLastAssistantSuggestions([], false);
            }
        }).catch(function () {
            setLastAssistantSuggestions([], false);
        });
    }

    function sendChatMessage() {
        if (isBusy) {
            return;
        }

        if (!vitrineAiData.isConfigured) {
            alert(t('errorNotConfig', 'API nao configurada.'));
            return;
        }

        var text = $('#vitrine-ai-input').val();
        if (!addUserMessage(text)) {
            return;
        }
        $('#vitrine-ai-input').val('');

        setBusy(true, t('thinking', 'Assistente esta respondendo...'));

        var request = (vitrineAiData.streamEnabled && window.fetch && window.ReadableStream)
            ? sendChatMessageStream()
            : sendChatMessageFallback();

        request.catch(function (err) {
            if (err && err.name === 'AbortError') {
                return;
            }

            removeStreamingBubble();

            if (vitrineAiData.streamEnabled) {
                return sendChatMessageFallback().catch(function (fallbackErr) {
                    alert(fallbackErr.message || t('errorChat', 'Erro no chat.'));
                });
            }

            alert((err && err.message) ? err.message : t('errorChat', 'Erro no chat.'));
        }).finally(function () {
            setBusy(false);
        });
    }

    function generateVitrine() {
        if (isBusy) {
            return;
        }

        if (!vitrineAiData.isConfigured) {
            alert(t('errorNotConfig', 'API nao configurada.'));
            return;
        }

        var pending = $('#vitrine-ai-input').val();
        if (pending && String(pending).trim()) {
            addUserMessage(pending);
            $('#vitrine-ai-input').val('');
        }

        var userMessages = messages.filter(function (m) {
            return m.role === 'user';
        });

        if (!userMessages.length) {
            alert(t('errorGeneric', 'Descreva a vitrine primeiro.'));
            return;
        }

        setBusy(true);
        setGeneratingLoading(true);

        $.ajax({
            url: vitrineAiData.ajaxUrl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'vitrine_ai_generate',
                nonce: vitrineAiData.nonce,
                messages: JSON.stringify(messages)
            }
        }).done(function (res) {
            if (res.success && res.data && res.data.edit_url) {
                window.location.href = res.data.edit_url;
                return;
            }
            var msg = (res.data && res.data.message) ? res.data.message : t('errorGeneric', 'Erro.');
            alert(msg);
            setBusy(false);
            setGeneratingLoading(false);
        }).fail(function (xhr) {
            var msg = t('errorGeneric', 'Erro.');
            if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                msg = xhr.responseJSON.data.message;
            }
            alert(msg);
            setBusy(false);
            setGeneratingLoading(false);
        });
    }

    function resetChat() {
        if (isBusy) {
            return;
        }
        if (streamAbort) {
            streamAbort.abort();
            streamAbort = null;
        }
        messages = [];
        $('#vitrine-ai-input').val('');
        removeStreamingBubble();
        renderMessages();
    }

    $(function () {
        renderWelcome();
        renderExamples();

        $('#vitrine-ai-send').on('click', sendChatMessage);
        $('#vitrine-ai-generate').on('click', generateVitrine);
        $('#vitrine-ai-new-chat').on('click', resetChat);

        $('#vitrine-ai-input').on('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendChatMessage();
            }
        });

        $(document).on('click', '.vitrine-ai-example-chip', function () {
            $('#vitrine-ai-input').val($(this).text()).focus();
        });

        $(document).on('click', '.vitrine-ai-suggestion-chip', function () {
            if (isBusy) {
                return;
            }
            var text = $(this).data('text') || $(this).text();
            $('#vitrine-ai-input').val(text);
            sendChatMessage();
        });
    });
})(jQuery);
