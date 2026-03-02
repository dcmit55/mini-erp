function updateSelectColor(selectElement) {
    // Hapus semua kelas status dari elemen <select>
    selectElement.classList.remove(
        "status-pending",
        "status-approved",
        "status-delivered",
        "status-canceled"
    );

    // Tambahkan kelas berdasarkan nilai yang dipilih
    const selectedValue = selectElement.value;
    if (selectedValue === "pending") {
        selectElement.classList.add("status-pending");
    } else if (selectedValue === "approved") {
        selectElement.classList.add("status-approved");
    } else if (selectedValue === "delivered") {
        selectElement.classList.add("status-delivered");
    } else if (selectedValue === "canceled") {
        selectElement.classList.add("status-canceled");
    }
}

// Terapkan fungsi ke semua elemen <select> dengan kelas .status-select
document.addEventListener("DOMContentLoaded", () => {
    const statusSelectElements = document.querySelectorAll(".status-select");
    statusSelectElements.forEach((selectElement) => {
        // Perbarui warna saat halaman dimuat
        updateSelectColor(selectElement);

        // Perbarui warna saat nilai berubah
        selectElement.addEventListener("change", () => {
            updateSelectColor(selectElement);
        });
    });
});

// Agar klik tetap bisa toggle di mobile
document.querySelectorAll(".navbar .dropdown").forEach(function (dropdown) {
    dropdown.addEventListener("shown.bs.dropdown", function () {
        this.classList.add("show");
    });
    dropdown.addEventListener("hidden.bs.dropdown", function () {
        this.classList.remove("show");
    });
});
