function toggleSearch(inputId, btn, submitOnCollapse) {
    var input = document.getElementById(inputId);
    if (input.classList.contains('w-0')) {
        input.classList.remove('w-0', 'px-0', 'border-0', 'opacity-0', 'pointer-events-none');
        input.classList.add('w-48', 'sm:w-64', 'px-3', 'border', 'border-gray-300', 'rounded-lg', 'opacity-100', 'pointer-events-auto');
        input.focus();
    } else {
        input.classList.add('w-0', 'px-0', 'border-0', 'opacity-0', 'pointer-events-none');
        input.classList.remove('w-48', 'sm:w-64', 'px-3', 'border', 'border-gray-300', 'rounded-lg', 'opacity-100', 'pointer-events-auto');
        input.value = '';
        input.dispatchEvent(new Event('input'));
        if (submitOnCollapse) input.closest('form').submit();
    }
}

function positionSuggest(btn, listId) {
    var list = document.getElementById(listId);
    if (!list) return;
    if (list.parentElement !== document.body) {
        document.body.appendChild(list);
    }
    var rect = btn.getBoundingClientRect();
    list.style.position = 'fixed';
    list.style.top = (rect.bottom + 4) + 'px';
    list.style.right = (window.innerWidth - rect.right) + 'px';
}

document.addEventListener('click', function(e) {
    document.querySelectorAll('[id^="searchGerai"], [id="searchLaporan"], [id="searchRanking"], [id="searchPraMonitoring"], [id="searchKomplain"], [id="searchUser"], [id="searchPg"], [id="searchPeriode"], [id="geraiSearch"]').forEach(function(input) {
        var container = input.closest('.relative');
        if (container && !container.contains(e.target) && !input.classList.contains('w-0') && input.value === '') {
            input.classList.add('w-0', 'px-0', 'border-0', 'opacity-0', 'pointer-events-none');
            input.classList.remove('w-48', 'sm:w-64', 'px-3', 'border', 'border-gray-300', 'rounded-lg', 'opacity-100', 'pointer-events-auto');
        }
    });
});

function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    var overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('-translate-x-full');
    overlay.classList.toggle('hidden');

    if (!sidebar.classList.contains('-translate-x-full')) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }

    var top = document.getElementById('burgerTop');
    var mid = document.getElementById('burgerMid');
    var bot = document.getElementById('burgerBot');
    var navbarMars = document.getElementById('navbarMars');
    var sidebarMars = document.getElementById('sidebarMars');
    var fabMenus = document.querySelectorAll('.fixed.bottom-6.right-6');
    var burgerBtn = document.getElementById('burgerBtn');
    if (sidebar.classList.contains('-translate-x-full')) {
        top.style.transform = 'none';
        mid.style.opacity = '1';
        bot.style.transform = 'none';
        burgerBtn.style.opacity = '1';
        navbarMars.style.opacity = '1';
        navbarMars.style.transform = 'translateX(0)';
        fabMenus.forEach(function(el) { el.style.opacity = '1'; el.classList.remove('pointer-events-none'); });
    } else {
        top.style.transform = 'translateY(11px) rotate(45deg)';
        mid.style.opacity = '0';
        bot.style.transform = 'translateY(-11px) rotate(-45deg)';
        burgerBtn.style.opacity = '0.4';
        navbarMars.style.opacity = '0';
        navbarMars.style.transform = 'translateX(20px)';
        fabMenus.forEach(function(el) { el.style.opacity = '0.4'; el.classList.add('pointer-events-none'); });
    }
}

function toggleBuatLaporan() {
    var dd = document.getElementById('buatLaporanDropdown');
    dd.classList.toggle('hidden');
}

document.addEventListener('click', function(e) {
    var wrapper = document.getElementById('buatLaporanWrapper');
    if (wrapper && !wrapper.contains(e.target)) {
        document.getElementById('buatLaporanDropdown').classList.add('hidden');
    }
    var notifWrapper = document.getElementById('notifWrapper');
    if (notifWrapper && !notifWrapper.contains(e.target)) {
        document.getElementById('notifDropdown').classList.add('hidden');
    }
});

function toggleTugas() {
    document.getElementById('tugasSubmenu').classList.toggle('hidden');
    document.getElementById('tugasArrow').classList.toggle('rotate-180');
}

function toggleMonitoring() {
    document.getElementById('monitoringSubmenu').classList.toggle('hidden');
    document.getElementById('monitoringArrow').classList.toggle('rotate-180');
}

function closeModal() {
    Swal.close();
}

function showAlert(msg) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ text: msg, icon: 'info', confirmButtonText: 'OK', confirmButtonColor: '#3B82F6', width: '16em', padding: '0.8em', customClass: { popup: 'swal-small', confirmButton: 'swal-small-btn' } });
    } else {
        alert(msg);
    }
}

function showConfirm(msg, onConfirm) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3B82F6',
            cancelButtonColor: '#9CA3AF',
            confirmButtonText: 'Ya',
            cancelButtonText: 'Tidak',
            width: '16em',
            padding: '0.8em',
            customClass: {
                popup: 'swal-small',
                confirmButton: 'swal-small-btn',
                cancelButton: 'swal-small-btn',
            }
        }).then(function(result) {
            if (result.isConfirmed) onConfirm();
        });
    } else {
        if (confirm(msg)) onConfirm();
    }
}

document.addEventListener('submit', function(e) {
    var form = e.target;
    if (form.method && form.method.toUpperCase() === 'GET') return;
    var submitBtn = e.submitter;
    if (submitBtn && submitBtn.type === 'submit') {
        submitBtn.disabled = true;
        setTimeout(function() { submitBtn.disabled = false; }, 3000);
    }
});


