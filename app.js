(function () {
    'use strict';

    var BASE = window.APP_BASE_URL || '';
    var RECOMMENDED_LIMIT = 6;

    var state = {
        categories: [],
        municipalities: [],
        favoriteMunicipalities: [],
        hotlines: [],
        activeCategory: 'all',
        municipality: '',
        search: '',
        expanded: false,
    };

    var el = {
        chips: document.getElementById('categoryFilters'),
        grid: document.getElementById('hotlineGrid'),
        search: document.getElementById('searchInput'),
        searchButton: document.getElementById('searchButton'),
        municipalitySelect: document.getElementById('municipalitySelect'),
        favoriteForm: document.getElementById('favoriteMunicipalityForm'),
        favoriteSelect: document.getElementById('favoriteMunicipalitySelect'),
        favoriteOptions: document.getElementById('favoriteMunicipalityOptions'),
        favoriteList: document.getElementById('favoriteMunicipalityList'),
        favoriteStatus: document.getElementById('favoriteMunicipalityStatus'),
        favoriteCsrf: document.getElementById('favoriteMunicipalityCsrf'),
        resultsTitleText: document.getElementById('resultsTitleText'),
        resultsCount: document.getElementById('resultsCount'),
        seeAllBtn: document.getElementById('seeAllBtn'),
        modal: document.getElementById('callModal'),
        modalTitle: document.getElementById('callModalTitle'),
        modalNumber: document.getElementById('callModalNumber'),
        modalCta: document.getElementById('callModalCta'),
        modalClose: document.getElementById('callModalClose'),
        shareLocationBtn: document.getElementById('shareLocationBtn'),
        helpDescription: document.getElementById('helpDescription'),
        locationStatus: document.getElementById('locationStatus'),
    };

    // This file also loads (harmlessly) on pages without the directory markup.
    if (!el.grid) return;

    var PHONE_ICON = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.362 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.338 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>';

    function fetchJSON(url) {
        return fetch(url).then(function (res) { return res.json(); }).then(function (json) {
            if (!json.success) throw new Error('Request failed: ' + url);
            return json.data;
        });
    }

    function escapeHTML(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function badgeClassFor(slug) {
        var known = ['medical', 'fire', 'police', 'others'];
        return known.indexOf(slug) !== -1 ? 'badge--' + slug : '';
    }

    function debounce(fn, wait) {
        var timer;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () { fn.apply(null, args); }, wait);
        };
    }

    function renderChips() {
        var chips = [{ slug: 'all', display_name: 'All' }].concat(state.categories);
        el.chips.innerHTML = '';
        chips.forEach(function (cat) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'chip' + (state.activeCategory === cat.slug ? ' is-active' : '');
            chip.textContent = cat.display_name;
            chip.addEventListener('click', function () {
                state.activeCategory = cat.slug;
                state.expanded = false;
                renderChips();
                renderResults();
            });
            el.chips.appendChild(chip);
        });
    }

    function renderMunicipalityOptions() {
        var current = el.municipalitySelect.value;
        el.municipalitySelect.innerHTML = '<option value="">All Cities/Municipalities</option>';
        state.municipalities.forEach(function (name) {
            var opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            el.municipalitySelect.appendChild(opt);

        });
        el.municipalitySelect.value = current;
        renderFavoriteMunicipalityOptions();
    }

    function renderFavoriteMunicipalityOptions() {
        if (!el.favoriteOptions) return;
        el.favoriteOptions.innerHTML = '';
        state.municipalities.forEach(function (name) {
            if (isFavoriteMunicipality(name)) return;
            var option = document.createElement('option');
            option.value = name;
            el.favoriteOptions.appendChild(option);
        });
    }

    function renderFavoriteMunicipalities() {
        if (!el.favoriteList) return;
        el.favoriteList.innerHTML = '';
        renderFavoriteMunicipalityOptions();
        if (!state.favoriteMunicipalities.length) {
            el.favoriteList.innerHTML = '<p class="favorite-municipalities__empty">No priority cities/municipalities saved yet.</p>';
            return;
        }
        state.favoriteMunicipalities.forEach(function (name) {
            var chip = document.createElement('span');
            chip.className = 'favorite-municipality';
            chip.innerHTML = '<span>' + escapeHTML(name) + '</span>' +
                '<button type="button" data-remove-favorite="' + escapeHTML(name) + '" aria-label="Remove ' + escapeHTML(name) + ' from priority cities/municipalities">&times;</button>';
            el.favoriteList.appendChild(chip);
        });
    }

    function isFavoriteMunicipality(name) {
        return state.favoriteMunicipalities.indexOf(name) !== -1;
    }

    function matchesFilters(hotline) {
        if (state.activeCategory !== 'all' && hotline.category_slug !== state.activeCategory) return false;
        if (state.municipality && hotline.municipality !== state.municipality) return false;
        if (state.search) {
            var haystack = (hotline.title + ' ' + hotline.municipality + ' ' + (hotline.description || '')).toLowerCase();
            if (haystack.indexOf(state.search.toLowerCase()) === -1) return false;
        }
        return true;
    }

    function cardHTML(hotline) {
        return (
            '<article class="hotline-card" data-id="' + hotline.id + '">' +
                '<div class="hotline-card__top">' +
                    '<div>' +
                        '<p class="hotline-card__title">' + escapeHTML(hotline.title) + '</p>' +
                        '<p class="hotline-card__meta">' + escapeHTML(hotline.municipality) + ' &middot; ' + escapeHTML(hotline.availability) + '</p>' +
                    '</div>' +
                    '<div class="hotline-card__badges">' +
                        (isFavoriteMunicipality(hotline.municipality) ? '<span class="priority-badge">Priority</span>' : '') +
                        '<span class="badge ' + badgeClassFor(hotline.category_slug) + '">' + escapeHTML(hotline.tag_label) + '</span>' +
                    '</div>' +
                '</div>' +
                '<p class="hotline-card__description">' + escapeHTML(hotline.description || '') + '</p>' +
                '<button type="button" class="btn-call" data-action="call" data-id="' + hotline.id + '">' + PHONE_ICON + ' Call now</button>' +
            '</article>'
        );
    }

    function currentTitle(matchCount) {
        if (state.search) return 'Search results';
        if (state.activeCategory !== 'all') {
            var cat = state.categories.filter(function (c) { return c.slug === state.activeCategory; })[0];
            return (cat ? cat.display_name : 'Hotlines') + ' Hotlines';
        }
        if (state.municipality) return state.municipality;
        if (state.favoriteMunicipalities.length) return 'Your priority hotlines';
        return 'Recommended';
    }

    function renderResults() {
        var filtered = state.hotlines.filter(matchesFilters);
        filtered.sort(function (a, b) {
            var aPriority = state.favoriteMunicipalities.indexOf(a.municipality);
            var bPriority = state.favoriteMunicipalities.indexOf(b.municipality);
            if (aPriority === -1) aPriority = Number.MAX_SAFE_INTEGER;
            if (bPriority === -1) bPriority = Number.MAX_SAFE_INTEGER;
            return aPriority - bPriority
                || a.municipality.localeCompare(b.municipality)
                || a.title.localeCompare(b.title);
        });
        var noFilterActive = state.activeCategory === 'all' && !state.municipality && !state.search;
        var toShow = filtered;

        if (noFilterActive && !state.expanded) {
            toShow = filtered.slice(0, RECOMMENDED_LIMIT);
        }

        el.resultsTitleText.textContent = currentTitle(filtered.length);
        el.resultsCount.textContent = filtered.length ? ' (' + filtered.length + ')' : '';
        el.seeAllBtn.hidden = !(noFilterActive && !state.expanded && filtered.length > RECOMMENDED_LIMIT);

        if (!toShow.length) {
            el.grid.innerHTML = '<p class="empty-state">No hotlines match your search. Try a different keyword or filter.</p>';
            return;
        }

        el.grid.innerHTML = toShow.map(cardHTML).join('');
    }

    function openCallModal(hotline) {
        el.modal.dataset.hotlineId = hotline.id;
        el.modalTitle.textContent = hotline.title;
        el.modalNumber.textContent = hotline.phone_number;
        el.helpDescription.value = '';
        el.locationStatus.textContent = '';
        el.shareLocationBtn.disabled = false;
        var dialNumber = String(hotline.phone_number).split('/')[0].replace(/[^\d+]/g, '');
        el.modalCta.setAttribute('href', 'tel:' + dialNumber);
        el.modal.hidden = false;
    }

    function closeCallModal() {
        el.modal.hidden = true;
    }

    function bindEvents() {
        el.search.addEventListener('input', debounce(function () {
            state.search = el.search.value.trim();
            renderResults();
        }, 150));

        if (el.searchButton) {
            el.searchButton.addEventListener('click', function () {
                state.search = el.search.value.trim();
                state.expanded = true;
                renderResults();
            });
        }

        el.municipalitySelect.addEventListener('change', function () {
            state.municipality = el.municipalitySelect.value;
            renderResults();
        });

        el.seeAllBtn.addEventListener('click', function () {
            state.expanded = true;
            renderResults();
        });

        if (el.favoriteForm && el.favoriteSelect) {
            el.favoriteForm.addEventListener('submit', function (event) {
                event.preventDefault();
                var municipality = el.favoriteSelect.value;
                if (!municipality || isFavoriteMunicipality(municipality)) return;
                if (state.municipalities.indexOf(municipality) === -1) {
                    el.favoriteStatus.textContent = 'Choose a city/municipality from the suggestions.';
                    return;
                }
                updateFavoriteMunicipality('add', municipality);
            });
        }

        if (el.favoriteList) {
            el.favoriteList.addEventListener('click', function (event) {
                var trigger = event.target.closest('[data-remove-favorite]');
                if (trigger) updateFavoriteMunicipality('remove', trigger.dataset.removeFavorite);
            });
        }

        el.grid.addEventListener('click', function (event) {
            var trigger = event.target.closest('[data-id]');
            if (!trigger) return;
            var hotline = state.hotlines.filter(function (h) { return String(h.id) === trigger.dataset.id; })[0];
            if (hotline) openCallModal(hotline);
        });

        el.modalClose.addEventListener('click', closeCallModal);
        el.shareLocationBtn.addEventListener('click', function () {
            if (!window.isSecureContext) {
                el.locationStatus.textContent = 'GPS requires HTTPS. Open this site using https://192.168.254.160 or use localhost.';
                return;
            }
            if (!navigator.geolocation) {
                el.locationStatus.textContent = 'GPS is not supported by this browser.';
                return;
            }
            el.shareLocationBtn.disabled = true;
            el.locationStatus.textContent = 'Requesting your location...';
            navigator.geolocation.getCurrentPosition(function (position) {
                var body = new URLSearchParams({
                    hotline_id: el.modal.dataset.hotlineId,
                    latitude: position.coords.latitude,
                    longitude: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                    help_description: el.helpDescription.value,
                });
                fetch(BASE + '/location-request.php', { method: 'POST', body: body })
                    .then(function (res) { return res.json(); })
                    .then(function (json) {
                        el.locationStatus.textContent = json.message || 'Location sharing failed.';
                        el.shareLocationBtn.disabled = !json.success;
                    })
                    .catch(function () {
                        el.locationStatus.textContent = 'Could not share your location. Please try again.';
                        el.shareLocationBtn.disabled = false;
                    });
            }, function () {
                el.locationStatus.textContent = 'Location permission was denied or unavailable.';
                el.shareLocationBtn.disabled = false;
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 30000 });
        });
        el.modal.addEventListener('click', function (event) {
            if (event.target === el.modal) closeCallModal();
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !el.modal.hidden) closeCallModal();
        });
    }

    function init() {
        bindEvents();
        Promise.all([
            fetchJSON(BASE + '/categories.php'),
            fetchJSON(BASE + '/municipalities.php'),
            fetchJSON(BASE + '/hotlines.php'),
        ]).then(function (results) {
            state.categories = results[0];
            state.municipalities = results[1];
            state.hotlines = results[2];
            renderChips();
            renderMunicipalityOptions();
            if (!el.favoriteForm || !el.favoriteSelect || !el.favoriteList || !el.favoriteStatus) {
                renderResults();
                return;
            }
            el.favoriteStatus.textContent = 'Loading your saved cities/municipalities...';
            return fetchJSON(BASE + '/favorite-municipalities.php').then(function (favorites) {
                state.favoriteMunicipalities = favorites;
                el.favoriteStatus.textContent = '';
                renderFavoriteMunicipalities();
                renderResults();
            }).catch(function () {
                el.favoriteStatus.textContent = 'Could not load your saved cities/municipalities. Please refresh to try again.';
                renderResults();
            });
        }).catch(function () {
            el.grid.innerHTML = '<p class="empty-state">Could not load hotlines right now. Please check your database connection and try again.</p>';
        });
    }

    function updateFavoriteMunicipality(action, municipality) {
        if (!el.favoriteForm || !el.favoriteList || !el.favoriteStatus || !el.favoriteCsrf || !el.favoriteSelect) return;
        var buttons = Array.prototype.slice.call(el.favoriteForm.querySelectorAll('button'))
            .concat(Array.prototype.slice.call(el.favoriteList.querySelectorAll('button')));
        Array.prototype.forEach.call(buttons, function (button) { button.disabled = true; });
        el.favoriteStatus.textContent = action === 'add' ? 'Saving priority city/municipality...' : 'Removing priority city/municipality...';
        fetch(BASE + '/favorite-municipalities.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: new URLSearchParams({
                action: action,
                municipality: municipality,
                csrf_token: el.favoriteCsrf.value,
            }),
        }).then(function (res) {
            return res.json().then(function (json) {
                if (!res.ok || !json.success) throw new Error(json.message || 'Could not update priority cities/municipalities.');
                return json.data;
            });
        }).then(function (favorites) {
            state.favoriteMunicipalities = favorites;
            el.favoriteStatus.textContent = action === 'add'
                ? municipality + ' is now a priority city/municipality.'
                : municipality + ' was removed from priority cities/municipalities.';
            el.favoriteSelect.value = '';
            renderFavoriteMunicipalities();
            renderResults();
        }).catch(function (error) {
            el.favoriteStatus.textContent = error.message || 'Could not update priority cities/municipalities. Please try again.';
        }).then(function () {
            Array.prototype.forEach.call(buttons, function (button) { button.disabled = false; });
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
