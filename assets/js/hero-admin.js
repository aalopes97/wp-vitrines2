/**
 * Vitrine Builder – Hero meta box (admin)
 */
(function ($) {
    'use strict';

    var settings = (window.vitrineHeroData && vitrineHeroData.settings) ? vitrineHeroData.settings : {};

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

    function syncDescriptionField() {
        var html = $('#vitrine-hero-description').html();
        $('#vitrine-hero-description-input').val(html);
    }

    function renderHeroPreview() {
        var $prev = $('#vitrine-hero-preview');
        if (!$prev.length) return;

        var img   = $('#vitrine-hero-image').val() || '';
        var text  = $('#vitrine-hero-text').val() || '';
        var color = $('#vitrine-hero-text-color').val() || '#ffffff';
        var opa   = parseInt($('#vitrine-hero-opacity').val() || '50', 10) / 100;
        var h     = parseInt($('#vitrine-hero-height').val() || '400', 10);
        var fs    = parseInt($('#vitrine-hero-font-size').val() || '36', 10);
        var align = $('#vitrine-hero-text-align').val() || 'center';
        var desc  = $('#vitrine-hero-description').html() || '';
        var dfs   = parseInt($('#vitrine-hero-desc-size').val() || '18', 10);
        var descColor = $('#vitrine-hero-desc-color').val() || color;
        var descMw = parseInt($('#vitrine-hero-desc-max-width').val() || '0', 10);
        var textBold = $('#vitrine-hero-text-bold').val() !== '0' ? '700' : '400';
        var textItal = $('#vitrine-hero-text-italic').val() === '1' ? 'italic' : 'normal';
        var heroDate = $('#vitrine-hero-date').val() || '';
        var dateFs = parseInt($('#vitrine-hero-date-size').val() || '16', 10);
        var dateColor = $('#vitrine-hero-date-color').val() || color;
        var dateAlign = $('#vitrine-hero-date-align').val() || 'right';

        if (!img && !text && !desc && !heroDate) {
            $prev.empty();
            return;
        }

        var dateLabel = heroDate;
        if (heroDate && heroDate.indexOf('-') !== -1) {
            var p = heroDate.split('-');
            if (p.length === 3) {
                dateLabel = p[2] + '/' + p[1] + '/' + p[0];
            }
        }

        var justifyMap = { left: 'flex-start', center: 'center', right: 'flex-end' };
        var justifyContent = justifyMap[align] || 'center';
        var dateJustify = justifyMap[dateAlign] || 'flex-end';
        var bgStyle = img ? 'background:url(' + escapeAttr(img) + ') center/cover no-repeat;' : 'background:#333;';

        var html = '<div style="position:relative;' + bgStyle + 'height:' + h + 'px;border-radius:6px;overflow:hidden;display:flex;flex-direction:column;align-items:' + justifyContent + ';justify-content:center;gap:10px;padding:0 20px;">';
        html += '<div style="position:absolute;inset:0;background:rgba(0,0,0,' + opa + ');"></div>';
        if (text) {
            html += '<span style="position:relative;z-index:1;font-size:' + fs + 'px;font-weight:' + textBold + ';font-style:' + textItal + ';color:' + escapeAttr(color) + ';text-align:' + escapeAttr(align) + ';text-shadow:0 2px 8px rgba(0,0,0,.5);">' + escapeHtml(text) + '</span>';
        }
        if (desc) {
            var descStyle = 'position:relative;z-index:1;font-size:' + dfs + 'px;color:' + escapeAttr(descColor) + ';text-align:' + escapeAttr(align) + ';opacity:0.9;text-shadow:0 1px 4px rgba(0,0,0,.5);';
            if (descMw > 0) {
                descStyle += 'max-width:' + descMw + 'px;margin-left:auto;margin-right:auto;display:block;width:100%;';
            }
            html += '<span style="' + descStyle + '">' + desc + '</span>';
        }
        if (heroDate) {
            html += '<span style="position:absolute;z-index:2;left:20px;right:20px;bottom:16px;display:flex;justify-content:' + dateJustify + ';"><time style="font-size:' + dateFs + 'px;color:' + escapeAttr(dateColor) + ';font-weight:600;text-shadow:0 2px 8px rgba(0,0,0,.5);">' + escapeHtml(dateLabel) + '</time></span>';
        }
        html += '</div>';

        $prev.html(html);
    }

    $(function () {
        if (!$('#vitrine-hero-meta-box').length) return;

        renderHeroPreview();

        $('#vitrine-hero-select-image').on('click', function (e) {
            e.preventDefault();
            var frame = wp.media({ title: 'Imagem do Hero', multiple: false, library: { type: 'image' } });
            frame.on('select', function () {
                var url = frame.state().get('selection').first().toJSON().url;
                $('#vitrine-hero-image').val(url);
                var $wrap = $('.vitrine-hero-image-field');
                $wrap.find('.vitrine-image-preview').remove();
                $wrap.prepend('<img src="' + escapeAttr(url) + '" class="vitrine-image-preview" alt="" />');
                var $actions = $wrap.find('.vitrine-hero-image-field__actions');
                if (!$actions.length) {
                    $wrap.append('<div class="vitrine-hero-image-field__actions"><button type="button" class="button" id="vitrine-hero-select-image">Selecionar</button></div>');
                    $actions = $wrap.find('.vitrine-hero-image-field__actions');
                }
                if (!$('#vitrine-hero-remove-image').length) {
                    $actions.append(' <button type="button" class="button" id="vitrine-hero-remove-image">Remover</button>');
                }
                renderHeroPreview();
            });
            frame.open();
        });

        $(document).on('click', '#vitrine-hero-remove-image', function () {
            $('#vitrine-hero-image').val('');
            $('.vitrine-hero-image-field .vitrine-image-preview').remove();
            $(this).remove();
            renderHeroPreview();
        });

        $('#vitrine-hero-meta-box').on('input change', '#vitrine-hero-text, #vitrine-hero-text-color, #vitrine-hero-opacity, #vitrine-hero-height, #vitrine-hero-font-size, #vitrine-hero-text-align, #vitrine-hero-desc-size, #vitrine-hero-desc-color, #vitrine-hero-desc-max-width', function () {
            if (this.id === 'vitrine-hero-opacity') {
                $('#vitrine-hero-opacity-val').text($(this).val() + '%');
            }
            renderHeroPreview();
        });

        // Date fields live in the builder right sidebar (outside the hero meta box).
        $(document).on('input change', '#vitrine-hero-date, #vitrine-hero-date-size, #vitrine-hero-date-color, #vitrine-hero-date-align', function () {
            renderHeroPreview();
        });

        $('#vitrine-hero-description').on('input', function () {
            syncDescriptionField();
            renderHeroPreview();
        });

        $('#vitrine-hero-desc-toolbar .vitrine-wysiwyg-btn').on('mousedown', function (e) {
            e.preventDefault();
            document.execCommand($(this).data('cmd'), false, null);
            syncDescriptionField();
            renderHeroPreview();
        });

        $('#vitrine-hero-meta-box .vitrine-format-btn').on('click', function () {
            var prop = $(this).data('prop');
            var $input = prop === 'bold' ? $('#vitrine-hero-text-bold') : $('#vitrine-hero-text-italic');
            var next = $input.val() === '1' ? '0' : '1';
            $input.val(next);
            $(this).toggleClass('is-active', next === '1');
            renderHeroPreview();
        });

        $('#post').on('submit', function () {
            syncDescriptionField();
        });
    });
})(jQuery);
