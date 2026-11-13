// ===============================
// MODAL TAMBAH
// ===============================

function bukaModalTambah() {
    const modal = document.getElementById("modalTambah");

    if (modal) {
        modal.classList.add("active");
    }
}


function tutupModal() {
    const modal = document.getElementById("modalTambah");

    if (modal) {
        modal.classList.remove("active");
    }
}


// ===============================
// MODAL EDIT
// ===============================

function bukaModalEdit(id, nomor, lokasi, status) {

    const modal = document.getElementById("modalEdit");

    const idInput = document.getElementById("edit_id_loker");
    const nomorInput = document.getElementById("edit_nomor_loker");
    const lokasiInput = document.getElementById("edit_lokasi");
    const statusTampilan =
        document.getElementById("edit_status_tampilan");


    if (!modal) {
        return;
    }


    // Masukkan data ke form
    idInput.value = id;
    nomorInput.value = nomor;
    lokasiInput.value = lokasi;


    // Tampilkan status saja
    if (status === "Kosong") {

        statusTampilan.textContent = "● Kosong";
        statusTampilan.className = "status kosong";

    } else {

        statusTampilan.textContent = "● Terisi";
        statusTampilan.className = "status terisi";

    }


    // Buka modal
    modal.classList.add("active");
}


function tutupEdit() {

    const modal = document.getElementById("modalEdit");

    if (modal) {
        modal.classList.remove("active");
    }

}


// ===============================
// MODAL HAPUS
// ===============================

function bukaModalHapus(id, nomor) {

    const modal = document.getElementById("modalHapus");

    const namaLoker =
        document.getElementById("namaLokerHapus");

    const linkHapus =
        document.getElementById("linkHapus");


    if (!modal) {
        return;
    }


    namaLoker.textContent = nomor;

    linkHapus.href =
        "hapus_loker.php?id_loker=" + id;


    modal.classList.add("active");
}


function tutupHapus() {

    const modal = document.getElementById("modalHapus");

    if (modal) {
        modal.classList.remove("active");
    }

}


// ===============================
// KLIK DI LUAR MODAL
// ===============================

document.addEventListener("click", function (event) {

    if (
        event.target.classList.contains("modal-overlay")
    ) {

        event.target.classList.remove("active");

    }

});


// ===============================
// TOMBOL ESC
// ===============================

document.addEventListener("keydown", function (event) {

    if (event.key === "Escape") {

        document
            .querySelectorAll(".modal-overlay")
            .forEach(function (modal) {

                modal.classList.remove("active");

            });

    }

});


// ===============================
// NOTIFIKASI TOASTR
// ===============================

document.addEventListener("DOMContentLoaded", function () {

    // Pastikan library toastr sudah termuat
    if (typeof toastr === "undefined") {
        return;
    }

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    const params = new URLSearchParams(window.location.search);
    const pesan = params.get("pesan");

    if (pesan === "tambah") {

        toastr.success("Data Loker berhasil ditambahkan!", "Berhasil");

    } else if (pesan === "edit") {

        toastr.success("Data Loker berhasil diubah!", "Berhasil");

    } else if (pesan === "hapus") {

        toastr.error("Data Loker berhasil dihapus!", "Dihapus");

    } else if (pesan === "duplikat") {

        toastr.error("Kode Loker sudah digunakan!", "Gagal");

    } else if (pesan === "kosong") {

        toastr.error("Nomor loker dan gedung wajib diisi!", "Gagal");

    } else if (pesan === "terisi") {

        toastr.error("Loker yang sedang terisi tidak dapat dihapus!", "Gagal");

    } else if (pesan === "gagal") {

        toastr.error("Terjadi kesalahan saat memproses data!", "Gagal");

    }

    // Hilangkan ?pesan=... dari URL supaya toast
    // tidak muncul lagi saat halaman di-refresh
    if (pesan) {
        window.history.replaceState({}, document.title, "dataloker.php");
    }

});