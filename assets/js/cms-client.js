/**
 * St. Monica Junior School - Dynamic CMS Client Integrator
 * Fetches published data from the PHP backend API and seamlessly updates the DOM.
 * Gracefully preserves existing markup if offline or during API loading.
 */

(function () {
    'use strict';

    // Detect API Base URL relative to current location
    function getApiBaseUrl() {
        // If served over HTTP/HTTPS, compute relative path to ADMIN/api
        const loc = window.location.pathname;
        const rootIdx = loc.lastIndexOf('/');
        const pathPrefix = (rootIdx !== -1) ? loc.substring(0, rootIdx + 1) : '/';
        return pathPrefix + 'ADMIN/api';
    }

    const API_BASE = getApiBaseUrl();

    // Helper fetch with timeout
    async function fetchApi(endpoint) {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 4000);
            const res = await fetch(API_BASE + endpoint, {
                signal: controller.signal,
                headers: { 'Accept': 'application/json' }
            });
            clearTimeout(timeoutId);
            if (!res.ok) return null;
            const json = await res.json();
            return json && json.success ? json.data : null;
        } catch (e) {
            // Silently fallback to existing static HTML
            return null;
        }
    }

    // Helper: Escapes HTML to avoid XSS
    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // ==========================================================
    // 1. GLOBAL CONTACT & FOOTER UPDATER
    // ==========================================================
    // Shows a full-page maintenance notice to public visitors when enabled in
    // ADMIN > Settings > Website. The ADMIN/ panel itself is a separate app and
    // is never affected by this - administrators keep working normally there.
    function applyMaintenanceMode(contact) {
        if (!contact || !contact.maintenance_mode) return false;

        const message = contact.maintenance_message || 'We are currently performing scheduled maintenance. Please check back shortly.';
        const schoolName = contact.school_name || 'St. Monica Junior School';

        document.documentElement.innerHTML = '';
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;background:#0f172a;color:#fff;font-family:sans-serif;text-align:center;padding:24px;';
        overlay.innerHTML = `
            <div style="max-width:480px;">
                <div style="width:64px;height:64px;border-radius:50%;background:rgba(217,54,51,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:28px;">🛠️</div>
                <h1 style="font-size:22px;font-weight:700;margin-bottom:12px;">${escapeHtml(schoolName)}</h1>
                <p style="font-size:15px;line-height:1.6;color:#cbd5e1;">${escapeHtml(message)}</p>
                <p style="font-size:12px;color:#64748b;margin-top:24px;">We appreciate your patience and will be back online soon.</p>
            </div>
        `;
        document.body ? document.body.appendChild(overlay) : document.documentElement.appendChild(overlay);
        return true;
    }

    async function updateGlobalContact() {
        const contact = await fetchApi('/contact/');
        if (!contact) return;

        if (applyMaintenanceMode(contact)) return;

        // Phone numbers in footer and contact items
        const phoneItems = document.querySelectorAll('.footer-contact-item');
        phoneItems.forEach(item => {
            const text = item.textContent;
            if (text.includes('+256') || text.includes('Phone') || item.querySelector('.material-symbols-outlined')?.textContent.trim() === 'phone') {
                const phoneContainer = item.querySelector('.flex.flex-col') || item.querySelector('div') || item;
                if (contact.alternative_phone) {
                    phoneContainer.innerHTML = `
                        <span class="text-[16px] leading-[24px]">${escapeHtml(contact.phone)}</span>
                        <span class="text-[16px] leading-[24px]">${escapeHtml(contact.alternative_phone)}</span>
                    `;
                } else {
                    phoneContainer.innerHTML = `<span class="text-[16px] leading-[24px]">${escapeHtml(contact.phone)}</span>`;
                }
            } else if (text.includes('@') || item.querySelector('.material-symbols-outlined')?.textContent.trim() === 'mail') {
                const mailSpan = item.querySelector('span:not(.material-symbols-outlined)') || item;
                mailSpan.textContent = contact.email;
            } else if (item.querySelector('.material-symbols-outlined')?.textContent.trim() === 'location_on') {
                const locSpan = item.querySelector('span:not(.material-symbols-outlined)') || item;
                locSpan.textContent = contact.village || contact.address;
            }
        });

        // Contact Page specific cards
        const contactCards = document.querySelectorAll('.contact-info-card');
        contactCards.forEach(card => {
            const heading = card.querySelector('h3')?.textContent.trim().toLowerCase();
            const p = card.querySelector('p');
            if (!p) return;

            if (heading?.includes('visit') || heading?.includes('location')) {
                p.innerHTML = `${escapeHtml(contact.address)}<br/>${escapeHtml(contact.district)}<br/>${escapeHtml(contact.country || 'Uganda')}`;
            } else if (heading?.includes('call') || heading?.includes('phone')) {
                p.innerHTML = `${escapeHtml(contact.phone)}<br/>${escapeHtml(contact.alternative_phone || '')}`;
            } else if (heading?.includes('email')) {
                p.innerHTML = `${escapeHtml(contact.email)}<br/>${escapeHtml(contact.admissions_email || '')}`;
            }
        });

        // Map iframes
        if (contact.map_url) {
            const mapIframes = document.querySelectorAll('iframe[src*="google.com/maps"]');
            mapIframes.forEach(iframe => {
                iframe.src = contact.map_url;
            });
        }
    }

    // ==========================================================
    // 2. HOMEPAGE SPECIFIC DYNAMIC INTEGRATION
    // ==========================================================
    async function updateHomepage() {
        const homeData = await fetchApi('/homepage/');
        if (!homeData) return;

        // A. Hero Slides
        if (homeData.hero_slides && homeData.hero_slides.length > 0) {
            const sliderList = document.querySelector('.carousel .list');
            const thumbnailList = document.querySelector('.carousel .thumbnail');

            if (sliderList && thumbnailList) {
                let slidesHtml = '';
                let thumbnailsHtml = '';

                homeData.hero_slides.forEach(slide => {
                    const btnText = slide.button_text || 'Know More';
                    const btnUrl = slide.button_url || 'about.html';

                    slidesHtml += `
                    <div class="item">
                        <img src="${escapeHtml(slide.image)}" alt="${escapeHtml(slide.title)}">
                        <div class="content">
                            <div class="title">
                                <h1>${escapeHtml(slide.title)}</h1>
                            </div>
                            <div class="des">
                                ${escapeHtml(slide.description || '')}
                            </div>
                            <div class="buttons">
                                <a href="${escapeHtml(btnUrl)}" class="bg-[#d93633] cursor-pointer text-white px-7 py-3 rounded-full hover:bg-[#b51a1e] transition duration-300 inline-flex items-center book-appointment">
                                    <span>${escapeHtml(btnText)}</span>
                                    <i class="fas fa-arrow-right ml-2 text-sm"></i>
                                </a>
                            </div>
                        </div>
                    </div>`;

                    thumbnailsHtml += `
                    <div class="item">
                        <img src="${escapeHtml(slide.image)}" alt="${escapeHtml(slide.title)}">
                    </div>`;
                });

                sliderList.innerHTML = slidesHtml;
                thumbnailList.innerHTML = thumbnailsHtml;
            }
        }

        // B. Director's Welcome Message
        if (homeData.director_message) {
            const dir = homeData.director_message;
            const dirImg = document.querySelector('.director-image img');
            const dirName = document.querySelector('.director-image p.font-semibold, .director-image p.text-\\[24px\\]');
            const dirTitle = document.querySelector('.director-image p.opacity-90');
            const dirHeading = document.querySelector('.director-text h2');
            const dirText = document.querySelector('.director-text p');

            if (dirImg && dir.image) dirImg.src = dir.image;
            if (dirName && dir.author_name) dirName.textContent = dir.author_name;
            if (dirTitle && dir.author_title) dirTitle.textContent = dir.author_title;
            if (dirHeading && dir.title) {
                dirHeading.innerHTML = `<span class="w-12 h-1 bg-[#d93633] block"></span> ${escapeHtml(dir.title)}`;
            }
            if (dirText && dir.content) {
                // Convert double linebreaks to paragraphs
                const paras = dir.content.split(/\n\s*\n/).map(p => escapeHtml(p.trim())).filter(Boolean);
                dirText.innerHTML = paras.join('<br><br>');
            }
        }

        // C. Why Choose Us Section Header & Intro
        const whyIntro = homeData.why_choose_us && homeData.why_choose_us.intro;
        if (whyIntro) {
            const whyHeading = document.querySelector('.why-choose-text > h2');
            const whyText = document.querySelector('.why-choose-text > p');
            if (whyHeading && whyIntro.title) whyHeading.textContent = whyIntro.title;
            if (whyText && whyIntro.content) whyText.textContent = whyIntro.content;
        }

        // C2. Why Choose Us Highlights
        if (homeData.why_choose_us && homeData.why_choose_us.items && homeData.why_choose_us.items.length > 0) {
            const container = document.querySelector('.why-choose-text .space-y-4');
            if (container) {
                let itemsHtml = '';
                homeData.why_choose_us.items.forEach(item => {
                    const bgClass = item.color_theme === 'red' ? 'bg-[#d93633]' : 'bg-[#1e2a4a]';
                    itemsHtml += `
                    <div class="why-item opacity-100 flex items-start gap-4">
                        <div class="w-12 h-12 ${bgClass} rounded-lg flex items-center justify-center flex-shrink-0">
                            <i class="${escapeHtml(item.icon)} text-white text-[22px]"></i>
                        </div>
                        <div>
                            <h3 class="text-[18px] leading-[28px] font-semibold text-[#1e2a4a] mb-1">${escapeHtml(item.title)}</h3>
                            <p class="text-[16px] leading-[24px] text-[#45464e]">${escapeHtml(item.description)}</p>
                        </div>
                    </div>`;
                });
                container.innerHTML = itemsHtml;
            }
        }

        // D. Statistics Counters
        // Rebuilt from the admin list so added, removed and reordered counters all show
        if (homeData.statistics && homeData.statistics.length > 0) {
            const firstItem = document.querySelector('.counter-item');
            const counterGrid = firstItem && firstItem.parentElement;
            if (counterGrid) {
                const count = homeData.statistics.length;
                const colClasses = {
                    1: 'grid-cols-1',
                    2: 'grid-cols-1 md:grid-cols-2',
                    3: 'grid-cols-1 md:grid-cols-3',
                    4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
                };
                counterGrid.className = `grid gap-8 ${colClasses[count] || 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3'}`;

                counterGrid.innerHTML = homeData.statistics.map(stat => {
                    const value = parseInt(stat.number_value, 10) || 0;
                    return `
                    <div class="text-center counter-item" style="opacity: 1;">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-white/20 rounded-full mb-4 animate-icon">
                            <span class="material-symbols-outlined text-white text-[40px]">${escapeHtml(stat.icon || 'verified')}</span>
                        </div>
                        <h3 class="text-[48px] leading-[56px] font-bold text-white mb-2">
                            <span class="counter" data-target="${value}">0</span>${escapeHtml(stat.suffix || '')}
                        </h3>
                        <p class="text-[18px] leading-[28px] text-white/90 font-semibold">${escapeHtml(stat.label)}</p>
                    </div>`;
                }).join('');

                // Count up when scrolled into view (defined in index.html); without it, show final values
                if (typeof window.observeCounterItems === 'function') {
                    window.observeCounterItems();
                } else {
                    counterGrid.querySelectorAll('.counter').forEach(el => { el.textContent = el.dataset.target; });
                }
            }
        }

        // E. Featured Staff Cards on Homepage
        if (homeData.featured_staff && homeData.featured_staff.length > 0) {
            // "Our Dedicated Team" grid, tagged in index.html (style classes change too often to target)
            const staffGrid = document.querySelector('[data-home="team-grid"]');
            if (staffGrid) {
                let staffHtml = '';
                homeData.featured_staff.forEach(staff => {
                    staffHtml += `
                    <div class="vision-card opacity-100 bg-[#f3f3f3] rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all duration-500 hover:scale-105 hover:-translate-y-2 group">
                        <div class="h-72 overflow-hidden">
                            <img class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                                src="${escapeHtml(staff.photo || 'assets/imgz/headteacher.webp')}" alt="${escapeHtml(staff.name)}" />
                        </div>
                        <div class="p-6">
                            <h3 class="text-[20px] leading-[28px] font-semibold text-[#1e2a4a] mb-1">${escapeHtml(staff.name)}</h3>
                            <p class="text-[14px] leading-[20px] text-[#d93633] font-semibold mb-3">${escapeHtml(staff.position)}</p>
                            <p class="text-[16px] leading-[24px] text-[#45464e]">${escapeHtml(staff.biography || '')}</p>
                        </div>
                    </div>`;
                });
                staffGrid.innerHTML = staffHtml;

                // Fit the grid to the number of featured staff so 1 or 2 cards don't leave empty columns
                const staffCount = homeData.featured_staff.length;
                const staffLayouts = {
                    1: 'grid grid-cols-1 gap-8 max-w-sm mx-auto',
                    2: 'grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto',
                };
                staffGrid.className = staffLayouts[staffCount] || 'grid grid-cols-1 md:grid-cols-3 gap-8';
            }
        }

        // F. News & Events on Homepage: every live post in one row that scales to fit
        if (homeData.latest_news && homeData.latest_news.length > 0) {
            const newsRow = document.querySelector('[data-home="news-row"]');
            if (newsRow) {
                const count = homeData.latest_news.length;
                const typeLabels = { news: 'News', event: 'Event', sports: 'Sports' };

                newsRow.innerHTML = homeData.latest_news.map(item => {
                    const articleUrl = escapeHtml(`article.html?slug=${encodeURIComponent(item.slug)}`);
                    const dateValue = String(item.event_date || item.created_at || '').replace(' ', 'T');
                    const date = new Date(dateValue);
                    const dateFormatted = isNaN(date) ? '' : date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });

                    return `
                    <article class="news-card opacity-100 bg-white rounded-xl overflow-hidden shadow-sm transition-all duration-300 group">
                        <a href="${articleUrl}" class="news-card-media" tabindex="-1" aria-hidden="true">
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                src="${escapeHtml(item.featured_image || 'assets/imgz/3 graduants.webp')}" alt="${escapeHtml(item.title)}" loading="lazy" />
                        </a>
                        <div class="news-card-body">
                            <div class="news-card-top">
                                <span class="text-[12px] leading-[16px] font-bold text-[#d93633] bg-[#d93633]/10 px-3 py-1 rounded-full uppercase">${escapeHtml(typeLabels[item.type] || item.type)}</span>
                                ${dateFormatted ? `<span class="news-card-date"><span class="material-symbols-outlined text-[16px]">calendar_today</span>${escapeHtml(dateFormatted)}</span>` : ''}
                            </div>
                            <h3 class="news-card-title"><a href="${articleUrl}" class="hover:text-[#d93633] transition-colors">${escapeHtml(item.title)}</a></h3>
                            ${item.excerpt ? `<p class="news-card-excerpt">${escapeHtml(item.excerpt)}</p>` : ''}
                            <a class="news-card-more" href="${articleUrl}" aria-label="Read more: ${escapeHtml(item.title)}">Read More →</a>
                        </div>
                    </article>`;
                }).join('');

                // Tighter spacing as more cards share the row
                newsRow.style.setProperty('--news-gap', count <= 4 ? '24px' : count <= 6 ? '18px' : '14px');
                initNewsRowArrows(newsRow);
            }
        }
    }

    // Prev/next arrows for the News row, shown only when cards overflow (too many to fit at a readable size)
    function initNewsRowArrows(row) {
        const wrap = row.closest('.news-row-wrap');
        if (!wrap) return;
        const prev = wrap.querySelector('[data-news-prev]');
        const next = wrap.querySelector('[data-news-next]');
        if (!prev || !next) return;

        const update = () => {
            const overflowing = row.scrollWidth > row.clientWidth + 2;
            prev.hidden = next.hidden = !overflowing;
            prev.disabled = row.scrollLeft <= 2;
            next.disabled = row.scrollLeft + row.clientWidth >= row.scrollWidth - 2;
        };
        const step = () => {
            const card = row.querySelector('.news-card');
            return card ? card.getBoundingClientRect().width + parseFloat(getComputedStyle(row).columnGap || 0) : row.clientWidth;
        };

        if (!wrap.dataset.arrowsReady) {
            prev.addEventListener('click', () => row.scrollBy({ left: -step(), behavior: 'smooth' }));
            next.addEventListener('click', () => row.scrollBy({ left: step(), behavior: 'smooth' }));
            row.addEventListener('scroll', update, { passive: true });
            window.addEventListener('resize', update);
            wrap.dataset.arrowsReady = '1';
        }
        update();
        // Re-check once images have loaded and fonts have settled
        window.addEventListener('load', update, { once: true });
    }

    // ==========================================================
    // 3. ABOUT US PAGE SPECIFIC INTEGRATION
    // ==========================================================
    async function updateAboutPage() {
        const aboutData = await fetchApi('/about/');
        if (!aboutData) return;

        // Text sections edited under About Us in the admin panel (targets tagged data-about="..." in about.html)
        const sections = aboutData.sections || {};
        const aboutEl = (key) => document.querySelector(`[data-about="${key}"]`);
        const toParagraphs = (text) => (text || '').split(/\r?\n\s*\r?\n/).map(p => p.trim()).filter(Boolean);
        const setText = (key, value) => {
            const el = aboutEl(key);
            if (el && value && value.trim()) el.textContent = value.trim();
        };

        // Page hero banner: headline, intro text, photo
        if (sections.hero) {
            setText('hero-title', sections.hero.title);
            setText('hero-text', sections.hero.content);
            const heroImg = aboutEl('hero-image');
            if (heroImg && sections.hero.image) {
                heroImg.src = sections.hero.image;
                heroImg.alt = sections.hero.image_alt || sections.hero.title || heroImg.alt;
            }
        }

        // History: title + paragraphs
        if (sections.history) {
            setText('history-title', sections.history.title);
            const historyBody = aboutEl('history-body');
            const paras = toParagraphs(sections.history.content);
            if (historyBody && paras.length) {
                historyBody.innerHTML = paras.map(p => `<p>${escapeHtml(p)}</p>`).join('');
            }
        }

        // Vision & Mission cards
        if (sections.vision) setText('vision', sections.vision.content);
        if (sections.mission) setText('mission', sections.mission.content);

        // Motto: first paragraph is the motto itself, anything after a blank line is the supporting text
        if (sections.motto) {
            const [motto, ...rest] = toParagraphs(sections.motto.content);
            setText('motto', motto);
            if (rest.length) setText('motto-text', rest.join(' '));
        }

        // Support St.Monica (donation) heading & intro
        if (sections.support_cta) {
            setText('support-title', sections.support_cta.title);
            setText('support-text', sections.support_cta.content);
        }

        // Core Values
        if (aboutData.core_values && aboutData.core_values.length > 0) {
            const valuesList = document.querySelector('.grid-cols-1.md\\:grid-cols-3 ul.space-y-2');
            if (valuesList) {
                valuesList.innerHTML = aboutData.core_values.map(v => `
                    <li class="flex items-start gap-2 transition-transform duration-300 hover:translate-x-1">
                        <span class="material-symbols-outlined text-[#d93633] text-[20px] flex-shrink-0 mt-1">check_circle</span>
                        <span>${escapeHtml(v.title)}</span>
                    </li>
                `).join('');
            }
        }

        // Facilities ("What We Provide")
        if (aboutData.facilities && aboutData.facilities.length > 0) {
            const facilitiesGrid = document.querySelector('#administrators')?.previousElementSibling?.querySelector('.grid');
            if (facilitiesGrid) {
                facilitiesGrid.innerHTML = aboutData.facilities.map(f => `
                    <div class="bg-white rounded-[0.5rem] overflow-hidden shadow-sm hover:shadow-md transition-shadow border border-[#c6c6cf]/20 group">
                        <div class="h-48 relative overflow-hidden">
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="${escapeHtml(f.image || 'assets/imgz/school building.webp')}" alt="${escapeHtml(f.title)}"/>
                        </div>
                        <div class="p-6">
                            <h3 class="text-[24px] leading-[32px] font-semibold text-[#1e2a4a] mb-2">${escapeHtml(f.title)}</h3>
                            <p class="text-[16px] leading-[24px] text-[#2C2C2C]">${escapeHtml(f.description)}</p>
                        </div>
                    </div>
                `).join('');
            }
        }

        // Administrators
        // "Meet Our Staff": every published staff member (featured or not)
        if (aboutData.administrators && aboutData.administrators.length > 0) {
            const adminGrid = document.querySelector('#administrators .grid');
            if (adminGrid) {
                const staffLayouts = {
                    1: 'grid grid-cols-1 gap-8 max-w-sm mx-auto',
                    2: 'grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto',
                };
                adminGrid.className = staffLayouts[aboutData.administrators.length] || 'grid grid-cols-1 md:grid-cols-3 gap-8';
                adminGrid.innerHTML = aboutData.administrators.map(a => `
                    <div class="bg-[#f3f3f3] rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow group">
                        <div class="h-80 overflow-hidden">
                            <img class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="${escapeHtml(a.photo || 'assets/imgz/headteacher.webp')}" alt="${escapeHtml(a.name)}"/>
                        </div>
                        <div class="p-6">
                            <h3 class="text-[20px] leading-[28px] font-semibold text-[#1e2a4a] mb-2">${escapeHtml(a.name)}</h3>
                            <p class="text-[14px] leading-[20px] text-[#d93633] font-semibold mb-3 uppercase tracking-wide">${escapeHtml(a.position)}</p>
                            <p class="text-[16px] leading-[24px] text-[#2C2C2C] mb-4">${escapeHtml(a.biography || '')}</p>
                            ${a.email ? `
                            <div class="flex items-center gap-2 text-[#45464e] text-[14px]">
                                <span class="material-symbols-outlined text-[18px]">mail</span>
                                <span>${escapeHtml(a.email)}</span>
                            </div>` : ''}
                        </div>
                    </div>
                `).join('');
            }
        }
    }

    // ==========================================================
    // 4. GALLERY PAGE SPECIFIC INTEGRATION
    // ==========================================================
    async function updateGalleryPage() {
        const galleryItems = await fetchApi('/gallery/');
        if (!galleryItems || galleryItems.length === 0) return;

        const grid = document.querySelector('.masonry-grid');
        if (!grid) return;

        function renderGallery(filterCategory = 'All Photos') {
            const filtered = (filterCategory === 'All Photos')
                ? galleryItems
                : galleryItems.filter(item => item.category === filterCategory);

            // Caption and description are optional in the admin panel. The hover caption shows the
            // title (or the description when there is no title); photos with neither show no caption.
            grid.innerHTML = filtered.map(img => {
                const title = (img.title || '').trim();
                const description = (img.description || '').trim();
                const caption = title || description;
                return `
                <div class="masonry-item gallery-item rounded-[0.5rem] overflow-hidden bg-[#f3f3f3] border border-[#e2e2e2]"
                     tabindex="0" role="button" aria-label="${escapeHtml(caption ? 'View photo: ' + caption : 'View photo')}"
                     data-caption="${escapeHtml(title)}" data-description="${escapeHtml(description)}">
                    <img class="w-full h-auto object-cover" src="${escapeHtml(img.file_path)}" alt="${escapeHtml(caption || img.category || 'School photo')}" loading="lazy"/>
                    ${caption ? `<span class="gallery-caption">${escapeHtml(caption)}</span>` : ''}
                </div>`;
            }).join('');
        }

        // Render initial
        renderGallery('All Photos');

        // Hook up filter buttons
        const filterBtns = document.querySelectorAll('button');
        filterBtns.forEach(btn => {
            const text = btn.textContent.trim();
            if (['All Photos', 'Administration', 'Academics', 'Co-curricular Activities', 'Special Events'].includes(text)) {
                btn.addEventListener('click', () => {
                    // Update active button styling
                    filterBtns.forEach(b => {
                        b.classList.remove('bg-[#1e2a4a]', 'text-white', 'shadow-md');
                        b.classList.add('border-2', 'border-[#c6c6cf]', 'text-[#45464e]');
                    });
                    btn.classList.add('bg-[#1e2a4a]', 'text-white', 'shadow-md');
                    btn.classList.remove('border-2', 'border-[#c6c6cf]', 'text-[#45464e]');
                    renderGallery(text);
                });
            }
        });
    }

    // ==========================================================
    // 5. TESTIMONIALS DYNAMIC INTEGRATION (Homepage)
    // ==========================================================
    async function updateTestimonials() {
        const testimonials = await fetchApi('/testimonials/');
        if (!testimonials || testimonials.length === 0) return;

        const container = document.querySelector('.testimonial-header + .grid') || 
                          document.querySelector('.testimonial-card')?.parentElement;
        if (!container) return;

        container.innerHTML = testimonials.map(t => {
            const starCount = Math.min(5, Math.max(1, parseInt(t.rating || 5, 10)));
            const starsHtml = Array.from({ length: starCount })
                .map(() => '<span class="material-symbols-outlined text-[#d93633]" style="font-variation-settings: \'FILL\' 1;">star</span>')
                .join('');

            const avatarHtml = t.photo 
                ? `<img src="${escapeHtml(t.photo)}" alt="${escapeHtml(t.name)}" class="w-12 h-12 rounded-full object-cover shadow-sm">`
                : `<div class="w-12 h-12 bg-[#1e2a4a] rounded-full flex items-center justify-center text-white font-semibold text-sm">${escapeHtml(t.initials || 'P')}</div>`;

            return `
            <div class="testimonial-card bg-[#f3f3f3] rounded-xl p-8 relative transition-all duration-700 hover:scale-105 hover:-translate-y-2 shadow-sm">
                <div class="mb-6">
                    <div class="flex gap-1 mb-4">
                        ${starsHtml}
                    </div>
                    <p class="text-[16px] leading-[24px] text-[#2C2C2C] mb-6">
                        "${escapeHtml(t.content)}"
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    ${avatarHtml}
                    <div>
                        <p class="text-[16px] leading-[24px] font-semibold text-[#1e2a4a]">${escapeHtml(t.name)}</p>
                        <p class="text-[14px] leading-[20px] text-[#45464e]">${escapeHtml(t.child_info || t.role || 'Parent')}</p>
                    </div>
                </div>
            </div>`;
        }).join('');
    }

    // ==========================================================
    // 6. ADMISSION PROCEDURE & INFO INTEGRATION (About Us)
    // ==========================================================
    async function updateAdmissionInfo() {
        const data = await fetchApi('/admission-info/');
        if (!data || !data.procedure_steps || data.procedure_steps.length === 0) return;

        const headings = Array.from(document.querySelectorAll('h2'));
        const joinHeading = headings.find(h => h.textContent.toLowerCase().includes('how to join us'));
        if (!joinHeading) return;

        const joinSection = joinHeading.closest('section');
        if (!joinSection) return;

        const stepsGrid = joinSection.querySelector('.grid');
        if (!stepsGrid) return;

        stepsGrid.innerHTML = data.procedure_steps.map((step, idx) => `
            <div class="join-step-card bg-white rounded-xl p-8 shadow-sm border border-[#c6c6cf]/30 relative">
                <div class="join-step-number absolute -top-4 -left-4 w-12 h-12 bg-[#d93633] rounded-full flex items-center justify-center text-white font-bold text-[20px] shadow-lg">
                    ${idx + 1}
                </div>
                <div class="join-step-icon w-16 h-16 bg-[#1e2a4a] rounded-full flex items-center justify-center mb-6 mx-auto text-white">
                    <span class="material-symbols-outlined text-white text-[32px]">${escapeHtml(step.icon || 'help_center')}</span>
                </div>
                <h3 class="text-[20px] leading-[28px] font-semibold text-[#1e2a4a] mb-3 text-center">${escapeHtml(step.title)}</h3>
                <p class="text-[16px] leading-[24px] text-[#2C2C2C] text-center">
                    ${escapeHtml(step.content)}
                </p>
            </div>
        `).join('');
    }

    // ==========================================================
    // 7. WEBSITE SEO & SOCIAL META DYNAMIC INJECTION
    // ==========================================================
    function setMetaTag(name, content, isProperty = false) {
        if (!content) return;
        const selector = isProperty ? `meta[property="${name}"]` : `meta[name="${name}"]`;
        let meta = document.querySelector(selector);
        if (!meta) {
            meta = document.createElement('meta');
            if (isProperty) {
                meta.setAttribute('property', name);
            } else {
                meta.setAttribute('name', name);
            }
            document.head.appendChild(meta);
        }
        meta.setAttribute('content', content);
    }

    async function updatePageSeo() {
        const path = window.location.pathname.toLowerCase();
        let pageKey = 'homepage';

        if (path.includes('about.html')) pageKey = 'about';
        else if (path.includes('academics.html')) pageKey = 'academics';
        else if (path.includes('curriculum.html')) pageKey = 'curriculum';
        else if (path.includes('cocurricular.html')) pageKey = 'cocurricular';
        else if (path.includes('gallery.html')) pageKey = 'gallery';
        else if (path.includes('contact.html')) pageKey = 'contact';

        const seo = await fetchApi('/seo/?page=' + encodeURIComponent(pageKey));
        if (!seo) return;

        if (seo.meta_title) {
            document.title = seo.meta_title;
        }

        if (seo.meta_description) {
            setMetaTag('description', seo.meta_description);
        }

        if (seo.meta_keywords) {
            setMetaTag('keywords', seo.meta_keywords);
        }

        if (seo.canonical_url) {
            let canonical = document.querySelector('link[rel="canonical"]');
            if (!canonical) {
                canonical = document.createElement('link');
                canonical.setAttribute('rel', 'canonical');
                document.head.appendChild(canonical);
            }
            canonical.setAttribute('href', seo.canonical_url);
            setMetaTag('og:url', seo.canonical_url, true);
        }

        if (seo.og_title) setMetaTag('og:title', seo.og_title, true);
        if (seo.og_description) setMetaTag('og:description', seo.og_description, true);
        if (seo.og_image) setMetaTag('og:image', seo.og_image, true);
    }

    // ==========================================================
    // INITIALIZATION DISPATCHER
    // ==========================================================
    // ==========================================================
    // NEWSLETTER SIGN-UP (footer "Newsletter" box on every page)
    // ==========================================================
    function ensureSweetAlert() {
        if (typeof Swal !== 'undefined') {
            return Promise.resolve(window.Swal);
        }
        return new Promise((resolve) => {
            const existingScript = document.querySelector('script[src*="sweetalert2"]');
            if (existingScript) {
                if (typeof Swal !== 'undefined') return resolve(window.Swal);
                existingScript.addEventListener('load', () => resolve(window.Swal));
                existingScript.addEventListener('error', () => resolve(null));
                // Fallback timeout in case load event already fired
                setTimeout(() => resolve(window.Swal || null), 1500);
            } else {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                script.onload = () => resolve(window.Swal);
                script.onerror = () => resolve(null);
                document.head.appendChild(script);
            }
        });
    }

    async function showNewsletterAlert(isSuccess, title, text) {
        const swal = await ensureSweetAlert();
        if (swal) {
            swal.fire({
                icon: isSuccess ? 'success' : 'error',
                title: title,
                text: text,
                timer: 3000,
                timerProgressBar: true,
                showConfirmButton: false,
                customClass: {
                    popup: 'swal-newsletter-popup',
                    title: 'swal-newsletter-title',
                    htmlContainer: 'swal-newsletter-html'
                }
            });
        }
    }

    function initNewsletterForms() {
        document.querySelectorAll('footer form').forEach(form => {
            const input = form.querySelector('input[type="email"]');
            const button = form.querySelector('button');
            if (!input || !button || form.dataset.newsletterReady) return;
            form.dataset.newsletterReady = '1';

            input.name = 'email';
            input.required = true;
            input.autocomplete = 'email';
            input.setAttribute('aria-label', 'Your email address');
            button.type = 'submit';

            // Hidden honeypot field: people never see it, spam bots fill it in
            const trap = document.createElement('input');
            trap.type = 'text';
            trap.name = 'website';
            trap.tabIndex = -1;
            trap.autocomplete = 'off';
            trap.setAttribute('aria-hidden', 'true');
            trap.style.cssText = 'position:absolute;left:-9999px;width:1px;height:1px;opacity:0;';
            form.appendChild(trap);

            const message = document.createElement('p');
            message.className = 'text-[13px] leading-[18px] font-semibold hidden';
            message.setAttribute('role', 'status');
            message.setAttribute('aria-live', 'polite');
            form.appendChild(message);

            let hideTimeout = null;
            const show = (text, ok) => {
                if (hideTimeout) clearTimeout(hideTimeout);
                message.textContent = text;
                message.classList.remove('hidden', 'text-emerald-300', 'text-red-300');
                message.classList.add(ok ? 'text-emerald-300' : 'text-red-300');
                hideTimeout = setTimeout(() => {
                    message.classList.add('hidden');
                }, 3000);
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const email = input.value.trim();
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    show('Please enter a valid email address.', false);
                    showNewsletterAlert(false, 'Invalid Email Address', 'Please provide a valid email address (e.g., parent@example.com).');
                    input.focus();
                    return;
                }

                const originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = '<span class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span> Subscribing...';
                try {
                    const res = await fetch(API_BASE + '/newsletter/subscribe.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                        body: JSON.stringify({
                            email,
                            website: trap.value,
                            source: (window.location.pathname.split('/').pop() || 'index.html')
                        })
                    });
                    const json = await res.json().catch(() => null);
                    if (json && json.success) {
                        show(json.message, true);
                        input.value = '';
                        const title = (json.data && json.data.status === 'resubscribed')
                            ? 'Welcome Back!'
                            : ((json.data && json.data.status === 'already') ? 'Already Subscribed' : 'Subscribed Successfully!');
                        showNewsletterAlert(true, title, json.message || 'Thank you for subscribing to our school newsletter!');
                    } else {
                        const errMsg = (json && json.message) || 'Could not subscribe right now. Please try again.';
                        show(errMsg, false);
                        showNewsletterAlert(false, 'Subscription Failed', errMsg);
                    }
                } catch (err) {
                    const netErr = 'Could not connect to the server. Please check your internet connection and try again.';
                    show(netErr, false);
                    showNewsletterAlert(false, 'Connection Error', netErr);
                } finally {
                    button.disabled = false;
                    button.innerHTML = originalHtml;
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const path = window.location.pathname.toLowerCase();

        initNewsletterForms();

        // Dynamic SEO tags across all public pages (article.html sets its own from the article)
        if (!path.includes('article.html')) {
            updatePageSeo();
        }

        // Always update global contact details & footers
        updateGlobalContact();

        if (path.endsWith('index.html') || path.endsWith('/') || path.endsWith('st.monica/') || path === '') {
            updateHomepage();
            updateTestimonials();
        } else if (path.includes('about.html')) {
            updateAboutPage();
            updateAdmissionInfo();
        } else if (path.includes('gallery.html')) {
            updateGalleryPage();
        }
    });
})();
