// Filter state
const filterState = {
    gen: new Set(),
    type: new Set(),
    rarity: new Set()
};

function getSelectedValues(filterName) {
    return Array.from(filterState[filterName] || []);
}

function applyFilters() {
    const genSel    = getSelectedValues('gen');
    const typeSel   = getSelectedValues('type');
    const raritySel = getSelectedValues('rarity');
    const rows = document.querySelectorAll('#statstable tbody tr');

    rows.forEach(row => {
        const rowGen    = row.dataset.gen || '';
        const rowTypes  = (row.dataset.type || '').split(',').map(t => t.trim().toLowerCase()).filter(Boolean);
        const rowRarity = row.dataset.rarity || '';

        let show = true;
        if (genSel.length && !genSel.includes(rowGen)) show = false;
        if (typeSel.length && !typeSel.some(t => rowTypes.includes(t))) show = false;
        if (raritySel.length && !raritySel.includes(rowRarity)) show = false;
        if (row.dataset.searchmatch === '0') show = false;

        row.style.display = show ? '' : 'none';
    });

    updateSearchCount();
    buildMobileList();
}

function updateSearchCount() {
    const rows = document.querySelectorAll('#statstable tbody tr');
    const visible = Array.from(rows).filter(r => r.style.display !== 'none').length;
    const countEl = document.getElementById('search-count');
    if (countEl) {
        countEl.textContent = `Showing ${visible} of ${rows.length} Pokémon`;
    }
}

function searchpokemon() {
    const input = document.getElementById('searchpokemon');
    const filter = input.value.toLowerCase();
    const rows = document.querySelectorAll('#statstable tbody tr');

    rows.forEach(row => {
        const nameCell = row.querySelector('td[data-label="Pokemon"]');
        if (!nameCell) return;
        const txt = nameCell.textContent.toLowerCase();
        row.dataset.searchmatch = txt.indexOf(filter) > -1 ? '1' : '0';
    });

    applyFilters();
}

function initSearch() {
    const input = document.getElementById('searchpokemon');
    const clearBtn = document.getElementById('search-clear');
    if (!input) return;

    input.addEventListener('input', () => {
        clearBtn.style.display = input.value ? 'block' : 'none';
        searchpokemon();
    });

    clearBtn.addEventListener('click', () => {
        input.value = '';
        clearBtn.style.display = 'none';
        document.querySelectorAll('#statstable tbody tr').forEach(r => { r.dataset.searchmatch = '1'; });
        applyFilters();
    });

    document.querySelectorAll('#statstable tbody tr').forEach(r => r.dataset.searchmatch = '1');
    updateSearchCount();
}

function initThemeToggle() {
    const toggle = document.getElementById('toggleTheme');
    if (!toggle) return;

    toggle.addEventListener('change', () => {
        if (toggle.checked) {
            document.body.classList.add('dark');
            document.cookie = "theme=1;path=/;max-age=31536000";
        } else {
            document.body.classList.remove('dark');
            document.cookie = "theme=;path=/;expires=Thu, 01 Jan 1970 00:00:00 UTC;";
        }
    });
}

function initFilterToggle() {
    const btn = document.getElementById('filter-toggle');
    const container = document.getElementById('filter-container');
    if (!btn || !container) return;

    btn.addEventListener('click', () => {
        const isOpen = container.classList.toggle('open');
        btn.innerHTML = isOpen ? 'Filters &#9652;' : 'Filters &#9662;';
    });
}

function initChips() {
    document.querySelectorAll('.filter-row').forEach(row => {
        const filterName = row.dataset.filter;
        const chips = row.querySelectorAll('.chip');
        const clearBtn = row.querySelector('.chip-clear');

        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                const value = chip.dataset.value;
                chip.classList.toggle('active');

                if (chip.classList.contains('active')) {
                    filterState[filterName].add(value);
                } else {
                    filterState[filterName].delete(value);
                }

                row.classList.toggle('has-active', filterState[filterName].size > 0);
                applyFilters();
            });
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                filterState[filterName].clear();
                chips.forEach(c => c.classList.remove('active'));
                row.classList.remove('has-active');
                applyFilters();
            });
        }
    });
}

function initSorting() {
    const headers = document.querySelectorAll('#statstable th.sortable');
    headers.forEach((th, index) => {
        th.addEventListener('click', () => {
            const tbody = document.querySelector('#statstable tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            const currentDir = th.classList.contains('sorted-asc') ? 'asc' :
                th.classList.contains('sorted-desc') ? 'desc' : null;
            const newDir = currentDir === 'asc' ? 'desc' : 'asc';

            headers.forEach(h => {
                h.classList.remove('sorted-asc', 'sorted-desc');
            });

            th.classList.add(newDir === 'asc' ? 'sorted-asc' : 'sorted-desc');

            rows.sort((a, b) => {
                // index is among all th's including the icon column at 0
                // data cells start at index 0 (icon), 1 (name), 2 (shinies), etc.
                const aCell = a.querySelectorAll('td')[index];
                const bCell = b.querySelectorAll('td')[index];

                const aSort = aCell ? parseFloat(aCell.dataset.sort || '0') : 0;
                const bSort = bCell ? parseFloat(bCell.dataset.sort || '0') : 0;

                return newDir === 'asc' ? aSort - bSort : bSort - aSort;
            });

            rows.forEach(r => tbody.appendChild(r));
            buildMobileList();
        });
    });
}

function buildMobileList() {
    const tableRows = document.querySelectorAll('#statstable tbody tr');
    const container = document.getElementById('mobile-list');
    if (!container) return;

    container.innerHTML = '';

    tableRows.forEach(tr => {
        if (tr.style.display === 'none') return;

        const nameCell = tr.querySelector('td[data-label="Pokemon"]');
        if (!nameCell) return;

        const sprite = nameCell.querySelector('img.icon');
        const tds = tr.querySelectorAll('td');

        // New 6-col layout: 0=icon, 1=name, 2=shinies, 3=hundos, 4=nundos, 5=total
        const getText = (td) => {
            if (!td) return '—';
            const muted = td.querySelector('.muted');
            if (muted) return '—';
            const rateSub = td.querySelector('.rate-sub');
            const count = td.childNodes[0]?.textContent?.trim() || '';
            const rate = rateSub ? rateSub.textContent.trim() : '';
            return count + (rate ? ' (' + rate + ')' : '');
        };

        const shinies = getText(tds[2]);
        const hundos  = getText(tds[3]);
        const nundos  = getText(tds[4]);
        const total   = tds[5]?.textContent.trim() || '—';

        const row = document.createElement('div');
        row.className = 'mobile-row';

        row.innerHTML = `
            ${sprite ? `<img class="mobile-sprite" src="${sprite.src}" alt="">` : ''}
            <div class="mobile-main">
                <div class="mobile-name">${nameCell.textContent.trim()}</div>
                <div class="mobile-details d-none">
                    <div>Shinies: ${shinies}</div>
                    <div>Hundos: ${hundos}</div>
                    <div>Nundos: ${nundos}</div>
                    <div>Total: ${total}</div>
                </div>
            </div>
            <div class="mobile-keystat"><span class="mobile-keystat-label">Total</span>${total}</div>
        `;

        row.addEventListener('click', () => {
            document.querySelectorAll('.mobile-details').forEach(d => {
                if (d !== row.querySelector('.mobile-details')) d.classList.add('d-none');
            });
            row.querySelector('.mobile-details').classList.toggle('d-none');
        });

        container.appendChild(row);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initThemeToggle();
    initFilterToggle();
    initChips();
    initSearch();
    initSorting();
    applyFilters();
    buildMobileList();
});
