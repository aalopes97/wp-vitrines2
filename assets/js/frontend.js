/**
 * Vitrine Builder – Frontend JS
 */
(function () {
    'use strict';

    document.addEventListener('click', function (e) {
        var header = e.target.closest('.vitrine-toggle-header');
        if (!header) return;

        var item = header.closest('.vitrine-toggle-item');
        if (!item) return;

        item.classList.toggle('is-open');
    });

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function observeRevealElements(selector, onVisible) {
        var elements = document.querySelectorAll(selector);
        if (!elements.length) return;

        var reduceMotion = prefersReducedMotion();

        elements.forEach(function (el) {
            if (reduceMotion) {
                el.classList.add('is-reveal-ready', 'is-reveal-visible');
                if (typeof onVisible === 'function') onVisible(el, true);
                return;
            }

            el.classList.add('is-reveal-ready');

            if (!('IntersectionObserver' in window)) {
                if (typeof onVisible === 'function') onVisible(el, false);
                return;
            }

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    observer.unobserve(entry.target);
                    if (typeof onVisible === 'function') onVisible(entry.target, false);
                });
            }, {
                threshold: 0.1,
                rootMargin: '0px 0px -5% 0px'
            });

            observer.observe(el);
        });
    }

    /* ── Containers: aparecem ao rolar ── */

    function getContainerChildBlocks(container) {
        var inner = container.querySelector('.vitrine-container-inner');
        if (inner) {
            return inner.querySelectorAll(':scope > .vitrine-block');
        }
        return container.querySelectorAll(':scope > .vitrine-block');
    }

    function playContainerReveal(container) {
        if (container.classList.contains('is-reveal-visible')) return;

        container.classList.add('is-reveal-visible');

        var children = getContainerChildBlocks(container);
        var baseDelay = 180;
        var step = 90;

        children.forEach(function (child, index) {
            child.classList.add('vitrine-reveal-child');
            child.style.setProperty('--reveal-delay', (baseDelay + index * step) + 'ms');
        });
    }

    function initContainerReveal() {
        observeRevealElements('.vitrine-container--reveal', playContainerReveal);
    }

    /* ── Aranha Grade: animação de entrada ── */

    function getA3CardZone(card) {
        if (card.closest('.vitrine-a3-band--top')) return 'top';
        if (card.closest('.vitrine-a3-band--bottom')) return 'bottom';
        if (card.closest('.vitrine-a3-side--left')) return 'left';
        if (card.closest('.vitrine-a3-side--right')) return 'right';

        var frame = card.closest('.vitrine-a3-frame--inline');
        if (frame) {
            var cards = frame.querySelectorAll('.vitrine-a3-cell--card');
            if (cards[0] === card) return 'left';
            if (cards[1] === card) return 'right';
        }

        return 'bottom';
    }

    function playAranha3Entry(el) {
        if (el.classList.contains('is-a3-visible')) return;

        el.classList.add('is-a3-visible');

        var image = el.querySelector('.vitrine-a3-cell--image');
        if (image) {
            requestAnimationFrame(function () {
                image.classList.add('is-a3-in');
            });
        }

        var cards = el.querySelectorAll('.vitrine-a3-cell--card');
        var baseDelay = 460;
        var step = 65;

        cards.forEach(function (card, index) {
            card.setAttribute('data-a3-from', getA3CardZone(card));
            card.style.setProperty('--a3-delay', (baseDelay + index * step) + 'ms');
            card.classList.add('is-a3-in');
        });
    }

    function initAranha3Animations() {
        var blocks = document.querySelectorAll('.vitrine-el-aranha3.vitrine-a3--animate');
        if (!blocks.length) return;

        var reduceMotion = prefersReducedMotion();

        blocks.forEach(function (el) {
            if (reduceMotion) {
                el.classList.add('is-a3-ready', 'is-a3-visible');
                return;
            }

            el.classList.add('is-a3-ready');

            if (!('IntersectionObserver' in window)) {
                playAranha3Entry(el);
                return;
            }

            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;
                    observer.unobserve(entry.target);
                    playAranha3Entry(entry.target);
                });
            }, {
                threshold: 0.12,
                rootMargin: '0px 0px -4% 0px'
            });

            observer.observe(el);
        });
    }

    function initScrollAnimations() {
        initContainerReveal();
        initAranha3Animations();
        initAranha2Compact();
        initItemCarousels();
    }

    /* ── Aranha Circular: colapsa para ícone + título se encavalar ── */

    function rectsOverlap(a, b, gap) {
        return !(
            a.right + gap < b.left ||
            b.right + gap < a.left ||
            a.bottom + gap < b.top ||
            b.bottom + gap < a.top
        );
    }

    function aranha2HasOverlap(root, gap) {
        var cards = root.querySelectorAll('.vitrine-aranha2__card');
        if (cards.length < 2) return false;

        var rects = [];
        for (var i = 0; i < cards.length; i++) {
            rects.push(cards[i].getBoundingClientRect());
        }

        for (var x = 0; x < rects.length; x++) {
            for (var y = x + 1; y < rects.length; y++) {
                if (rectsOverlap(rects[x], rects[y], gap)) {
                    return true;
                }
            }
        }
        return false;
    }

    function updateAranha2Compact(root) {
        if (!root || root.getAttribute('data-a2-compact-auto') !== '1') return;

        // No mobile a aranha já vira coluna — não precisa colapsar.
        if (window.matchMedia('(max-width: 768px)').matches) {
            root.classList.remove('is-compact');
            return;
        }

        var stage = root.querySelector('.vitrine-aranha2__stage');
        if (!stage || !stage.querySelector('.vitrine-aranha2__card')) return;

        // Mede no modo expandido.
        root.classList.remove('is-compact');
        // Força reflow antes de medir.
        void root.offsetHeight;

        var gap = 8;
        var overlaps = aranha2HasOverlap(root, gap);
        root.classList.toggle('is-compact', overlaps);
    }

    function initAranha2Compact() {
        var roots = document.querySelectorAll('.vitrine-el-aranha2[data-a2-compact-auto="1"]');
        if (!roots.length) return;

        var timer = null;
        function runAll() {
            roots.forEach(function (root) {
                updateAranha2Compact(root);
            });
        }

        // Após fontes/imagens carregarem e no resize.
        runAll();
        window.addEventListener('load', runAll);
        window.addEventListener('resize', function () {
            if (timer) clearTimeout(timer);
            timer = setTimeout(runAll, 120);
        });

        if ('ResizeObserver' in window) {
            roots.forEach(function (root) {
                var ro = new ResizeObserver(function () {
                    if (timer) clearTimeout(timer);
                    timer = setTimeout(function () {
                        updateAranha2Compact(root);
                    }, 80);
                });
                ro.observe(root);
            });
        }
    }

    function initItemCarousels() {
        var IC_BREAK_TABLET = 900;
        var IC_BREAK_MOBILE = 560;

        document.querySelectorAll('.vitrine-el-itemcarousel').forEach(function (root) {
            if (root.dataset.icReady === '1') return;
            root.dataset.icReady = '1';

            var track = root.querySelector('.vitrine-ic-track');
            var slides = root.querySelectorAll('.vitrine-ic-slide');
            if (!track || !slides.length) return;

            function parseSlidesAttr(key, fallback) {
                var v = parseInt(root.dataset[key] || '', 10);
                if (isNaN(v) || v < 1) {
                    v = parseInt(fallback, 10);
                }
                return Math.max(1, Math.min(4, v));
            }

            var slidesDesktop = parseSlidesAttr('slidesDesktop', root.style.getPropertyValue('--ic-slides-desktop') || '3');
            var slidesTablet = parseSlidesAttr('slidesTablet', root.style.getPropertyValue('--ic-slides-tablet') || '2');
            var slidesMobile = parseSlidesAttr('slidesMobile', root.style.getPropertyValue('--ic-slides-mobile') || '1');

            // Compat: layouts antigos com data-slides único
            if (!root.dataset.slidesDesktop && root.dataset.slides) {
                slidesDesktop = parseSlidesAttr('slides', '3');
            }

            var autoplay = root.dataset.autoplay === '1';
            var autoplaySpeed = parseInt(root.dataset.autoplaySpeed || '5000', 10);
            var index = 0;
            var maxIndex = 0;
            var timer = null;

            function getSlidesPerView() {
                var w = root.offsetWidth;
                if (w <= IC_BREAK_MOBILE) return slidesMobile;
                if (w <= IC_BREAK_TABLET) return slidesTablet;
                return slidesDesktop;
            }

            function update() {
                var spv = getSlidesPerView();
                maxIndex = Math.max(0, slides.length - spv);
                if (index > maxIndex) index = maxIndex;

                var slide = slides[0];
                if (!slide) return;
                var gap = parseFloat(getComputedStyle(track).gap) || 0;
                var slideW = slide.offsetWidth;
                var offset = index * (slideW + gap);
                track.style.transform = 'translateX(' + (-offset) + 'px)';

                root.querySelectorAll('.vitrine-ic-dot').forEach(function (dot) {
                    var dotIdx = parseInt(dot.dataset.index || '0', 10);
                    dot.classList.toggle('is-active', dotIdx === index);
                    dot.hidden = dotIdx > maxIndex;
                });

                var prev = root.querySelector('.vitrine-ic-arrow--prev');
                var next = root.querySelector('.vitrine-ic-arrow--next');
                if (prev) prev.disabled = index <= 0;
                if (next) next.disabled = index >= maxIndex;
            }

            function goTo(i) {
                index = Math.max(0, Math.min(maxIndex, i));
                update();
            }

            function next() {
                goTo(index >= maxIndex ? 0 : index + 1);
            }

            function prev() {
                goTo(index <= 0 ? maxIndex : index - 1);
            }

            function startAutoplay() {
                stopAutoplay();
                if (!autoplay || slides.length <= getSlidesPerView()) return;
                timer = setInterval(next, autoplaySpeed);
            }

            function stopAutoplay() {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            }

            var prevBtn = root.querySelector('.vitrine-ic-arrow--prev');
            var nextBtn = root.querySelector('.vitrine-ic-arrow--next');
            if (prevBtn) prevBtn.addEventListener('click', function () { stopAutoplay(); prev(); startAutoplay(); });
            if (nextBtn) nextBtn.addEventListener('click', function () { stopAutoplay(); next(); startAutoplay(); });

            root.querySelectorAll('.vitrine-ic-dot').forEach(function (dot) {
                dot.addEventListener('click', function () {
                    stopAutoplay();
                    goTo(parseInt(dot.dataset.index || '0', 10));
                    startAutoplay();
                });
            });

            var touchStartX = 0;
            root.addEventListener('touchstart', function (e) {
                if (!e.changedTouches || !e.changedTouches.length) return;
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });
            root.addEventListener('touchend', function (e) {
                if (!e.changedTouches || !e.changedTouches.length) return;
                var dx = e.changedTouches[0].screenX - touchStartX;
                if (Math.abs(dx) < 48) return;
                stopAutoplay();
                if (dx < 0) next();
                else prev();
                startAutoplay();
            }, { passive: true });

            root.addEventListener('mouseenter', stopAutoplay);
            root.addEventListener('mouseleave', startAutoplay);

            window.addEventListener('resize', function () {
                update();
                startAutoplay();
            });
            update();
            startAutoplay();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScrollAnimations);
    } else {
        initScrollAnimations();
    }
})();
