<?php
session_start();
include __DIR__ . "/../login/koneksi.php";

// ==========================================
// AMBIL DATA GEDUNG DARI DATABASE
// ==========================================

$query_gedung = mysqli_query(
    $koneksi,
    "SELECT id_gedung, nama_gedung
     FROM gedung
     ORDER BY id_gedung ASC"
);

$daftar_gedung = [];

while ($row = mysqli_fetch_assoc($query_gedung)) {
    $daftar_gedung[] = $row;
}


// ==========================================
// TENTUKAN GEDUNG YANG DIPILIH
// ==========================================

// Default = Gedung pertama
$id_gedung = isset($_GET['gedung']) ? (int) $_GET['gedung'] : 1;

$nama_gedung = '';
$gedung_ditemukan = false;


// Cari nama gedung berdasarkan ID
foreach ($daftar_gedung as $gedung) {

    if ((int) $gedung['id_gedung'] === $id_gedung) {

        $gedung_ditemukan = true;
        $nama_gedung = $gedung['nama_gedung'];

        break;
    }
}


// Jika ID gedung tidak ditemukan,
// kembali ke gedung pertama
if (!$gedung_ditemukan && !empty($daftar_gedung)) {

    $id_gedung = (int) $daftar_gedung[0]['id_gedung'];
    $nama_gedung = $daftar_gedung[0]['nama_gedung'];

}


// ==========================================
// AMBIL DATA LOKER BERDASARKAN GEDUNG
// ==========================================

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT nomor_loker, id_gedung, status
     FROM loker
     WHERE id_gedung = ?
     ORDER BY nomor_loker ASC
     LIMIT 35"
);

mysqli_stmt_bind_param($stmt, "i", $id_gedung);
mysqli_stmt_execute($stmt);

$hasil = mysqli_stmt_get_result($stmt);


// ==========================================
// HITUNG STATUS LOKER
// ==========================================

$lokers = [];
$kosong = 0;

while ($row = mysqli_fetch_assoc($hasil)) {

    $lokers[] = $row;

    if (strtolower($row['status']) == 'kosong') {
        $kosong++;
    }

}

$terisi = count($lokers) - $kosong;

?>

<?php
// Isi bagian Fitur dan Cara Kerja (ubah teksnya di sini)
$fitur = [
    ['Pendataan Barang',      'Setiap barang yang disimpan di loker tercatat lengkap, mulai dari nama, kategori, sampai foto barang.'],
    ['Status Loker Langsung', 'Lihat loker mana yang masih kosong dan mana yang sudah terisi, tanpa perlu mengecek satu per satu.'],
    ['Pantau per Gedung',     'Data loker dikelompokkan per gedung, jadi lebih mudah dicari dan dipantau oleh petugas maupun siswa.'],
    ['Rekap & Laporan',       'Rekap barang tersimpan dan diambil bisa dilihat dan dicetak kapan saja untuk kebutuhan administrasi.'],
];

$langkah = [
    ['Login',         'Masuk dengan akun Anda, atau daftar jika belum punya akun.'],
    ['Simpan Barang', 'Pilih loker yang tersedia, lalu catat barang yang akan disimpan.'],
    ['Ambil Barang',  'Saat barang diambil, status loker diperbarui dan kembali tersedia.'],
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LokerKu - Pendataan Barang Loker Sekolah</title>
    <link rel="stylesheet" href="landing.css">
</head>

<body>

<!-- ========== NAVBAR ========== -->
<nav class="nav" id="nav">

    <a href="#beranda" class="brand">Loker<span>Ku</span></a>

    <div class="nav-links">
        <a href="#beranda">Beranda</a>
        <a href="#tentang">Tentang</a>
        <a href="#fitur">Fitur</a>
        <a href="#cara-kerja">Cara Kerja</a>
        <a href="#status">Status Loker</a>
    </div>

    <div class="nav-btn">
        <a href="../login/login.php" class="btn btn-light">Login</a>
        <a href="../login/login.php" class="btn">Register</a>
    </div>

    <button class="nav-toggle" id="navToggle" type="button" aria-label="Menu">&#9776;</button>

</nav>


<!-- ========== BERANDA ========== -->
<header class="hero" id="beranda">

    <span class="label">PENDATAAN LOKER SEKOLAH</span>

    <h1>   Pendataan Barang di Loker Sekolah, <em>Tercatat rapi.</em></h1>

    <p>
        LokerKu adalah website untuk mencatat barang yang disimpan
        di loker sekolah dan memantau loker mana yang masih kosong,
        semuanya dalam satu tempat.
    </p>

    <div class="hero-cta">
        <a href="#status" class="btn">Lihat Loker Kosong</a>
        <a href="#tentang" class="btn btn-light">Apa itu LokerKu?</a>
    </div>

    <!-- Data langsung dari database -->
    <div class="live-pill">
        <i></i>
        <span>
            <b><?php echo $kosong; ?> loker kosong</b>
            di <?php echo htmlspecialchars($nama_gedung); ?> saat ini
        </span>
    </div>

    <ul class="hero-points">
        <li>Cek loker kosong tanpa perlu login</li>
        <li>Barang yang disimpan tercatat otomatis</li>
        <li>Data dipisah per gedung</li>
    </ul>

</header>


<!-- ========== TENTANG ========== -->
<section class="section alt" id="tentang">

    <div class="about">

        <div>
            <span class="label">TENTANG LOKERKU</span>
            <h2>Apa itu Lokerku?</h2>

            <p>
                LokerKu adalah website pendataan barang di loker sekolah.
                Setiap barang yang dititipkan dicatat, status tiap loker
                diperbarui, dan siapa pun bisa melihat loker mana yang
                masih kosong.
            </p>

            <p>
                Dibuat agar petugas tidak perlu lagi mencatat di buku,
                dan siswa tidak perlu keliling mencari loker yang kosong.
            </p>

            <a href="#fitur" class="btn">Lihat Fiturnya</a>
        </div>

        <div class="compare">

            <div class="box minus">
                <h3>Tanpa LokerKu</h3>
                <ul>
                    <li>Barang dicatat manual di buku</li>
                    <li>Sulit tahu loker mana yang kosong</li>
                    <li>Barang rawan terlupa atau tertukar</li>
                </ul>
            </div>

            <div class="box plus">
                <h3>Dengan LokerKu</h3>
                <ul>
                    <li>Data tersimpan rapi di database</li>
                    <li>Status loker tampil langsung per gedung</li>
                    <li>Barang tersimpan dan diambil terekap</li>
                </ul>
            </div>

        </div>

    </div>

</section>


<!-- ========== FITUR ========== -->
<section class="section" id="fitur">

    <div class="section-head">
        <span class="label">FITUR</span>
        <h2>Semua yang dibutuhkan untuk mengelola loker</h2>
        <p>Dari pencatatan barang sampai laporan, semuanya ada di satu tempat.</p>
    </div>

    <div class="cards">
        <?php foreach ($fitur as $i => $f) { ?>
            <div class="item">
                <div class="icon">0<?php echo $i + 1; ?></div>
                <h3><?php echo htmlspecialchars($f[0]); ?></h3>
                <p><?php echo htmlspecialchars($f[1]); ?></p>
            </div>
        <?php } ?>
    </div>

</section>


<!-- ========== CARA KERJA ========== -->
<section class="section alt" id="cara-kerja">

    <div class="section-head">
        <span class="label">CARA KERJA</span>
        <h2>Mulai dalam tiga langkah</h2>
        <p>Sederhana, tanpa pencatatan manual di buku.</p>
    </div>

    <div class="cards">
        <?php foreach ($langkah as $i => $l) { ?>
            <div class="item center">
                <div class="icon"><?php echo $i + 1; ?></div>
                <h3><?php echo htmlspecialchars($l[0]); ?></h3>
                <p><?php echo htmlspecialchars($l[1]); ?></p>
            </div>
        <?php } ?>
    </div>

</section>


<!-- ========== STATUS LOKER ========== -->
<section class="section" id="status">

    <div class="section-head">
        <span class="label">STATUS LOKER</span>
        <h2>Ketersediaan loker saat ini</h2>
        <p>Pilih gedung untuk melihat loker yang masih kosong.</p>
    </div>

    <div class="panel">

        <!-- Tab gedung + statistik -->
        <div class="top">

            <div class="tabs">
                <?php foreach ($daftar_gedung as $g) { ?>
                    <a class="tab <?php echo ((int)$g['id_gedung'] === $id_gedung) ? 'active' : ''; ?>"
                       href="?gedung=<?php echo $g['id_gedung']; ?>#status">
                        <?php echo htmlspecialchars($g['nama_gedung']); ?>
                    </a>
                <?php } ?>
            </div>

            <div class="stat">
                <span>Kosong: <b><?php echo $kosong; ?></b></span>
                <span>Terisi: <b><?php echo $terisi; ?></b></span>
            </div>

        </div>

        <!-- Grid loker -->
        <div class="grid">

            <?php if (empty($lokers)) { ?>

                <p class="empty">Belum ada data loker di <?php echo htmlspecialchars($nama_gedung); ?>.</p>

            <?php } else { ?>

                <?php foreach ($lokers as $row) {
                    $ada = strtolower($row['status']) == 'kosong';
                ?>
                    <div class="card">
                        <div class="num"><?php echo htmlspecialchars($row['nomor_loker']); ?></div>
                        <div class="loc"><?php echo htmlspecialchars($nama_gedung); ?></div>
                        <span class="st <?php echo $ada ? 'tersedia' : 'terisi'; ?>">
                            <i></i> <?php echo $ada ? 'TERSEDIA' : 'TERISI'; ?>
                        </span>
                    </div>
                <?php } ?>

            <?php } ?>

        </div>

        <p class="note">Tampilan ini hanya untuk melihat status loker.</p>

    </div>

</section>


<!-- ========== AJAKAN AKHIR ========== -->
<section class="section">

    <div class="cta-box">
        <h2>Siap merapikan pendataan loker?</h2>
        <p>Login untuk mulai menyimpan dan mengambil barang dengan lebih tertib.</p>
        <a href="../login/login.php" class="btn">Mulai Sekarang</a>
    </div>

</section>


<footer>&copy; LokerKu &middot; Pendataan Barang di Loker Sekolah</footer>


<script>
var nav = document.getElementById('nav');
var links = document.querySelectorAll('.nav-links a');

// Tombol hamburger (layar kecil)
document.getElementById('navToggle').onclick = function () {
    nav.classList.toggle('open');
};

// Klik menu: geser ke bagian tujuan (cukup sekali klik)
links.forEach(function (a) {
    a.onclick = function (e) {
        e.preventDefault();
        var tujuan = document.querySelector(a.getAttribute('href')).offsetTop - nav.offsetHeight;
        window.scrollTo({ top: tujuan, behavior: 'smooth' });
        nav.classList.remove('open');
    };
});

// Tandai menu sesuai bagian yang sedang dilihat
function tandaiMenu() {
    var tengah = scrollY + innerHeight / 2;
    var aktif = links[0];
    links.forEach(function (a) {
        if (document.querySelector(a.getAttribute('href')).offsetTop <= tengah) aktif = a;
    });
    links.forEach(function (a) { a.classList.toggle('active', a === aktif); });
}
addEventListener('scroll', tandaiMenu);
tandaiMenu();

// Animasi muncul saat di-scroll
var pengamat = new IntersectionObserver(function (daftar) {
    daftar.forEach(function (d) {
        if (d.isIntersecting) { d.target.classList.add('in'); pengamat.unobserve(d.target); }
    });
}, { threshold: 0.15 });

document.querySelectorAll('.hero > *, .about > *, .box, .section-head, .item, .panel, .cta-box')
    .forEach(function (el) { el.classList.add('reveal'); pengamat.observe(el); });

// Ganti gedung tanpa reload (posisi scroll tidak lompat)
document.addEventListener('click', function (e) {
    var tab = e.target.closest('.tab');
    if (!tab) return;
    e.preventDefault();

    fetch(tab.href)
        .then(function (res) { return res.text(); })
        .then(function (html) {
            var baru = new DOMParser().parseFromString(html, 'text/html');
            document.querySelector('.panel').innerHTML = baru.querySelector('.panel').innerHTML;
            history.pushState(null, '', tab.getAttribute('href'));
        })
        .catch(function () { location.href = tab.href; });
});

addEventListener('popstate', function () { location.reload(); });
</script>

</body>

</html>