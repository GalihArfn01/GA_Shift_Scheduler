import "./bootstrap";
import Alpine from "alpinejs";
import Swup from "swup";
import SwupFadeTheme from "@swup/fade-theme";

window.Alpine = Alpine;

Alpine.data("regenForm", () => ({
    regenShowModal: false,
    regenLoading: false,
    regenShowCfg: false,
    regenSubmit() {
        this.regenLoading = true;
        const form = document.getElementById("regen-form-element");
        if (!form) {
            this.regenLoading = false;
            return;
        }
        const fields = [
            "population_size", "max_generations", "stagnation_limit",
            "mutation_rate", "elite_rate", "tournament_size",
            "weight_sc1", "weight_sc2", "weight_sc3", "weight_sc4",
            "weight_sc5", "weight_sc6", "weight_sc7", "weight_sc8",
        ];
        fields.forEach((field) => {
            const input = document.getElementById("regen_" + field);
            if (input && input.value.trim() !== "") {
                const old = form.querySelector('[name="' + field + '"]');
                if (old) old.remove();
                const hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = field;
                hidden.value = input.value.trim();
                form.appendChild(hidden);
            }
        });
        form.submit();
    },
}));

Alpine.start();

const swup = new Swup({
    containers: ["#swup"],
    plugins: [new SwupFadeTheme()],
    ignoreVisit: (url) =>
        url.includes("/export-excel") ||
        url.includes("/export-pdf") ||
        url.includes("/logout"),
});

window.swup = swup;

// Fallback: jika Swup gagal memproses response (misal ternyata ada link lain
// yang menuju file/endpoint non-HTML dan lolos dari ignoreVisit di atas),
// reload halaman secara manual daripada membiarkan UI blank/macet.
swup.hooks.on("visit:end", (visit) => {
    if (visit.to.html === undefined && !document.querySelector("#swup")?.innerHTML.trim()) {
        window.location.reload();
    }
});

// Alpine hanya scan DOM sekali saat load awal. Setiap kali Swup mengganti
// konten #swup dengan halaman baru, kita perlu memberitahu Alpine untuk
// menginisialisasi ulang elemen-elemen x-data di konten baru tersebut.
//
// Penting: pakai destroyTree dulu lalu initTree. Hanya mengandalkan guard
// `_x_dataStack` tidak cukup — saat observer mutasi Alpine dan hook Swup
// berjalan di urutan yang bersaing, efek direktif (mis. x-show) bisa terikat
// ke scope/proxy lama sehingga reaksi reactivitas mati (state berubah tapi
// tampilan tidak). destroyTree+initTree memastikan SEMUA efek dibuat ulang
// terhadap scope terbaru, deterministik setiap navigasi.
// Swup TIDAK mengeksekusi inline <script> dari konten yang di-swap. Padahal
// beberapa halaman mendefinisikan komponen Alpine / IIFE lewat script inline
// (wizard: kebutuhanWizard + inboundToggle, team-schedule: teamSchedule,
// shift-swaps/create: swapForm, run: polling GA). Tanpa eksekusi ulang, semua
// x-data yang bergantung fungsi tersebut rusak setelah navigasi swup
// ("xxx is not defined"). Jalankan ulang di global scope, persis seperti
// browser mengeksekusi script saat full page load.
function reexecuteInlineScripts(root) {
    root.querySelectorAll("script:not([src])").forEach((script) => {
        const code = script.textContent;
        if (!code.trim()) return;
        try {
            // Indirect eval → dieksekusi di global scope, sehingga deklarasi
            // `function foo() {}` menjadi window.foo (sama seperti script asli).
            (0, eval)(code);
        } catch (e) {
            console.error("[swup] Gagal mengeksekusi inline script:", e);
        }
    });
}

// Urutan kritis saat navigasi swup:
//   Swup fetch halaman -> ganti isi #swup (default handler) -> jalankan
//   handler `after` (registrasi via hooks.on).
// Alpine punya MutationObserver sendiri di document. Saat Swup mengganti
// konten, observer itu keburu meng-init [x-data] baru SEBELUM handler `after`
// kita berjalan — padahal inline script (definisi fungsi komponen) belum
// dijalankan ulang. Akibatnya Alpine mengevaluasi `x-data="kebutuhanWizard()"`
// saat fungsinya belum ada → error "xxx is not defined" beruntun.
//
// Solusi: hentikan observer Alpine SEBELUM swap (hook `before`), lalu setelah
// konten masuk: eksekusi ulang inline script + init tree manual, barulah
// observer diaktifkan kembali. Dengan begitu tidak ada race sama sekali.
swup.hooks.before("content:replace", () => {
    Alpine.stopObservingMutations();
});

swup.hooks.on("content:replace", () => {
    const container = document.querySelector("#swup");
    if (!container) return;
    try {
        // 1. Eksekusi ulang inline script DULU (definisi fungsi global),
        //    baru Alpine di-init terhadap scope yang valid.
        reexecuteInlineScripts(container);

        // 2. Re-init Alpine untuk semua elemen x-data di konten baru.
        container.querySelectorAll("[x-data]").forEach((el) => {
            Alpine.destroyTree(el);
            Alpine.initTree(el);
        });
    } finally {
        // 3. Kembalikan observer Alpine (untuk konten hasil navigasi berikutnya).
        //    pakai finally supaya observer TIDAK pernah tertinggal mati.
        Alpine.startObservingMutations();
    }
});

// Sidebar (di luar #swup) tidak ikut di-replace oleh Swup saat navigasi,
// sehingga status menu aktif perlu diberi tahu secara manual. Sidebar
// mendengarkan event ini untuk memperbarui highlight menu sesuai URL terbaru.
swup.hooks.on("page:view", () => {
    window.dispatchEvent(new Event("swup:page-view"));
});

// CSRF token bisa basi karena halaman tidak full-reload. Setelah konten
// baru dimuat, perbarui meta tag csrf-token dari response Swup supaya
// form yang ada di halaman baru tetap punya token yang valid.
swup.hooks.on("content:replace", (visit) => {
    const newToken = visit.to.document?.querySelector('meta[name="csrf-token"]')?.content;
    if (newToken) {
        document.querySelector('meta[name="csrf-token"]').setAttribute("content", newToken);
    }
});
