require("./bootstrap");

import moment from "moment";

// --- Fungsi-fungsi utilitas ---
function ucfirst(string) {
    if (!string) return ""; // Pastikan string tidak null atau undefined
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function getDepartmentName(materialRequest) {
    return materialRequest.user && materialRequest.user.department
        ? materialRequest.user.department.name
        : "";
}

// --- Audio ---
let audioContext;
let audioBuffer;
function initializeAudio() {
    // Inisialisasi AudioContext
    audioContext = new (window.AudioContext || window.webkitAudioContext)();

    // Ambil file audio dan decode menjadi buffer
    fetch("/sounds/notification.mp3") // Pastikan path ini sesuai dengan lokasi file audio Anda
        .then((response) => response.arrayBuffer())
        .then((data) => audioContext.decodeAudioData(data))
        .then((buffer) => {
            audioBuffer = buffer;
        })
        .catch((error) => {
            console.error("Failed to load audio file:", error);
        });
}
function playNotificationSound() {
    // Web Audio API
    if (audioContext && audioBuffer) {
        const source = audioContext.createBufferSource();
        source.buffer = audioBuffer;
        source.connect(audioContext.destination);
        source.start(0);
    } else {
        // Fallback ke HTML5 Audio jika gagal
        const audioEl = document.getElementById("notification-sound");
        if (audioEl) {
            audioEl.currentTime = 0;
            audioEl.play();
        }
    }
}

// --- Toast ---
function showToast(materialRequest, action, playSound = true) {
    const toastContainer = document.getElementById("toast-container");
    const toastTemplate = document.getElementById("toast-template");

    const departmentName = getDepartmentName(materialRequest);

    // Pastikan container dan template ada
    if (!toastContainer || !toastTemplate) return;

    // Clone elemen template toast
    const toastElement = toastTemplate.cloneNode(true);
    toastElement.classList.remove("d-none"); // Tampilkan toast
    toastElement.classList.add("toast"); // Tambahkan kelas toast

    // Tentukan pesan berdasarkan jenis aksi
    let message = "";
    if (action === "created") {
        message = `
            <strong>${ucfirst(materialRequest.requested_by)} (${ucfirst(
            departmentName
        )})</strong><br>
            <span class="text-success">New Request:</span> <strong>${
                materialRequest.inventory?.name || "N/A"
            }</strong>
            for <strong>${materialRequest.project?.name || "N/A"}</strong><br>
            <a href="/material_requests/${
                materialRequest.id
            }/edit" class="text-primary">View More...</a>
        `;
    } else if (action === "updated") {
        message = `
            <strong>${ucfirst(materialRequest.requested_by)} (${ucfirst(
            departmentName
        )})</strong><br>
            Material Request: <strong>${
                materialRequest.inventory?.name || "N/A"
            }</strong>
            for <strong>${materialRequest.project?.name || "N/A"}</strong>
            <span class="text-warning">has been updated.</span><br>
            <a href="/material_requests/${
                materialRequest.id
            }/edit" class="text-primary">View More...</a>
        `;
    } else if (action === "deleted") {
        message = `
            <strong>${ucfirst(materialRequest.requested_by)} (${ucfirst(
            departmentName
        )})</strong><br>
            Material Request: <strong>${
                materialRequest.inventory?.name || "N/A"
            }</strong>
            for <strong>${materialRequest.project?.name || "N/A"}</strong>
            <span class="text-danger">has been deleted.</span>
        `;
    } else if (action === "reminder") {
        message = `
            <strong>Hi Admin</strong><br>
            You haven't send <strong>${
                materialRequest.inventory?.name || "N/A"
            }</strong>
            for <strong>${materialRequest.project?.name || "N/A"}</strong>
            from <strong>${ucfirst(materialRequest.requested_by)}</strong>.<br>
            <span class="text-danger">Tolong segera diantar!</span>
        `;
    } else {
        // Jika action tidak dikenali, jangan tampilkan toast
        return;
    }

    // Isi konten toast
    toastElement.querySelector(".toast-time").textContent = moment(
        materialRequest.created_at
    ).fromNow();
    toastElement.querySelector(".toast-body").innerHTML = message;

    // Tambahkan toast ke dalam container
    toastContainer.appendChild(toastElement);

    // Tampilkan toast dengan opsi autohide: true
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true, // Atur autohide sesuai kebutuhan
        delay: 15000, // Tampilkan selama 15 detik
    });
    toast.show();

    // Putar suara notifikasi jika diizinkan
    if (playSound) {
        playNotificationSound();
    }

    // Hapus toast dari DOM jika tombol silang diklik
    toastElement.addEventListener("hidden.bs.toast", () => {
        toastElement.remove();
    });
}

// --- DataTable & Select Color ---
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

function updateDataTable(materialRequest) {
    const table = $("#datatable").DataTable();
    const rowSelector = `#row-${materialRequest.id}`;

    if (table.page.info().serverSide) {
        // Check if the updated row is in current view
        const currentData = table.rows().data().toArray();
        const existingRowIndex = currentData.findIndex(
            (row) => row.id == materialRequest.id
        );

        if (existingRowIndex !== -1) {
            // Row exists in current view, reload table
            table.draw("page"); // Reload current page only
        } else {
            // Row might be on different page, check if it should be visible with current filters
            table.draw(false); // Reload without resetting pagination
        }
        table.ajax.reload(null, false);
    } else {
        // Logika untuk kolom status
        let statusColumn = "";
        if (["admin_logistic", "super_admin"].includes(authUserRole)) {
            statusColumn = `<select name="status" class="form-select form-select-sm status-select status-select-rounded status-quick-update"
            data-id="${materialRequest.id}" ${
                materialRequest.status === "delivered" ? "disabled" : ""
            }>
            <option value="pending" ${
                materialRequest.status === "pending" ? "selected" : ""
            }>Pending</option>
            <option value="approved" ${
                materialRequest.status === "approved" ? "selected" : ""
            }>Approved</option>
            <option value="canceled" ${
                materialRequest.status === "canceled" ? "selected" : ""
            }>Canceled</option>
            <option value="delivered" ${
                materialRequest.status === "delivered" ? "selected" : ""
            } disabled>Delivered</option>
            </select>`;
        } else {
            const badgeClass =
                materialRequest.status === "pending"
                    ? "text-bg-warning"
                    : materialRequest.status === "approved"
                    ? "text-bg-primary"
                    : materialRequest.status === "delivered"
                    ? "text-bg-success"
                    : materialRequest.status === "canceled"
                    ? "text-bg-danger"
                    : "";

            statusColumn = `<span class="badge rounded-pill ${badgeClass}">${ucfirst(
                materialRequest.status
            )}</span>`;
        }

        // Pastikan juga setelah row update, initialize previous value
        setTimeout(() => {
            const $newSelect = $(
                `#row-${materialRequest.id} .status-quick-update`
            );
            if ($newSelect.length) {
                $newSelect.data("previous-value", $newSelect.val());
                updateSelectColor($newSelect[0]);
            }
        }, 100);

        // Logika untuk checkbox
        let checkboxColumn = "";
        if (materialRequest.status === "approved") {
            checkboxColumn = `<input type="checkbox" class="select-row" value="${materialRequest.id}">`;
        }

        // Logika untuk tombol Goods Out, Edit, Delete
        const authUser = window.authUser || {};
        const isLogisticAdmin = !!authUser.is_logistic_admin;
        const isSuperAdmin = !!authUser.is_super_admin;
        const isRequestOwner =
            authUser.username === materialRequest.requested_by;

        let actionColumn = `<div class="d-flex flex-nowrap gap-1">`;

        // Goods Out Button
        if (
            materialRequest.status === "approved" &&
            materialRequest.status !== "canceled" &&
            materialRequest.qty - (materialRequest.processed_qty ?? 0) > 0 &&
            isLogisticAdmin
        ) {
            actionColumn += `
            <a href="/goods_out/create/${materialRequest.id}" class="btn btn-sm btn-success" title="Goods Out">
                <i class="bi bi-box-arrow-right"></i>
            </a>
            `;
        }

        // Edit Button
        if (
            materialRequest.status !== "canceled" &&
            (isRequestOwner || isLogisticAdmin)
        ) {
            actionColumn += `
            <a href="/material_requests/${materialRequest.id}/edit" class="btn btn-sm btn-warning" title="Edit">
                <i class="bi bi-pencil-square"></i>
            </a>
            `;
        }

        // Delete Button - Updated Logic
        let canDelete = false;
        let deleteTooltip = "Delete";

        if (["approved", "delivered"].includes(materialRequest.status)) {
            // Only super admin can delete approved/delivered requests
            if (isSuperAdmin) {
                canDelete = true;
                deleteTooltip = "Delete (Super Admin Only)";
            }
        } else if (materialRequest.status === "pending") {
            // Pending: Only Owner or Super Admin can delete
            if (isRequestOwner || isSuperAdmin) {
                canDelete = true;
                deleteTooltip = isRequestOwner
                    ? "Delete Your Request"
                    : "Delete (Super Admin)";
            }
        } else if (materialRequest.status === "canceled") {
            // Canceled: Owner, Admin Logistic, or Super Admin can delete
            if (isRequestOwner || isLogisticAdmin || isSuperAdmin) {
                canDelete = true;
                if (isRequestOwner) {
                    deleteTooltip = "Delete Your Canceled Request";
                } else if (isLogisticAdmin) {
                    deleteTooltip = "Delete Canceled Request (Logistic Admin)";
                } else {
                    deleteTooltip = "Delete Canceled Request (Super Admin)";
                }
            }
        }

        if (canDelete) {
            actionColumn += `
        <form action="/material_requests/${
            materialRequest.id
        }" method="POST" class="delete-form" style="display:inline;">
            <input type="hidden" name="_method" value="DELETE">
            <input type="hidden" name="_token" value="${$(
                'meta[name="csrf-token"]'
            ).attr("content")}">
            <button type="button" class="btn btn-sm btn-danger btn-delete" title="${deleteTooltip}">
                <i class="bi bi-trash3"></i>
            </button>
        </form>
        `;
        }

        // Reminder Button
        if (
            ["pending", "approved"].includes(materialRequest.status) &&
            (isRequestOwner || isSuperAdmin)
        ) {
            actionColumn += `
            <button class="btn btn-sm btn-primary btn-reminder"
                data-id="${materialRequest.id}" data-bs-toggle="tooltip"
                data-bs-placement="bottom" title="Remind Logistic">
                <i class="bi bi-bell"></i>
            </button>
        `;
        }

        // Format angka dinamis
        function formatNumberDynamic(num) {
            if (num == null) return "0";
            num = Number(num);
            if (Number.isInteger(num)) return num.toString();
            return num % 1 === 0
                ? num.toFixed(0)
                : num.toString().replace(/\.?0+$/, "");
        }

        // Format tanggal
        const formattedDate = moment(materialRequest.created_at).format(
            "YYYY-MM-DD, HH:mm"
        );

        // Ambil nama departemen dari user yang membuat permintaan
        const departmentName = getDepartmentName(materialRequest);

        const rowData = [
            checkboxColumn, // Checkbox
            materialRequest.id, // Kolom ID tersembunyi
            materialRequest.project?.name || "N/A", // Project
            `<span class="material-detail-link gradient-link" data-id="${
                materialRequest.inventory?.id || ""
            }" style="cursor:pointer;">
        ${materialRequest.inventory?.name || "(No Material)"}
        </span>`, // Material Name with gradient link
            `${formatNumberDynamic(materialRequest.qty)} ${
                materialRequest.inventory?.unit || ""
            }`, // Requested Qty
            `<span data-bs-toggle="tooltip" data-bs-placement="right" title="${
                materialRequest.inventory?.unit || "(No Unit)"
            }">
            ${formatNumberDynamic(
                materialRequest.qty - (materialRequest.processed_qty ?? 0)
            )}
        </span>`, // Remaining Qty with tooltip
            `<span data-bs-toggle="tooltip" data-bs-placement="right" title="${
                materialRequest.inventory?.unit || "(No Unit)"
            }">
            ${formatNumberDynamic(materialRequest.processed_qty ?? 0)}
        </span>`, // Processed Qty with tooltip
            `<span data-bs-toggle="tooltip" data-bs-placement="right" title="${ucfirst(
                departmentName
            )}" class="requested-by-tooltip">${ucfirst(
                materialRequest.requested_by
            )}</span>`, // Requested By
            formattedDate, // Requested At (format lokal)
            statusColumn, // Status
            materialRequest.remark || "-", // Remark
            actionColumn, // Action
        ];

        if (!row.node()) {
            table.row.add(rowData).draw();
            table.order([8, "desc"]).draw(); // Urutkan ulang tabel berdasarkan kolom `Requested At`
            return;
        }

        row.data(rowData).draw();
        table.order([8, "desc"]).draw(); // Urutkan ulang tabel setelah pembaruan

        // Perbarui warna elemen <select> setelah elemen ditambahkan
        const selectElement = row.node().querySelector(".status-select");
        if (selectElement) {
            updateSelectColor(selectElement);
        }

        // Inisialisasi ulang tooltip Bootstrap pada elemen baru
        const tooltipTriggerList = [].slice.call(
            row.node().querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Update bulk goods out button count after DataTable update
    setTimeout(() => {
        if (window.updateBulkGoodsOutButton) {
            window.updateBulkGoodsOutButton();
        }
    }, 100);
}

// Fungsi untuk deteksi apakah sedang di halaman form create, bulk create, atau edit material request
function isMaterialRequestFormPage() {
    const path = window.location.pathname;
    // Cek path create, bulk_create, atau edit (misal: /material_requests/create, /material_requests/bulk_create, /material_requests/123/edit)
    return (
        /\/material_requests\/create$/.test(path) ||
        /\/material_requests\/bulk_create$/.test(path) ||
        /\/material_requests\/\d+\/edit$/.test(path)
    );
}

function ensureAudioContextActive() {
    if (audioContext && audioContext.state === "suspended") {
        audioContext.resume();
    }
}

// --- Event DOMContentLoaded ---
document.addEventListener("DOMContentLoaded", () => {
    initializeAudio();

    // Aktifkan AudioContext pada gesture pertama user (klik, tap, dsb)
    const resumeAudio = () => {
        ensureAudioContextActive();
        // Hanya perlu sekali, lalu hapus listener
        document.body.removeEventListener("click", resumeAudio);
        document.body.removeEventListener("keydown", resumeAudio);
        document.body.removeEventListener("touchstart", resumeAudio);
    };
    document.body.addEventListener("click", resumeAudio);
    document.body.addEventListener("keydown", resumeAudio);
    document.body.addEventListener("touchstart", resumeAudio);

    // Listener real-time toast & suara: SELALU AKTIF di semua halaman
    window.Echo.channel("material-requests").listen(
        "MaterialRequestUpdated",
        (e) => {
            function handleRequest(request) {
                // Hanya update DataTable jika tabel material request ada
                const materialRequestTable = document.querySelector(
                    '#datatable[data-material-request-table="1"]'
                );
                if (materialRequestTable) {
                    if (e.action === "deleted") {
                        const table = $("#datatable").DataTable();
                        const row = table.row(`#row-${request.id}`);
                        if (row.node()) {
                            row.remove().draw();
                        }
                    } else {
                        updateDataTable(request);
                    }
                }
                // Toast & suara: HANYA jika BUKAN di halaman form
                if (e.action !== "status" && !isMaterialRequestFormPage()) {
                    showToast(request, e.action, true);
                }
            }

            if (Array.isArray(e.materialRequest)) {
                e.materialRequest.forEach(handleRequest);
            } else {
                handleRequest(e.materialRequest);
            }
        }
    );

    window.Echo.channel("material-requests").listen(
        "MaterialRequestReminder",
        function (e) {
            if (window.authUser && window.authUser.is_logistic_admin) {
                showToast(e.materialRequest, "reminder", true);
            }
        }
    );

    // Hanya jalankan select color logic jika tabel material request ada
    const materialRequestTable = document.querySelector(
        '#datatable[data-material-request-table="1"]'
    );
    if (materialRequestTable) {
        // Terapkan fungsi ke semua elemen <select> dengan kelas .status-select
        const statusSelectElements =
            document.querySelectorAll(".status-select");
        statusSelectElements.forEach((selectElement) => {
            updateSelectColor(selectElement);
            selectElement.addEventListener("change", () => {
                updateSelectColor(selectElement);
            });
        });
    }
});

// --- Event DataTable redraw ---
$("#datatable").on("draw.dt", function () {
    const statusSelectElements = document.querySelectorAll(".status-select");
    statusSelectElements.forEach((selectElement) => {
        updateSelectColor(selectElement);
    });
});

// --- Audio resume on user interaction ---
document.body.addEventListener(
    "click",
    function () {
        if (audioContext && audioContext.state === "suspended") {
            audioContext.resume();
        }
    },
    { once: true }
);
