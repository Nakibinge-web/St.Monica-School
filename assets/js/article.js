/**
 * St. Monica Junior School - News & Events Article Page
 * Loads one published article by its slug (article.html?slug=...) from the CMS API,
 * plus a few other recent posts under "More News & Events".
 */
(function () {
    'use strict';

    // API lives next to the site: /<site>/ADMIN/api
    const pagePath = window.location.pathname;
    const API_BASE = pagePath.substring(0, pagePath.lastIndexOf('/') + 1) + 'ADMIN/api';
    const FALLBACK_IMAGE = 'assets/imgz/3 graduants.webp';
    const TYPE_LABELS = { news: 'News', event: 'Event', sports: 'Sports' };

    const $ = (id) => document.getElementById(id);

    async function fetchJson(url) {
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 8000);
        try {
            const res = await fetch(url, { signal: controller.signal, headers: { Accept: 'application/json' } });
            const json = await res.json().catch(() => null);
            return { ok: res.ok && json && json.success, status: res.status, data: json ? json.data : null };
        } catch (e) {
            return { ok: false, status: 0, data: null };
        } finally {
            clearTimeout(timer);
        }
    }

    function formatDate(value, withYear = true) {
        if (!value) return '';
        const date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date)) return '';
        return date.toLocaleDateString('en-GB', withYear
            ? { day: 'numeric', month: 'long', year: 'numeric' }
            : { day: 'numeric', month: 'short' });
    }

    function metaItem(icon, text) {
        const span = document.createElement('span');
        span.className = 'inline-flex items-center gap-1.5';
        span.innerHTML = `<span class="material-symbols-outlined text-[18px]">${icon}</span>`;
        span.appendChild(document.createTextNode(text));
        return span;
    }

    function eventDetail(icon, label, value) {
        const box = document.createElement('div');
        box.className = 'flex items-start gap-3 bg-[#f3f3f3] rounded-xl p-4';
        box.innerHTML = `
            <span class="w-10 h-10 rounded-lg bg-[#d93633] text-white flex items-center justify-center flex-shrink-0">
                <span class="material-symbols-outlined text-[22px]">${icon}</span>
            </span>
            <span class="min-w-0">
                <span class="block text-[12px] font-bold uppercase tracking-wider text-[#76777f]"></span>
                <span class="block text-[16px] font-semibold text-[#1e2a4a] break-words"></span>
            </span>`;
        const [labelEl, valueEl] = box.querySelectorAll('span.block');
        labelEl.textContent = label;
        valueEl.textContent = value;
        return box;
    }

    // Content is sanitized HTML from the admin editor; older posts may be plain text
    function renderContent(content) {
        const container = $('articleContent');
        const html = (content || '').trim();
        if (/<[a-z][\s\S]*>/i.test(html)) {
            container.innerHTML = html;
            container.querySelectorAll('a[href^="http"]').forEach(a => {
                a.target = '_blank';
                a.rel = 'noopener noreferrer';
            });
        } else {
            // Decode entities such as &nbsp; / &amp; the editor saves even without any tags
            const decoder = document.createElement('textarea');
            decoder.innerHTML = html;
            decoder.value.split(/\r?\n\s*\r?\n/).map(p => p.trim()).filter(Boolean).forEach(text => {
                const p = document.createElement('p');
                p.textContent = text;
                container.appendChild(p);
            });
        }
    }

    function setMeta(selector, attr, value) {
        let tag = document.head.querySelector(selector);
        if (!tag) {
            tag = document.createElement('meta');
            const [, key, name] = selector.match(/\[(\w+)="([^"]+)"\]/);
            tag.setAttribute(key, name);
            document.head.appendChild(tag);
        }
        tag.setAttribute(attr, value);
    }

    function showArticle(item) {
        const typeLabel = TYPE_LABELS[item.type] || 'News';
        const published = formatDate(item.published_at || item.created_at);

        document.title = `${item.title} - St. Monica Junior School Kasanje`;
        const summary = item.excerpt || (item.content || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 160);
        if (summary) {
            setMeta('meta[name="description"]', 'content', summary);
            setMeta('meta[property="og:description"]', 'content', summary);
        }
        setMeta('meta[property="og:title"]', 'content', item.title);

        $('articleType').textContent = typeLabel;
        $('articleTitle').textContent = item.title;

        const meta = $('articleMeta');
        if (published) meta.appendChild(metaItem('calendar_today', `Posted ${published}`));
        meta.appendChild(metaItem('sell', typeLabel));

        const image = item.featured_image || FALLBACK_IMAGE;
        $('articleImage').src = image;
        $('articleImage').alt = item.title;
        $('articleFigure').classList.remove('hidden');
        setMeta('meta[property="og:image"]', 'content', new URL(image, window.location.href).href);

        if (item.event_date || item.event_location) {
            const box = $('articleEventBox');
            if (item.event_date) box.appendChild(eventDetail('event', 'Event date', formatDate(item.event_date)));
            if (item.event_location) box.appendChild(eventDetail('location_on', 'Location', item.event_location));
            box.classList.remove('hidden');
        }

        if (item.excerpt) {
            $('articleExcerpt').textContent = item.excerpt;
            $('articleExcerpt').classList.remove('hidden');
        }
        renderContent(item.content);

        $('articleHeaderLoading').classList.add('hidden');
        $('articleBodyLoading').classList.add('hidden');
        $('articleHeader').classList.remove('hidden');
        $('articleBody').classList.remove('hidden');
    }

    function showMissing(title, text) {
        document.title = `${title} - St. Monica Junior School Kasanje`;
        $('articleMissingTitle').textContent = title;
        $('articleMissingText').textContent = text;
        $('articleHeaderLoading').classList.add('hidden');
        $('articleBodyLoading').classList.add('hidden');
        $('articleHeader').classList.remove('hidden');
        $('articleType').classList.add('hidden');
        $('articleTitle').textContent = 'News & Events';
        $('articleMissing').classList.remove('hidden');
    }

    function relatedCard(item) {
        const link = document.createElement('a');
        link.href = `article.html?slug=${encodeURIComponent(item.slug)}`;
        link.className = 'group block bg-white rounded-xl overflow-hidden shadow-sm border border-[#c6c6cf]/30 hover:shadow-md hover:-translate-y-1 transition-all duration-300';
        link.innerHTML = `
            <div class="h-40 overflow-hidden bg-[#eeeeee]">
                <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="" loading="lazy">
            </div>
            <div class="p-5">
                <div class="flex items-center justify-between gap-2 mb-2">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-[#d93633] bg-[#d93633]/10 px-2.5 py-0.5 rounded-full" data-type></span>
                    <span class="text-[13px] text-[#76777f]" data-date></span>
                </div>
                <h3 class="text-[17px] leading-[24px] font-semibold text-[#1e2a4a] group-hover:text-[#d93633] transition-colors" data-title></h3>
            </div>`;
        const img = link.querySelector('img');
        img.src = item.featured_image || FALLBACK_IMAGE;
        img.alt = item.title;
        link.querySelector('[data-type]').textContent = TYPE_LABELS[item.type] || 'News';
        link.querySelector('[data-date]').textContent = formatDate(item.event_date || item.published_at || item.created_at, false);
        link.querySelector('[data-title]').textContent = item.title;
        return link;
    }

    async function loadRelated(currentSlug) {
        const res = await fetchJson(`${API_BASE}/news-events/?limit=4`);
        if (!res.ok || !Array.isArray(res.data)) return;
        const others = res.data.filter(item => item.slug !== currentSlug).slice(0, 3);
        if (!others.length) return;
        const grid = $('relatedGrid');
        others.forEach(item => grid.appendChild(relatedCard(item)));
        $('relatedWrap').classList.remove('hidden');
    }

    function initShare() {
        const btn = $('articleShareBtn');
        const label = $('articleShareLabel');
        btn.addEventListener('click', async () => {
            const url = window.location.href;
            if (navigator.share) {
                try { await navigator.share({ title: document.title, url }); } catch (e) { /* dismissed */ }
                return;
            }
            try {
                await navigator.clipboard.writeText(url);
                label.textContent = 'Link copied!';
            } catch (e) {
                label.textContent = 'Copy the address bar link';
            }
            setTimeout(() => { label.textContent = 'Share'; }, 2500);
        });
    }

    async function init() {
        const slug = new URLSearchParams(window.location.search).get('slug');
        initShare();

        if (!slug) {
            showMissing('Article not found', 'No article was specified. Browse the latest news and events instead.');
            loadRelated('');
            return;
        }

        const res = await fetchJson(`${API_BASE}/news-events/?slug=${encodeURIComponent(slug)}`);
        if (res.ok && res.data) {
            showArticle(res.data);
        } else if (res.status === 404) {
            showMissing('Article not available', 'This article may have been removed, has expired, or is not published yet.');
        } else {
            showMissing('Could not load the article', 'Please check your internet connection and try again.');
        }
        loadRelated(slug);
    }

    document.addEventListener('DOMContentLoaded', init);
})();
