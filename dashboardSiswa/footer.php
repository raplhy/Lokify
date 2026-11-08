    </div><!-- /.content-area -->
</div><!-- /.main-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
/* ============ DASHBOARD JS ============ */

function toggleSidebar() {
    if (window.innerWidth <= 768) {
        document.body.classList.toggle("sidebar-mobile-open");
    } else {
        document.body.classList.toggle("sidebar-collapsed");
    }
}

function toggleGedungMenu() {
    const subMenu = document.getElementById("subMenuGedung");
    const arrow   = document.getElementById("arrowGedung");
    if (!subMenu) return;
    const isOpen = subMenu.style.display === "block";
    subMenu.style.display = isOpen ? "none" : "block";
    if (arrow) arrow.style.transform = isOpen ? "rotate(0deg)" : "rotate(180deg)";
}

function toggleDropdown() {
    const dropdown  = document.getElementById("userMenu");
    const container = document.getElementById("profileDropdownContainer");
    if (dropdown) dropdown.classList.toggle("show");
    if (container) container.classList.toggle("active");
}

function openModal() {
    const m = document.getElementById('modalInput');
    if (m) m.style.display = 'flex';
}
function closeModal() {
    const m = document.getElementById('modalInput');
    if (m) m.style.display = 'none';
}
function openModalProfile() {
    const m = document.getElementById('modalProfile');
    if (m) m.style.display = 'flex';
    const d = document.getElementById("userMenu");
    if (d) d.classList.remove("show");
    const c = document.getElementById("profileDropdownContainer");
    if (c) c.classList.remove("active");
}
function closeModalProfile() {
    const m = document.getElementById('modalProfile');
    if (m) m.style.display = 'none';
}
function openModalHistory() {
    const m = document.getElementById('modalHistory');
    if (m) m.style.display = 'flex';
}
function closeModalHistory() {
    const m = document.getElementById('modalHistory');
    if (m) m.style.display = 'none';
}

/* KONFIRMASI AMBIL BARANG (TAMPILAN ONLY) */
function konfirmasiAmbil(noLoker) {
    Swal.fire({
        title: 'Ambil Barang?',
        html: `Anda akan mengambil barang dari loker <strong style="color:#FF9149;">${noLoker}</strong>.`,
        showCancelButton: true,
        confirmButtonColor: '#FF9149',
        cancelButtonColor: '#94a3b8',
        confirmButtonText: '<i class="fa-solid fa-check"></i> Ya, Ambil!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'info',
                title: 'Fitur Belum Aktif',
                text: 'Ambil barang akan aktif setelah integrasi backend.',
                confirmButtonColor: '#FF9149'
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const subMenu = document.getElementById("subMenuGedung");
    const arrow   = document.getElementById("arrowGedung");
    if (subMenu) subMenu.style.display = "block";
    if (arrow) arrow.style.transform = "rotate(180deg)";

    /* Tutup dropdown profile kalau klik di luar */
    window.addEventListener('click', (event) => {
        if (!event.target.closest('.profile-dropdown-container')) {
            const dropdown  = document.getElementById("userMenu");
            const container = document.getElementById("profileDropdownContainer");
            if (dropdown && dropdown.classList.contains('show')) {
                dropdown.classList.remove('show');
                if (container) container.classList.remove('active');
            }
        }
    });

    /* Modal klik background = tutup */
    document.querySelectorAll('.modal-bg').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    });

    /* Tutup sidebar mobile kalau klik di luar */
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && 
            document.body.classList.contains('sidebar-mobile-open') &&
            !e.target.closest('.sidebar') && 
            !e.target.closest('.btn-toggle-sidebar')) {
            document.body.classList.remove('sidebar-mobile-open');
        }
    });
});
</script>
</body>
</html>