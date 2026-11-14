<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$id_sesi = (int)($_SESSION['id_user'] ?? 0);
$sesi_valid = false;
if ($id_sesi > 0 && isset($koneksi) && $koneksi) {
    $cek_sesi = mysqli_query($koneksi, "SELECT id_user, username, nama_lengkap, role FROM users WHERE id_user = $id_sesi");
    if ($cek_sesi && $r_sesi = mysqli_fetch_assoc($cek_sesi)) {
        $_SESSION['id_user']      = (int)$r_sesi['id_user'];
        $_SESSION['username']     = $r_sesi['username'];
        $_SESSION['nama_lengkap'] = $r_sesi['nama_lengkap'];
        $_SESSION['role']         = $r_sesi['role'];
        $sesi_valid = true;
    }
}

if (!$sesi_valid && isset($koneksi) && $koneksi) {
    $q_adm = mysqli_query($koneksi, "SELECT id_user, username, nama_lengkap, role FROM users WHERE role = 'admin' LIMIT 1");
    if ($q_adm && $r_adm = mysqli_fetch_assoc($q_adm)) {
        $_SESSION['id_user']      = (int)$r_adm['id_user'];
        $_SESSION['username']     = $r_adm['username'];
        $_SESSION['nama_lengkap'] = $r_adm['nama_lengkap'];
        $_SESSION['role']         = $r_adm['role'];
    } else {
        $_SESSION['id_user']      = 1;
        $_SESSION['username']     = 'admin';
        $_SESSION['nama_lengkap'] = 'Administrator';
        $_SESSION['role']         = 'admin';
    }
}

if (!function_exists('dapatkanIdAdminAktif')) {
    function dapatkanIdAdminAktif($koneksi) {
        $id = (int)($_SESSION['id_user'] ?? 0);
        if ($id > 0 && isset($koneksi) && $koneksi) {
            $cek = mysqli_query($koneksi, "SELECT id_user FROM users WHERE id_user = $id");
            if ($cek && mysqli_num_rows($cek) > 0) {
                return $id;
            }
        }
        if (isset($koneksi) && $koneksi) {
            $q = mysqli_query($koneksi, "SELECT id_user FROM users WHERE role = 'admin' LIMIT 1");
            if ($q && $r = mysqli_fetch_assoc($q)) {
                return (int)$r['id_user'];
            }
            $q2 = mysqli_query($koneksi, "SELECT id_user FROM users LIMIT 1");
            if ($q2 && $r2 = mysqli_fetch_assoc($q2)) {
                return (int)$r2['id_user'];
            }
        }
        return 1;
    }
}

$mock_gedung = [
    ['id_gedung' => 1, 'nama_gedung' => 'Gedung A', 'kode' => 'A', 'total_loker' => 35, 'loker_kosong' => 35, 'loker_terisi' => 0],
    ['id_gedung' => 2, 'nama_gedung' => 'Gedung B', 'kode' => 'B', 'total_loker' => 35, 'loker_kosong' => 35, 'loker_terisi' => 0],
    ['id_gedung' => 3, 'nama_gedung' => 'Gedung C', 'kode' => 'C', 'total_loker' => 35, 'loker_kosong' => 35, 'loker_terisi' => 0],
    ['id_gedung' => 4, 'nama_gedung' => 'Gedung D', 'kode' => 'D', 'total_loker' => 35, 'loker_kosong' => 35, 'loker_terisi' => 0],
];

$mock_loker = [];
$id_counter = 1;
foreach (['A' => 'Gedung A', 'B' => 'Gedung B', 'C' => 'Gedung C', 'D' => 'Gedung D'] as $kode_g => $nama_g) {
    for ($i = 1; $i <= 35; $i++) {
        $no_loker = $kode_g . str_pad($i, 3, '0', STR_PAD_LEFT);
        $mock_loker[] = [
            'id_loker'    => $id_counter++,
            'nomor_loker' => $no_loker,
            'id_gedung'   => ord($kode_g) - 64,
            'lokasi'      => $nama_g,
            'nama_gedung' => $nama_g,
            'terisi'      => 0,
            'status'      => 'Kosong'
        ];
    }
}

$mock_barang = [];
$mock_log = [];

function tanggalIndonesia() {
    $hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];

    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];

    return $hari[date('l')] . ', ' . date('j') . ' ' . $bulan[(int)date('n')] . ' ' . date('Y');
}

function perbaruiStatusLoker($koneksi, $id_loker) {
    $id_loker = (int)$id_loker;
    if ($id_loker <= 0 || !$koneksi) {
        return;
    }

    $q = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM barang WHERE id_loker = $id_loker AND status = 'Tersimpan'");
    if (!$q) {
        return;
    }

    $data = mysqli_fetch_assoc($q);
    $status = ((int)($data['total'] ?? 0) > 0) ? 'Terisi' : 'Kosong';

    mysqli_query($koneksi, "UPDATE loker SET status = '$status' WHERE id_loker = $id_loker");
}

function ambilStatistik($koneksi, $mock_loker, $mock_barang) {
    if ($koneksi) {
        $qLokerStat = mysqli_query($koneksi, "SELECT 
            COUNT(*) AS total,
            SUM(status = 'Kosong') AS kosong,
            SUM(status = 'Terisi') AS terisi
            FROM loker");
        if ($qLokerStat && $rowLoker = mysqli_fetch_assoc($qLokerStat)) {
            $total_loker = (int)($rowLoker['total'] ?? 0);
            $kosong      = (int)($rowLoker['kosong'] ?? 0);
            $terisi      = (int)($rowLoker['terisi'] ?? 0);

            $qBarangStat = mysqli_query($koneksi, "SELECT
                SUM(status = 'Tersimpan') AS tersimpan,
                SUM(status = 'Sudah diambil') AS diambil
                FROM barang");
            $rowBarang = mysqli_fetch_assoc($qBarangStat);
            $b_tersimpan = (int)($rowBarang['tersimpan'] ?? 0);
            $b_diambil   = (int)($rowBarang['diambil'] ?? 0);

            return [
                'total_loker'      => $total_loker,
                'loker_kosong'     => $kosong,
                'loker_terisi'     => $terisi,
                'barang_tersimpan' => $b_tersimpan,
                'barang_diambil'   => $b_diambil
            ];
        }
    }

    return [
        'total_loker'      => count($mock_loker),
        'loker_kosong'     => count($mock_loker),
        'loker_terisi'     => 0,
        'barang_tersimpan' => 0,
        'barang_diambil'   => count($mock_barang)
    ];
}

function ambilRingkasanGedung($koneksi, $mock_gedung = []) {
    if ($koneksi) {
        $sql = "SELECT g.id_gedung, g.nama_gedung,
                       COUNT(l.id_loker) AS total_loker,
                       SUM(CASE WHEN l.status = 'Terisi' THEN 1 ELSE 0 END) AS loker_terisi
                FROM gedung g
                LEFT JOIN loker l ON g.id_gedung = l.id_gedung
                GROUP BY g.id_gedung, g.nama_gedung
                ORDER BY g.id_gedung ASC";
        $q = mysqli_query($koneksi, $sql);
        if ($q && mysqli_num_rows($q) > 0) {
            $data = [];
            while ($r = mysqli_fetch_assoc($q)) {
                $total  = (int)$r['total_loker'];
                $terisi = (int)($r['loker_terisi'] ?? 0);
                $kosong = max(0, $total - $terisi);
                $nama   = trim($r['nama_gedung']);
                $kode   = strtoupper(trim(str_replace('Gedung', '', $nama)));

                $data[] = [
                    'id_gedung'    => (int)$r['id_gedung'],
                    'nama_gedung'  => $nama,
                    'kode'         => $kode,
                    'total_loker'  => $total,
                    'loker_terisi' => $terisi,
                    'loker_kosong' => $kosong
                ];
            }
            if (!empty($data)) {
                return $data;
            }
        }
    }

    return $mock_gedung;
}

function ambilDaftarLoker($koneksi, $mock_loker) {
    if ($koneksi) {
        $sql = "SELECT l.*, g.nama_gedung, COUNT(b.id_barang) AS terisi
                FROM loker l
                LEFT JOIN gedung g ON l.id_gedung = g.id_gedung
                LEFT JOIN barang b ON l.id_loker = b.id_loker AND b.status = 'Tersimpan'
                GROUP BY l.id_loker
                ORDER BY l.nomor_loker ASC, l.id_loker ASC";
        $q = mysqli_query($koneksi, $sql);

        if ($q && mysqli_num_rows($q) > 0) {
            $data = [];
            while ($r = mysqli_fetch_assoc($q)) {
                $terisi = (int)$r['terisi'];
                $r['lokasi'] = trim($r['nama_gedung'] ?? 'Gedung A');

                if ($r['status'] === 'Terisi' || $terisi > 0) $r['status'] = 'Terisi';
                else $r['status'] = 'Kosong';

                $data[] = $r;
            }
            return $data;
        }
    }
    return $mock_loker;
}

function ambilDaftarBarang($koneksi, $mock_barang, $cari = '', $filter_status = '') {
    if ($koneksi) {
        $kondisi = "WHERE 1=1";
        if ($cari !== '') {
            $c = mysqli_real_escape_string($koneksi, $cari);
            $kondisi .= " AND (b.nama_barang LIKE '%$c%' OR u.nama_lengkap LIKE '%$c%' OR u.username LIKE '%$c%' OR l.nomor_loker LIKE '%$c%')";
        }
        if ($filter_status !== '') {
            $s = mysqli_real_escape_string($koneksi, $filter_status);
            $kondisi .= " AND b.status = '$s'";
        }

        $sql = "SELECT b.*, u.nama_lengkap AS nama_siswa, u.username AS nis_siswa, l.nomor_loker, g.nama_gedung AS lokasi
                FROM barang b
                LEFT JOIN users u ON b.id_siswa = u.id_user
                LEFT JOIN loker l ON b.id_loker = l.id_loker
                LEFT JOIN gedung g ON l.id_gedung = g.id_gedung
                $kondisi ORDER BY b.id_barang DESC";
        $q = mysqli_query($koneksi, $sql);

        if ($q) {
            $data = [];
            while ($r = mysqli_fetch_assoc($q)) {
                $r['lokasi'] = trim($r['lokasi'] ?? 'Gedung A');
                $data[] = $r;
            }
            return $data;
        }
    }
    return $mock_barang;
}

function ambilLogAktivitas($koneksi, $mock_log, $filter_aksi = '', $filter_tanggal = '') {
    if ($koneksi) {
        $kondisi = "WHERE 1=1";
        if ($filter_aksi !== '') {
            $a = mysqli_real_escape_string($koneksi, $filter_aksi);
            $kondisi .= " AND l.aksi = '$a'";
        }
        if ($filter_tanggal !== '') {
            $t = mysqli_real_escape_string($koneksi, $filter_tanggal);
            $kondisi .= " AND DATE(l.waktu) = '$t'";
        }

        $sql = "SELECT l.*, u.nama_lengkap AS nama_pengguna, u.role, b.nama_barang
                FROM log_aktivitas l
                LEFT JOIN users u ON l.id_admin = u.id_user
                LEFT JOIN barang b ON l.id_barang_terkait = b.id_barang
                $kondisi ORDER BY l.id_log DESC";

        $q = mysqli_query($koneksi, $sql);

        if ($q) {
            $data = [];
            while ($r = mysqli_fetch_assoc($q)) {
                $r['nama_admin'] = $r['nama_pengguna'] ?? 'Administrator';
                $r['role']       = $r['role'] ?? 'admin';
                if (!empty($r['nama_barang'])) {
                    $r['barang'] = $r['nama_barang'];
                } else {
                    $r['barang'] = 'Barang';
                }
                $data[] = $r;
            }
            return $data;
        }
    }

    return $mock_log;
}

function ambilRekapBarang($koneksi, $mock_barang, $tgl_awal = '', $tgl_akhir = '', $status = '', $kategori = '') {
    $daftar = ambilDaftarBarang($koneksi, $mock_barang, $kategori, $status);
    $data = [];
    $tersimpan = 0;
    $diambil = 0;
    $kategori_hitung = [];

    foreach ($daftar as $r) {
        $tgl_item = substr($r['waktu_penitipan'], 0, 10);
        if ($tgl_awal !== '' && $tgl_item < $tgl_awal) continue;
        if ($tgl_akhir !== '' && $tgl_item > $tgl_akhir) continue;

        $w_masuk  = strtotime($r['waktu_penitipan']);
        $w_keluar = !empty($r['waktu_pengambilan']) ? strtotime($r['waktu_pengambilan']) : time();
        $menit    = max(1, round(($w_keluar - $w_masuk) / 60));

        if ($menit < 60) $r['durasi'] = $menit . ' menit';
        elseif ($menit < 1440) $r['durasi'] = round($menit / 60, 1) . ' jam';
        else $r['durasi'] = round($menit / 1440) . ' hari';

        if ($r['status'] === 'Tersimpan') $tersimpan++;
        else $diambil++;

        $nama = strtolower($r['nama_barang']);
        $kat  = 'Lainnya';
        foreach (['tas', 'jaket', 'helm', 'laptop', 'sepatu', 'buku', 'botol'] as $k) {
            if (strpos($nama, $k) !== false) {
                $kat = ucfirst($k);
                break;
            }
        }
        $kategori_hitung[$kat] = ($kategori_hitung[$kat] ?? 0) + 1;
        $data[] = $r;
    }

    arsort($kategori_hitung);
    $total = count($data);

    $siswa_unik = [];
    foreach ($data as $r) {
        $nama_s = trim($r['nama_siswa'] ?? '');
        if ($nama_s !== '') $siswa_unik[$nama_s] = true;
        elseif (!empty($r['id_siswa'])) $siswa_unik['id_' . $r['id_siswa']] = true;
    }
    $total_siswa = count($siswa_unik);

    return [
        'data'             => $data,
        'total'            => $total,
        'tersimpan'        => $tersimpan,
        'diambil'          => $diambil,
        'total_siswa'      => $total_siswa,
        'kategori_populer' => $kategori_hitung
    ];
}

function ambilGrafikSiswaBulanan($koneksi) {
    $tahun = (int)date('Y');
    $bulan = (int)date('m');
    $total_hari = (int)date('t');

    $data_db = [];
    if ($koneksi) {
        $sql = "SELECT DATE(waktu_penitipan) AS tgl, COUNT(DISTINCT id_siswa) AS total_siswa
                FROM barang
                WHERE YEAR(waktu_penitipan) = '$tahun'
                  AND MONTH(waktu_penitipan) = '$bulan'
                  AND DAYOFWEEK(waktu_penitipan) BETWEEN 2 AND 6
                GROUP BY DATE(waktu_penitipan)";
        $q = mysqli_query($koneksi, $sql);
        if ($q) {
            while ($r = mysqli_fetch_assoc($q)) {
                $data_db[$r['tgl']] = (int)$r['total_siswa'];
            }
        }
    }

    $daftar_hari_singkat = [1 => 'Sen', 2 => 'Sel', 3 => 'Rab', 4 => 'Kam', 5 => 'Jum'];
    $hasil = [];
    $max_val = 5;

    for ($d = 1; $d <= $total_hari; $d++) {
        $time = mktime(0, 0, 0, $bulan, $d, $tahun);
        $day_num = (int)date('N', $time);
        if ($day_num <= 5) {
            $tgl_str = date('Y-m-d', $time);
            $jml = $data_db[$tgl_str] ?? 0;
            if ($jml > $max_val) {
                $max_val = $jml;
            }
            $hasil[] = [
                'tanggal'      => $tgl_str,
                'tgl_no'       => $d,
                'hari'         => $daftar_hari_singkat[$day_num],
                'label'        => str_pad($d, 2, '0', STR_PAD_LEFT) . '/' . str_pad($bulan, 2, '0', STR_PAD_LEFT),
                'jumlah_siswa' => $jml
            ];
        }
    }

    return [
        'bulan_nama'   => date('F Y'),
        'data'         => $hasil,
        'max_val'      => $max_val
    ];
}
?>