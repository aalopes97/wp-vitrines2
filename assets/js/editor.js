/**
 * Vitrine Builder – Editor JS
 *
 * - Containers obrigatórios na raiz do canvas
 * - Drag & drop com SortableJS (nesting)
 * - Redimensionamento de largura via divisor entre elementos (porcentagem)
 * - Larguras em % totalizando 100% dentro de containers row
 * - Redimensionamento de altura (borda inferior)
 * - Salvamento via AJAX
 */
(function ($) {
    'use strict';

    var builderI18n = vitrineData.i18n || {};

    function t(key, fallback) {
        if (builderI18n[key]) {
            return builderI18n[key];
        }
        return fallback !== undefined ? fallback : key;
    }

    /* ──────────────────────────── Estado ──────────────────────────── */

    var elements   = vitrineData.elements || {};
    var selectedId = null;
    var settingsPanelItemId = null;
    var itemgridExpandedIdx = null;
    var itemgridSortInstances = [];
    var itemcarouselExpandedIdx = null;
    var itemcarouselSortInstances = [];
    var aranhaExpandedIdx = null;
    var toggleExpandedIdx = null;
    var toggleSortInstances = [];
    var settingsPanelTab = 'content';
    var collapsedContainerIds = {};
    var rawPage = vitrineData.pageSettings || {};
    var pageSettings = {
        show_header:   rawPage.show_header !== undefined ? rawPage.show_header : '1',
        show_footer:   rawPage.show_footer !== undefined ? rawPage.show_footer : '1',
        page_bg_color: rawPage.page_bg_color || '',
        custom_css:    rawPage.custom_css || ''
    };

    /* ──────────── Listas de ícones para o picker ──────────── */

    var DASHICONS_LIST = [
        'dashicons-admin-appearance','dashicons-admin-comments','dashicons-admin-home',
        'dashicons-admin-media','dashicons-admin-network','dashicons-admin-page',
        'dashicons-admin-plugins','dashicons-admin-settings','dashicons-admin-site',
        'dashicons-admin-users','dashicons-awards','dashicons-building',
        'dashicons-businessman','dashicons-calendar','dashicons-camera',
        'dashicons-cart','dashicons-chart-area','dashicons-chart-bar',
        'dashicons-chart-line','dashicons-chart-pie','dashicons-clipboard',
        'dashicons-clock','dashicons-controls-play','dashicons-desktop',
        'dashicons-download','dashicons-edit','dashicons-email',
        'dashicons-email-alt2','dashicons-feedback','dashicons-filter',
        'dashicons-flag','dashicons-format-image','dashicons-format-video',
        'dashicons-groups','dashicons-hammer','dashicons-heart',
        'dashicons-id','dashicons-images-alt','dashicons-info',
        'dashicons-instagram','dashicons-laptop','dashicons-layout',
        'dashicons-lightbulb','dashicons-list-view','dashicons-location',
        'dashicons-location-alt','dashicons-lock','dashicons-marker',
        'dashicons-menu','dashicons-microphone','dashicons-money',
        'dashicons-networking','dashicons-palmtree','dashicons-performance',
        'dashicons-phone','dashicons-plus','dashicons-portfolio',
        'dashicons-products','dashicons-rss','dashicons-saved',
        'dashicons-schedule','dashicons-search','dashicons-share',
        'dashicons-shield','dashicons-smartphone','dashicons-smiley',
        'dashicons-star-empty','dashicons-star-filled','dashicons-sticky',
        'dashicons-store','dashicons-tag','dashicons-tablet',
        'dashicons-thumbs-up','dashicons-tickets-alt','dashicons-twitter',
        'dashicons-upload','dashicons-vault','dashicons-video-alt3',
        'dashicons-visibility','dashicons-warning','dashicons-yes',
        'dashicons-youtube'
    ];

    var FA_ICONS_LIST = [
        'fas fa-address-book','fas fa-anchor','fas fa-award',
        'fas fa-ban','fas fa-bars','fas fa-bell',
        'fas fa-bolt','fas fa-book','fas fa-bookmark',
        'fas fa-briefcase','fas fa-building','fas fa-bullhorn',
        'fas fa-bullseye','fas fa-calendar','fas fa-camera',
        'fas fa-certificate','fas fa-chart-bar','fas fa-chart-line',
        'fas fa-chart-pie','fas fa-check','fas fa-check-circle',
        'fas fa-clock','fas fa-cloud','fas fa-code',
        'fas fa-cog','fas fa-comment','fas fa-comments',
        'fas fa-compass','fas fa-crown','fas fa-cube',
        'fas fa-database','fas fa-desktop','fas fa-download',
        'fas fa-envelope','fas fa-exclamation-circle','fas fa-eye',
        'fas fa-fire','fas fa-flag','fas fa-flask',
        'fas fa-folder','fas fa-gem','fas fa-gift',
        'fas fa-globe','fas fa-graduation-cap','fas fa-handshake',
        'fas fa-hashtag','fas fa-headphones','fas fa-heart',
        'fas fa-home','fas fa-image','fas fa-info-circle',
        'fas fa-key','fas fa-laptop','fas fa-layer-group',
        'fas fa-leaf','fas fa-lightbulb','fas fa-link',
        'fas fa-list','fas fa-location-dot','fas fa-lock',
        'fas fa-medal','fas fa-mobile','fas fa-money-bill',
        'fas fa-music','fas fa-network-wired','fas fa-palette',
        'fas fa-paper-plane','fas fa-pen','fas fa-percent',
        'fas fa-phone','fas fa-plane','fas fa-play',
        'fas fa-plug','fas fa-plus','fas fa-puzzle-piece',
        'fas fa-circle-question','fas fa-recycle','fas fa-rocket',
        'fas fa-rss','fas fa-magnifying-glass','fas fa-share',
        'fas fa-shield','fas fa-signal','fas fa-sliders',
        'fas fa-smile','fas fa-star','fas fa-store',
        'fas fa-tag','fas fa-tags','fas fa-thumbs-up',
        'fas fa-ticket','fas fa-toolbox','fas fa-trophy',
        'fas fa-truck','fas fa-tv','fas fa-umbrella',
        'fas fa-upload','fas fa-user','fas fa-users',
        'fas fa-video','fas fa-wallet','fas fa-wifi',
        'fas fa-wrench',
        'fab fa-facebook','fab fa-instagram','fab fa-linkedin',
        'fab fa-x-twitter','fab fa-whatsapp','fab fa-youtube',
        'fab fa-tiktok','fab fa-pinterest','fab fa-github'
    ];

    function getDashiconsList() {
        if (vitrineData.iconPicker && vitrineData.iconPicker.dashicons && vitrineData.iconPicker.dashicons.length) {
            return vitrineData.iconPicker.dashicons;
        }
        return DASHICONS_LIST;
    }

    function getFaIconsList() {
        if (vitrineData.iconPicker && vitrineData.iconPicker.fontawesome && vitrineData.iconPicker.fontawesome.length) {
            return vitrineData.iconPicker.fontawesome;
        }
        return FA_ICONS_LIST;
    }

    function populateIconGrid($grid, tab) {
        if (!$grid || !$grid.length) return;
        var html = '';
        if (tab === 'dashicons') {
            getDashiconsList().forEach(function (d) {
                html += '<button type="button" class="vitrine-icon-pick" data-icon="' + escapeAttr(d) + '" title="' + escapeAttr(d) + '"><span class="dashicons ' + escapeAttr(d) + '"></span></button>';
            });
        } else if (tab === 'fa') {
            getFaIconsList().forEach(function (f) {
                html += '<button type="button" class="vitrine-icon-pick" data-icon="' + escapeAttr(f) + '" title="' + escapeAttr(f) + '"><i class="' + escapeAttr(f) + '"></i></button>';
            });
        }
        $grid.html(html);
    }

    function ensureIconGrid($picker, tab) {
        if (!$picker || !$picker.length || tab === 'upload') return;
        var $panel = $picker.find('.vitrine-icon-tab-panel[data-panel="' + tab + '"]');
        if (!$panel.length || $panel.data('grid-loaded')) return;
        $panel.data('grid-loaded', true);
        populateIconGrid($panel.find('.vitrine-icons-grid'), tab);
    }

    function isIconClass(icon) {
        return icon && (icon.indexOf('dashicons-') === 0 || /^fa[srlbd]?\s/.test(icon));
    }

    function renderIconPreviewHtml(icon) {
        if (!icon) return '';
        if (icon.indexOf('dashicons-') === 0) {
            return '<span class="dashicons ' + escapeAttr(icon) + '" style="font-size:30px;width:30px;height:30px;color:#0073aa;display:inline-block;"></span>';
        }
        if (/^fa[srlbd]?\s/.test(icon)) {
            return '<i class="' + escapeAttr(icon) + '" style="font-size:26px;color:#0073aa;"></i>';
        }
        return '<img src="' + escapeAttr(icon) + '" style="max-width:48px;max-height:48px;border-radius:3px;object-fit:contain;display:block;" />';
    }

    /* ──────────────────── Helpers ──────────────────── */

    function uid() {
        return 'el_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
    }

    function findItemById(id, items) {
        items = items || layout;
        for (var i = 0; i < items.length; i++) {
            if (items[i].id === id) return items[i];
            if (items[i].children && items[i].children.length) {
                var found = findItemById(id, items[i].children);
                if (found) return found;
            }
        }
        return null;
    }

    function removeItemById(id, items) {
        items = items || layout;
        for (var i = 0; i < items.length; i++) {
            if (items[i].id === id) {
                items.splice(i, 1);
                return true;
            }
            if (items[i].children && removeItemById(id, items[i].children)) {
                return true;
            }
        }
        return false;
    }

    function insertAfterById(afterId, newItem, items) {
        items = items || layout;
        for (var i = 0; i < items.length; i++) {
            if (items[i].id === afterId) {
                items.splice(i + 1, 0, newItem);
                return true;
            }
            if (items[i].children && insertAfterById(afterId, newItem, items[i].children)) {
                return true;
            }
        }
        return false;
    }

    function findParentArray(id, items) {
        items = items || layout;
        for (var i = 0; i < items.length; i++) {
            if (items[i].id === id) return items;
            if (items[i].children) {
                var found = findParentArray(id, items[i].children);
                if (found) return found;
            }
        }
        return null;
    }

    function findParentContainer(id, items) {
        items = items || layout;
        for (var i = 0; i < items.length; i++) {
            if (items[i].children) {
                for (var j = 0; j < items[i].children.length; j++) {
                    if (items[i].children[j].id === id) return items[i];
                }
                var found = findParentContainer(id, items[i].children);
                if (found) return found;
            }
        }
        return null;
    }

    function createItem(type) {
        var elDef = elements[type];
        var item = {
            id: uid(),
            type: type,
            settings: JSON.parse(JSON.stringify(elDef ? elDef.defaults : {})),
            height: 0,
            width: ''
        };
        if (type === 'container') {
            item.children = [];
        }
        return item;
    }

    function getContainerDisplayName(settings) {
        var name = settings && settings.name ? String(settings.name).trim() : '';
        return name || t('ui.container_default', 'Container');
    }

    function getContainerDirectionUi(direction) {
        var current = normalizeContainerDirection(direction);
        var isRow = current === 'row';
        // UI: "Linha" = um abaixo do outro (flex column); "Coluna" = lado a lado (flex row).
        return {
            current: current,
            previewLabel: isRow ? 'coluna' : 'linha',
            title: 'Direção: linha (um abaixo do outro) ou coluna (lado a lado)'
        };
    }

    function normalizeContainerDirection(direction) {
        var raw = String(direction || 'column').toLowerCase().trim();
        if (raw === 'row' || raw === 'linha' || raw === 'horizontal') {
            return 'row';
        }
        return 'column';
    }

    function getContainerPreviewLabel(settings) {
        var dirUi = getContainerDirectionUi(settings.direction || 'column');
        var cFull = settings.full_width_bg === '1' || settings.full_width_bg === 1;
        return getContainerDisplayName(settings) + ' – ' + dirUi.previewLabel + (cFull ? ' · Fundo largura total' : '');
    }

    function getBlockToolbarLabel(item, elDef) {
        if (item.type === 'container') {
            return getContainerDisplayName($.extend({}, elDef.defaults, item.settings));
        }
        return elDef.label;
    }

    function isAranhaType(type) {
        return type === 'aranha' || type === 'aranha2' || type === 'aranha3';
    }

    function getAranhaLayoutMode(item) {
        if (!item) return 'circular';
        if (item.type === 'aranha3') return 'grade';
        if (item.type === 'aranha2') return 'circular';
        var mode = item.settings && item.settings.layout_mode
            ? String(item.settings.layout_mode).toLowerCase().trim()
            : 'circular';
        return mode === 'grade' ? 'grade' : 'circular';
    }

    function migrateAranhaLayout(list) {
        if (!Array.isArray(list)) return [];
        return list.map(function (item) {
            if (!item || typeof item !== 'object') return item;
            var copy = $.extend(true, {}, item);
            if (!copy.settings || typeof copy.settings !== 'object') {
                copy.settings = {};
            }
            if (copy.type === 'aranha2') {
                copy.type = 'aranha';
                copy.settings.layout_mode = 'circular';
            } else if (copy.type === 'aranha3') {
                copy.type = 'aranha';
                copy.settings.layout_mode = 'grade';
            } else if (copy.type === 'aranha') {
                copy.settings.layout_mode = getAranhaLayoutMode(copy);
            }
            if (Array.isArray(copy.children)) {
                copy.children = migrateAranhaLayout(copy.children);
            }
            return copy;
        });
    }

    var layout = migrateAranhaLayout(vitrineData.layout || []);

    function containerContainsAranha(container) {
        if (!container || !container.children || !container.children.length) {
            return false;
        }
        for (var i = 0; i < container.children.length; i++) {
            var child = container.children[i];
            if (isAranhaType(child.type)) {
                return true;
            }
            if (child.type === 'container' && containerContainsAranha(child)) {
                return true;
            }
        }
        return false;
    }

    function enforceContainerColumnForAranha(container) {
        if (!container || container.type !== 'container' || !containerContainsAranha(container)) {
            return;
        }
        if (container.settings.direction === 'row') {
            container.settings.direction = 'column';
            clearWidths(container.id);
        }
    }

    function enforceAllContainerAranhaLayouts(items) {
        items = items || layout;
        items.forEach(function (item) {
            if (item.type === 'container') {
                enforceContainerColumnForAranha(item);
                if (item.children && item.children.length) {
                    enforceAllContainerAranhaLayouts(item.children);
                }
            }
        });
    }

    /**
     * Distribui larguras igualmente entre filhos de um container row.
     */
    function distributeWidths(containerId) {
        var container = findItemById(containerId);
        if (!container || !container.children || !container.children.length) return;
        if (!container.settings || container.settings.direction !== 'row') return;

        var n   = container.children.length;
        var pct = Math.floor(10000 / n) / 100;
        var sum = 0;
        for (var i = 0; i < n - 1; i++) {
            container.children[i].width = pct + '%';
            sum += pct;
        }
        container.children[n - 1].width = (Math.round((100 - sum) * 100) / 100) + '%';
    }

    /**
     * Redistribui proporcionalmente (preserva proporções relativas após remoção).
     */
    function redistributeProportional(containerId) {
        var container = findItemById(containerId);
        if (!container || !container.children || !container.children.length) return;
        if (!container.settings || container.settings.direction !== 'row') return;

        var children = container.children;
        var total = 0;
        children.forEach(function (c) { total += parseFloat(c.width) || 0; });

        if (total <= 0) { distributeWidths(containerId); return; }

        var sum = 0;
        for (var i = 0; i < children.length - 1; i++) {
            var p = Math.round(((parseFloat(children[i].width) || 0) / total * 100) * 100) / 100;
            children[i].width = p + '%';
            sum += p;
        }
        children[children.length - 1].width = (Math.round((100 - sum) * 100) / 100) + '%';
    }

    /**
     * Limpa larguras (quando container muda para column).
     */
    function clearWidths(containerId) {
        var container = findItemById(containerId);
        if (!container || !container.children) return;
        container.children.forEach(function (c) { c.width = ''; });
    }

    /* ──────────────────── Templates ──────────────────── */

    var vitrineTemplates = [
        {
            nameKey: 'ui.template_complete_name',
            nameFallback: 'Complete showcase',
            descKey: 'ui.template_complete_desc',
            descFallback: 'Highlights + Text + Toggle + Text + Banner',
            items: [
                { type: 'aranha', overrides: { layout_mode: 'grade' } },
                { type: 'text', overrides: { content: '<p>Escreva um texto descritivo aqui...</p>' } },
                { type: 'toggle' },
                { type: 'text', overrides: { content: '<p>Mais informações sobre o assunto...</p>' } },
                { type: 'image' }
            ]
        }
    ];

    function applyTemplate(tplIndex) {
        var tpl = vitrineTemplates[tplIndex];
        if (!tpl) return;

        layout.length = 0;

        tpl.items.forEach(function (tplItem) {
            var container = createItem('container');
            var child = createItem(tplItem.type);
            if (tplItem.overrides) {
                $.extend(child.settings, tplItem.overrides);
            }
            container.children.push(child);
            layout.push(container);
        });

        renderCanvas();
        renderSettings();
    }

    /* ──────────────────── Renderização do Canvas ──────────────────── */

    function syncCollapsedContainerIdsFromDOM() {
        var next = {};
        $('#vitrine-canvas .vitrine-canvas-block--container.is-collapsed').each(function () {
            var id = $(this).data('id');
            if (id) {
                next[id] = true;
            }
        });
        collapsedContainerIds = next;
    }

    function applyCollapsedContainers($canvas) {
        $.each(collapsedContainerIds, function (id) {
            $canvas.find('.vitrine-canvas-block--container[data-id="' + id + '"]').addClass('is-collapsed');
        });
    }

    function purgeCollapsedContainerIdsForItem(item) {
        if (!item) return;
        if (item.type === 'container') {
            delete collapsedContainerIds[item.id];
            if (item.children && item.children.length) {
                item.children.forEach(purgeCollapsedContainerIdsForItem);
            }
        }
    }

    function renderCanvas() {
        enforceAllContainerAranhaLayouts();
        syncCollapsedContainerIdsFromDOM();
        var $canvas = $('#vitrine-canvas');
        $canvas.empty();

        if (!layout.length) {
            var tplHtml = '<div class="vitrine-template-picker">';
            tplHtml += '<p class="vitrine-template-picker__title">' + escapeHtml(t('ui.template_picker_title', 'Initial template')) + '</p>';
            tplHtml += '<div class="vitrine-template-picker__grid vitrine-template-picker__grid--single">';
            vitrineTemplates.forEach(function (tpl, idx) {
                tplHtml += '<button type="button" class="vitrine-template-picker__item" data-tpl-idx="' + idx + '">';
                tplHtml += '<strong>' + escapeHtml(t(tpl.nameKey, tpl.nameFallback)) + '</strong>';
                tplHtml += '<span>' + escapeHtml(t(tpl.descKey, tpl.descFallback)) + '</span>';
                tplHtml += '</button>';
            });
            tplHtml += '</div>';
            tplHtml += '<p class="vitrine-template-picker__hint">' + escapeHtml(t('ui.template_picker_hint', 'Or drag containers from the sidebar to start from scratch')) + '</p>';
            tplHtml += '</div>';
            $canvas.append(tplHtml);
        } else {
            renderItems(layout, $canvas);
            applyCollapsedContainers($canvas);
        }

        if (selectedId) {
            $canvas.find('[data-id="' + selectedId + '"]').first().addClass('is-selected');
        }

        initAllSortables();
    }

    /**
     * Renderiza itens recursivamente.
     */
    function renderItems(items, $parent, parentItem) {
        var isRowContainer = parentItem && parentItem.type === 'container' &&
            parentItem.settings && parentItem.settings.direction === 'row';

        items.forEach(function (item, index) {
            var elDef = elements[item.type];
            if (!elDef) return;

            var settings    = $.extend({}, elDef.defaults, item.settings);
            var heightStyle = item.height ? 'min-height:' + item.height + 'px;' : '';
            var isContainer = item.type === 'container';
            var isFullBg    = isContainer && (settings.full_width_bg === '1' || settings.full_width_bg === 1);
            var customCss   = (item.settings && item.settings.custom_css) ? item.settings.custom_css : '';

            // Largura em flex para filhos de row container
            var widthStyle = '';
            var widthBadgeHtml = '';
            if (isRowContainer && item.width) {
                widthStyle = 'flex:0 1 ' + escapeAttr(item.width) + ';max-width:' + escapeAttr(item.width) + ';min-width:0;box-sizing:border-box;';
                var pctNum = Math.round(parseFloat(item.width));
                widthBadgeHtml = '<span class="vitrine-width-badge">' + pctNum + '%</span>';
            }

            var blockBgStyle = '';
            if (isFullBg) {
                if (settings.bg_image) {
                    blockBgStyle = 'background-image:url(' + escapeAttr(settings.bg_image) + ');background-size:' + escapeAttr(settings.bg_size || 'cover') + ';background-position:center;background-repeat:no-repeat;';
                } else {
                    blockBgStyle = 'background-color:' + escapeAttr(settings.bg_color || '#f5f5f5') + ';';
                }
            }

            var html =
                '<div class="vitrine-canvas-block' +
                    (isContainer ? ' vitrine-canvas-block--container' + (isFullBg ? ' vitrine-canvas-block--full-bg' : '') : '') +
                    (isRowContainer ? ' vitrine-canvas-block--in-row' : '') +
                '" data-id="' + item.id + '" data-type="' + item.type + '" style="' + heightStyle + widthStyle + blockBgStyle + escapeAttr(customCss) + '">' +
                    '<div class="vitrine-block-toolbar">' +
                        '<span class="vitrine-drag-handle dashicons dashicons-move"></span>' +
                        '<span class="vitrine-block-label">' + escapeHtml(getBlockToolbarLabel(item, elDef)) + '</span>' +
                        widthBadgeHtml +
                        (isContainer && !containerContainsAranha(item) ? (function() {
                            var dirUi = getContainerDirectionUi(settings.direction);
                            var isRow = dirUi.current === 'row';
                            return '<div class="vitrine-dir-toggle" role="group" title="' + escapeAttr(dirUi.title) + '" data-id="' + escapeAttr(item.id) + '">' +
                                '<button type="button" class="vitrine-dir-option' + (!isRow ? ' is-active' : '') + '" data-dir="column" title="Linha (um abaixo do outro)">Linha</button>' +
                                '<button type="button" class="vitrine-dir-option' + (isRow ? ' is-active' : '') + '" data-dir="row" title="Coluna (lado a lado)">Coluna</button>' +
                            '</div>';
                        })() : '') +
                        (isContainer ? '<button type="button" class="vitrine-block-collapse" title="Colapsar/Expandir"><span class="dashicons dashicons-arrow-right-alt2"></span></button>' : '') +
                        '<button type="button" class="vitrine-block-duplicate" title="Duplicar">' +
                            '<span class="dashicons dashicons-admin-page"></span>' +
                        '</button>' +
                        '<button type="button" class="vitrine-block-remove" title="Remover">' +
                            '<span class="dashicons dashicons-trash"></span>' +
                        '</button>' +
                    '</div>' +
                    '<div class="vitrine-block-preview"' + (function () {
                        var previewBg = getTextBlockPreviewBgStyle(item.type, settings);
                        return previewBg ? ' style="' + previewBg + '"' : '';
                    })() + '>' +
                        buildPreview(item.type, settings) +
                    '</div>' +
                    (isContainer ? (function() {
                        var dropStyle = 'gap:' + parseInt(settings.gap || 0, 10) + 'px;align-items:' + escapeAttr(settings.align_items || 'stretch') + ';';
                        if (!isFullBg) {
                            if (settings.bg_image) {
                                dropStyle += 'background-image:url(' + escapeAttr(settings.bg_image) + ');background-size:' + escapeAttr(settings.bg_size || 'cover') + ';background-position:center;background-repeat:no-repeat;';
                            } else {
                                dropStyle += 'background-color:' + escapeAttr(settings.bg_color || '#f5f5f5') + ';';
                            }
                        }
                        return '<div class="vitrine-container-drop' + (normalizeContainerDirection(settings.direction) === 'row' ? ' vitrine-container-drop--row' : '') + (isFullBg ? ' vitrine-container-drop--boxed' : '') + '" data-parent-id="' + item.id + '" style="' + dropStyle + '"></div>';
                    })() : '') +
                    '<div class="vitrine-resize-handle"></div>' +
                '</div>';

            var $block = $(html);
            $parent.append($block);

            if (isContainer && item.children && item.children.length) {
                var $drop = $block.find('> .vitrine-container-drop');
                renderItems(item.children, $drop, item);
            }

            // Divisor como IRMÃO entre blocos (fora do bloco, no flex container)
            if (isRowContainer && index < items.length - 1) {
                var nextItem = items[index + 1];
                $parent.append('<div class="vitrine-col-divider" data-left-id="' + item.id + '" data-right-id="' + nextItem.id + '"></div>');
            }
        });
    }

    function buildAranhaEditorPlaceholder(type, settings) {
        var mode = getAranhaLayoutMode({ type: type, settings: settings || {} });
        var icon = mode === 'grade' ? 'dashicons-grid-view' : 'dashicons-chart-pie';
        var label = mode === 'grade' ? 'Aranha · Grade' : 'Aranha · Circular';
        return '<div class="vitrine-block-preview-placeholder vitrine-block-preview-placeholder--aranha">' +
            '<span class="dashicons ' + icon + '"></span>' +
            '<span class="vitrine-block-preview-placeholder__label">' + escapeHtml(label) + '</span>' +
            '<small>Configure no painel lateral · visualização no frontend</small>' +
        '</div>';
    }

    function isWhiteTextColor(color) {
        var c = String(color || '').trim().toLowerCase();
        if (c === 'white') return true;
        if (/^#fff(f{3})?$/i.test(c)) return true;
        return /^rgb\(\s*255\s*,\s*255\s*,\s*255\s*\)$/i.test(c);
    }

    function getTextBlockPreviewBgStyle(type, settings) {
        if (type !== 'text') return '';
        var bg = String(settings.bg_color || '').trim();
        if (bg) {
            return 'background:' + escapeAttr(bg) + ';';
        }
        return isWhiteTextColor(settings.color) ? 'background:#000;' : '';
    }

    function refreshBlockPreview($block, type, settings) {
        $block.html(buildPreview(type, settings));
        var bgStyle = getTextBlockPreviewBgStyle(type, settings);
        if (bgStyle) {
            $block.attr('style', bgStyle);
        } else if (type === 'text') {
            $block.removeAttr('style');
        }
    }

    /**
     * Preview simplificado de cada tipo de elemento dentro do canvas.
     */
    function buildPreview(type, settings) {
        if (isAranhaType(type)) {
            return buildAranhaEditorPlaceholder(type, settings);
        }

        switch (type) {
            case 'title':
                var tag = settings.tag || 'h2';
                return '<' + tag + ' class="vitrine-el-title" style="margin:0;padding:0;color:' + escapeAttr(settings.color || '#333') + ';font-size:' + parseInt(settings.font_size || 28, 10) + 'px;text-align:' + escapeAttr(settings.align || 'left') + ';line-height:1.25;">' + escapeHtml(settings.text || '') + '</' + tag + '>';
            case 'text':
                return '<div class="vitrine-el-text" style="color:' + escapeAttr(settings.color || '#555') + ';font-size:' + parseInt(settings.font_size || 16, 10) + 'px;text-align:' + escapeAttr(settings.align || 'left') + ';--vitrine-text-color:' + escapeAttr(settings.color || '#555') + ';--vitrine-text-size:' + parseInt(settings.font_size || 16, 10) + 'px;">' + (settings.content || '') + '</div>';
            case 'textimage': {
                var tiTag   = settings.title_tag || 'h2';
                var tiTitle = settings.title || '';
                var tiCont  = settings.content || '';
                var tiBg    = escapeAttr(settings.bg_color || '#f8f8f6');
                var tiPad   = parseInt(settings.padding || 32, 10);
                var tiRad   = parseInt(settings.border_radius || 8, 10);
                var tiGap   = parseInt(settings.gap || 24, 10);
                var tiTitCl = escapeAttr(settings.title_color || '#1a1a1a');
                var tiTitSz = parseInt(settings.title_font_size || 28, 10);
                var tiTxtCl = escapeAttr(settings.content_color || '#555555');
                var tiTxtSz = parseInt(settings.content_font_size || 16, 10);
                var tiImgW  = Math.max(20, Math.min(60, parseInt(settings.image_width || 42, 10)));
                var tiTxtW  = 100 - tiImgW;
                var tiImgPos = settings.image_position === 'left' ? 'row-reverse' : 'row';
                var tiHtml  = '<div style="background:' + tiBg + ';padding:' + tiPad + 'px;border-radius:' + tiRad + 'px;">';
                tiHtml += '<div style="display:flex;flex-direction:' + tiImgPos + ';align-items:center;gap:' + tiGap + 'px;">';
                tiHtml += '<div style="flex:1 1 ' + tiTxtW + '%;min-width:0;">';
                if (tiTitle) {
                    tiHtml += '<' + tiTag + ' style="margin:0 0 8px;color:' + tiTitCl + ';font-size:' + tiTitSz + 'px;line-height:1.25;">' + escapeHtml(tiTitle) + '</' + tiTag + '>';
                }
                if (tiCont) {
                    tiHtml += '<div style="color:' + tiTxtCl + ';font-size:' + tiTxtSz + 'px;line-height:1.6;">' + tiCont + '</div>';
                }
                tiHtml += '</div>';
                tiHtml += '<div style="flex:0 0 ' + tiImgW + '%;min-width:0;">';
                if (settings.image) {
                    tiHtml += '<img src="' + escapeAttr(settings.image) + '" style="width:100%;height:auto;border-radius:6px;display:block;" alt="" />';
                } else {
                    tiHtml += '<div style="background:#e8e8e4;border-radius:6px;min-height:80px;display:flex;align-items:center;justify-content:center;color:#aaa;font-size:10px;">Imagem</div>';
                }
                tiHtml += '</div></div></div>';
                return tiHtml;
            }
            case 'imagelinks': {
                var ilImgH    = Math.max(0, Math.min(280, parseInt(settings.image_height || 220, 10)));
                var ilImgFit  = settings.image_fit === 'contain' ? 'contain' : 'cover';
                var ilCapBg   = escapeAttr(settings.caption_bg || '#000000');
                var ilCapCl   = escapeAttr(settings.caption_color || '#ffffff');
                var ilCapSz   = parseInt(settings.caption_font_size || 16, 10);
                var ilCap     = settings.caption || '';
                var ilBoxBg   = escapeAttr(settings.box_bg || '#e8e8e8');
                var ilBoxTit  = settings.box_title || '';
                var ilTitCl   = escapeAttr(settings.box_title_color || '#333333');
                var ilTitSz   = parseInt(settings.box_title_font_size || 16, 10);
                var ilContent = getImagelinksContent(settings);
                var ilTextCl  = escapeAttr(settings.content_color || settings.link_color || '#333333');
                var ilTextSz  = parseInt(settings.content_font_size || settings.link_font_size || 14, 10);
                var ilSepCl   = escapeAttr(settings.separator_color || '#333333');
                var ilPad     = parseInt(settings.box_padding || 16, 10);
                var ilMediaRad = parseInt(settings.media_radius_top || 0, 10);
                var ilBoxRad  = parseInt(settings.box_radius_bottom || 0, 10);

                var ilHtml = '<div style="max-width:100%;overflow:hidden;font-family:inherit;">';
                ilHtml += '<div style="' + (ilImgH > 0 ? 'height:' + ilImgH + 'px;' : 'height:0;min-height:0;') + 'background:#ddd;overflow:hidden;border-top-left-radius:' + ilMediaRad + 'px;border-top-right-radius:' + ilMediaRad + 'px;">';
                if (settings.image) {
                    ilHtml += '<img src="' + escapeAttr(settings.image) + '" style="width:100%;height:100%;object-fit:' + ilImgFit + ';object-position:center;display:block;" alt="" />';
                } else {
                    ilHtml += '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;font-size:10px;">Imagem</div>';
                }
                ilHtml += '</div>';
                if (ilCap) {
                    ilHtml += '<div style="background:' + ilCapBg + ';color:' + ilCapCl + ';font-size:' + ilCapSz + 'px;font-weight:700;text-align:center;padding:8px 10px;line-height:1.3;">' + escapeHtml(ilCap) + '</div>';
                }
                if (ilBoxTit || ilContent) {
                    ilHtml += '<div style="background:' + ilBoxBg + ';padding:' + ilPad + 'px;border-bottom-left-radius:' + ilBoxRad + 'px;border-bottom-right-radius:' + ilBoxRad + 'px;">';
                    if (ilBoxTit) {
                        ilHtml += '<div style="color:' + ilTitCl + ';font-size:' + ilTitSz + 'px;font-weight:700;margin:0 0 8px;line-height:1.3;">' + escapeHtml(ilBoxTit) + '</div>';
                    }
                    if (ilBoxTit && ilContent) {
                        ilHtml += '<hr style="border:none;border-top:1px solid ' + ilSepCl + ';margin:0 0 8px;" />';
                    }
                    if (ilContent) {
                        ilHtml += '<div style="color:' + ilTextCl + ';font-size:' + ilTextSz + 'px;line-height:1.5;">' + ilContent + '</div>';
                    }
                    ilHtml += '</div>';
                }
                ilHtml += '</div>';
                return ilHtml;
            }
            case 'itemgrid': {
                var igItems = settings.items || [];
                var igCols  = Math.max(1, Math.min(4, parseInt(settings.columns || 3, 10)));
                var igGap   = parseInt(settings.gap || 24, 10);
                var igBg    = escapeAttr(settings.card_bg || '#ffffff');
                var igRad   = parseInt(settings.card_radius || 12, 10);
                var igPad   = parseInt(settings.card_padding || 20, 10);
                var igImgH  = Math.max(0, parseInt(settings.image_height || 180, 10));
                var igTitCl = escapeAttr(settings.title_color || '#1a1a1a');
                var igTitSz = parseInt(settings.title_size || 20, 10);
                var igTitWt = escapeAttr(settings.title_weight || '700');
                var igDescCl = escapeAttr(settings.desc_color || '#555555');
                var igDescSz = parseInt(settings.desc_size || 15, 10);
                var igDescWt = escapeAttr(settings.desc_weight || '400');

                if (!igItems.length) {
                    return '<div style="text-align:center;padding:20px;color:#999;font-size:12px;border:1px dashed #c3c4c7;border-radius:6px;">Adicione cards na grade</div>';
                }

                var igHtml = '<div style="display:grid;grid-template-columns:repeat(' + igCols + ',1fr);gap:' + igGap + 'px;">';
                igItems.slice(0, 6).forEach(function (ci) {
                    var cTitle = stripHtmlPreview(ci.title) || 'Card';
                    var cDesc  = stripHtmlPreview(ci.description);
                    var tCl = ci.title_color || igTitCl;
                    var tSz = ci.title_size || igTitSz;
                    var tWt = ci.title_weight || igTitWt;
                    var dCl = ci.desc_color || igDescCl;
                    var dSz = ci.desc_size || igDescSz;
                    var dWt = ci.desc_weight || igDescWt;

                    igHtml += '<div style="background:' + igBg + ';border-radius:' + igRad + 'px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.08);">';
                    if (ci.image && igImgH > 0) {
                        igHtml += '<div style="height:' + Math.min(igImgH, 80) + 'px;overflow:hidden;background:#ddd;">';
                        igHtml += '<img src="' + escapeAttr(ci.image) + '" style="width:100%;height:100%;object-fit:cover;display:block;" alt="" />';
                        igHtml += '</div>';
                    }
                    igHtml += '<div style="padding:' + Math.min(igPad, 14) + 'px;">';
                    igHtml += '<div style="color:' + escapeAttr(tCl) + ';font-size:' + Math.min(tSz, 16) + 'px;font-weight:' + escapeAttr(tWt) + ';margin:0 0 4px;line-height:1.3;">' + escapeHtml(cTitle) + '</div>';
                    if (cDesc) {
                        igHtml += '<div style="color:' + escapeAttr(dCl) + ';font-size:' + Math.min(dSz, 13) + 'px;font-weight:' + escapeAttr(dWt) + ';line-height:1.4;opacity:.9;">' + escapeHtml(cDesc.substring(0, 60)) + (cDesc.length > 60 ? '…' : '') + '</div>';
                    }
                    igHtml += '</div></div>';
                });
                if (igItems.length > 6) {
                    igHtml += '<div style="grid-column:1/-1;text-align:center;font-size:11px;color:#8c8f94;">+' + (igItems.length - 6) + ' cards</div>';
                }
                igHtml += '</div>';
                return igHtml;
            }
            case 'itemcarousel': {
                var icItems = settings.items || [];
                var icSpv   = Math.max(1, Math.min(4, parseInt(
                    settings.slides_per_view_desktop || settings.slides_per_view || 3,
                    10
                )));
                var icGap   = parseInt(settings.gap || 20, 10);
                var icBg    = escapeAttr(settings.card_bg || '#ffffff');
                var icRad   = parseInt(settings.card_radius || 12, 10);
                var icPad   = parseInt(settings.card_padding || 20, 10);
                var icImgH  = Math.max(0, parseInt(settings.image_height || 160, 10));
                var icTitCl = escapeAttr(settings.title_color || '#1a1a1a');
                var icTitSz = parseInt(settings.title_size || 18, 10);
                var icTxtCl = escapeAttr(settings.text_color || '#555555');
                var icTxtSz = parseInt(settings.text_size || 14, 10);

                if (!icItems.length) {
                    return '<div style="text-align:center;padding:20px;color:#999;font-size:12px;border:1px dashed #c3c4c7;border-radius:6px;">Adicione slides ao carrossel</div>';
                }

                var icHtml = '<div style="display:flex;gap:' + icGap + 'px;overflow:hidden;padding:4px 0;">';
                icItems.slice(0, icSpv + 1).forEach(function (si) {
                    var isIcon = si.item_type === 'icon';
                    var sTitle = stripHtmlPreview(si.title) || 'Slide';
                    var sText  = stripHtmlPreview(si.text || si.description || '');

                    icHtml += '<div style="flex:0 0 calc(' + (100 / icSpv) + '% - ' + icGap + 'px);min-width:0;background:' + icBg + ';border-radius:' + icRad + 'px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.08);">';
                    if (isIcon && si.icon) {
                        icHtml += '<div style="display:flex;justify-content:center;padding:12px 10px 0;"><span style="display:inline-flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:50%;background:#eef5fc;">' + renderIconPreviewHtml(si.icon) + '</span></div>';
                    } else if (!isIcon && si.image && icImgH > 0) {
                        icHtml += '<div style="height:' + Math.min(icImgH, 70) + 'px;overflow:hidden;background:#ddd;"><img src="' + escapeAttr(si.image) + '" style="width:100%;height:100%;object-fit:cover;display:block;" alt="" /></div>';
                    }
                    icHtml += '<div style="padding:' + Math.min(icPad, 12) + 'px;text-align:center;">';
                    icHtml += '<div style="color:' + icTitCl + ';font-size:' + Math.min(icTitSz, 15) + 'px;font-weight:700;margin:0 0 4px;">' + escapeHtml(sTitle) + '</div>';
                    if (sText) {
                        icHtml += '<div style="color:' + icTxtCl + ';font-size:' + Math.min(icTxtSz, 12) + 'px;line-height:1.35;">' + escapeHtml(sText.substring(0, 50)) + (sText.length > 50 ? '…' : '') + '</div>';
                    }
                    icHtml += '</div></div>';
                });
                if (icItems.length > icSpv) {
                    icHtml += '<div style="flex:0 0 24px;display:flex;align-items:center;justify-content:center;color:#8c8f94;font-size:18px;">›</div>';
                }
                icHtml += '</div>';
                return icHtml;
            }
            case 'image':
                if (settings.url) {
                    return '<div style="text-align:' + escapeAttr(settings.align || 'center') + '"><img src="' + escapeAttr(settings.url) + '" style="max-width:' + parseInt(settings.width || 100, 10) + '%;height:auto;" alt="" /></div>';
                }
                return '<p style="text-align:center;color:#999;">Nenhuma imagem selecionada</p>';
            case 'button':
                return '<div style="text-align:' + escapeAttr(settings.align || 'center') + '"><span style="display:inline-block;padding:10px 24px;background:' + escapeAttr(settings.bg_color || '#0073aa') + ';color:' + escapeAttr(settings.color || '#fff') + ';border-radius:4px;">' + escapeHtml(settings.text || 'Botão') + '</span></div>';
            case 'shortcode': {
                var scContent = (settings.content || '').trim();
                var scAlign   = escapeAttr(settings.align || 'left');
                if (!scContent) {
                    return '<div style="text-align:center;color:#999;font-size:12px;padding:12px;border:1px dashed #c3c4c7;border-radius:4px;">Cole um shortcode no painel</div>';
                }
                return '<div style="text-align:' + scAlign + ';padding:10px 12px;border:1px dashed #8c8f94;border-radius:4px;background:#f6f7f7;">'
                    + '<div style="font-size:10px;color:#646970;margin-bottom:4px;">Shortcode (executado no frontend)</div>'
                    + '<code style="display:block;font-size:11px;color:#1d2327;word-break:break-all;white-space:pre-wrap;">' + escapeHtml(scContent) + '</code>'
                    + '</div>';
            }
            case 'html': {
                var htmlContent = (settings.content || '').trim();
                var htmlAlign   = escapeAttr(settings.align || 'left');
                if (!htmlContent) {
                    return '<div style="text-align:center;color:#999;font-size:12px;padding:12px;border:1px dashed #c3c4c7;border-radius:4px;">Cole HTML no painel</div>';
                }
                return '<div style="text-align:' + htmlAlign + ';padding:10px 12px;border:1px dashed #8c8f94;border-radius:4px;background:#f6f7f7;">'
                    + '<div style="font-size:10px;color:#646970;margin-bottom:4px;">HTML (renderizado no frontend)</div>'
                    + '<code style="display:block;font-size:11px;color:#1d2327;word-break:break-all;white-space:pre-wrap;">' + escapeHtml(htmlContent) + '</code>'
                    + '</div>';
            }
            case 'container':
                var cBg = 'background-color:' + escapeAttr(settings.bg_color || '#f5f5f5') + ';';
                if (settings.bg_image) {
                    cBg += 'background-image:url(' + escapeAttr(settings.bg_image) + ');background-size:' + escapeAttr(settings.bg_size || 'cover') + ';background-position:center;';
                }
                var cFull = settings.full_width_bg === '1' || settings.full_width_bg === 1;
                var cLabel = getContainerPreviewLabel(settings);
                if (cFull) {
                    return '<div style="' + cBg + 'padding:6px 0;margin:0 -8px;"><div style="max-width:' + parseInt(settings.max_width || 1200, 10) + 'px;margin:0 auto;padding:0 8px;"><div class="vitrine-container-label" style="padding:4px 10px;font-size:11px;color:#666;">' + escapeHtml(cLabel) + '</div></div></div>';
                }
                return '<div class="vitrine-container-label" style="' + cBg + 'padding:4px 10px;font-size:11px;color:#666;border-radius:3px 3px 0 0;">' + escapeHtml(cLabel) + '</div>';
            case 'toggle':
                var tItems = settings.items || [];
                if (!tItems.length) {
                    return '<div style="color:#999;font-size:12px;text-align:center;padding:8px;">Nenhum toggle adicionado</div>';
                }
                var tHtml = '';
                tItems.forEach(function (ti, ti_idx) {
                    var tTitle = ti.title || '(sem título)';
                    tHtml += '<div style="display:flex;align-items:center;gap:6px;padding:4px 8px;background:' + escapeAttr(settings.header_bg || '#f5f5f5') + ';border-bottom:1px solid ' + escapeAttr(settings.border_color || '#dcdcde') + ';font-size:12px;color:' + escapeAttr(settings.header_color || '#333') + ';">';
                    tHtml += '<span style="color:' + escapeAttr(settings.icon_color || '#0073aa') + ';font-weight:700;">+</span> ';
                    tHtml += '<span>' + escapeHtml(tTitle) + '</span>';
                    tHtml += '</div>';
                });
                return '<div style="border:1px solid ' + escapeAttr(settings.border_color || '#dcdcde') + ';border-radius:4px;overflow:hidden;">' + tHtml + '</div>';
            case 'video': {
                var videoQty = Math.max(1, Math.min(3, parseInt(settings.qty || '1', 10)));
                var videoHtml = '';
                var hasAnyVideo = false;

                for (var vi = 1; vi <= videoQty; vi++) {
                    var vSrc = settings['source_' + vi] || 'youtube';
                    var vUrl = settings['url_' + vi] || '';

                    if (vi === 1 && !vUrl) {
                        if (settings.youtube_url) {
                            vSrc = 'youtube';
                            vUrl = settings.youtube_url;
                        } else if (settings.local_url) {
                            vSrc = 'local';
                            vUrl = settings.local_url;
                        } else if (settings.source) {
                            vSrc = settings.source;
                        }
                    }

                    if (!vUrl) continue;
                    hasAnyVideo = true;

                    if (vSrc === 'youtube') {
                        var ytId = extractYoutubeId(vUrl);
                        if (ytId) {
                            videoHtml += '<div style="flex:1;min-width:0;text-align:center;">';
                            videoHtml += '<img src="https://img.youtube.com/vi/' + escapeAttr(ytId) + '/hqdefault.jpg" style="max-width:100%;height:auto;border-radius:4px;" alt="" />';
                            videoHtml += '<div style="font-size:11px;color:#666;margin-top:4px;">YouTube: ' + escapeHtml(ytId) + '</div>';
                            videoHtml += '</div>';
                        } else {
                            videoHtml += '<div style="flex:1;min-width:0;text-align:center;color:#d63638;font-size:12px;">URL do YouTube inv\u00e1lida</div>';
                        }
                    } else {
                        videoHtml += '<div style="flex:1;min-width:0;text-align:center;padding:8px;">';
                        videoHtml += '<video src="' + escapeAttr(vUrl) + '" style="max-width:100%;max-height:120px;border-radius:4px;" muted></video>';
                        videoHtml += '<div style="font-size:11px;color:#666;margin-top:4px;">V\u00eddeo local</div>';
                        videoHtml += '</div>';
                    }
                }

                if (!hasAnyVideo) {
                    return '<div style="text-align:center;padding:16px;color:#999;font-size:12px;"><span class="dashicons dashicons-video-alt3" style="font-size:28px;display:block;margin:0 auto 4px;"></span>Nenhum v\u00eddeo selecionado</div>';
                }

                return '<div style="display:flex;gap:10px;align-items:flex-start;">' + videoHtml + '</div>';
            }

            default:
                return '<p>' + escapeHtml(type) + '</p>';
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escapeAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function safeTextareaHtml(str) {
        return String(str || '').replace(/<\/textarea/gi, '&lt;/textarea');
    }

    function getImagelinksContent(settings) {
        if (settings.content) {
            return settings.content;
        }
        var items = settings.items || [];
        if (!items.length) {
            return '';
        }
        var html = '';
        items.forEach(function (li) {
            if (!li.label) return;
            if (li.url) {
                html += '<p style="margin:0 0 6px;line-height:1.45;"><a href="' + escapeAttr(li.url) + '">' + escapeHtml(li.label) + '</a></p>';
            } else {
                html += '<p style="margin:0 0 6px;line-height:1.45;">' + escapeHtml(li.label) + '</p>';
            }
        });
        return html;
    }

    /* ──────────────────── Sortable (drag & drop) ──────────────────── */

    var sortableInstances = [];

    function destroyAllSortables() {
        sortableInstances.forEach(function (s) {
            if (s && s.destroy) s.destroy();
        });
        sortableInstances = [];
    }

    function initAllSortables() {
        destroyAllSortables();

        document.querySelectorAll('#vitrine-element-list .vitrine-element-group__items').forEach(function (elList) {
            sortableInstances.push(Sortable.create(elList, {
                group: { name: 'vitrine', pull: 'clone', put: false },
                sort: false,
                animation: 200
            }));
        });

        var canvas = document.getElementById('vitrine-canvas');
        if (canvas) {
            sortableInstances.push(createDropZone(canvas, null));
        }

        $('.vitrine-container-drop').each(function () {
            var parentId = $(this).data('parent-id');
            sortableInstances.push(createDropZone(this, parentId));
        });
    }

    function createDropZone(el, parentId) {
        return Sortable.create(el, {
            group: { name: 'vitrine', put: true },
            handle: '.vitrine-drag-handle',
            draggable: '.vitrine-canvas-block, .vitrine-element-item',
            animation: 200,
            ghostClass: 'vitrine-ghost',
            fallbackOnBody: true,
            swapThreshold: 0.65,
            onAdd: function (evt) { handleDrop(evt, false); },
            onUpdate: function (evt) { handleDrop(evt, true); }
        });
    }

    /**
     * Processa o drop.
     * - Não-containers na raiz → auto-encapsulados em container row.
     * - Ao adicionar/mover em row container → redistribui larguras.
     */
    function handleDrop(evt, isSameList) {
        var $item      = $(evt.item);
        var $to        = $(evt.to);
        var isNew      = $item.hasClass('vitrine-element-item');
        var toIsCanvas = evt.to.id === 'vitrine-canvas';
        var toParentId = $to.data('parent-id') || null;
        var targetArray = toIsCanvas ? layout : getChildrenArray(toParentId);

        if (!targetArray) { renderCanvas(); return; }

        // Calcula o índice correto ignorando divisores (conta apenas blocos antes do item)
        var realIndex = $item.prevAll('.vitrine-canvas-block').length;

        if (isNew) {
            var type    = $item.data('type');
            var newItem = createItem(type);

            if (toIsCanvas && type !== 'container') {
                // Auto-wrap em container row
                var wrapper = createItem('container');
                wrapper.settings.direction = 'row';
                wrapper.children.push(newItem);
                newItem.width = '100%';
                targetArray.splice(realIndex, 0, wrapper);
                selectedId = newItem.id;
            } else {
                targetArray.splice(realIndex, 0, newItem);
                selectedId = newItem.id;
                if (toParentId) {
                    var parentAfterAdd = findItemById(toParentId);
                    if (parentAfterAdd) enforceContainerColumnForAranha(parentAfterAdd);
                    if (parentAfterAdd && parentAfterAdd.settings.direction === 'row') {
                        distributeWidths(toParentId);
                    }
                }
            }
        } else {
            var draggedId   = $item.data('id');
            var draggedItem = findItemById(draggedId);
            if (!draggedItem) { renderCanvas(); return; }

            if (isSameList) {
                // Reordenação no mesmo container: preserva larguras
                removeItemById(draggedId);
                targetArray = toIsCanvas ? layout : getChildrenArray(toParentId);
                if (!targetArray) { renderCanvas(); return; }
                targetArray.splice(realIndex, 0, draggedItem);
            } else {
                // Movendo entre listas diferentes
                var oldParent = findParentContainer(draggedId);
                var oldParentId = oldParent ? oldParent.id : null;

                removeItemById(draggedId);
                if (oldParentId) redistributeProportional(oldParentId);

                targetArray = toIsCanvas ? layout : getChildrenArray(toParentId);
                if (!targetArray) { renderCanvas(); return; }

                if (toIsCanvas && draggedItem.type !== 'container') {
                    var wrapper2 = createItem('container');
                    wrapper2.settings.direction = 'row';
                    wrapper2.children.push(draggedItem);
                    draggedItem.width = '100%';
                    targetArray.splice(realIndex, 0, wrapper2);
                } else {
                    targetArray.splice(realIndex, 0, draggedItem);
                    if (toParentId) {
                        var parentAfterMove = findItemById(toParentId);
                        if (parentAfterMove) enforceContainerColumnForAranha(parentAfterMove);
                        if (parentAfterMove && parentAfterMove.settings.direction === 'row') {
                            distributeWidths(toParentId);
                        }
                    }
                }
            }
        }

        renderCanvas();
        renderSettings();
    }

    function getChildrenArray(parentId) {
        if (!parentId) return layout;
        var parent = findItemById(parentId);
        if (!parent) return null;
        if (!parent.children) parent.children = [];
        return parent.children;
    }

    /* ──────────────────── Seleção & Remoção ──────────────────── */

    $(document).on('click', '.vitrine-canvas-block', function (e) {
        e.stopPropagation();
        selectedId = $(this).data('id');
        $('.vitrine-canvas-block').removeClass('is-selected');
        $(this).addClass('is-selected');
        renderSettings();
    });

    $(document).on('click', '#vitrine-canvas', function (e) {
        if ($(e.target).closest('.vitrine-canvas-block').length === 0) {
            selectedId = null;
            $('.vitrine-canvas-block').removeClass('is-selected');
            renderSettings();
        }
    });

    $(document).on('click', '.vitrine-block-remove', function (e) {
        e.stopPropagation();
        var id = $(this).closest('.vitrine-canvas-block').data('id');

        var parentContainer = findParentContainer(id);
        var parentContainerId = parentContainer ? parentContainer.id : null;
        var removedItem = findItemById(id);

        purgeCollapsedContainerIdsForItem(removedItem);
        removeItemById(id);
        if (parentContainerId) redistributeProportional(parentContainerId);

        if (selectedId === id) selectedId = null;
        renderCanvas();
        renderSettings();
    });

    $(document).on('click', '.vitrine-dir-option', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $opt = $(this);
        var id   = $opt.closest('.vitrine-dir-toggle').attr('data-id');
        var item = findItemById(id);
        if (!item) return;
        if (!item.settings) item.settings = {};
        var newDir = normalizeContainerDirection($opt.attr('data-dir'));
        var current = normalizeContainerDirection(item.settings.direction);
        if (newDir === current) return;
        item.settings.direction = newDir;
        if (newDir === 'row') {
            distributeWidths(id);
        } else {
            clearWidths(id);
        }
        renderCanvas();
        if (selectedId === id) renderSettings();
    });

    $(document).on('click', '.vitrine-block-collapse', function (e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        var $block = $(this).closest('.vitrine-canvas-block--container');
        var id = $block.data('id');
        $block.toggleClass('is-collapsed');
        if ($block.hasClass('is-collapsed')) {
            collapsedContainerIds[id] = true;
        } else {
            delete collapsedContainerIds[id];
        }
    });

    $(document).on('click', '.vitrine-block-duplicate', function (e) {
        e.stopPropagation();
        var id       = $(this).closest('.vitrine-canvas-block').data('id');
        var original = findItemById(id);
        if (!original) return;

        var parentContainer = findParentContainer(id);
        var parentContainerId = parentContainer ? parentContainer.id : null;

        var clone = deepCloneItem(original);
        insertAfterById(id, clone);

        if (parentContainerId) distributeWidths(parentContainerId);

        selectedId = clone.id;
        renderCanvas();
        renderSettings();
    });

    function deepCloneItem(item) {
        var clone = {
            id: uid(),
            type: item.type,
            settings: JSON.parse(JSON.stringify(item.settings)),
            height: item.height,
            width: item.width || ''
        };
        if (item.children && item.children.length) {
            clone.children = item.children.map(function (child) {
                return deepCloneItem(child);
            });
        } else if (item.type === 'container') {
            clone.children = [];
        }
        return clone;
    }

    /* ──────────────────── Redimensionamento de altura ──────────────────── */

    (function () {
        var resizing = false, startY = 0, startH = 0, $block = null, itemRef = null;

        $(document).on('mousedown', '.vitrine-resize-handle', function (e) {
            e.preventDefault();
            e.stopPropagation();
            resizing = true;
            $block   = $(this).closest('.vitrine-canvas-block');
            startY   = e.pageY;
            startH   = $block.outerHeight();
            itemRef  = findItemById($block.data('id'));
            $('body').addClass('vitrine-resizing');
        });

        $(document).on('mousemove', function (e) {
            if (!resizing) return;
            var newH = Math.max(40, startH + (e.pageY - startY));
            $block.css('min-height', newH + 'px');
        });

        $(document).on('mouseup', function () {
            if (!resizing) return;
            resizing = false;
            $('body').removeClass('vitrine-resizing');
            if (itemRef && $block) itemRef.height = Math.round($block.outerHeight());
        });
    })();

    /* ──────────────────── Redimensionamento de largura (divisor) ──────────────────── */

    (function () {
        var resizing      = false;
        var startX        = 0;
        var leftItem      = null;
        var rightItem     = null;
        var leftStartPct  = 0;
        var rightStartPct = 0;
        var currentLeftPct  = 0;
        var currentRightPct = 0;
        var $leftBlock    = null;
        var $rightBlock   = null;
        var containerW    = 0;

        $(document).on('mousedown', '.vitrine-col-divider', function (e) {
            e.preventDefault();
            e.stopPropagation();
            resizing = true;
            startX   = e.pageX;

            var leftId  = $(this).data('left-id');
            var rightId = $(this).data('right-id');
            leftItem    = findItemById(leftId);
            rightItem   = findItemById(rightId);

            $leftBlock  = $('[data-id="' + leftId + '"]').first();
            $rightBlock = $('[data-id="' + rightId + '"]').first();
            containerW  = $leftBlock.parent().innerWidth();

            leftStartPct   = parseFloat(leftItem.width) || 50;
            rightStartPct  = parseFloat(rightItem.width) || 50;
            currentLeftPct  = leftStartPct;
            currentRightPct = rightStartPct;

            $('body').addClass('vitrine-resizing-width');
        });

        $(document).on('mousemove', function (e) {
            if (!resizing) return;

            var deltaPct = ((e.pageX - startX) / containerW) * 100;
            var totalPct = leftStartPct + rightStartPct;
            var newLeft  = leftStartPct + deltaPct;
            var newRight = rightStartPct - deltaPct;

            // Mínimo 5% por elemento
            if (newLeft < 5)  { newLeft = 5; newRight = totalPct - 5; }
            if (newRight < 5) { newRight = 5; newLeft = totalPct - 5; }

            currentLeftPct  = Math.round(newLeft * 100) / 100;
            currentRightPct = Math.round(newRight * 100) / 100;

            // Aplica visualmente
            $leftBlock.css({ flex: '0 1 ' + currentLeftPct + '%', maxWidth: currentLeftPct + '%' });
            $rightBlock.css({ flex: '0 1 ' + currentRightPct + '%', maxWidth: currentRightPct + '%' });

            // Atualiza badges
            $leftBlock.find('.vitrine-width-badge').text(Math.round(currentLeftPct) + '%');
            $rightBlock.find('.vitrine-width-badge').text(Math.round(currentRightPct) + '%');
        });

        $(document).on('mouseup', function () {
            if (!resizing) return;
            resizing = false;
            $('body').removeClass('vitrine-resizing-width');

            if (leftItem && rightItem) {
                leftItem.width  = currentLeftPct + '%';
                rightItem.width = currentRightPct + '%';
            }

            renderCanvas();
            if (selectedId) renderSettings();

            leftItem    = null;
            rightItem   = null;
            $leftBlock  = null;
            $rightBlock = null;
        });
    })();

    /* ──────────────────── Redimensionamento dos painéis laterais ──────────────────── */

    var SIDEBAR_WIDTHS_KEY = 'vitrine_editor_sidebar_widths';
    var sidebarPanelLimits = {
        left:  { min: 160, max: 420, defaultWidth: 210 },
        right: { min: 260, max: 640, defaultWidth: 300 }
    };
    var sidebarPanelWidths = {
        left: sidebarPanelLimits.left.defaultWidth,
        right: sidebarPanelLimits.right.defaultWidth
    };

    function clampSidebarWidth(panel, width) {
        var limits = sidebarPanelLimits[panel];
        return Math.max(limits.min, Math.min(limits.max, Math.round(width)));
    }

    function loadSidebarPanelWidths() {
        try {
            var raw = localStorage.getItem(SIDEBAR_WIDTHS_KEY);
            if (!raw) return;
            var parsed = JSON.parse(raw);
            if (parsed.left) {
                sidebarPanelWidths.left = clampSidebarWidth('left', parsed.left);
            }
            if (parsed.right) {
                sidebarPanelWidths.right = clampSidebarWidth('right', parsed.right);
            }
        } catch (err) { /* ignore */ }
    }

    function saveSidebarPanelWidths() {
        try {
            localStorage.setItem(SIDEBAR_WIDTHS_KEY, JSON.stringify(sidebarPanelWidths));
        } catch (err) { /* ignore */ }
    }

    function applySidebarPanelWidths() {
        var $root = $('#vitrine-editor');
        if (!$root.length) return;
        $root.css({
            '--vitrine-sidebar-left-w': sidebarPanelWidths.left + 'px',
            '--vitrine-sidebar-right-w': sidebarPanelWidths.right + 'px'
        });
    }

    var SIDEBAR_COLLAPSED_KEY = 'vitrine_editor_sidebar_collapsed';
    var sidebarCollapsed = {
        left: false,
        right: false
    };

    function loadSidebarCollapsed() {
        try {
            var raw = localStorage.getItem(SIDEBAR_COLLAPSED_KEY);
            if (!raw) return;
            var parsed = JSON.parse(raw);
            if (typeof parsed.left === 'boolean') sidebarCollapsed.left = parsed.left;
            if (typeof parsed.right === 'boolean') sidebarCollapsed.right = parsed.right;
        } catch (err) { /* ignore */ }
    }

    function saveSidebarCollapsed() {
        try {
            localStorage.setItem(SIDEBAR_COLLAPSED_KEY, JSON.stringify(sidebarCollapsed));
        } catch (err) { /* ignore */ }
    }

    function applySidebarCollapsed() {
        var $top = $('#vitrine-editor-top');
        if (!$top.length) return;
        $top.toggleClass('is-left-collapsed', !!sidebarCollapsed.left);
        $top.toggleClass('is-right-collapsed', !!sidebarCollapsed.right);
    }

    function setSidebarCollapsed(panel, collapsed) {
        if (panel !== 'left' && panel !== 'right') return;
        sidebarCollapsed[panel] = !!collapsed;
        saveSidebarCollapsed();
        applySidebarCollapsed();
    }

    loadSidebarCollapsed();
    applySidebarCollapsed();

    $(document).on('click', '.vitrine-sidebar-collapse', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setSidebarCollapsed($(this).data('panel'), true);
    });

    $(document).on('click', '.vitrine-sidebar-expand', function (e) {
        e.preventDefault();
        e.stopPropagation();
        setSidebarCollapsed($(this).data('panel'), false);
    });

    function filterElementList(query) {
        var q = String(query || '').trim().toLowerCase();
        var $items = $('#vitrine-element-list .vitrine-element-item');
        var visible = 0;

        $items.each(function () {
            var $item = $(this);
            var label = String($item.data('label') || $item.find('span:last').text() || '').toLowerCase();
            var type = String($item.data('type') || '').toLowerCase();
            var match = !q || label.indexOf(q) !== -1 || type.indexOf(q) !== -1;
            $item.toggleClass('is-filter-hidden', !match);
            if (match) visible++;
        });

        $('#vitrine-element-list .vitrine-element-group').each(function () {
            var $group = $(this);
            var groupVisible = $group.find('.vitrine-element-item:not(.is-filter-hidden)').length > 0;
            $group.toggleClass('is-filter-empty', !groupVisible);
        });

        var $empty = $('#vitrine-element-list-empty');
        if (!$empty.length) return;
        if (visible === 0 && q) {
            $empty.removeAttr('hidden');
        } else {
            $empty.attr('hidden', 'hidden');
        }
    }

    $(document).on('input', '#vitrine-element-search', function () {
        filterElementList(this.value);
    });

    (function initSidebarPanelResize() {
        var resizingPanel = null;
        var startX = 0;
        var startWidth = 0;
        var $activeHandle = null;

        loadSidebarPanelWidths();
        applySidebarPanelWidths();

        $(document).on('mousedown', '.vitrine-panel-resizer', function (e) {
            if (e.button !== 0) return;
            if (sidebarCollapsed.left && $(this).data('panel') === 'left') return;
            if (sidebarCollapsed.right && $(this).data('panel') === 'right') return;
            e.preventDefault();
            e.stopPropagation();

            resizingPanel = $(this).data('panel');
            if (resizingPanel !== 'left' && resizingPanel !== 'right') return;
            startX = e.pageX;
            startWidth = sidebarPanelWidths[resizingPanel];
            $activeHandle = $(this).addClass('is-dragging');
            $('body').addClass('vitrine-resizing-sidebar');
        });

        $(document).on('mousemove', function (e) {
            if (!resizingPanel) return;

            var delta = e.pageX - startX;
            var nextWidth = resizingPanel === 'left'
                ? startWidth + delta
                : startWidth - delta;

            sidebarPanelWidths[resizingPanel] = clampSidebarWidth(resizingPanel, nextWidth);
            applySidebarPanelWidths();
        });

        $(document).on('mouseup', function () {
            if (!resizingPanel) return;
            saveSidebarPanelWidths();
            resizingPanel = null;
            startX = 0;
            startWidth = 0;
            if ($activeHandle) {
                $activeHandle.removeClass('is-dragging');
                $activeHandle = null;
            }
            $('body').removeClass('vitrine-resizing-sidebar');
        });
    })();

    /* ──────────────────── Sidebar de Configurações ──────────────────── */

    function shouldSkipSettingsField(item, field) {
        if (item.type === 'container' && field.name === 'direction' && containerContainsAranha(item)) {
            return true;
        }

        if (isAranhaType(item.type)) {
            var aranhaMode = getAranhaLayoutMode(item);
            var circularOnly = {
                center_label: 1,
                radius: 1,
                card_border: 1
            };
            var gradeOnly = {
                center_image_fit: 1,
                columns: 1,
                card_border_radius: 1,
                card_border_style: 1,
                card_border_width: 1,
                card_border_color: 1,
                image_border_radius: 1,
                card_shadow: 1,
                center_bg_opacity: 1,
                image_shadow: 1,
                gap: 1,
                card_height: 1,
                card_text_align: 1,
                wrapper_padding: 1,
                wrapper_border_style: 1
            };
            if (aranhaMode === 'circular' && gradeOnly[field.name]) {
                return true;
            }
            if (aranhaMode === 'grade' && circularOnly[field.name]) {
                return true;
            }

            var cardStyle = item.settings.card_style || 'default';
            var isPresetCard = cardStyle !== 'default';
            if (field.name.indexOf('preset_') === 0 && !isPresetCard) {
                return true;
            }
            if (isPresetCard && (
                field.name === 'card_bg' ||
                field.name === 'card_border' ||
                field.name === 'card_border_style' ||
                field.name === 'card_border_width' ||
                field.name === 'card_border_color' ||
                field.name === 'card_height'
            )) {
                return true;
            }
            if (field.name === 'card_min_height' && !isPresetCard) {
                return true;
            }
        }

        if (item.type === 'video') {
            var videoQty = parseInt(item.settings.qty || '1', 10);
            if (!videoQty || videoQty < 1) {
                videoQty = 1;
            }
            var slotMatch = field.name.match(/^(source|url|width)_(\d+)$/);
            if (slotMatch && parseInt(slotMatch[2], 10) > videoQty) {
                return true;
            }
        }

        return false;
    }

    function extractYoutubeId(url) {
        if (!url) return '';
        var match = String(url).match(/(?:youtube\.com\/watch\?v=|youtube\.com\/embed\/|youtu\.be\/|youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/);
        if (match) return match[1];
        if (/^[a-zA-Z0-9_-]{11}$/.test(url)) return url;
        return '';
    }

    function isLikelyVideoFileUrl(url) {
        return /\.(mp4|webm|ogg|mov|m4v)(\?.*)?$/i.test(String(url || ''));
    }

    function buildVideoPreviewHtml(url, source) {
        if (!url) return '';
        if (source === 'local' || isLikelyVideoFileUrl(url)) {
            return '<video src="' + escapeAttr(url) + '" class="vitrine-image-preview vitrine-video-preview" style="max-height:120px;" muted></video>';
        }
        var ytId = extractYoutubeId(url);
        if (ytId) {
            return '<img src="https://img.youtube.com/vi/' + escapeAttr(ytId) + '/hqdefault.jpg" class="vitrine-image-preview" alt="" />';
        }
        return '';
    }

    function buildVideoFieldInput(item, field, val) {
        var slotMatch = field.name.match(/^url_(\d+)$/);
        var slotNum = slotMatch ? slotMatch[1] : '1';
        var source = item.settings['source_' + slotNum] || 'youtube';
        var placeholder = source === 'local'
            ? 'Cole a URL do arquivo ou selecione da biblioteca'
            : 'https://youtube.com/watch?v=... ou ID do vídeo';
        var previewHtml = buildVideoPreviewHtml(val, source);

        return '<div class="vitrine-video-field vitrine-image-field">' +
            previewHtml +
            '<input type="hidden" class="vitrine-field" data-field="' + escapeAttr(field.name) + '" value="' + escapeAttr(val) + '" />' +
            '<input type="text" class="vitrine-image-url-input widefat" placeholder="' + escapeAttr(placeholder) + '" value="' + escapeAttr(val) + '" />' +
            '<div class="vitrine-video-field-actions">' +
            '<button type="button" class="button vitrine-select-image">Selecionar da biblioteca</button>' +
            (val ? ' <button type="button" class="button vitrine-remove-image">Remover</button>' : '') +
            '</div>' +
            '<p class="vitrine-field-hint">' + (source === 'youtube'
                ? 'Cole o link do YouTube ou apenas o ID de 11 caracteres.'
                : 'Formatos comuns: MP4, WebM. Também aceita URL externa.') + '</p>' +
        '</div>';
    }

    function getFieldSettingsTab(itemType, field) {
        var name = field.name;

        if (field.type === 'textarea' || field.type === 'plaintextarea') {
            return 'content';
        }
        if (name === 'layout_mode') {
            return 'content';
        }
        if (/^(text|title|content|url|caption|box_title|center_label|name|alt)$/.test(name)) {
            return 'content';
        }
        if (name === 'center_image' || name === 'image' || name === 'bg_image') {
            return 'content';
        }
        if (field.type === 'image') {
            return 'content';
        }
        if (name === 'qty' || name === 'tag' || name === 'title_tag') {
            return 'content';
        }
        if (/^url_\d+$/.test(name) || /^source_\d+$/.test(name)) {
            return 'content';
        }

        if (name === 'custom_css') {
            return 'style';
        }
        if (field.type === 'color' || field.type === 'range') {
            return 'style';
        }
        if (/^preset_/.test(name)) {
            return 'style';
        }

        if (/(^|_)(color|bg|size|weight|radius|shadow|padding|gap|border|fit|height|width|opacity|align|font_size|style|min_height|max_width|aspect|autoplay|controls|show_|slides_|wrapper_|direction|columns|icon_size|link_color|link_size|link_underline|heading_size|image_position|full_width|bg_size|align_items)$/.test(name) ||
            name === 'align' || name === 'color' || name === 'radius' || name === 'padding' || name === 'gap' ||
            name === 'card_style' || name === 'card_min_height' || /^width_\d+$/.test(name)) {
            return 'style';
        }

        return 'content';
    }

    function buildSettingsFieldGroup(item, elDef, field) {
        if (field.type === 'heading') {
            return '<div class="vitrine-settings-section-title">' + escapeHtml(field.label) + '</div>';
        }

        var val = item.settings[field.name] !== undefined ? item.settings[field.name] : (elDef.defaults[field.name] || '');
        var inputHtml = '';

        switch (field.type) {
            case 'textarea':
                inputHtml = '<textarea id="vitrine-field-mce-' + escapeAttr(selectedId) + '-' + escapeAttr(field.name) + '" class="vitrine-field-mce" data-field="' + escapeAttr(field.name) + '" rows="4">' + safeTextareaHtml(val) + '</textarea>';
                if (item.type === 'imagelinks' && field.name === 'content') {
                    inputHtml += '<p class="vitrine-field-hint">Editor livre abaixo da imagem — negrito, itálico, links, listas etc.</p>';
                }
                break;
            case 'plaintextarea':
                inputHtml = '<textarea class="vitrine-field vitrine-field-plaintextarea" data-field="' + escapeAttr(field.name) + '" rows="5" spellcheck="false" placeholder="[meu_shortcode attr=&quot;valor&quot;]">' + escapeHtml(val) + '</textarea>';
                if (field.name === 'content' && item.type === 'shortcode') {
                    inputHtml += '<p class="vitrine-field-hint">Cole o shortcode do WordPress ou de outro plugin. Será executado na página publicada.</p>';
                }
                if (field.name === 'content' && item.type === 'html') {
                    inputHtml += '<p class="vitrine-field-hint">Cole HTML válido. Será renderizado na página publicada (scripts são removidos por segurança).</p>';
                }
                break;
            case 'number':
                inputHtml = '<input type="number" class="vitrine-field" data-field="' + escapeAttr(field.name) + '" value="' + escapeAttr(val) + '"';
                if (item.type === 'itemcarousel' && /^slides_per_view_/.test(field.name)) {
                    inputHtml += ' min="1" max="4" step="1"';
                }
                inputHtml += ' />';
                break;
            case 'color':
                if (item.type === 'text' && field.name === 'bg_color') {
                    var bgColorVal = val || '#ffffff';
                    inputHtml = '<div class="vitrine-color-row">' +
                        '<input type="color" class="vitrine-field vitrine-field-bg-color" data-field="bg_color" value="' + escapeAttr(bgColorVal) + '" />' +
                        (val ? '<button type="button" class="button button-small vitrine-field-bg-color-clear" title="Sem fundo">&#10005;</button>' : '') +
                    '</div>';
                } else {
                    inputHtml = '<input type="color" class="vitrine-field" data-field="' + escapeAttr(field.name) + '" value="' + escapeAttr(val) + '" />';
                }
                break;
            case 'range': {
                var rMin = field.min !== undefined ? field.min : 0;
                var rMax = field.max !== undefined ? field.max : 100;
                var rVal = val !== '' && val !== undefined ? val : rMin;
                inputHtml = '<div class="vitrine-range-row">' +
                    '<input type="range" class="vitrine-field vitrine-field-range" data-field="' + escapeAttr(field.name) + '" min="' + rMin + '" max="' + rMax + '" value="' + escapeAttr(rVal) + '" />' +
                    '<span class="vitrine-range-val">' + escapeHtml(String(rVal)) + '</span>' +
                '</div>';
                break;
            }
            case 'select':
                var opts = field.options || {};
                inputHtml = '<select class="vitrine-field" data-field="' + escapeAttr(field.name) + '">';
                for (var optKey in opts) {
                    if (opts.hasOwnProperty(optKey)) {
                        inputHtml += '<option value="' + escapeAttr(optKey) + '"' + (val === optKey ? ' selected' : '') + '>' + escapeHtml(opts[optKey]) + '</option>';
                    }
                }
                inputHtml += '</select>';
                break;
            case 'image':
                inputHtml =
                    '<div class="vitrine-image-field">' +
                        (val ? '<img src="' + escapeAttr(val) + '" class="vitrine-image-preview" />' : '') +
                        '<input type="hidden" class="vitrine-field" data-field="' + escapeAttr(field.name) + '" value="' + escapeAttr(val) + '" />' +
                        '<button type="button" class="button vitrine-select-image">Selecionar Imagem</button>' +
                        (val ? ' <button type="button" class="button vitrine-remove-image">Remover</button>' : '') +
                        '<input type="text" class="vitrine-image-url-input" placeholder="ou cole a URL da imagem" value="' + escapeAttr(val) + '" />' +
                    '</div>';
                break;
            case 'video':
                inputHtml = buildVideoFieldInput(item, field, val);
                break;
            default:
                inputHtml = '<input type="text" class="vitrine-field" data-field="' + escapeAttr(field.name) + '" value="' + escapeAttr(val) + '" />';
        }

        var extraClass = (field.type === 'textarea' || field.type === 'plaintextarea' || field.type === 'image' || field.type === 'video') ? ' vitrine-field-group--full' : '';
        var fieldHint = '';
        if (item.type === 'container' && field.name === 'name') {
            fieldHint = '<p class="vitrine-field-hint">Aparece na barra do bloco no canvas para identificar cada container.</p>';
        }
        if (isAranhaType(item.type) && field.name === 'card_min_height') {
            fieldHint = '<p class="vitrine-field-hint">Aplica-se aos modelos Escuro, Branco e Borda esquerda.</p>';
        }
        if (isAranhaType(item.type) && field.name.indexOf('preset_') === 0) {
            fieldHint = '<p class="vitrine-field-hint">Personaliza o modelo de card selecionado (substitui o CSS manual).</p>';
        }
        if (isAranhaType(item.type) && field.name === 'layout_mode') {
            fieldHint = '<p class="vitrine-field-hint">Circular: cards em órbita. Grade: cards em moldura ao redor da imagem.</p>';
        }
        if (item.type === 'imagelinks' && field.name === 'image_height') {
            fieldHint = '<p class="vitrine-field-hint">Use 0 para ocultar a área da imagem (só legenda/conteúdo).</p>';
        }
        if (item.type === 'itemcarousel' && /^slides_per_view_/.test(field.name)) {
            fieldHint = '<p class="vitrine-field-hint">Quantidade de slides visíveis ao mesmo tempo (1 a 4).</p>';
        }

        return '<div class="vitrine-field-group' + extraClass + '">' +
            '<label>' + escapeHtml(field.label) + '</label>' +
            inputHtml +
            fieldHint +
        '</div>';
    }

    function buildSettingsWidthField(item) {
        var widthVal = item.width || '';
        return '<div class="vitrine-field-group vitrine-field-group--width">' +
            '<label>Largura</label>' +
            '<div class="vitrine-width-input-row">' +
                '<input type="text" class="vitrine-field-width" value="' + escapeAttr(widthVal) + '" placeholder="auto" />' +
                '<button type="button" class="button button-small vitrine-btn-distribute" title="Distribuir igualmente">&#8862;</button>' +
            '</div>' +
            '<p class="vitrine-field-hint">Arraste o divisor entre elementos para ajustar visualmente.</p>' +
        '</div>';
    }

    function flushActiveSettingsPanel() {
        if (_skipMCESync || !settingsPanelItemId) return;
        var restoreSelectedId = selectedId;
        selectedId = settingsPanelItemId;
        syncAllAranhaMCE();
        syncAllItemgridMCE();
        syncAllItemcarouselMCE();
        syncAllFieldMCE();
        selectedId = restoreSelectedId;
    }

    function renderSettings() {
        flushActiveSettingsPanel();
        destroyAllMCE();
        var $panel = $('#vitrine-settings-panel');
        $panel.empty().removeClass('has-tabs');

        if (!selectedId) {
            settingsPanelItemId = null;
            $('#vitrine-settings-el-icon').attr('class', 'dashicons dashicons-admin-settings');
            $('#vitrine-settings-el-label').text(t('ui.settings', 'Settings'));
            $('#vitrine-settings-sidebar').removeClass('has-selection');
            $panel.html(
                '<div class="vitrine-settings-empty-state">' +
                    '<span class="dashicons dashicons-edit-large"></span>' +
                    '<p>' + escapeHtml(t('ui.settings_empty', 'Click an element on the canvas to edit its settings.')) + '</p>' +
                '</div>'
            );
            return;
        }

        var item = findItemById(selectedId);
        if (!item) return;

        if (item.type !== 'itemgrid') {
            itemgridExpandedIdx = null;
        }
        if (item.type !== 'itemcarousel') {
            itemcarouselExpandedIdx = null;
        }
        if (!isAranhaType(item.type)) {
            aranhaExpandedIdx = null;
        }
        if (item.type !== 'toggle') {
            toggleExpandedIdx = null;
        }

        var elDef = elements[item.type];
        if (!elDef) return;

        // Atualiza cabeçalho da sidebar com ícone e nome do elemento
        $('#vitrine-settings-el-icon').attr('class', 'dashicons ' + (elDef.icon || 'dashicons-admin-settings'));
        var settingsHeaderLabel = item.type === 'container'
            ? getContainerDisplayName($.extend({}, elDef.defaults, item.settings))
            : (elDef.label || t('ui.settings', 'Settings'));
        $('#vitrine-settings-el-label').text(settingsHeaderLabel);
        $('#vitrine-settings-sidebar').addClass('has-selection');

        var fields = elDef.fields;
        var contentTabActive = settingsPanelTab === 'style' ? '' : ' is-active';
        var styleTabActive = settingsPanelTab === 'style' ? ' is-active' : '';

        $panel.addClass('has-tabs');
        $panel.append(
            '<div class="vitrine-settings-main-tabs">' +
                '<button type="button" class="vitrine-settings-main-tab' + contentTabActive + '" data-settings-tab="content" title="' + escapeAttr(t('ui.content_tab', 'Content')) + '">' +
                    '<span class="dashicons dashicons-edit"></span>' +
                    '<span class="vitrine-settings-main-tab-label">' + escapeHtml(t('ui.content_tab', 'Content')) + '</span>' +
                '</button>' +
                '<button type="button" class="vitrine-settings-main-tab' + styleTabActive + '" data-settings-tab="style" title="' + escapeAttr(t('ui.style_tab', 'Styles')) + '">' +
                    '<span class="dashicons dashicons-admin-appearance"></span>' +
                    '<span class="vitrine-settings-main-tab-label">' + escapeHtml(t('ui.style_tab', 'Styles')) + '</span>' +
                '</button>' +
            '</div>' +
            '<div class="vitrine-settings-tab-pane' + contentTabActive + '" data-settings-pane="content"></div>' +
            '<div class="vitrine-settings-tab-pane' + styleTabActive + '" data-settings-pane="style"></div>'
        );

        var $contentPane = $panel.find('[data-settings-pane="content"]');
        var $stylePane = $panel.find('[data-settings-pane="style"]');

        // Verifica se está dentro de um container row
        var parentContainer = findParentContainer(selectedId);
        var isInsideRowContainer = parentContainer &&
            parentContainer.settings && parentContainer.settings.direction === 'row';

        if (isInsideRowContainer) {
            $stylePane.append(buildSettingsWidthField(item));
        }

        fields.forEach(function (field) {
            if (shouldSkipSettingsField(item, field)) {
                return;
            }

            var tab = getFieldSettingsTab(item.type, field);
            var $targetPane = tab === 'style' ? $stylePane : $contentPane;
            $targetPane.append(buildSettingsFieldGroup(item, elDef, field));
        });

        // ── Aranha: itens (circular ou grade) ──
        if (isAranhaType(item.type)) {
            if (getAranhaLayoutMode(item) === 'grade') {
                renderAranha3Repeater($contentPane, item);
            } else {
                renderAranha2Repeater($contentPane, item);
            }
        }

        // ── Grade de Itens: cards com abas ──
        if (item.type === 'itemgrid') {
            renderItemgridRepeater($contentPane, item);
        }

        // ── Carrossel de Itens: slides com abas ──
        if (item.type === 'itemcarousel') {
            renderItemcarouselRepeater($contentPane, item);
        }

        // ── Toggle: seção de itens repetíveis ──
        if (item.type === 'toggle') {
            renderToggleRepeater($contentPane, item);
        }

        // ── CSS Personalizado (todos os elementos) ──
        var customCss = item.settings.custom_css || '';
        $stylePane.append(
            '<div class="vitrine-field-group vitrine-field-group--full vitrine-field-group--custom-css">' +
                '<label>CSS Personalizado</label>' +
                '<textarea class="vitrine-field" data-field="custom_css" rows="3" placeholder="ex: background: #f00; border-radius: 8px;">' + escapeHtml(customCss) + '</textarea>' +
                '<p class="vitrine-field-hint">Estilos aplicados diretamente neste elemento.</p>' +
            '</div>'
        );

        if (!$contentPane.children().length) {
            $contentPane.append('<p class="vitrine-settings-tab-empty">Nenhuma opção de conteúdo para este elemento.</p>');
        }
        if (!$stylePane.children().length) {
            $stylePane.append('<p class="vitrine-settings-tab-empty">Nenhuma opção de estilo para este elemento.</p>');
        }

        // Inicializa TinyMCE e drag-sort nos itens das aranhas
        if (isAranhaType(item.type)) {
            setTimeout(initAranhaMCE, 50);
            setTimeout(initAranhaSort, 80);
        }

        if (item.type === 'itemgrid') {
            setTimeout(initItemgridMCE, 50);
            setTimeout(initItemgridSort, 80);
        }

        if (item.type === 'itemcarousel') {
            setTimeout(initItemcarouselMCE, 50);
            setTimeout(initItemcarouselSort, 80);
        }

        // Inicializa TinyMCE nos campos textarea genéricos
        setTimeout(initFieldMCE, 50);

        // Inicializa TinyMCE nos campos do toggle
        if (item.type === 'toggle') {
            setTimeout(initToggleMCE, 50);
            setTimeout(initToggleSort, 80);
        }

        settingsPanelItemId = selectedId;
    }

    /**
     * Renderiza a seção de itens repetíveis do toggle.
     */
    function buildRepeaterSectionToolsHtml() {
        return '<div class="vitrine-repeater-section-tools">' +
            '<button type="button" class="button-link vitrine-repeater-collapse-all">Recolher todos</button>' +
            '</div>';
    }

    function clampRepeaterExpandedIdx(expandedIdx, length) {
        if (expandedIdx === null || expandedIdx === undefined) return null;
        if (!length) return null;
        if (expandedIdx >= length) return length - 1;
        if (expandedIdx < 0) return null;
        return expandedIdx;
    }

    function renderToggleRepeater($panel, item) {
        if (!item.settings.items || !Array.isArray(item.settings.items)) {
            item.settings.items = [];
        }
        var items = item.settings.items;
        toggleExpandedIdx = clampRepeaterExpandedIdx(toggleExpandedIdx, items.length);

        var html = '<div class="vitrine-toggle-section">';
        html += '<hr style="border:none;border-top:1px solid #dcdcde;margin:14px 0 10px;" />';
        html += '<div class="vitrine-repeater-section-head">';
        html += '<h4 class="vitrine-repeater-section-title">Itens do Toggle (' + items.length + ')</h4>';
        html += buildRepeaterSectionToolsHtml();
        html += '</div>';
        html += '<p class="vitrine-field-hint vitrine-repeater-section-hint">Clique no cabeçalho para expandir ou recolher. Arraste pelo ícone <span class="dashicons dashicons-move" style="font-size:14px;width:14px;height:14px;vertical-align:middle;"></span> para reordenar.</p>';
        html += '<div class="vitrine-toggle-items-list">';

        items.forEach(function (ti, idx) {
            var isExpanded = toggleExpandedIdx === idx;
            var preview = stripHtmlPreview(ti.title) || ('Item ' + (idx + 1));
            html += '<div class="vitrine-repeater-item vitrine-toggle-editor-item vitrine-repeater-collapse-item' + (isExpanded ? ' is-expanded' : '') + '" data-toggle-idx="' + idx + '">';
            html += '<div class="vitrine-repeater-item-header vitrine-repeater-collapse-header">';
            html += '<span class="vitrine-toggle-drag dashicons dashicons-move" title="Arrastar para reordenar"></span>';
            html += '<span class="vitrine-repeater-item-num">' + (idx + 1) + '</span>';
            html += '<span class="vitrine-repeater-item-preview">' + escapeHtml(preview) + '</span>';
            html += '<span class="vitrine-repeater-chevron dashicons dashicons-arrow-down-alt2"></span>';
            html += '<button type="button" class="button button-small vitrine-toggle-remove-item" title="Remover">&times;</button>';
            html += '</div>';
            html += '<div class="vitrine-repeater-collapse-body">';
            html += '<div class="vitrine-field-group"><label>Título</label>';
            html += '<input type="text" class="vitrine-toggle-field" data-toggle-prop="title" value="' + escapeAttr(ti.title || '') + '" /></div>';
            html += '<div class="vitrine-field-group"><label>Conteúdo</label>';
            html += '<textarea id="vitrine-toggle-mce-' + idx + '" class="vitrine-toggle-mce" data-toggle-prop="content" rows="5">' + escapeHtml(ti.content || '') + '</textarea>';
            html += '</div>';
            html += '</div>';
            html += '</div>';
        });

        html += '</div>';
        html += '<button type="button" class="button vitrine-toggle-add-item">+ Adicionar Toggle</button>';
        html += '</div>';

        $panel.append(html);
    }

    function updateTogglePreview() {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'toggle') return;
        var elDef = elements[item.type];
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        if ($block.length && elDef) {
            var s = $.extend(true, {}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, s);
        }
    }

    function initToggleSort() {
        toggleSortInstances.forEach(function (inst) {
            if (inst && inst.destroy) inst.destroy();
        });
        toggleSortInstances = [];

        var $list = $('#vitrine-settings-panel .vitrine-toggle-items-list');
        if (!$list.length || typeof Sortable === 'undefined') return;

        var instance = Sortable.create($list[0], {
            handle: '.vitrine-toggle-drag',
            draggable: '.vitrine-toggle-editor-item',
            animation: 150,
            ghostClass: 'vitrine-toggle-ghost',
            onEnd: function (evt) {
                if (evt.oldIndex === evt.newIndex) return;
                var current = findItemById(selectedId);
                if (!current || !current.settings.items) return;

                var moved = current.settings.items.splice(evt.oldIndex, 1)[0];
                current.settings.items.splice(evt.newIndex, 0, moved);

                if (toggleExpandedIdx === evt.oldIndex) {
                    toggleExpandedIdx = evt.newIndex;
                } else if (toggleExpandedIdx !== null) {
                    if (evt.oldIndex < toggleExpandedIdx && evt.newIndex >= toggleExpandedIdx) {
                        toggleExpandedIdx--;
                    } else if (evt.oldIndex > toggleExpandedIdx && evt.newIndex <= toggleExpandedIdx) {
                        toggleExpandedIdx++;
                    }
                }

                renderSettings();
                updateTogglePreview();
            }
        });
        toggleSortInstances.push(instance);
    }

    /* ── TinyMCE helpers ── */

    var _skipMCESync = false;
    var aranhaSortInstances = [];

    function destroyAranhaSort() {
        aranhaSortInstances.forEach(function (s) {
            try {
                if (s && s.destroy) s.destroy();
            } catch (e) { /* ignore */ }
        });
        aranhaSortInstances = [];
    }

    function syncAranha3ItemsFromDOM(item) {
        var zones = ['left', 'top', 'right', 'bottom', 'auto'];
        var oldItems = item.settings.items || [];
        var newItems = [];

        zones.forEach(function (zone) {
            $('#vitrine-settings-panel .vitrine-aranha-items-list[data-aranha-zone="' + zone + '"]').each(function () {
                $(this).children('.vitrine-aranha-item').each(function () {
                    var idx = parseInt($(this).data('aranha-idx'), 10);
                    if (isNaN(idx) || !oldItems[idx]) return;
                    var it = $.extend(true, {}, oldItems[idx]);
                    it.position = zone === 'auto' ? 'auto' : zone;
                    newItems.push(it);
                });
            });
        });

        item.settings.items = newItems;
    }

    function handleAranhaSortEnd(evt, item) {
        var fromKey = $(evt.from).data('aranha-key');
        var toKey = $(evt.to).data('aranha-key');
        var oldIndex = evt.oldIndex;
        var newIndex = evt.newIndex;

        if (getAranhaLayoutMode(item) === 'grade') {
            syncAranha3ItemsFromDOM(item);
            aranhaExpandedIdx = null;
            renderSettings();
            renderCanvas();
            return;
        }

        if (!fromKey || !toKey) return;

        if (isAranhaType(item.type)) {
            var arr = item.settings[fromKey];
            if (!arr) return;
            var movedA2 = arr.splice(oldIndex, 1)[0];
            if (!movedA2) return;
            arr.splice(newIndex, 0, movedA2);
        }

        renderSettings();
        renderCanvas();
    }

    function destroyAllMCE() {
        destroyAranhaSort();
        itemgridSortInstances.forEach(function (inst) {
            if (inst && inst.destroy) inst.destroy();
        });
        itemgridSortInstances = [];
        itemcarouselSortInstances.forEach(function (inst) {
            if (inst && inst.destroy) inst.destroy();
        });
        itemcarouselSortInstances = [];
        toggleSortInstances.forEach(function (inst) {
            if (inst && inst.destroy) inst.destroy();
        });
        toggleSortInstances = [];
        _skipMCESync = true;
        $('.vitrine-aranha-mce, .vitrine-field-mce, .vitrine-toggle-mce, .vitrine-ig-mce, .vitrine-ic-mce').each(function () {
            var id = $(this).attr('id');
            if (id && typeof wp !== 'undefined' && wp.editor) {
                try { wp.editor.remove(id); } catch (e) {}
            }
        });
        _skipMCESync = false;
    }

    function initAranhaSort() {
        destroyAranhaSort();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || !isAranhaType(item.type)) return;

        var groupName = 'aranha-items-' + selectedId;

        $('#vitrine-settings-panel .vitrine-aranha-items-list').each(function () {
            var instance = Sortable.create(this, {
                group: { name: groupName, pull: true, put: true },
                handle: '.vitrine-aranha-drag',
                draggable: '.vitrine-aranha-item',
                animation: 150,
                ghostClass: 'vitrine-aranha-ghost',
                emptyInsertThreshold: 12,
                onEnd: function (evt) {
                    if (evt.oldIndex === evt.newIndex && evt.from === evt.to) return;
                    var current = findItemById(selectedId);
                    if (!current) return;
                    handleAranhaSortEnd(evt, current);
                }
            });
            aranhaSortInstances.push(instance);
        });
    }

    function aranhaMceId(key, idx, prop) {
        return 'vitrine-aranha-mce-' + String(key).replace(/_/g, '-') + '-' + idx + '-' + prop;
    }

    function stripHtmlPreview(html) {
        var div = document.createElement('div');
        div.innerHTML = html || '';
        return (div.textContent || div.innerText || '').trim();
    }

    function defaultAranhaItem(typeOrMode) {
        var mode = (typeOrMode === 'grade' || typeOrMode === 'aranha3') ? 'grade' : 'circular';
        if (mode === 'grade') {
            return { title: '', text: '', icon: '', link: '', position: 'auto' };
        }
        return { title: '', text: '', icon: '', link: '' };
    }

    function syncAllAranhaMCE() {
        if (_skipMCESync) return;
        $('.vitrine-aranha-mce').each(function () {
            var id = $(this).attr('id');
            if (id) {
                syncAranhaMCE(id, true);
            }
        });
    }

    function syncAllFieldMCE() {
        if (_skipMCESync) return;
        $('.vitrine-field-mce').each(function () {
            var id = $(this).attr('id');
            if (id) {
                syncFieldMCE(id);
            }
        });
    }

    function initAranhaMCE() {
        if (typeof wp === 'undefined' || !wp.editor) return;
        $('.vitrine-aranha-mce').each(function () {
            var id = $(this).attr('id');
            if (!id) return;
            if (typeof tinymce !== 'undefined' && tinymce.get(id)) return;

            wp.editor.initialize(id, {
                tinymce: {
                    toolbar1: 'bold,italic,underline,link,bullist,numlist,alignleft,aligncenter,alignright',
                    menubar: false,
                    branding: false,
                    resize: true,
                    height: 100,
                    setup: function (editor) {
                        editor.on('change keyup', function () {
                            editor.save();
                            syncAranhaMCE(editor.id);
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        });
    }

    function syncAranhaMCE(editorId, silent) {
        if (_skipMCESync) return;
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var $ta      = $('#' + editorId);
        if (!$ta.length) return;

        var $itemEl  = $ta.closest('.vitrine-aranha-item');
        var $section = $ta.closest('.vitrine-aranha-section');
        var key      = $section.data('aranha-key');
        var idx      = $itemEl.data('aranha-idx');
        var prop     = $ta.data('aranha-prop') || 'text';

        if (key === undefined || idx === undefined) return;

        if (!item.settings[key]) item.settings[key] = [];
        if (!item.settings[key][idx]) {
            item.settings[key][idx] = defaultAranhaItem(item.type);
        }

        var content = (typeof wp !== 'undefined' && wp.editor && wp.editor.getContent)
            ? wp.editor.getContent(editorId)
            : $ta.val();

        item.settings[key][idx][prop] = content;

        if (!silent && (prop === 'text' || prop === 'title')) {
            var preview = stripHtmlPreview(content);
            var fallback = prop === 'title' ? 'Card sem título' : 'Item sem rótulo';
            $itemEl.find('.vitrine-a2-item-preview-text').text(preview || fallback);
        }
    }

    function initFieldMCE() {
        if (typeof wp === 'undefined' || !wp.editor) return;
        $('.vitrine-field-mce').each(function () {
            var id = $(this).attr('id');
            if (!id) return;
            if (typeof tinymce !== 'undefined' && tinymce.get(id)) return;

            wp.editor.initialize(id, {
                tinymce: {
                    toolbar1: 'bold,italic,underline,link,bullist,numlist,alignleft,aligncenter,alignright',
                    menubar: false,
                    branding: false,
                    resize: true,
                    height: 160,
                    setup: function (editor) {
                        editor.on('change keyup', function () {
                            editor.save();
                            syncFieldMCE(editor.id);
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        });
    }

    function syncFieldMCE(editorId) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var $ta   = $('#' + editorId);
        var field = $ta.data('field');
        if (!field) return;

        var content = (typeof wp !== 'undefined' && wp.editor)
            ? wp.editor.getContent(editorId)
            : $ta.val();

        item.settings[field] = content;

        // Atualiza preview
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        var elDef  = elements[item.type];
        if ($block.length && elDef) {
            var s = $.extend({}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, s);
        }
    }

    function initToggleMCE() {
        $('.vitrine-toggle-mce').each(function () {
            var id = $(this).attr('id');
            if (!id) return;
            wp.editor.initialize(id, {
                tinymce: {
                    toolbar1: 'bold,italic,underline,link,bullist,numlist',
                    menubar: false,
                    branding: false,
                    resize: false,
                    height: 120,
                    setup: function (editor) {
                        editor.on('change keyup', function () {
                            editor.save();
                            syncToggleMCE(editor.id);
                        });
                    }
                },
                quicktags: false,
                mediaButtons: false
            });
        });
    }

    function syncToggleMCE(editorId) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var $ta   = $('#' + editorId);
        var $tItem = $ta.closest('.vitrine-toggle-editor-item');
        var idx   = $tItem.data('toggle-idx');

        if (!item.settings.items) item.settings.items = [];
        if (!item.settings.items[idx]) item.settings.items[idx] = { title: '', content: '' };

        var content = (typeof wp !== 'undefined' && wp.editor)
            ? wp.editor.getContent(editorId)
            : $ta.val();

        item.settings.items[idx].content = content;

        // Atualiza preview
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        var elDef  = elements[item.type];
        if ($block.length && elDef) {
            var s = $.extend(true, {}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, s);
        }
    }

    /**
     * Gera o HTML do picker de ícones (reutilizável).
     */
    function buildIconPickerHtml() {
        var h = '<div class="vitrine-icon-picker" style="display:none;">';
        h += '<div class="vitrine-icon-picker-tabs">';
        h += '<button type="button" class="vitrine-icon-tab is-active" data-tab="dashicons">Dashicons</button>';
        h += '<button type="button" class="vitrine-icon-tab" data-tab="fa">Font Awesome</button>';
        h += '<button type="button" class="vitrine-icon-tab" data-tab="upload">Imagem</button>';
        h += '</div>';
        h += '<div class="vitrine-icon-tab-panel" data-panel="dashicons">';
        h += '<input type="text" class="vitrine-icon-search" placeholder="Filtrar dashicons..." />';
        h += '<div class="vitrine-icons-grid"></div>';
        h += '</div>';
        h += '<div class="vitrine-icon-tab-panel" data-panel="fa" style="display:none;">';
        h += '<input type="text" class="vitrine-icon-search" placeholder="Buscar Font Awesome (ex: leaf, user, github)..." />';
        h += '<div class="vitrine-icons-grid vitrine-icons-grid--fa"></div>';
        h += '</div>';
        h += '<div class="vitrine-icon-tab-panel" data-panel="upload" style="display:none;">';
        h += '<p class="vitrine-field-hint" style="margin:0 0 8px;">Use imagem apenas se não houver ícone adequado.</p>';
        h += '<button type="button" class="button vitrine-aranha-select-icon">Selecionar da Biblioteca</button>';
        h += '</div>';
        h += '</div>';
        return h;
    }

    /**
     * Painel de itens da Aranha Circular (aranha2) — UX redesenhado.
     * Layout dois colunas: ícone clicável à esquerda, rótulo + link à direita.
     */
    function renderAranha2Repeater($panel, item) {
        if (!item.settings.items || !Array.isArray(item.settings.items)) {
            item.settings.items = [];
        }
        var items = item.settings.items;

        items.forEach(function (ai) {
            if (!stripHtmlPreview(ai.title) && stripHtmlPreview(ai.text)) {
                ai.title = ai.text;
                ai.text = '';
            }
        });

        // Cabeçalho da seção
        var html = '<div class="vitrine-aranha-section vitrine-a2-section" data-aranha-key="items">';
        html += '<hr style="border:none;border-top:1px solid #dcdcde;margin:14px 0 12px;" />';
        html += '<div class="vitrine-a2-section-head">';
        html += '<div>';
        html += '<h4 class="vitrine-aranha-section-title">Itens Orbitais</h4>';
        html += '<p class="vitrine-a2-section-hint">Clique no item para expandir. Arraste para reordenar no círculo.</p>';
        html += buildRepeaterSectionToolsHtml();
        html += '</div>';
        html += '<span class="vitrine-a2-count-badge">' + items.length + '</span>';
        html += '</div>';

        aranhaExpandedIdx = clampRepeaterExpandedIdx(aranhaExpandedIdx, items.length);

        // Lista de itens (drag-sortable)
        html += '<div class="vitrine-aranha-items-list vitrine-a2-items-list" data-aranha-key="items">';

        items.forEach(function (ai, idx) {
            html += buildAranhaCardItemHtml(ai, idx, 'circular', aranhaExpandedIdx === idx);
        });

        html += '</div>'; // items-list

        // Botão adicionar
        html += '<button type="button" class="button vitrine-aranha-add-item vitrine-a2-add-btn">'
            + '<span class="dashicons dashicons-plus-alt2"></span> Adicionar Item'
            + '</button>';

        html += '</div>'; // section

        $panel.append(html);
    }

    function buildAranhaCardItemHtml(ai, idx, itemType, isExpanded) {
        var isGrade = itemType === 'aranha3' || itemType === 'grade';
        var itemClass = isGrade ? 'vitrine-a3-item' : 'vitrine-a2-item';
        var hasIcon = !!ai.icon;
        var hasLink = !!ai.link;
        var previewLabel = stripHtmlPreview(ai.title) || (isGrade ? ('Card ' + (idx + 1)) : ('Item ' + (idx + 1)));
        var expandedClass = isExpanded ? ' is-expanded' : '';
        var html = '<div class="vitrine-aranha-item ' + itemClass + ' vitrine-repeater-collapse-item' + expandedClass + '" data-aranha-idx="' + idx + '">';

        html += '<div class="vitrine-a2-item-header vitrine-repeater-collapse-header">';
        html += '<span class="vitrine-aranha-drag dashicons dashicons-move" title="Arrastar para reordenar"></span>';
        html += '<span class="vitrine-aranha-item-num">' + (idx + 1) + '</span>';
        html += '<span class="vitrine-a2-item-preview-text vitrine-repeater-item-preview">' + escapeHtml(previewLabel) + '</span>';
        if (hasIcon) { html += '<span class="vitrine-a2-badge vitrine-a2-badge--icon" title="Tem ícone"><span class="dashicons dashicons-format-image"></span></span>'; }
        if (hasLink) { html += '<span class="vitrine-a2-badge vitrine-a2-badge--link" title="Tem link"><span class="dashicons dashicons-admin-links"></span></span>'; }
        html += '<span class="vitrine-repeater-chevron dashicons dashicons-arrow-down-alt2"></span>';
        html += '<button type="button" class="button button-small vitrine-aranha-remove-item" title="Remover">&times;</button>';
        html += '</div>';

        html += '<div class="vitrine-a2-item-body vitrine-a3-item-body vitrine-repeater-collapse-body">';

        html += '<div class="vitrine-aranha-icon-field vitrine-a3-icon-row">';
        html += '<label class="vitrine-a3-icon-row-label">Ícone / Imagem do card</label>';
        html += '<div class="vitrine-a3-icon-row-inner">';
        var iconPreview = hasIcon
            ? renderIconPreviewHtml(ai.icon)
            : '<span class="dashicons dashicons-format-image" style="font-size:28px;width:28px;height:28px;color:#c3c4c7;"></span>';

        html += '<div class="vitrine-a2-icon-display vitrine-aranha-open-picker" title="Clique para escolher ícone ou imagem">';
        html += '<div class="vitrine-icon-current">' + iconPreview + '</div>';
        html += '<span class="vitrine-a2-icon-hint">' + (hasIcon ? 'Trocar' : 'Adicionar') + '</span>';
        html += '</div>';

        html += '<input type="hidden" class="vitrine-aranha-field" data-aranha-prop="icon" value="' + escapeAttr(ai.icon || '') + '" />';

        html += '<div class="vitrine-icon-actions vitrine-a2-icon-actions">';
        if (hasIcon) {
            html += '<button type="button" class="button vitrine-aranha-remove-icon">Remover</button>';
        }
        html += '</div>';

        html += buildIconPickerHtml();
        html += '</div>';
        html += '</div>';

        html += '<div class="vitrine-a3-fields-stack">';

        if (isGrade) {
            html += '<div class="vitrine-field-group vitrine-field-group--full">';
            html += '<label>Posição no grid</label>';
            html += '<select class="vitrine-aranha-field" data-aranha-prop="position">';
            var posVal = ai.position || 'auto';
            html += '<option value="auto"' + (posVal === 'auto' ? ' selected' : '') + '>Automático</option>';
            html += '<option value="top"' + (posVal === 'top' ? ' selected' : '') + '>Topo</option>';
            html += '<option value="bottom"' + (posVal === 'bottom' ? ' selected' : '') + '>Base</option>';
            html += '<option value="left"' + (posVal === 'left' ? ' selected' : '') + '>Esquerda</option>';
            html += '<option value="right"' + (posVal === 'right' ? ' selected' : '') + '>Direita</option>';
            html += '</select>';
            html += '<p class="vitrine-field-hint">Ou arraste o card para outra zona abaixo.</p>';
            html += '</div>';
        }

        html += '<div class="vitrine-field-group vitrine-field-group--full">';
        html += '<label>Título</label>';
        html += '<textarea id="' + aranhaMceId('items', idx, 'title') + '" class="vitrine-aranha-mce" data-aranha-prop="title" rows="3">' + (ai.title || '') + '</textarea>';
        html += '</div>';

        html += '<div class="vitrine-field-group vitrine-field-group--full">';
        html += '<label>Descrição</label>';
        html += '<textarea id="' + aranhaMceId('items', idx, 'text') + '" class="vitrine-aranha-mce" data-aranha-prop="text" rows="5">' + (ai.text || '') + '</textarea>';
        html += '</div>';

        html += '<div class="vitrine-field-group vitrine-field-group--full">';
        html += '<label><span class="dashicons dashicons-admin-links" style="font-size:13px;vertical-align:middle;margin-right:3px;color:#0073aa;"></span>Link <span style="font-weight:400;color:#8c8f94;">(opcional)</span></label>';
        html += '<input type="text" class="vitrine-aranha-field" data-aranha-prop="link"'
            + ' value="' + escapeAttr(ai.link || '') + '"'
            + ' placeholder="https://..." />';
        html += '<p class="vitrine-field-hint">Torna o card clicável.</p>';
        html += '</div>';

        html += '</div>';
        html += '</div>';
        html += '</div>';

        return html;
    }

    function buildAranha3ItemHtml(ai, idx) {
        return buildAranhaCardItemHtml(ai, idx, 'grade', aranhaExpandedIdx === idx);
    }

    /* ──────────────────────────────────────────────────────
       Aranha Grade (aranha3) — repeater por zona do grid
    ────────────────────────────────────────────────────── */
    function renderAranha3Repeater($panel, item) {
        if (!item.settings.items || !Array.isArray(item.settings.items)) {
            item.settings.items = [];
        }
        var items = item.settings.items;
        var zones = [
            { id: 'left',   label: 'Esquerda' },
            { id: 'top',    label: 'Topo' },
            { id: 'right',  label: 'Direita' },
            { id: 'bottom', label: 'Base' },
            { id: 'auto',   label: 'Automático' }
        ];

        var html = '<div class="vitrine-aranha-section vitrine-a3-section" data-aranha-key="items">';
        html += '<hr style="border:none;border-top:1px solid #dcdcde;margin:14px 0 10px;" />';
        html += '<div class="vitrine-repeater-section-head">';
        html += '<h4 class="vitrine-aranha-section-title" style="margin:0 0 4px;">Cards do Grid</h4>';
        html += buildRepeaterSectionToolsHtml();
        html += '</div>';
        html += '<p class="vitrine-field-hint vitrine-repeater-section-hint" style="margin:0 0 12px;">Clique no card para expandir. Arraste entre zonas. Total: ' + items.length + '.</p>';

        aranhaExpandedIdx = clampRepeaterExpandedIdx(aranhaExpandedIdx, items.length);

        zones.forEach(function (zone) {
            var zoneItems = [];
            items.forEach(function (ai, idx) {
                var pos = ai.position || 'auto';
                if (pos === zone.id) {
                    zoneItems.push({ ai: ai, idx: idx });
                }
            });

            html += '<div class="vitrine-aranha-zone-section vitrine-a3-zone" data-aranha-key="items" data-aranha-zone="' + zone.id + '">';
            html += '<h5 class="vitrine-aranha-zone-title">' + escapeHtml(zone.label) + ' <span class="vitrine-aranha-zone-count">(' + zoneItems.length + ')</span></h5>';
            html += '<div class="vitrine-aranha-items-list vitrine-a3-items-list" data-aranha-key="items" data-aranha-zone="' + zone.id + '">';

            zoneItems.forEach(function (entry) {
                html += buildAranha3ItemHtml(entry.ai, entry.idx);
            });

            html += '</div>';
            html += '<button type="button" class="button button-small vitrine-aranha-add-item vitrine-a3-add-btn">+ Adicionar em ' + escapeHtml(zone.label) + '</button>';
            html += '</div>';
        });

        html += '</div>';

        $panel.append(html);
    }

    /* ──────────────────────────────────────────────────────
       Grade de Itens — repeater com abas (Conteúdo / Tipografia / Link)
    ────────────────────────────────────────────────────── */

    var IG_WEIGHT_OPTS = {
        '': 'Padrão do bloco',
        '300': 'Leve (300)',
        '400': 'Normal (400)',
        '500': 'Médio (500)',
        '600': 'Semi-negrito (600)',
        '700': 'Negrito (700)',
        '800': 'Extra-negrito (800)'
    };

    function defaultItemgridItem() {
        return {
            image: '',
            title: '',
            description: '',
            link: '',
            link_new_tab: '0',
            title_color: '',
            title_size: '',
            title_weight: '',
            desc_color: '',
            desc_size: '',
            desc_weight: ''
        };
    }

    function itemgridMceId(idx) {
        return 'vitrine-ig-mce-' + idx;
    }

    function buildIgWeightSelect(prop, val) {
        var html = '<select class="vitrine-ig-field" data-ig-prop="' + escapeAttr(prop) + '">';
        for (var k in IG_WEIGHT_OPTS) {
            if (IG_WEIGHT_OPTS.hasOwnProperty(k)) {
                html += '<option value="' + escapeAttr(k) + '"' + (String(val || '') === k ? ' selected' : '') + '>' + escapeHtml(IG_WEIGHT_OPTS[k]) + '</option>';
            }
        }
        html += '</select>';
        return html;
    }

    function buildItemgridCardHtml(ci, idx, isExpanded) {
        var titlePreview = stripHtmlPreview(ci.title) || ('Card ' + (idx + 1));
        var hasImage = !!ci.image;
        var hasLink  = !!ci.link;
        var hasCustomType = !!(ci.title_color || ci.title_size || ci.title_weight || ci.desc_color || ci.desc_size || ci.desc_weight);
        var expandedClass = isExpanded ? ' is-expanded' : '';

        var html = '<div class="vitrine-ig-item' + expandedClass + '" data-ig-idx="' + idx + '">';

        html += '<div class="vitrine-ig-item-header">';
        html += '<span class="vitrine-ig-drag dashicons dashicons-move" title="Arrastar"></span>';
        html += '<div class="vitrine-ig-item-thumb">';
        if (hasImage) {
            html += '<img src="' + escapeAttr(ci.image) + '" alt="" />';
        } else {
            html += '<span class="dashicons dashicons-format-image"></span>';
        }
        html += '</div>';
        html += '<div class="vitrine-ig-item-summary">';
        html += '<span class="vitrine-ig-item-title-preview">' + escapeHtml(titlePreview) + '</span>';
        html += '<span class="vitrine-ig-item-meta">Card ' + (idx + 1) + (hasCustomType ? ' · tipografia personalizada' : '') + '</span>';
        html += '</div>';
        html += '<div class="vitrine-ig-item-badges">';
        if (hasImage) { html += '<span class="vitrine-ig-badge vitrine-ig-badge--img" title="Com imagem"><span class="dashicons dashicons-format-image"></span></span>'; }
        if (hasLink)  { html += '<span class="vitrine-ig-badge vitrine-ig-badge--link" title="Com link"><span class="dashicons dashicons-admin-links"></span></span>'; }
        html += '</div>';
        html += '<span class="vitrine-ig-item-chevron dashicons dashicons-arrow-down-alt2"></span>';
        html += '<button type="button" class="button button-small vitrine-ig-item-remove" title="Remover">&times;</button>';
        html += '</div>';

        html += '<div class="vitrine-ig-item-body">';
        html += '<div class="vitrine-ig-tabs">';
        html += '<button type="button" class="vitrine-ig-tab is-active" data-ig-tab="content">Conteúdo</button>';
        html += '<button type="button" class="vitrine-ig-tab" data-ig-tab="type">Tipografia</button>';
        html += '<button type="button" class="vitrine-ig-tab" data-ig-tab="link">Link</button>';
        html += '</div>';

        // Aba Conteúdo
        html += '<div class="vitrine-ig-tab-panel is-active" data-ig-panel="content">';
        html += '<div class="vitrine-ig-image-picker">';
        html += '<div class="vitrine-ig-image-preview-wrap">';
        if (hasImage) {
            html += '<img src="' + escapeAttr(ci.image) + '" alt="" class="vitrine-ig-preview-img" />';
        } else {
            html += '<span class="dashicons dashicons-format-image"></span>';
        }
        html += '</div>';
        html += '<div class="vitrine-ig-image-actions">';
        html += '<input type="hidden" class="vitrine-ig-field" data-ig-prop="image" value="' + escapeAttr(ci.image || '') + '" />';
        html += '<button type="button" class="button vitrine-ig-select-image">' + (hasImage ? 'Trocar imagem' : 'Adicionar imagem') + '</button>';
        if (hasImage) {
            html += ' <button type="button" class="button vitrine-ig-remove-image">Remover</button>';
        }
        html += '<p class="vitrine-field-hint" style="margin:4px 0 0;">Opcional. Deixe vazio para card só com texto.</p>';
        html += '</div></div>';

        html += '<div class="vitrine-field-group" style="margin-top:14px;">';
        html += '<label>Título</label>';
        html += '<input type="text" class="vitrine-ig-field widefat" data-ig-prop="title" value="' + escapeAttr(ci.title || '') + '" placeholder="Título do card" />';
        html += '</div>';

        html += '<div class="vitrine-field-group vitrine-field-group--full">';
        html += '<label>Descrição</label>';
        html += '<textarea id="' + itemgridMceId(idx) + '" class="vitrine-ig-mce" data-ig-prop="description" rows="4">' + escapeHtml(ci.description || '') + '</textarea>';
        html += '</div>';
        html += '</div>';

        // Aba Tipografia
        html += '<div class="vitrine-ig-tab-panel" data-ig-panel="type">';
        html += '<p class="vitrine-field-hint" style="margin:0 0 12px;">Deixe em branco ou “Padrão do bloco” para usar os valores gerais do elemento.</p>';
        html += '<div class="vitrine-ig-type-row">';
        html += '<div class="vitrine-ig-type-block">';
        html += '<h5>Título</h5>';
        html += '<div class="vitrine-ig-type-field"><label>Cor</label>';
        html += '<input type="color" class="vitrine-ig-field" data-ig-prop="title_color" value="' + escapeAttr(ci.title_color || '#1a1a1a') + '" /></div>';
        html += '<div class="vitrine-ig-type-field"><label>Tamanho (px)</label>';
        html += '<input type="number" class="vitrine-ig-field" data-ig-prop="title_size" value="' + escapeAttr(ci.title_size || '') + '" min="10" max="72" placeholder="padrão" /></div>';
        html += '<div class="vitrine-ig-type-field"><label>Peso</label>' + buildIgWeightSelect('title_weight', ci.title_weight) + '</div>';
        html += '</div>';
        html += '<div class="vitrine-ig-type-block">';
        html += '<h5>Descrição</h5>';
        html += '<div class="vitrine-ig-type-field"><label>Cor</label>';
        html += '<input type="color" class="vitrine-ig-field" data-ig-prop="desc_color" value="' + escapeAttr(ci.desc_color || '#555555') + '" /></div>';
        html += '<div class="vitrine-ig-type-field"><label>Tamanho (px)</label>';
        html += '<input type="number" class="vitrine-ig-field" data-ig-prop="desc_size" value="' + escapeAttr(ci.desc_size || '') + '" min="10" max="48" placeholder="padrão" /></div>';
        html += '<div class="vitrine-ig-type-field"><label>Peso</label>' + buildIgWeightSelect('desc_weight', ci.desc_weight) + '</div>';
        html += '</div></div></div>';

        // Aba Link
        html += '<div class="vitrine-ig-tab-panel" data-ig-panel="link">';
        html += '<div class="vitrine-field-group">';
        html += '<label><span class="dashicons dashicons-admin-links" style="font-size:13px;vertical-align:middle;margin-right:3px;color:#0073aa;"></span> URL do link</label>';
        html += '<input type="text" class="vitrine-ig-field widefat" data-ig-prop="link" value="' + escapeAttr(ci.link || '') + '" placeholder="https://..." />';
        html += '</div>';
        html += '<label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:8px;">';
        html += '<input type="checkbox" class="vitrine-ig-field-check" data-ig-prop="link_new_tab"' + (ci.link_new_tab === '1' ? ' checked' : '') + ' />';
        html += ' Abrir em nova aba</label>';
        html += '<p class="vitrine-field-hint">Torna o card inteiro clicável.</p>';
        html += '</div>';

        html += '</div></div>';
        return html;
    }

    function renderItemgridRepeater($panel, item) {
        if (!item.settings.items || !Array.isArray(item.settings.items)) {
            item.settings.items = [];
        }
        var items = item.settings.items;
        itemgridExpandedIdx = clampRepeaterExpandedIdx(itemgridExpandedIdx, items.length);

        var html = '<div class="vitrine-ig-section" data-ig-key="items">';
        html += '<hr style="border:none;border-top:1px solid #dcdcde;margin:14px 0 12px;" />';
        html += '<div class="vitrine-ig-section-head">';
        html += '<div>';
        html += '<h4>Cards da Grade</h4>';
        html += '<p class="vitrine-field-hint" style="margin:0;">Clique no cabeçalho para expandir ou recolher. Arraste para reordenar.</p>';
        html += buildRepeaterSectionToolsHtml();
        html += '</div>';
        html += '<span class="vitrine-ig-count-badge">' + items.length + '</span>';
        html += '</div>';

        html += '<div class="vitrine-ig-items-list" data-ig-key="items">';
        items.forEach(function (ci, idx) {
            html += buildItemgridCardHtml(ci, idx, itemgridExpandedIdx === idx);
        });
        html += '</div>';

        html += '<button type="button" class="button vitrine-ig-add-btn">';
        html += '<span class="dashicons dashicons-plus-alt2"></span> Adicionar card';
        html += '</button></div>';

        $panel.append(html);
    }

    function initItemgridMCE() {
        $('.vitrine-ig-mce').each(function () {
            var id = $(this).attr('id');
            if (!id || typeof wp === 'undefined' || !wp.editor) return;
            if (tinymce.get(id)) return;

            wp.editor.initialize(id, {
                tinymce: {
                    toolbar1: 'bold,italic,underline,link,bullist,numlist',
                    menubar: false,
                    branding: false,
                    resize: false,
                    height: 120,
                    setup: function (editor) {
                        editor.on('change keyup', function () {
                            editor.save();
                            syncItemgridMCE(editor.id);
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        });
    }

    function syncItemgridMCE(editorId) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemgrid') return;

        var $ta = $('#' + editorId);
        var idx = $ta.closest('.vitrine-ig-item').data('ig-idx');
        if (idx === undefined) return;

        if (!item.settings.items) item.settings.items = [];
        if (!item.settings.items[idx]) item.settings.items[idx] = defaultItemgridItem();

        item.settings.items[idx].description = wp.editor.getContent(editorId);

        updateItemgridPreview();
        updateItemgridItemHeader(idx);
    }

    function syncAllItemgridMCE() {
        if (_skipMCESync) return;
        $('.vitrine-ig-mce').each(function () {
            var id = $(this).attr('id');
            if (id) syncItemgridMCE(id);
        });
    }

    function initItemgridSort() {
        itemgridSortInstances.forEach(function (inst) {
            if (inst && inst.destroy) inst.destroy();
        });
        itemgridSortInstances = [];

        var $list = $('#vitrine-settings-panel .vitrine-ig-items-list');
        if (!$list.length || typeof Sortable === 'undefined') return;

        var instance = Sortable.create($list[0], {
            handle: '.vitrine-ig-drag',
            draggable: '.vitrine-ig-item',
            animation: 150,
            ghostClass: 'vitrine-ig-ghost',
            onEnd: function (evt) {
                if (evt.oldIndex === evt.newIndex) return;
                var current = findItemById(selectedId);
                if (!current || !current.settings.items) return;

                var moved = current.settings.items.splice(evt.oldIndex, 1)[0];
                current.settings.items.splice(evt.newIndex, 0, moved);

                if (itemgridExpandedIdx === evt.oldIndex) {
                    itemgridExpandedIdx = evt.newIndex;
                } else if (itemgridExpandedIdx !== null) {
                    if (evt.oldIndex < itemgridExpandedIdx && evt.newIndex >= itemgridExpandedIdx) {
                        itemgridExpandedIdx--;
                    } else if (evt.oldIndex > itemgridExpandedIdx && evt.newIndex <= itemgridExpandedIdx) {
                        itemgridExpandedIdx++;
                    }
                }

                renderSettings();
                updateItemgridPreview();
            }
        });
        itemgridSortInstances.push(instance);
    }

    function updateItemgridPreview() {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemgrid') return;
        var elDef = elements[item.type];
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        if ($block.length && elDef) {
            var s = $.extend(true, {}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, s);
        }
    }

    function updateItemgridItemHeader(idx) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || !item.settings.items || !item.settings.items[idx]) return;
        var ci = item.settings.items[idx];
        var $card = $('.vitrine-ig-item[data-ig-idx="' + idx + '"]');
        if (!$card.length) return;

        var titlePreview = stripHtmlPreview(ci.title) || ('Card ' + (idx + 1));
        $card.find('.vitrine-ig-item-title-preview').text(titlePreview);

        var hasCustom = !!(ci.title_color || ci.title_size || ci.title_weight || ci.desc_color || ci.desc_size || ci.desc_weight);
        $card.find('.vitrine-ig-item-meta').text('Card ' + (idx + 1) + (hasCustom ? ' · tipografia personalizada' : ''));

        var $thumb = $card.find('.vitrine-ig-item-thumb');
        if (ci.image) {
            $thumb.html('<img src="' + escapeAttr(ci.image) + '" alt="" />');
            if (!$card.find('.vitrine-ig-badge--img').length) {
                $card.find('.vitrine-ig-item-badges').prepend('<span class="vitrine-ig-badge vitrine-ig-badge--img" title="Com imagem"><span class="dashicons dashicons-format-image"></span></span>');
            }
        } else {
            $thumb.html('<span class="dashicons dashicons-format-image"></span>');
            $card.find('.vitrine-ig-badge--img').remove();
        }

        if (ci.link) {
            if (!$card.find('.vitrine-ig-badge--link').length) {
                $card.find('.vitrine-ig-item-badges').append('<span class="vitrine-ig-badge vitrine-ig-badge--link" title="Com link"><span class="dashicons dashicons-admin-links"></span></span>');
            }
        } else {
            $card.find('.vitrine-ig-badge--link').remove();
        }
    }

    function syncItemgridField($el) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemgrid') return;

        var $card = $el.closest('.vitrine-ig-item');
        var idx  = $card.data('ig-idx');
        var prop = $el.data('ig-prop');
        if (idx === undefined || !prop) return;

        if (!item.settings.items) item.settings.items = [];
        if (!item.settings.items[idx]) item.settings.items[idx] = defaultItemgridItem();

        if ($el.is(':checkbox')) {
            item.settings.items[idx][prop] = $el.is(':checked') ? '1' : '0';
        } else {
            var val = $el.val();
            if (prop === 'title_size' || prop === 'desc_size') {
                item.settings.items[idx][prop] = val === '' ? '' : val;
            } else if (prop === 'title_color' || prop === 'desc_color') {
                item.settings.items[idx][prop] = val;
            } else if (prop === 'title_weight' || prop === 'desc_weight') {
                item.settings.items[idx][prop] = val;
            } else {
                item.settings.items[idx][prop] = val;
            }
        }

        updateItemgridPreview();
        updateItemgridItemHeader(idx);
    }

    /* ──────────────────────────────────────────────────────
       Carrossel de Itens — repeater com abas (Conteúdo / Link)
    ────────────────────────────────────────────────────── */

    function defaultItemcarouselItem() {
        return {
            item_type: 'image',
            image: '',
            icon: '',
            title: '',
            text: '',
            link: '',
            link_new_tab: '0'
        };
    }

    function itemcarouselMceId(idx) {
        return 'vitrine-ic-mce-' + idx;
    }

    function buildItemcarouselSlideHtml(si, idx, isExpanded) {
        var itemType = si.item_type === 'icon' ? 'icon' : 'image';
        var titlePreview = stripHtmlPreview(si.title) || ('Slide ' + (idx + 1));
        var hasImage = !!si.image;
        var hasIcon  = !!si.icon;
        var hasLink  = !!si.link;
        var expandedClass = isExpanded ? ' is-expanded' : '';
        var typeLabel = itemType === 'icon' ? 'Ícone + texto' : 'Imagem + texto';

        var html = '<div class="vitrine-ic-item vitrine-ig-item' + expandedClass + '" data-ic-idx="' + idx + '">';

        html += '<div class="vitrine-ic-item-header vitrine-ig-item-header">';
        html += '<span class="vitrine-ic-drag vitrine-ig-drag dashicons dashicons-move" title="Arrastar"></span>';
        html += '<div class="vitrine-ic-item-thumb vitrine-ig-item-thumb">';
        if (itemType === 'icon' && hasIcon) {
            html += '<span class="vitrine-ic-thumb-icon">' + renderIconPreviewHtml(si.icon) + '</span>';
        } else if (hasImage) {
            html += '<img src="' + escapeAttr(si.image) + '" alt="" />';
        } else {
            html += '<span class="dashicons dashicons-' + (itemType === 'icon' ? 'star-filled' : 'format-image') + '"></span>';
        }
        html += '</div>';
        html += '<div class="vitrine-ic-item-summary vitrine-ig-item-summary">';
        html += '<span class="vitrine-ic-item-title-preview vitrine-ig-item-title-preview">' + escapeHtml(titlePreview) + '</span>';
        html += '<span class="vitrine-ic-item-meta vitrine-ig-item-meta">' + escapeHtml(typeLabel) + ' · Slide ' + (idx + 1) + '</span>';
        html += '</div>';
        html += '<div class="vitrine-ic-item-badges vitrine-ig-item-badges">';
        if (hasLink) { html += '<span class="vitrine-ig-badge vitrine-ig-badge--link" title="Com link"><span class="dashicons dashicons-admin-links"></span></span>'; }
        html += '</div>';
        html += '<span class="vitrine-ic-item-chevron vitrine-ig-item-chevron dashicons dashicons-arrow-down-alt2"></span>';
        html += '<button type="button" class="button button-small vitrine-ic-item-remove vitrine-ig-item-remove" title="Remover">&times;</button>';
        html += '</div>';

        html += '<div class="vitrine-ic-item-body vitrine-ig-item-body">';
        html += '<div class="vitrine-ic-tabs vitrine-ig-tabs">';
        html += '<button type="button" class="vitrine-ic-tab vitrine-ig-tab is-active" data-ic-tab="content">Conteúdo</button>';
        html += '<button type="button" class="vitrine-ic-tab vitrine-ig-tab" data-ic-tab="link">Link</button>';
        html += '</div>';

        html += '<div class="vitrine-ic-tab-panel vitrine-ig-tab-panel is-active" data-ic-panel="content">';

        html += '<div class="vitrine-ic-type-switch">';
        html += '<button type="button" class="vitrine-ic-type-btn' + (itemType === 'image' ? ' is-active' : '') + '" data-ic-type="image"><span class="dashicons dashicons-format-image"></span> Imagem + texto</button>';
        html += '<button type="button" class="vitrine-ic-type-btn' + (itemType === 'icon' ? ' is-active' : '') + '" data-ic-type="icon"><span class="dashicons dashicons-star-filled"></span> Ícone + texto</button>';
        html += '</div>';
        html += '<input type="hidden" class="vitrine-ic-field" data-ic-prop="item_type" value="' + escapeAttr(itemType) + '" />';

        html += '<div class="vitrine-ic-type-panel vitrine-ic-type-panel--image"' + (itemType === 'image' ? '' : ' style="display:none;"') + '>';
        html += '<div class="vitrine-ig-image-picker vitrine-ic-image-picker">';
        html += '<div class="vitrine-ig-image-preview-wrap vitrine-ic-image-preview-wrap">';
        if (hasImage) {
            html += '<img src="' + escapeAttr(si.image) + '" alt="" />';
        } else {
            html += '<span class="dashicons dashicons-format-image"></span>';
        }
        html += '</div>';
        html += '<div class="vitrine-ig-image-actions vitrine-ic-image-actions">';
        html += '<input type="hidden" class="vitrine-ic-field" data-ic-prop="image" value="' + escapeAttr(si.image || '') + '" />';
        html += '<button type="button" class="button vitrine-ic-select-image">' + (hasImage ? 'Trocar imagem' : 'Adicionar imagem') + '</button>';
        if (hasImage) {
            html += ' <button type="button" class="button vitrine-ic-remove-image">Remover</button>';
        }
        html += '</div></div></div>';

        html += '<div class="vitrine-ic-type-panel vitrine-ic-type-panel--icon"' + (itemType === 'icon' ? '' : ' style="display:none;"') + '>';
        html += '<div class="vitrine-aranha-icon-field vitrine-ic-icon-field">';
        html += '<label class="vitrine-a3-icon-row-label">Ícone do slide</label>';
        html += '<div class="vitrine-a3-icon-row-inner">';
        var iconPreview = hasIcon ? renderIconPreviewHtml(si.icon) : '<span class="dashicons dashicons-star-filled" style="font-size:28px;width:28px;height:28px;color:#c3c4c7;"></span>';
        html += '<div class="vitrine-a2-icon-display vitrine-ic-open-picker" title="Escolher ícone">';
        html += '<div class="vitrine-icon-current">' + iconPreview + '</div>';
        html += '<span class="vitrine-a2-icon-hint">' + (hasIcon ? 'Trocar' : 'Adicionar') + '</span>';
        html += '</div>';
        html += '<input type="hidden" class="vitrine-ic-field" data-ic-prop="icon" value="' + escapeAttr(si.icon || '') + '" />';
        html += '<div class="vitrine-icon-actions vitrine-a2-icon-actions">';
        if (hasIcon) {
            html += '<button type="button" class="button vitrine-ic-remove-icon">Remover</button>';
        }
        html += '</div>';
        html += buildIconPickerHtml();
        html += '</div></div></div>';

        html += '<div class="vitrine-field-group" style="margin-top:14px;">';
        html += '<label>Título</label>';
        html += '<input type="text" class="vitrine-ic-field widefat" data-ic-prop="title" value="' + escapeAttr(si.title || '') + '" placeholder="Título do slide" />';
        html += '</div>';

        html += '<div class="vitrine-field-group vitrine-field-group--full">';
        html += '<label>Texto / descrição</label>';
        html += '<textarea id="' + itemcarouselMceId(idx) + '" class="vitrine-ic-mce" data-ic-prop="text" rows="4">' + escapeHtml(si.text || si.description || '') + '</textarea>';
        html += '</div>';
        html += '</div>';

        html += '<div class="vitrine-ic-tab-panel vitrine-ig-tab-panel" data-ic-panel="link">';
        html += '<div class="vitrine-field-group">';
        html += '<label><span class="dashicons dashicons-admin-links" style="font-size:13px;vertical-align:middle;margin-right:3px;color:#0073aa;"></span> URL do link</label>';
        html += '<input type="text" class="vitrine-ic-field widefat" data-ic-prop="link" value="' + escapeAttr(si.link || '') + '" placeholder="https://..." />';
        html += '</div>';
        html += '<label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-top:8px;">';
        html += '<input type="checkbox" class="vitrine-ic-field-check" data-ic-prop="link_new_tab"' + (si.link_new_tab === '1' ? ' checked' : '') + ' />';
        html += ' Abrir em nova aba</label>';
        html += '<p class="vitrine-field-hint">Torna o slide inteiro clicável.</p>';
        html += '</div>';

        html += '</div></div>';
        return html;
    }

    function renderItemcarouselRepeater($panel, item) {
        if (!item.settings.items || !Array.isArray(item.settings.items)) {
            item.settings.items = [];
        }
        var items = item.settings.items;
        itemcarouselExpandedIdx = clampRepeaterExpandedIdx(itemcarouselExpandedIdx, items.length);

        var html = '<div class="vitrine-ic-section vitrine-ig-section" data-ic-key="items">';
        html += '<hr style="border:none;border-top:1px solid #dcdcde;margin:14px 0 12px;" />';
        html += '<div class="vitrine-ic-section-head vitrine-ig-section-head">';
        html += '<div>';
        html += '<h4>Slides do Carrossel</h4>';
        html += '<p class="vitrine-field-hint" style="margin:0;">Clique no cabeçalho para expandir ou recolher. Escolha imagem ou ícone em cada slide.</p>';
        html += buildRepeaterSectionToolsHtml();
        html += '</div>';
        html += '<span class="vitrine-ic-count-badge vitrine-ig-count-badge">' + items.length + '</span>';
        html += '</div>';

        html += '<div class="vitrine-ic-items-list vitrine-ig-items-list" data-ic-key="items">';
        items.forEach(function (si, idx) {
            html += buildItemcarouselSlideHtml(si, idx, itemcarouselExpandedIdx === idx);
        });
        html += '</div>';

        html += '<button type="button" class="button vitrine-ic-add-btn vitrine-ig-add-btn">';
        html += '<span class="dashicons dashicons-plus-alt2"></span> Adicionar slide';
        html += '</button></div>';

        $panel.append(html);
    }

    function initItemcarouselMCE() {
        $('.vitrine-ic-mce').each(function () {
            var id = $(this).attr('id');
            if (!id || typeof wp === 'undefined' || !wp.editor) return;
            if (tinymce.get(id)) return;

            wp.editor.initialize(id, {
                tinymce: {
                    toolbar1: 'bold,italic,underline,link,bullist',
                    menubar: false,
                    branding: false,
                    resize: false,
                    height: 100,
                    setup: function (editor) {
                        editor.on('change keyup', function () {
                            editor.save();
                            syncItemcarouselMCE(editor.id);
                        });
                    }
                },
                quicktags: true,
                mediaButtons: false
            });
        });
    }

    function syncItemcarouselMCE(editorId) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemcarousel') return;

        var $ta = $('#' + editorId);
        var idx = $ta.closest('.vitrine-ic-item').data('ic-idx');
        if (idx === undefined) return;

        if (!item.settings.items) item.settings.items = [];
        if (!item.settings.items[idx]) item.settings.items[idx] = defaultItemcarouselItem();

        item.settings.items[idx].text = wp.editor.getContent(editorId);

        updateItemcarouselPreview();
        updateItemcarouselItemHeader(idx);
    }

    function syncAllItemcarouselMCE() {
        if (_skipMCESync) return;
        $('.vitrine-ic-mce').each(function () {
            var id = $(this).attr('id');
            if (id) syncItemcarouselMCE(id);
        });
    }

    function initItemcarouselSort() {
        itemcarouselSortInstances.forEach(function (inst) {
            if (inst && inst.destroy) inst.destroy();
        });
        itemcarouselSortInstances = [];

        var $list = $('#vitrine-settings-panel .vitrine-ic-items-list');
        if (!$list.length || typeof Sortable === 'undefined') return;

        var instance = Sortable.create($list[0], {
            handle: '.vitrine-ic-drag',
            draggable: '.vitrine-ic-item',
            animation: 150,
            ghostClass: 'vitrine-ig-ghost',
            onEnd: function (evt) {
                if (evt.oldIndex === evt.newIndex) return;
                var current = findItemById(selectedId);
                if (!current || !current.settings.items) return;

                var moved = current.settings.items.splice(evt.oldIndex, 1)[0];
                current.settings.items.splice(evt.newIndex, 0, moved);

                if (itemcarouselExpandedIdx === evt.oldIndex) {
                    itemcarouselExpandedIdx = evt.newIndex;
                } else if (itemcarouselExpandedIdx !== null) {
                    if (evt.oldIndex < itemcarouselExpandedIdx && evt.newIndex >= itemcarouselExpandedIdx) {
                        itemcarouselExpandedIdx--;
                    } else if (evt.oldIndex > itemcarouselExpandedIdx && evt.newIndex <= itemcarouselExpandedIdx) {
                        itemcarouselExpandedIdx++;
                    }
                }

                renderSettings();
                updateItemcarouselPreview();
            }
        });
        itemcarouselSortInstances.push(instance);
    }

    function updateItemcarouselPreview() {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemcarousel') return;
        var elDef = elements[item.type];
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        if ($block.length && elDef) {
            var s = $.extend(true, {}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, s);
        }
    }

    function updateItemcarouselItemHeader(idx) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || !item.settings.items || !item.settings.items[idx]) return;
        var si = item.settings.items[idx];
        var $card = $('.vitrine-ic-item[data-ic-idx="' + idx + '"]');
        if (!$card.length) return;

        var itemType = si.item_type === 'icon' ? 'icon' : 'image';
        var typeLabel = itemType === 'icon' ? 'Ícone + texto' : 'Imagem + texto';
        var titlePreview = stripHtmlPreview(si.title) || ('Slide ' + (idx + 1));

        $card.find('.vitrine-ic-item-title-preview').text(titlePreview);
        $card.find('.vitrine-ic-item-meta').text(typeLabel + ' · Slide ' + (idx + 1));

        var $thumb = $card.find('.vitrine-ic-item-thumb');
        if (itemType === 'icon' && si.icon) {
            $thumb.html('<span class="vitrine-ic-thumb-icon">' + renderIconPreviewHtml(si.icon) + '</span>');
        } else if (si.image) {
            $thumb.html('<img src="' + escapeAttr(si.image) + '" alt="" />');
        } else {
            $thumb.html('<span class="dashicons dashicons-' + (itemType === 'icon' ? 'star-filled' : 'format-image') + '"></span>');
        }

        if (si.link) {
            if (!$card.find('.vitrine-ig-badge--link').length) {
                $card.find('.vitrine-ic-item-badges').append('<span class="vitrine-ig-badge vitrine-ig-badge--link" title="Com link"><span class="dashicons dashicons-admin-links"></span></span>');
            }
        } else {
            $card.find('.vitrine-ig-badge--link').remove();
        }
    }

    function syncItemcarouselField($el) {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemcarousel') return;

        var $slide = $el.closest('.vitrine-ic-item');
        var idx  = $slide.data('ic-idx');
        var prop = $el.data('ic-prop');
        if (idx === undefined || !prop) return;

        if (!item.settings.items) item.settings.items = [];
        if (!item.settings.items[idx]) item.settings.items[idx] = defaultItemcarouselItem();

        if ($el.is(':checkbox')) {
            item.settings.items[idx][prop] = $el.is(':checked') ? '1' : '0';
        } else {
            item.settings.items[idx][prop] = $el.val();
        }

        updateItemcarouselPreview();
        updateItemcarouselItemHeader(idx);
    }

    /* ──────────────────── Event Handlers ──────────────────── */

    // Atualiza settings ao modificar campo
    $(document).on('input change', '.vitrine-field', function () {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var field = $(this).data('field');
        var val   = $(this).val();
        var elDef = elements[item.type];
        item.settings[field] = val;

        if ($(this).hasClass('vitrine-field-range')) {
            $(this).closest('.vitrine-range-row').find('.vitrine-range-val').text(val);
        }

        // Mudou a direção do container
        if (item.type === 'container' && field === 'direction') {
            if (val === 'row') {
                distributeWidths(item.id);
            } else {
                clearWidths(item.id);
            }
            renderCanvas();
            renderSettings();
            return;
        }

        if (isAranhaType(item.type) && (field === 'card_style' || field === 'layout_mode')) {
            renderSettings();
            renderCanvas();
            return;
        }

        if (item.type === 'video' && (field === 'qty' || /^source_\d+$/.test(field))) {
            renderSettings();
            return;
        }

        if (item.type === 'container' && field === 'name') {
            var mergedSettings = $.extend({}, elDef.defaults, item.settings);
            var displayName = getContainerDisplayName(mergedSettings);
            var $rootBlock = $('[data-id="' + selectedId + '"]').first();
            $rootBlock.find('> .vitrine-block-toolbar .vitrine-block-label').text(displayName);
            $('#vitrine-settings-el-label').text(displayName);
        }

        // Re-renderiza preview do bloco selecionado
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        if ($block.length && elDef) {
            var settings = $.extend({}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, settings);
        }

        if (item.type === 'text' && field === 'bg_color' && val) {
            var $colorInput = $(this);
            if (!$colorInput.siblings('.vitrine-field-bg-color-clear').length) {
                $colorInput.after('<button type="button" class="button button-small vitrine-field-bg-color-clear" title="Sem fundo">&#10005;</button>');
            }
        }
    });

    $(document).on('click', '.vitrine-field-bg-color-clear', function (e) {
        e.preventDefault();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'text') return;

        item.settings.bg_color = '';
        $(this).siblings('.vitrine-field-bg-color').val('#ffffff');
        $(this).remove();

        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        var elDef  = elements[item.type];
        if ($block.length && elDef) {
            var settings = $.extend({}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, settings);
        }
    });

    // Atualiza largura manual
    $(document).on('input change', '.vitrine-field-width', function () {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;
        item.width = $(this).val().trim();

        var $block = $('[data-id="' + selectedId + '"]').first();
        if (item.width) {
            $block.css({ flex: '0 1 ' + item.width, maxWidth: item.width, minWidth: '0', boxSizing: 'border-box' });
        } else {
            $block.css({ flex: '', maxWidth: '', minWidth: '', boxSizing: '' });
        }
        $block.find('.vitrine-width-badge').text(Math.round(parseFloat(item.width) || 0) + '%');
    });

    // Botão distribuir igualmente
    $(document).on('click', '.vitrine-btn-distribute', function (e) {
        e.preventDefault();
        if (!selectedId) return;
        var parentContainer = findParentContainer(selectedId);
        if (parentContainer) {
            distributeWidths(parentContainer.id);
            renderCanvas();
            renderSettings();
        }
    });

    // Seletor de imagem/vídeo (WordPress Media Library)
    $(document).on('click', '.vitrine-select-image', function (e) {
        e.preventDefault();
        var $container = $(this).closest('.vitrine-image-field, .vitrine-video-field');
        var $input     = $container.find('.vitrine-field');
        var fieldName  = $input.data('field') || '';
        var isVideo    = fieldName === 'local_url' || /^url_\d+$/.test(fieldName);
        var mediaType  = isVideo ? 'video' : 'image';

        var frame = wp.media({
            title: isVideo ? 'Selecionar Vídeo' : 'Selecionar Imagem',
            multiple: false,
            library: { type: mediaType }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.url).trigger('change');
            $container.find('.vitrine-image-url-input').val(attachment.url);

            $container.find('.vitrine-image-preview, .vitrine-video-preview').remove();
            $container.find('.vitrine-remove-image').remove();
            if (isVideo) {
                $container.prepend(buildVideoPreviewHtml(attachment.url, 'local'));
                if (selectedId) {
                    var currentItem = findItemById(selectedId);
                    if (currentItem && currentItem.type === 'video') {
                        var slotMatch = fieldName.match(/^url_(\d+)$/);
                        if (slotMatch) {
                            currentItem.settings['source_' + slotMatch[1]] = 'local';
                        }
                    }
                }
            } else {
                $container.prepend('<img src="' + escapeAttr(attachment.url) + '" class="vitrine-image-preview" />');
            }
            $(e.target).after(' <button type="button" class="button vitrine-remove-image">Remover</button>');
        });

        frame.open();
    });

    $(document).on('click', '.vitrine-remove-image', function (e) {
        e.preventDefault();
        var $container = $(this).closest('.vitrine-image-field, .vitrine-video-field');
        $container.find('.vitrine-field').val('').trigger('change');
        $container.find('.vitrine-image-preview').remove();
        $container.find('.vitrine-image-url-input').val('');
        $(this).remove();
    });

    // URL manual para campo de imagem/vídeo
    $(document).on('change', '.vitrine-image-url-input', function () {
        var url = $.trim($(this).val());
        var $container = $(this).closest('.vitrine-image-field, .vitrine-video-field');
        var $input = $container.find('.vitrine-field');
        var fieldName = $input.data('field') || '';
        var isVideoField = fieldName === 'local_url' || /^url_\d+$/.test(fieldName);

        $input.val(url).trigger('change');
        $container.find('.vitrine-image-preview, .vitrine-video-preview').remove();
        $container.find('.vitrine-remove-image').remove();

        if (url) {
            if (isVideoField) {
                var source = 'youtube';
                if (selectedId) {
                    var currentItem = findItemById(selectedId);
                    var slotMatch = fieldName.match(/^url_(\d+)$/);
                    if (currentItem && slotMatch) {
                        source = currentItem.settings['source_' + slotMatch[1]] || 'youtube';
                    }
                }
                $container.prepend(buildVideoPreviewHtml(url, source));
            } else {
                $container.prepend('<img src="' + escapeAttr(url) + '" class="vitrine-image-preview" />');
            }
            $container.find('.vitrine-select-image').after(' <button type="button" class="button vitrine-remove-image">Remover</button>');
        }
    });

    /* ──────────────────── Aranha: Eventos dos itens dinâmicos ──────────────────── */

    // Campo de link/ícone da aranha alterado
    $(document).on('input change', '.vitrine-aranha-field', function () {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var $item    = $(this).closest('.vitrine-aranha-item');
        var $section = $(this).closest('.vitrine-aranha-section');
        var key  = $section.data('aranha-key');
        var idx  = $item.data('aranha-idx');
        var prop = $(this).data('aranha-prop');

        if (!item.settings[key]) item.settings[key] = [];
        if (!item.settings[key][idx]) {
            item.settings[key][idx] = defaultAranhaItem(getAranhaLayoutMode(item));
        }
        item.settings[key][idx][prop] = $(this).val();

        if (getAranhaLayoutMode(item) === 'grade' && prop === 'position') {
            renderSettings();
            renderCanvas();
            return;
        }

        // Atualiza preview
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        var elDef  = elements[item.type];
        if ($block.length && elDef) {
            var s = $.extend(true, {}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, s);
        }
    });

    // Adicionar item à aranha
    $(document).on('click', '.vitrine-aranha-add-item', function (e) {
        e.preventDefault();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var key = $(this).closest('.vitrine-aranha-section').data('aranha-key');
        var $zoneWrap = $(this).closest('[data-aranha-zone]');
        var zone = $zoneWrap.length ? $zoneWrap.data('aranha-zone') : null;
        if (!item.settings[key]) item.settings[key] = [];
        if (getAranhaLayoutMode(item) === 'grade') {
            var gradeItem = defaultAranhaItem('grade');
            gradeItem.position = zone && zone !== 'auto' ? zone : 'auto';
            item.settings[key].push(gradeItem);
        } else {
            item.settings[key].push(defaultAranhaItem('circular'));
        }

        aranhaExpandedIdx = item.settings[key].length - 1;
        renderSettings();
        renderCanvas();
    });

    // Remover item da aranha
    $(document).on('click', '.vitrine-aranha-remove-item', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var $section = $(this).closest('.vitrine-aranha-section');
        var key = $section.data('aranha-key');
        var idx = $(this).closest('.vitrine-aranha-item').data('aranha-idx');

        if (item.settings[key]) {
            item.settings[key].splice(idx, 1);
        }

        if (aranhaExpandedIdx === idx) {
            aranhaExpandedIdx = null;
        } else if (aranhaExpandedIdx !== null && aranhaExpandedIdx > idx) {
            aranhaExpandedIdx--;
        }

        renderSettings();
        renderCanvas();
    });

    // Abrir/fechar picker de ícone
    $(document).on('click', '.vitrine-aranha-open-picker', function (e) {
        e.stopPropagation();
        var $picker = $(this).closest('.vitrine-aranha-icon-field').find('.vitrine-icon-picker');
        $('.vitrine-icon-picker').not($picker).hide();
        var willShow = !$picker.is(':visible');
        $picker.toggle();
        if (willShow) {
            ensureIconGrid($picker, 'dashicons');
        }
    });

    // Trocar aba do picker
    $(document).on('click', '.vitrine-icon-tab', function () {
        var tab = $(this).data('tab');
        var $picker = $(this).closest('.vitrine-icon-picker');
        $picker.find('.vitrine-icon-tab').removeClass('is-active');
        $(this).addClass('is-active');
        $picker.find('.vitrine-icon-tab-panel').hide();
        $picker.find('[data-panel="' + tab + '"]').show();
        ensureIconGrid($picker, tab);
    });

    // Filtrar ícones no grid
    $(document).on('input', '.vitrine-icon-search', function () {
        var q = $(this).val().toLowerCase().replace(/^fa[srlbd]?\s+/, '');
        $(this).next('.vitrine-icons-grid').find('.vitrine-icon-pick').each(function () {
            var name = ($(this).data('icon') || '').toLowerCase().replace(/^fa[srlbd]?\s+/, '');
            $(this).toggle(q === '' || name.indexOf(q) !== -1);
        });
    });

    // Selecionar ícone (dashicons ou FA)
    $(document).on('click', '.vitrine-icon-pick', function (e) {
        e.preventDefault();
        var icon = $(this).data('icon');
        var $icField = $(this).closest('.vitrine-ic-icon-field');
        if ($icField.length) {
            var $slide = $icField.closest('.vitrine-ic-item');
            var idx = $slide.data('ic-idx');
            var item = findItemById(selectedId);
            if (item && item.settings.items && item.settings.items[idx]) {
                item.settings.items[idx].icon = icon;
                $icField.find('.vitrine-ic-field[data-ic-prop="icon"]').val(icon);
                $icField.find('.vitrine-icon-current').html(renderIconPreviewHtml(icon));
                $icField.find('.vitrine-icon-picker').hide();
                if (!$icField.find('.vitrine-ic-remove-icon').length) {
                    $icField.find('.vitrine-icon-actions').append(' <button type="button" class="button vitrine-ic-remove-icon">Remover</button>');
                }
                updateItemcarouselPreview();
                updateItemcarouselItemHeader(idx);
            }
            return;
        }
        var $field = $(this).closest('.vitrine-aranha-icon-field');
        $field.find('.vitrine-aranha-field[data-aranha-prop="icon"]').val(icon).trigger('change');
        $field.find('.vitrine-icon-current').html(renderIconPreviewHtml(icon));
        $field.find('.vitrine-icon-picker').hide();
        if (!$field.find('.vitrine-aranha-remove-icon').length) {
            $field.find('.vitrine-icon-actions').append(' <button type="button" class="button vitrine-aranha-remove-icon">Remover</button>');
        }
    });

    // Selecionar imagem da biblioteca WP
    $(document).on('click', '.vitrine-aranha-select-icon', function (e) {
        e.preventDefault();
        var $icContainer = $(this).closest('.vitrine-ic-icon-field');
        if ($icContainer.length) {
            var $slide = $icContainer.closest('.vitrine-ic-item');
            var idx = $slide.data('ic-idx');
            var frame = wp.media({ title: 'Selecionar Ícone', multiple: false, library: { type: 'image' } });
            frame.on('select', function () {
                var url = frame.state().get('selection').first().toJSON().url;
                var item = findItemById(selectedId);
                if (!item || !item.settings.items || !item.settings.items[idx]) return;
                item.settings.items[idx].icon = url;
                $icContainer.find('.vitrine-ic-field[data-ic-prop="icon"]').val(url);
                $icContainer.find('.vitrine-icon-current').html(renderIconPreviewHtml(url));
                $icContainer.find('.vitrine-icon-picker').hide();
                if (!$icContainer.find('.vitrine-ic-remove-icon').length) {
                    $icContainer.find('.vitrine-icon-actions').append(' <button type="button" class="button vitrine-ic-remove-icon">Remover</button>');
                }
                updateItemcarouselPreview();
                updateItemcarouselItemHeader(idx);
            });
            frame.open();
            return;
        }
        var $container = $(this).closest('.vitrine-aranha-icon-field');
        var $input     = $container.find('.vitrine-aranha-field[data-aranha-prop="icon"]');

        var frame = wp.media({
            title: 'Selecionar Ícone',
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $input.val(attachment.url).trigger('change');
            $container.find('.vitrine-icon-current').html(renderIconPreviewHtml(attachment.url));
            $container.find('.vitrine-icon-picker').hide();
            if (!$container.find('.vitrine-aranha-remove-icon').length) {
                $container.find('.vitrine-icon-actions').append(' <button type="button" class="button vitrine-aranha-remove-icon">Remover</button>');
            }
        });

        frame.open();
    });

    // Remover ícone
    $(document).on('click', '.vitrine-aranha-remove-icon', function (e) {
        e.preventDefault();
        var $container = $(this).closest('.vitrine-aranha-icon-field');
        $container.find('.vitrine-aranha-field[data-aranha-prop="icon"]').val('').trigger('change');
        $container.find('.vitrine-icon-current').empty();
        $(this).remove();
    });

    // Fechar picker ao clicar fora
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.vitrine-aranha-icon-field, .vitrine-ic-icon-field').length) {
            $('.vitrine-icon-picker').hide();
        }
    });

    // Aranha2/3: atualizar badge de link ao alterar campo link
    $(document).on('input', '.vitrine-a2-item .vitrine-aranha-field[data-aranha-prop="link"], .vitrine-a3-item .vitrine-aranha-field[data-aranha-prop="link"]', function () {
        var hasLink = !!$(this).val().trim();
        var $header = $(this).closest('.vitrine-a2-item, .vitrine-a3-item').find('.vitrine-a2-item-header');
        $header.find('.vitrine-a2-badge--link').remove();
        if (hasLink) {
            $header.find('.vitrine-aranha-remove-item').before(
                '<span class="vitrine-a2-badge vitrine-a2-badge--link" title="Tem link"><span class="dashicons dashicons-admin-links"></span></span>'
            );
        }
    });

    // Aranha2/3: atualizar badge e hint do ícone ao selecionar/remover
    $(document).on('change', '.vitrine-a2-item .vitrine-aranha-field[data-aranha-prop="icon"], .vitrine-a3-item .vitrine-aranha-field[data-aranha-prop="icon"]', function () {
        var hasIcon = !!$(this).val();
        var $item   = $(this).closest('.vitrine-a2-item, .vitrine-a3-item');
        var $header = $item.find('.vitrine-a2-item-header');
        $header.find('.vitrine-a2-badge--icon').remove();
        if (hasIcon) {
            $header.find('.vitrine-aranha-item-num').after(
                '<span class="vitrine-a2-badge vitrine-a2-badge--icon" title="Tem ícone"><span class="dashicons dashicons-format-image"></span></span>'
            );
        }
        $item.find('.vitrine-a2-icon-hint').text(hasIcon ? 'Trocar' : 'Adicionar');
        var $actions = $item.find('.vitrine-icon-actions');
        if (hasIcon && !$actions.find('.vitrine-aranha-remove-icon').length) {
            $actions.append('<button type="button" class="button vitrine-aranha-remove-icon">Remover</button>');
        }
    });

    /* ──────────────────── Toggle: Eventos dos itens dinâmicos ──────────────────── */

    // Campo de título/conteúdo do toggle alterado
    $(document).on('input change', '.vitrine-toggle-field', function () {
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var $tItem = $(this).closest('.vitrine-toggle-editor-item');
        var idx    = $tItem.data('toggle-idx');
        var prop   = $(this).data('toggle-prop');

        if (!item.settings.items) item.settings.items = [];
        if (!item.settings.items[idx]) item.settings.items[idx] = { title: '', content: '' };
        item.settings.items[idx][prop] = $(this).val();

        // Atualiza preview
        var $block = $('[data-id="' + selectedId + '"]').first().find('> .vitrine-block-preview');
        var elDef  = elements[item.type];
        if ($block.length && elDef) {
            var s = $.extend(true, {}, elDef.defaults, item.settings);
            refreshBlockPreview($block, item.type, s);
        }
    });

    /* ──────────────────── Repeaters: colapsar / expandir itens ──────────────────── */

    $(document).on('click', '.vitrine-repeater-collapse-header', function (e) {
        if ($(e.target).closest('.vitrine-aranha-remove-item, .vitrine-toggle-remove-item, .vitrine-aranha-drag, .vitrine-toggle-drag').length) {
            return;
        }

        var $item = $(this).closest('.vitrine-repeater-collapse-item');
        if (!$item.length) return;

        if ($item.hasClass('vitrine-toggle-editor-item')) {
            var toggleIdx = $item.data('toggle-idx');
            if (toggleExpandedIdx === toggleIdx) {
                toggleExpandedIdx = null;
                $item.removeClass('is-expanded');
            } else {
                $('.vitrine-toggle-editor-item').removeClass('is-expanded');
                toggleExpandedIdx = toggleIdx;
                $item.addClass('is-expanded');
                setTimeout(initToggleMCE, 30);
            }
            return;
        }

        if (!$item.hasClass('vitrine-aranha-item')) return;

        var aranhaIdx = $item.data('aranha-idx');
        if (aranhaExpandedIdx === aranhaIdx) {
            aranhaExpandedIdx = null;
            $item.removeClass('is-expanded');
        } else {
            $('.vitrine-aranha-item.vitrine-repeater-collapse-item').removeClass('is-expanded');
            aranhaExpandedIdx = aranhaIdx;
            $item.addClass('is-expanded');
            setTimeout(initAranhaMCE, 30);
        }
    });

    $(document).on('click', '.vitrine-repeater-collapse-all', function (e) {
        e.preventDefault();
        var $section = $(this).closest('.vitrine-aranha-section, .vitrine-toggle-section, .vitrine-ig-section, .vitrine-ic-section');
        if ($section.hasClass('vitrine-ic-section')) {
            itemcarouselExpandedIdx = null;
        } else if ($section.hasClass('vitrine-ig-section')) {
            itemgridExpandedIdx = null;
        } else if ($section.hasClass('vitrine-toggle-section')) {
            toggleExpandedIdx = null;
        } else if ($section.hasClass('vitrine-aranha-section')) {
            aranhaExpandedIdx = null;
        }
        $section.find('.vitrine-repeater-collapse-item, .vitrine-ig-item').removeClass('is-expanded');
    });

    $(document).on('input', '.vitrine-toggle-field[data-toggle-prop="title"]', function () {
        var preview = $(this).val() || 'Item ' + (parseInt($(this).closest('.vitrine-toggle-editor-item').data('toggle-idx'), 10) + 1);
        $(this).closest('.vitrine-toggle-editor-item').find('.vitrine-repeater-item-preview').text(preview);
    });

    /* ──────────────────── Grade de Itens: eventos ──────────────────── */

    $(document).on('click', '.vitrine-ig-item-header', function (e) {
        if ($(e.target).closest('.vitrine-ig-item-remove, .vitrine-ig-drag').length) return;
        var $item = $(this).closest('.vitrine-ig-item');
        var idx = $item.data('ig-idx');
        if (itemgridExpandedIdx === idx) {
            itemgridExpandedIdx = null;
            $item.removeClass('is-expanded');
        } else {
            $('.vitrine-ig-item').removeClass('is-expanded');
            itemgridExpandedIdx = idx;
            $item.addClass('is-expanded');
            setTimeout(initItemgridMCE, 30);
        }
    });

    $(document).on('click', '.vitrine-settings-main-tab', function (e) {
        e.preventDefault();
        var tab = $(this).data('settings-tab');
        if (!tab || tab === settingsPanelTab) return;
        settingsPanelTab = tab;
        $('#vitrine-settings-panel .vitrine-settings-main-tab').removeClass('is-active');
        $(this).addClass('is-active');
        $('#vitrine-settings-panel .vitrine-settings-tab-pane').removeClass('is-active');
        $('#vitrine-settings-panel .vitrine-settings-tab-pane[data-settings-pane="' + tab + '"]').addClass('is-active');
        if (tab === 'content') {
            setTimeout(initFieldMCE, 30);
            setTimeout(initItemgridMCE, 30);
            setTimeout(initItemcarouselMCE, 30);
            setTimeout(initAranhaMCE, 30);
            setTimeout(initToggleMCE, 30);
        }
    });

    $(document).on('click', '.vitrine-ig-tab', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var tab = $(this).data('ig-tab');
        var $item = $(this).closest('.vitrine-ig-item');
        $item.find('.vitrine-ig-tab').removeClass('is-active');
        $(this).addClass('is-active');
        $item.find('.vitrine-ig-tab-panel').removeClass('is-active');
        $item.find('.vitrine-ig-tab-panel[data-ig-panel="' + tab + '"]').addClass('is-active');
        if (tab === 'content') {
            setTimeout(initItemgridMCE, 30);
        }
    });

    $(document).on('input change', '.vitrine-ig-field', function () {
        syncItemgridField($(this));
    });

    $(document).on('change', '.vitrine-ig-field-check', function () {
        syncItemgridField($(this));
    });

    $(document).on('click', '.vitrine-ig-add-btn', function (e) {
        e.preventDefault();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemgrid') return;
        if (!item.settings.items) item.settings.items = [];
        item.settings.items.push(defaultItemgridItem());
        itemgridExpandedIdx = item.settings.items.length - 1;
        renderSettings();
        updateItemgridPreview();
    });

    $(document).on('click', '.vitrine-ig-item-remove', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemgrid') return;
        var $card = $(this).closest('.vitrine-ig-item');
        var idx = $card.data('ig-idx');
        if (!item.settings.items) return;
        item.settings.items.splice(idx, 1);
        if (itemgridExpandedIdx === idx) {
            itemgridExpandedIdx = item.settings.items.length ? Math.min(idx, item.settings.items.length - 1) : null;
        } else if (itemgridExpandedIdx !== null && itemgridExpandedIdx > idx) {
            itemgridExpandedIdx--;
        }
        renderSettings();
        updateItemgridPreview();
    });

    $(document).on('click', '.vitrine-ig-select-image', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedId) return;
        var $card = $(this).closest('.vitrine-ig-item');
        var idx = $card.data('ig-idx');

        var frame = wp.media({ title: 'Imagem do card', multiple: false, library: { type: 'image' } });
        frame.on('select', function () {
            var url = frame.state().get('selection').first().toJSON().url;
            var item = findItemById(selectedId);
            if (!item || !item.settings.items || !item.settings.items[idx]) return;
            item.settings.items[idx].image = url;
            renderSettings();
            itemgridExpandedIdx = idx;
            updateItemgridPreview();
        });
        frame.open();
    });

    $(document).on('click', '.vitrine-ig-remove-image', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $card = $(this).closest('.vitrine-ig-item');
        var idx = $card.data('ig-idx');
        var item = findItemById(selectedId);
        if (!item || !item.settings.items || !item.settings.items[idx]) return;
        item.settings.items[idx].image = '';
        renderSettings();
        itemgridExpandedIdx = idx;
        updateItemgridPreview();
    });

    /* ──────────────────── Carrossel de Itens: eventos ──────────────────── */

    $(document).on('click', '.vitrine-ic-item-header', function (e) {
        if ($(e.target).closest('.vitrine-ic-item-remove, .vitrine-ic-drag').length) return;
        var $item = $(this).closest('.vitrine-ic-item');
        var idx = $item.data('ic-idx');
        if (itemcarouselExpandedIdx === idx) {
            itemcarouselExpandedIdx = null;
            $item.removeClass('is-expanded');
        } else {
            $('.vitrine-ic-item').removeClass('is-expanded');
            itemcarouselExpandedIdx = idx;
            $item.addClass('is-expanded');
            setTimeout(initItemcarouselMCE, 30);
        }
    });

    $(document).on('click', '.vitrine-ic-tab', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var tab = $(this).data('ic-tab');
        var $item = $(this).closest('.vitrine-ic-item');
        $item.find('.vitrine-ic-tab').removeClass('is-active');
        $(this).addClass('is-active');
        $item.find('.vitrine-ic-tab-panel').removeClass('is-active');
        $item.find('.vitrine-ic-tab-panel[data-ic-panel="' + tab + '"]').addClass('is-active');
        if (tab === 'content') {
            setTimeout(initItemcarouselMCE, 30);
        }
    });

    $(document).on('click', '.vitrine-ic-type-btn', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var type = $(this).data('ic-type');
        var $item = $(this).closest('.vitrine-ic-item');
        var idx = $item.data('ic-idx');
        var item = findItemById(selectedId);
        if (!item || !item.settings.items || !item.settings.items[idx]) return;

        item.settings.items[idx].item_type = type;
        $item.find('.vitrine-ic-type-btn').removeClass('is-active');
        $(this).addClass('is-active');
        $item.find('.vitrine-ic-field[data-ic-prop="item_type"]').val(type);
        $item.find('.vitrine-ic-type-panel--image').toggle(type === 'image');
        $item.find('.vitrine-ic-type-panel--icon').toggle(type === 'icon');

        updateItemcarouselPreview();
        updateItemcarouselItemHeader(idx);
    });

    $(document).on('input change', '.vitrine-ic-field', function () {
        syncItemcarouselField($(this));
    });

    $(document).on('change', '.vitrine-ic-field-check', function () {
        syncItemcarouselField($(this));
    });

    $(document).on('click', '.vitrine-ic-add-btn', function (e) {
        e.preventDefault();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemcarousel') return;
        if (!item.settings.items) item.settings.items = [];
        item.settings.items.push(defaultItemcarouselItem());
        itemcarouselExpandedIdx = item.settings.items.length - 1;
        renderSettings();
        updateItemcarouselPreview();
    });

    $(document).on('click', '.vitrine-ic-item-remove', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item || item.type !== 'itemcarousel') return;
        var $card = $(this).closest('.vitrine-ic-item');
        var idx = $card.data('ic-idx');
        if (!item.settings.items) return;
        item.settings.items.splice(idx, 1);
        if (itemcarouselExpandedIdx === idx) {
            itemcarouselExpandedIdx = item.settings.items.length ? Math.min(idx, item.settings.items.length - 1) : null;
        } else if (itemcarouselExpandedIdx !== null && itemcarouselExpandedIdx > idx) {
            itemcarouselExpandedIdx--;
        }
        renderSettings();
        updateItemcarouselPreview();
    });

    $(document).on('click', '.vitrine-ic-select-image', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedId) return;
        var $card = $(this).closest('.vitrine-ic-item');
        var idx = $card.data('ic-idx');

        var frame = wp.media({ title: 'Imagem do slide', multiple: false, library: { type: 'image' } });
        frame.on('select', function () {
            var url = frame.state().get('selection').first().toJSON().url;
            var item = findItemById(selectedId);
            if (!item || !item.settings.items || !item.settings.items[idx]) return;
            item.settings.items[idx].image = url;
            renderSettings();
            itemcarouselExpandedIdx = idx;
            updateItemcarouselPreview();
        });
        frame.open();
    });

    $(document).on('click', '.vitrine-ic-remove-image', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $card = $(this).closest('.vitrine-ic-item');
        var idx = $card.data('ic-idx');
        var item = findItemById(selectedId);
        if (!item || !item.settings.items || !item.settings.items[idx]) return;
        item.settings.items[idx].image = '';
        renderSettings();
        itemcarouselExpandedIdx = idx;
        updateItemcarouselPreview();
    });

    $(document).on('click', '.vitrine-ic-open-picker', function (e) {
        e.stopPropagation();
        var $picker = $(this).closest('.vitrine-ic-icon-field').find('.vitrine-icon-picker');
        $('.vitrine-icon-picker').not($picker).hide();
        var willShow = !$picker.is(':visible');
        $picker.toggle();
        if (willShow) {
            ensureIconGrid($picker, 'dashicons');
        }
    });

    $(document).on('click', '.vitrine-ic-remove-icon', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var $card = $(this).closest('.vitrine-ic-item');
        var idx = $card.data('ic-idx');
        var item = findItemById(selectedId);
        if (!item || !item.settings.items || !item.settings.items[idx]) return;
        item.settings.items[idx].icon = '';
        renderSettings();
        itemcarouselExpandedIdx = idx;
        updateItemcarouselPreview();
    });

    // Adicionar item ao toggle
    $(document).on('click', '.vitrine-toggle-add-item', function (e) {
        e.preventDefault();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        if (!item.settings.items) item.settings.items = [];
        item.settings.items.push({ title: '', content: '' });

        toggleExpandedIdx = item.settings.items.length - 1;
        renderSettings();
        renderCanvas();
    });

    // Remover item do toggle
    $(document).on('click', '.vitrine-toggle-remove-item', function (e) {
        e.preventDefault();
        e.stopPropagation();
        if (!selectedId) return;
        var item = findItemById(selectedId);
        if (!item) return;

        var idx = $(this).closest('.vitrine-toggle-editor-item').data('toggle-idx');
        if (item.settings.items) {
            item.settings.items.splice(idx, 1);
        }

        if (toggleExpandedIdx === idx) {
            toggleExpandedIdx = null;
        } else if (toggleExpandedIdx !== null && toggleExpandedIdx > idx) {
            toggleExpandedIdx--;
        }

        renderSettings();
        renderCanvas();
    });

    /* ──────────────────── Salvar Layout ──────────────────── */

    function getBuilderPageSettings() {
        return {
            show_header:   pageSettings.show_header,
            show_footer:   pageSettings.show_footer,
            page_bg_color: pageSettings.page_bg_color,
            custom_css:    pageSettings.custom_css || ''
        };
    }

    function saveLayout(onDone) {
        flushActiveSettingsPanel();

        return $.ajax({
            url: vitrineData.ajaxUrl,
            method: 'POST',
            data: {
                action:        'vitrine_save_layout',
                nonce:         vitrineData.nonce,
                post_id:       vitrineData.postId,
                layout:        JSON.stringify(layout),
                page_settings: JSON.stringify(getBuilderPageSettings())
            }
        }).done(function (res) {
            if (typeof onDone === 'function') {
                onDone(!!(res && res.success), res);
            }
        }).fail(function () {
            if (typeof onDone === 'function') {
                onDone(false);
            }
        });
    }

    function getVitrinePreviewUrl() {
        return vitrineData.viewUrl || vitrineData.previewUrl || '';
    }

    function openVitrinePreview() {
        var url = getVitrinePreviewUrl();
        if (!url) {
            window.alert('Salve a vitrine como rascunho antes de visualizar.');
            return;
        }

        var $previewBtn = $('#vitrine-preview-btn');
        $previewBtn.prop('disabled', true);

        saveLayout(function (success) {
            $previewBtn.prop('disabled', false);
            if (success) {
                window.open(url, '_blank', 'noopener,noreferrer');
            } else {
                window.alert('Não foi possível salvar o layout antes de visualizar.');
            }
        });
    }

    function syncHeroFieldsBeforeSave() {
        if (!$('#vitrine-hero-description').length) {
            return;
        }
        $('#vitrine-hero-description-input').val($('#vitrine-hero-description').html());
    }

    function mountWordPressPublishActions() {
        var $submit = $('#submitdiv');

        if (!$submit.length) {
            return !!$('#vitrine-topbar-actions #publish, #vitrine-topbar-actions #save-post').length;
        }

        $submit.find('input[type="hidden"]').each(function () {
            var name = this.name;
            if (!name) {
                return;
            }
            if (!$('#post input[type="hidden"][name="' + name + '"]').length) {
                $(this).appendTo('#post');
            }
        });

        $submit.find('#post-status-select').appendTo('#post');

        if (!$('#vitrine-topbar-actions #publish, #vitrine-topbar-actions #save-post').length) {
            var $actions = $('#vitrine-topbar-actions');
            var $inside  = $submit.find('.inside').first();
            if ($actions.length) {
                if ($inside.length) {
                    $inside.children().appendTo($actions);
                } else {
                    $submit.children().appendTo($actions);
                }
            }
        }

        $submit.remove();
        return true;
    }

    function ensurePublishActionsMounted(retry) {
        retry = retry || 0;
        if (mountWordPressPublishActions()) {
            return;
        }
        if (retry < 15) {
            setTimeout(function () {
                ensurePublishActionsMounted(retry + 1);
            }, 100);
        }
    }

    function bindLayoutSaveOnPostSubmit() {
        var bypassLayoutSave = false;
        var submitIntent     = null;

        $(document).on('click', '#publish', function () {
            submitIntent = {
                name:  this.name || 'publish',
                value: this.value || '1'
            };
            $('#hidden_post_status').val('publish');
            if ($('#post_status').length) {
                $('#post_status').val('publish');
            }
        });

        $(document).on('click', '#save-post', function () {
            submitIntent = {
                name:  this.name || 'save',
                value: this.value || '1'
            };
            if (this.name === 'save' || this.id === 'save-post') {
                $('#hidden_post_status').val('draft');
                if ($('#post_status').length) {
                    $('#post_status').val('draft');
                }
            }
        });

        $('#post').on('submit', function (e) {
            if (bypassLayoutSave) {
                return true;
            }

            e.preventDefault();

            var $form     = $(this);
            var $publish  = $('#publish');
            var $savePost = $('#save-post');
            var $spinner  = $('#vitrine-topbar-actions .spinner').first();

            syncHeroFieldsBeforeSave();

            $publish.prop('disabled', true);
            if ($savePost.length) {
                $savePost.prop('disabled', true);
            }
            if ($spinner.length) {
                $spinner.addClass('is-active');
            }

            saveLayout(function (success) {
                if (!success) {
                    $publish.prop('disabled', false);
                    if ($savePost.length) {
                        $savePost.prop('disabled', false);
                    }
                    if ($spinner.length) {
                        $spinner.removeClass('is-active');
                    }
                    window.alert('Erro ao salvar o layout. Tente novamente.');
                    return;
                }

                $('#vitrine-submit-intent').remove();
                if (submitIntent && submitIntent.name) {
                    $('<input>', {
                        type:  'hidden',
                        id:    'vitrine-submit-intent',
                        name:  submitIntent.name,
                        value: submitIntent.value
                    }).appendTo($form);
                }

                bypassLayoutSave = true;
                $form[0].submit();
            });

            return false;
        });
    }

    /* ──────────────────── Inicialização ──────────────────── */

    $(function () {
        ensurePublishActionsMounted();
        bindLayoutSaveOnPostSubmit();

        $(document).on('input change', '#vitrine-page-custom-css', function () {
            pageSettings.custom_css = $(this).val();
        });

        $('#vitrine-opt-header').on('change', function () {
            pageSettings.show_header = this.checked ? '1' : '0';
        });
        $('#vitrine-opt-footer').on('change', function () {
            pageSettings.show_footer = this.checked ? '1' : '0';
        });
        $('#vitrine-opt-bg').on('input change', function () {
            pageSettings.page_bg_color = $(this).val();
            if (!$('#vitrine-opt-bg-clear').length) {
                $(this).after('<button type="button" id="vitrine-opt-bg-clear" class="button button-small" title="Limpar cor">&#10005;</button>');
            }
        });
        $(document).on('click', '#vitrine-opt-bg-clear', function () {
            pageSettings.page_bg_color = '';
            $('#vitrine-opt-bg').val('#ffffff');
            $(this).remove();
        });

        // Garante placeholder traduzido na busca de elementos.
        var $elSearch = $('#vitrine-element-search');
        if ($elSearch.length) {
            var searchPh = t('ui.search_elements', 'Search element...');
            $elSearch.attr('placeholder', searchPh).attr('aria-label', searchPh);
        }

        renderCanvas();
        renderSettings();

        // Fechar sidebar de configurações (deseleciona elemento)
        $(document).on('click', '#vitrine-settings-sidebar-close', function () {
            selectedId = null;
            renderSettings();
            renderCanvas();
        });

        // Template picker
        $(document).on('click', '.vitrine-template-picker__item', function (e) {
            e.preventDefault();
            var idx = $(this).data('tpl-idx');
            applyTemplate(idx);
        });

        $(document).on('click', '#vitrine-clone-lang-btn', function (e) {
            e.preventDefault();
            var lang = $('#vitrine-clone-lang').val();
            if (!lang || !vitrineData.polylang || !vitrineData.polylang.active) {
                return;
            }
            var $btn = $(this).prop('disabled', true);
            flushActiveSettingsPanel();
            saveLayout(function (success) {
                if (!success) {
                    $btn.prop('disabled', false);
                    window.alert(t('ui.clone_error', 'Could not clone vitrine.'));
                    return;
                }
                $.post(vitrineData.ajaxUrl, {
                    action:  'vitrine_clone_to_language',
                    nonce:   vitrineData.nonce,
                    post_id: vitrineData.postId,
                    lang:    lang
                }).done(function (res) {
                    if (res && res.success && res.data && res.data.edit_url) {
                        window.location.href = res.data.edit_url;
                        return;
                    }
                    var msg = (res && res.data && res.data.message) ? res.data.message : t('ui.clone_error', 'Could not clone vitrine.');
                    window.alert(msg);
                    $btn.prop('disabled', false);
                }).fail(function () {
                    window.alert(t('ui.clone_error', 'Could not clone vitrine.'));
                    $btn.prop('disabled', false);
                });
            });
        });

        $(document).on('click', '#vitrine-preview-btn', function (e) {
            e.preventDefault();
            openVitrinePreview();
        });
    });

})(jQuery);
