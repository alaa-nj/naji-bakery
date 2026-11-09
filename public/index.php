<?php
require '../includes/db.php';
require '../includes/category_icons.php';

// Get business info
$stmt = db_query("SELECT * FROM business_settings LIMIT 1");
$business = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all categories, in the order set on the admin Categories page
$stmt = db_query("SELECT * FROM categories ORDER BY display_order ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($business['business_name_ar']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/public.css?v=<?= asset_version(dirname(__DIR__) . '/assets/css/public.css') ?>">
</head>
<body>

<?php include '../includes/public_header.php'; ?>

<?php if (empty($categories)): ?>

    <p class="empty-category">القائمة غير متوفرة حالياً.</p>

<?php else: ?>

    <!-- Sticky bar: category chips (scroll sideways on phones) + search -->
    <div class="menu-sticky" id="menuSticky">
        <nav class="category-bar" aria-label="الأقسام">
            <div class="category-bar-track" id="categoryBarTrack">
                <?php foreach ($categories as $i => $category): ?>
                    <a href="#cat-<?= e($category['id']) ?>"
                       class="category-chip<?= $i === 0 ? ' active' : '' ?>"
                       data-category-id="<?= e($category['id']) ?>"
                       <?= $i === 0 ? 'aria-current="true"' : '' ?>>
                        <i class="fa-solid <?= e(category_icon_class($category['icon'] ?? '')) ?>" aria-hidden="true"></i>
                        <span><?= e($category['name_ar']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
        <div class="search-row">
            <div class="search-bar">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input type="text" id="searchInput" placeholder="ابحث عن منتج..." aria-label="ابحث عن منتج">
            </div>
        </div>
    </div>

    <?php foreach ($categories as $category): ?>
    <section class="category-section" id="cat-<?= e($category['id']) ?>" data-category-id="<?= e($category['id']) ?>">
        <h2 class="category-title brand-font"><span class="cat-icon"><i class="fa-solid <?= e(category_icon_class($category['icon'] ?? '')) ?>" aria-hidden="true"></i></span><?= e($category['name_ar']) ?></h2>

        <?php
        // Fetch every item in this category — unavailable ones are still shown,
        // just marked "sold out", rather than disappearing without a trace.
        $items = db_query("SELECT * FROM menu_items WHERE category_id = ? ORDER BY display_order ASC", [$category['id']])->fetchAll(PDO::FETCH_ASSOC);
        ?>

        <?php if (empty($items)): ?>
            <p class="empty-category">لا توجد عناصر متاحة حالياً</p>
        <?php else: ?>
            <div class="menu-list">
                <?php foreach ($items as $item): ?>
                    <?php $itemPhoto = !empty($item['image_path']) ? '../' . $item['image_path'] : (!empty($business['logo_path']) ? '../' . $business['logo_path'] : null); ?>
                    <div class="menu-list-item<?= $item['is_available'] ? '' : ' is-sold-out' ?>"
                         data-search="<?= e(mb_strtolower($item['name_ar'] . ' ' . ($item['description_ar'] ?? ''))) ?>">
                        <?php if ($itemPhoto): ?>
                            <img class="menu-list-photo" src="<?= e($itemPhoto) ?>" alt="" loading="lazy">
                        <?php endif; ?>
                        <div class="menu-list-main">
                            <h3 class="menu-list-name">
                                <?= e($item['name_ar']) ?>
                                <?php if (!$item['is_available']): ?>
                                    <span class="sold-out-tag">غير متوفر</span>
                                <?php endif; ?>
                            </h3>
                            <?php if (!empty($item['description_ar'])): ?>
                                <p class="menu-list-desc"><?= e($item['description_ar']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="menu-list-price">
                            <?php if ($item['currency'] === 'USD'): ?>
                                $ <?= number_format($item['price']) ?>
                            <?php else: ?>
                                <span dir="ltr"><?= number_format($item['price']) ?> L.L.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sticky = document.getElementById('menuSticky');
            var track = document.getElementById('categoryBarTrack');
            var chips = Array.prototype.slice.call(document.querySelectorAll('.category-chip'));
            var sections = Array.prototype.slice.call(document.querySelectorAll('.category-section'));
            var searchInput = document.getElementById('searchInput');

            // Sections stop just below the sticky bar when scrolled to
            function updateStickyHeight() {
                document.documentElement.style.setProperty('--sticky-h', sticky.offsetHeight + 'px');
            }
            updateStickyHeight();
            window.addEventListener('resize', updateStickyHeight);

            // ---------- Highlight the chip of the section being read ----------
            var activeId = chips.length ? chips[0].dataset.categoryId : null;
            var scrollingToSection = false; // while a chip-click scroll runs, don't flicker through sections

            function setActive(categoryId) {
                if (categoryId === activeId) return;
                activeId = categoryId;
                chips.forEach(function (chip) {
                    var isActive = chip.dataset.categoryId === categoryId;
                    chip.classList.toggle('active', isActive);
                    if (isActive) {
                        chip.setAttribute('aria-current', 'true');
                        // keep the active chip visible when the bar scrolls sideways (phones/tablets)
                        if (track.scrollWidth > track.clientWidth) {
                            var c = chip.getBoundingClientRect(), t = track.getBoundingClientRect();
                            track.scrollBy({ left: (c.left + c.width / 2) - (t.left + t.width / 2), behavior: 'smooth' });
                        }
                    } else {
                        chip.removeAttribute('aria-current');
                    }
                });
            }

            function onScroll() {
                if (scrollingToSection) return;
                var line = sticky.getBoundingClientRect().bottom + 12;
                var current = null;
                sections.forEach(function (section) {
                    if (!section.hidden && section.getBoundingClientRect().top <= line) current = section;
                });
                if (!current) current = sections.filter(function (s) { return !s.hidden; })[0];
                if (current) setActive(current.dataset.categoryId);
            }

            var ticking = false;
            window.addEventListener('scroll', function () {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function () { onScroll(); ticking = false; });
            }, { passive: true });

            // ---------- Tap a chip: smooth-scroll to its section ----------
            chips.forEach(function (chip) {
                chip.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (searchInput.value !== '') { // leaving a search shows every section again
                        searchInput.value = '';
                        runSearch();
                    }
                    var section = document.getElementById('cat-' + this.dataset.categoryId);
                    if (!section) return;
                    setActive(this.dataset.categoryId);

                    // Scroll the page so the section starts just below the sticky bar.
                    // Tracking pauses until the scroll really finishes ('scrollend',
                    // with a timer fallback for browsers that don't have it yet).
                    scrollingToSection = true;
                    var target = section.getBoundingClientRect().top + window.pageYOffset - sticky.offsetHeight - 8;
                    var finish = function () {
                        if (!scrollingToSection) return;
                        scrollingToSection = false;
                        window.removeEventListener('scrollend', finish);
                        onScroll();
                    };
                    window.addEventListener('scrollend', finish);
                    setTimeout(finish, 2500);
                    window.scrollTo({ top: Math.max(0, target), behavior: 'smooth' });
                });
            });

            // ---------- Search (looks across every category) ----------
            function runSearch() {
                var query = searchInput.value.trim().toLowerCase();
                var anyResultAtAll = false;

                sections.forEach(function (section) {
                    var anyVisibleHere = false;
                    section.querySelectorAll('.menu-list-item').forEach(function (card) {
                        var match = query === '' || (card.dataset.search || '').indexOf(query) !== -1;
                        card.hidden = !match;
                        if (match) anyVisibleHere = true;
                    });
                    // with no search, every section shows (even empty ones, with their message)
                    section.hidden = query !== '' && !anyVisibleHere;
                    if (anyVisibleHere) anyResultAtAll = true;
                });

                var noResultsEl = document.getElementById('searchNoResults');
                if (query !== '' && !anyResultAtAll) {
                    if (!noResultsEl) {
                        noResultsEl = document.createElement('p');
                        noResultsEl.id = 'searchNoResults';
                        noResultsEl.className = 'empty-category';
                        noResultsEl.textContent = 'لا توجد نتائج مطابقة';
                        sections[0].parentNode.insertBefore(noResultsEl, sections[0]);
                    }
                    noResultsEl.hidden = false;
                } else if (noResultsEl) {
                    noResultsEl.hidden = true;
                }
                onScroll();
            }
            searchInput.addEventListener('input', runSearch);
        });
    </script>

<?php endif; ?>

<?php if (!empty($business['phone']) || !empty($business['address_ar']) || !empty($business['hours_ar']) || !empty($business['instagram_url'])): ?>
<footer class="site-footer">
    <div class="site-footer-info">
        <?php if (!empty($business['hours_ar'])): ?>
            <span><i class="fa-solid fa-clock" aria-hidden="true"></i> <?= e($business['hours_ar']) ?></span>
        <?php endif; ?>
        <?php if (!empty($business['address_ar'])): ?>
            <span><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?= e($business['address_ar']) ?></span>
        <?php endif; ?>
        <?php if (!empty($business['phone'])): ?>
            <span><i class="fa-solid fa-phone" aria-hidden="true"></i> <?= e($business['phone']) ?></span>
        <?php endif; ?>
        <?php if (!empty($business['instagram_url']) && is_web_url($business['instagram_url'])): ?>
            <span><a href="<?= e($business['instagram_url']) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-instagram" aria-hidden="true"></i> انستغرام</a></span>
        <?php endif; ?>
    </div>
</footer>
<?php endif; ?>

<?php if ($whatsappLink !== '' || $telLink !== ''): ?>
<div class="floating-actions">
    <?php if ($whatsappLink !== ''): ?>
        <a href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener" class="floating-btn whatsapp-btn" aria-label="واتساب">
            <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
    <?php if ($telLink !== ''): ?>
        <a href="<?= e($telLink) ?>" class="floating-btn call-btn" aria-label="اتصل للطلب">
            <i class="fa-solid fa-phone" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>
