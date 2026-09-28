<?php
/**
 * St. Monica Junior School CMS - Website SEO Management
 */
if (!defined('CMS_ROOT')) define('CMS_ROOT', dirname(__DIR__));

require_once CMS_ROOT . '/includes/auth.php';
require_module('seo');

$pageTitle = 'Website SEO Management';
$activeMenu = 'seo';

// Defined managed pages
$pageDefinitions = [
    'homepage'     => ['name' => 'Homepage', 'file' => 'index.html', 'icon' => 'home'],
    'about'        => ['name' => 'About Us', 'file' => 'about.html', 'icon' => 'info'],
    'academics'    => ['name' => 'Academics', 'file' => 'academics.html', 'icon' => 'school'],
    'curriculum'   => ['name' => 'Curriculum', 'file' => 'curriculum.html', 'icon' => 'menu_book'],
    'cocurricular' => ['name' => 'Co-curricular', 'file' => 'cocurricular.html', 'icon' => 'sports_soccer'],
    'gallery'      => ['name' => 'Media Gallery', 'file' => 'gallery.html', 'icon' => 'photo_library'],
    'contact'      => ['name' => 'Contact & Admissions', 'file' => 'contact.html', 'icon' => 'contact_phone']
];

$activePageKey = trim($_GET['page'] ?? 'homepage');
if (!array_key_exists($activePageKey, $pageDefinitions)) {
    $activePageKey = 'homepage';
}

// Fetch all SEO rows
$allSettings = Database::fetchAll("SELECT * FROM `seo_settings`");
$settingsMap = [];
foreach ($allSettings as $row) {
    $settingsMap[$row['page_key']] = $row;
}

$currentSeo = $settingsMap[$activePageKey] ?? [
    'page_key'         => $activePageKey,
    'page_title'       => $pageDefinitions[$activePageKey]['name'],
    'meta_title'       => $pageDefinitions[$activePageKey]['name'] . ' — St. Monica Junior School',
    'meta_description' => 'Official website of St. Monica Junior School Kasanje.',
    'meta_keywords'    => '',
    'og_title'         => '',
    'og_description'   => '',
    'og_image'         => '',
    'canonical_url'    => 'https://stmonicakasanje.ac.ug/' . ($activePageKey === 'homepage' ? '' : $pageDefinitions[$activePageKey]['file'])
];

include CMS_ROOT . '/includes/header.php';
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 brand-font tracking-tight">Website SEO & Social Meta</h1>
        <p class="text-sm text-slate-500 mt-1">Optimize how St. Monica Junior School appears on Google search results, WhatsApp, and social networks.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="<?= admin_url('media/') ?>" class="cms-btn cms-btn-outline text-xs">
            <span class="material-symbols-outlined text-[16px]">photo_library</span>
            <span>Browse Media Assets</span>
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Page Navigation Sidebar -->
    <div class="lg:col-span-1">
        <div class="cms-card p-2 sticky top-24">
            <div class="p-3 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Public Pages</span>
            </div>
            <nav class="mt-2 space-y-1">
                <?php foreach ($pageDefinitions as $key => $meta): 
                    $isActive = ($key === $activePageKey);
                    $hasConfig = isset($settingsMap[$key]);
                ?>
                    <a href="<?= admin_url('seo/?page=' . $key) ?>" 
                       class="flex items-center justify-between px-3 py-2.5 rounded-lg text-xs font-semibold transition <?= $isActive ? 'bg-[#1e2a4a] text-white shadow-sm' : 'text-slate-700 hover:bg-slate-100' ?>">
                        <div class="flex items-center gap-2.5 truncate">
                            <span class="material-symbols-outlined text-[18px] <?= $isActive ? 'text-amber-400' : 'text-slate-400' ?>"><?= $meta['icon'] ?></span>
                            <span class="truncate"><?= e($meta['name']) ?></span>
                        </div>
                        <?php if ($hasConfig): ?>
                            <span class="w-2 h-2 rounded-full <?= $isActive ? 'bg-emerald-400' : 'bg-emerald-500' ?>" title="Configured"></span>
                        <?php else: ?>
                            <span class="w-2 h-2 rounded-full bg-slate-300" title="Default"></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="p-3 mt-4 bg-slate-50 rounded-lg text-[11px] text-slate-500">
                <span class="font-semibold text-slate-700 block mb-1">SEO Best Practice</span>
                Keep meta titles under 60 characters and descriptions between 140–160 characters for optimal display on search result pages.
            </div>
        </div>
    </div>

    <!-- Main SEO Editor & Live Previews -->
    <div class="lg:col-span-3 space-y-6">
        <!-- Form Section -->
        <div class="cms-card p-6 sm:p-8">
            <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 brand-font">Editing SEO: <?= e($pageDefinitions[$activePageKey]['name']) ?></h2>
                    <p class="text-xs text-slate-500 font-mono mt-0.5"><?= e($pageDefinitions[$activePageKey]['file']) ?></p>
                </div>
                <?php if (!empty($currentSeo['updated_at'])): ?>
                    <span class="text-[11px] text-slate-400">Last updated <?= date('M j, Y g:i A', strtotime($currentSeo['updated_at'])) ?></span>
                <?php endif; ?>
            </div>

            <form method="POST" action="<?= admin_url('seo/update.php') ?>" class="space-y-6" id="seoForm">
                <?= csrf_field() ?>
                <input type="hidden" name="page_key" value="<?= e($activePageKey) ?>">
                <input type="hidden" name="page_title" value="<?= e($pageDefinitions[$activePageKey]['name']) ?>">

                <!-- Meta Search Engine Tags -->
                <div>
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Google Search Snippet Tags</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700" for="metaTitle">
                                    Meta Title <span class="text-red-600">*</span>
                                </label>
                                <span class="text-[11px] text-slate-400 font-mono" id="titleCounter">0 / 60 chars</span>
                            </div>
                            <input type="text" id="metaTitle" name="meta_title" 
                                   value="<?= e($currentSeo['meta_title'] ?? '') ?>" 
                                   class="cms-input" required maxlength="120"
                                   placeholder="e.g. St. Monica Junior School Kasanje — Quality Primary & Nursery Education">
                            <p class="text-[11px] text-slate-400 mt-1">Appears as the clickable headline in search engine results.</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="text-xs font-semibold text-slate-700" for="metaDesc">
                                    Meta Description <span class="text-red-600">*</span>
                                </label>
                                <span class="text-[11px] text-slate-400 font-mono" id="descCounter">0 / 160 chars</span>
                            </div>
                            <textarea id="metaDesc" name="meta_description" rows="3" class="cms-input resize-none" 
                                      required maxlength="300"
                                      placeholder="Provide an enticing 150-160 character summary of the page..."><?= e($currentSeo['meta_description'] ?? '') ?></textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Brief summary shown beneath the title on Google search.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="metaKeywords">
                                Meta Keywords <span class="text-slate-400 font-normal">(Comma-separated)</span>
                            </label>
                            <input type="text" id="metaKeywords" name="meta_keywords" 
                                   value="<?= e($currentSeo['meta_keywords'] ?? '') ?>" 
                                   class="cms-input" placeholder="e.g. primary school, wakiso, kasanje, nursery education, PLE results">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="canonicalUrl">
                                Canonical URL
                            </label>
                            <input type="url" id="canonicalUrl" name="canonical_url" 
                                   value="<?= e($currentSeo['canonical_url'] ?? '') ?>" 
                                   class="cms-input font-mono text-xs" placeholder="https://stmonicakasanje.ac.ug/...">
                            <p class="text-[11px] text-slate-400 mt-1">Defines the authoritative URL to avoid duplicate content penalties.</p>
                        </div>
                    </div>
                </div>

                <!-- Social & Open Graph (WhatsApp, Facebook, Twitter) -->
                <div class="pt-6 border-t border-slate-100">
                    <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Open Graph & Social Sharing (WhatsApp, Facebook, LinkedIn)</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="ogTitle">
                                Social Share Title (OG Title)
                            </label>
                            <input type="text" id="ogTitle" name="og_title" 
                                   value="<?= e($currentSeo['og_title'] ?? '') ?>" 
                                   class="cms-input" placeholder="Defaults to Meta Title if left blank">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="ogDesc">
                                Social Share Description (OG Description)
                            </label>
                            <textarea id="ogDesc" name="og_description" rows="2" class="cms-input resize-none" 
                                      placeholder="Defaults to Meta Description if left blank"><?= e($currentSeo['og_description'] ?? '') ?></textarea>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5" for="ogImage">
                                Social Preview Image URL (OG Image)
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="ogImage" name="og_image" 
                                       value="<?= e($currentSeo['og_image'] ?? '') ?>" 
                                       class="cms-input font-mono text-xs" 
                                       placeholder="assets/images/... or https://...">
                                <button type="button" class="cms-btn cms-btn-outline text-xs whitespace-nowrap"
                                        data-media-picker-for="ogImage"
                                        data-endpoint="<?= e(admin_url('media/picker.php')) ?>"
                                        data-csrf="<?= e(csrf_token()) ?>">
                                    <span class="material-symbols-outlined text-[16px]">photo_library</span>
                                    <span>Choose from Library</span>
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1">Recommended dimension: 1200 x 630 pixels (JPG/PNG).</p>
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <button type="submit" class="cms-btn cms-btn-accent text-xs">
                        <span class="material-symbols-outlined text-[16px]">save</span>
                        <span>Save SEO Configuration</span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Live Visual Previews -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Google Search Preview -->
            <div class="cms-card p-5">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-blue-600">search</span>
                        Google Search Preview
                    </h4>
                    <span class="text-[10px] bg-blue-50 text-blue-700 px-2 py-0.5 rounded font-semibold">Desktop</span>
                </div>
                
                <div class="p-4 bg-white border border-slate-200 rounded-lg shadow-sm font-sans">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="w-5 h-5 rounded-full bg-slate-100 flex items-center justify-center text-[10px] text-slate-500 font-bold">SM</div>
                        <div class="text-xs text-slate-700 leading-none">
                            <span class="font-medium text-slate-800">St. Monica Junior School</span>
                            <span class="text-slate-400 text-[11px] block font-mono mt-0.5" id="previewUrl">https://stmonicakasanje.ac.ug</span>
                        </div>
                    </div>
                    <div class="text-[#1a0dab] hover:underline text-base font-medium leading-snug cursor-pointer mt-1" id="previewTitle">
                        <?= e($currentSeo['meta_title'] ?? 'St. Monica Junior School Kasanje') ?>
                    </div>
                    <div class="text-xs text-[#4d5156] mt-1 leading-relaxed" id="previewDesc">
                        <?= e($currentSeo['meta_description'] ?? 'School description here...') ?>
                    </div>
                </div>
            </div>

            <!-- Social Card Preview -->
            <div class="cms-card p-5">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                        <span class="material-symbols-outlined text-[16px] text-emerald-600">share</span>
                        Social Card Preview
                    </h4>
                    <span class="text-[10px] bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded font-semibold">WhatsApp / Facebook</span>
                </div>

                <div class="border border-slate-200 rounded-lg overflow-hidden bg-slate-50 shadow-sm">
                    <div class="h-32 bg-slate-200 flex items-center justify-center text-slate-400 relative overflow-hidden" id="previewOgImageBox">
                        <span class="material-symbols-outlined text-4xl text-slate-300">image</span>
                    </div>
                    <div class="p-3 bg-white border-t border-slate-100">
                        <div class="text-[10px] uppercase font-bold text-slate-400 font-mono tracking-wider">stmonicakasanje.ac.ug</div>
                        <div class="text-sm font-bold text-slate-900 leading-snug mt-1 truncate" id="previewOgTitle">
                            <?= e(!empty($currentSeo['og_title']) ? $currentSeo['og_title'] : $currentSeo['meta_title']) ?>
                        </div>
                        <div class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed" id="previewOgDesc">
                            <?= e(!empty($currentSeo['og_description']) ? $currentSeo['og_description'] : $currentSeo['meta_description']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const metaTitleInput = document.getElementById('metaTitle');
    const metaDescInput  = document.getElementById('metaDesc');
    const canonicalInput = document.getElementById('canonicalUrl');
    const ogTitleInput   = document.getElementById('ogTitle');
    const ogDescInput    = document.getElementById('ogDesc');
    const ogImageInput   = document.getElementById('ogImage');

    const titleCounter   = document.getElementById('titleCounter');
    const descCounter    = document.getElementById('descCounter');

    const previewTitle   = document.getElementById('previewTitle');
    const previewDesc    = document.getElementById('previewDesc');
    const previewUrl     = document.getElementById('previewUrl');
    const previewOgTitle = document.getElementById('previewOgTitle');
    const previewOgDesc  = document.getElementById('previewOgDesc');
    const previewOgBox   = document.getElementById('previewOgImageBox');

    function updateCounters() {
        const titleLen = metaTitleInput.value.length;
        const descLen = metaDescInput.value.length;

        titleCounter.textContent = titleLen + ' / 60 chars';
        titleCounter.className = 'text-[11px] font-mono ' + (titleLen > 60 ? 'text-amber-600 font-bold' : 'text-slate-400');

        descCounter.textContent = descLen + ' / 160 chars';
        descCounter.className = 'text-[11px] font-mono ' + (descLen > 160 ? 'text-amber-600 font-bold' : 'text-slate-400');
    }

    function syncPreviews() {
        const titleVal = metaTitleInput.value.trim() || 'St. Monica Junior School Kasanje';
        const descVal  = metaDescInput.value.trim() || 'Provide a compelling description for search engine visibility.';
        const urlVal   = canonicalInput.value.trim() || 'https://stmonicakasanje.ac.ug';
        const ogTVal   = ogTitleInput.value.trim() || titleVal;
        const ogDVal   = ogDescInput.value.trim() || descVal;
        const imgVal   = ogImageInput.value.trim();

        previewTitle.textContent = titleVal;
        previewDesc.textContent  = descVal;
        previewUrl.textContent   = urlVal;
        previewOgTitle.textContent = ogTVal;
        previewOgDesc.textContent  = ogDVal;

        if (imgVal) {
            const resolvedSrc = imgVal.startsWith('http') ? imgVal : ('../../' + imgVal.replace(/^\/+/, ''));
            previewOgBox.innerHTML = `<img src="${resolvedSrc}" class="w-full h-full object-cover" onerror="this.parentElement.innerHTML='<span class=\\'material-symbols-outlined text-4xl text-slate-300\\'>broken_image</span>'">`;
        } else {
            previewOgBox.innerHTML = `<span class="material-symbols-outlined text-4xl text-slate-300">image</span>`;
        }
    }

    [metaTitleInput, metaDescInput, canonicalInput, ogTitleInput, ogDescInput, ogImageInput].forEach(el => {
        if (el) {
            el.addEventListener('input', () => {
                updateCounters();
                syncPreviews();
            });
        }
    });

    updateCounters();
    syncPreviews();
});
</script>

<?php include CMS_ROOT . '/includes/footer.php'; ?>
