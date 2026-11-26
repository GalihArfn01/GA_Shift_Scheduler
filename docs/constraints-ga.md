# Constraints Genetic Algorithm Scheduler

Dokumen ini merangkum seluruh **Hard Constraint (HC)** dan **Soft Constraint (SC)** yang diimplementasikan dalam `app/Services/GeneticAlgorithmScheduler.php`.

---

## Hard Constraints (HC)

Hard Constraint adalah aturan yang **wajib dipenuhi**. Pelanggaran terhadap HC menghasilkan solusi yang tidak valid.

| ID | Deskripsi | Cara Penegakan |
|----|-----------|----------------|
| **HC1** | Semua slot kebutuhan operasional harus terisi oleh seorang agent | Dijamin oleh encoding slot-centric saat pembangunan kromosom. Slot yang masih kosong ditutup paksa oleh `fillRemainingGapsEmergency()` |
| **HC2** | Satu agent tidak boleh dijadwalkan lebih dari sekali dalam satu hari (*double-booking*) | Ditangani oleh `repairChromosome()` — konflik double-booking digantikan dengan agent lain atau dikosongkan |
| **HC3** | Level kompetensi agent harus sesuai dengan section yang ditugaskan | Dijamin saat encoding lewat `levelAllowedForSection()`. Aturan level per section: Inbound → L1/L2/L3, Informasi → L2/L3, Website → L3, Chat → L3 |
| **HC4** | Hanya agent berstatus `aktif` yang boleh dijadwalkan | Difilter di `getEligibleAgentIds()` dan `findReplacement()` sebelum agent masuk ke pool kandidat |
| **HC5** | Satu agent hanya boleh mendapat satu shift per hari | Ditangani bersama HC2 oleh `repairChromosome()` |
| **HC6** | Shift berkode `A7` dan `A` hanya boleh ditempatkan di section **Chat** | Dicek lewat `shiftAllowedForSection()` saat menentukan eligibilitas agent untuk setiap slot |

---

## Soft Constraints (SC)

Soft Constraint adalah aturan yang **diusahakan dipenuhi**. Pelanggaran tidak menjadikan solusi invalid, tetapi menambah nilai penalti yang menurunkan nilai fitness.

| ID | Deskripsi | Bobot Penalti | Cara Perhitungan |
|----|-----------|:-------------:|-----------------|
| **SC1** | Menghindari penjadwalan agent yang memiliki off request **prioritas tinggi** | 100.0 | Setiap pelanggaran off request prioritas tinggi dikali bobot. Dihitung di `calculatePenaltySc1Sc2()` |
| **SC2** | Menghindari penjadwalan agent yang memiliki off request **prioritas rendah** | 20.0 | Setiap pelanggaran off request prioritas rendah dikali bobot. Dihitung bersama SC1 di `calculatePenaltySc1Sc2()` |
| **SC3** | Jumlah hari kerja agent dalam satu periode tidak boleh melebihi **5 hari** | 30.0 | Kelebihan hari kerja di atas batas maksimum (`MAX_HARI_KERJA = 5`) dikali bobot. Dihitung di `calculatePenaltySc3()` |
| **SC4** | Pemerataan beban kerja antar agent (termasuk data histori periode sebelumnya di bulan yang sama) | 10.0 | Menggunakan standar deviasi jumlah hari kerja seluruh agent. Semakin kecil standar deviasi, semakin merata. Dihitung di `calculatePenaltySc4()` |
| **SC5** | Jarak istirahat antar shift pada hari kerja yang **berurutan** minimal **11 jam** | 5.0 | Membandingkan `jam_pulang` shift hari sebelumnya dengan `jam_masuk` shift hari berikutnya. Setiap pelanggaran dikali bobot. Dihitung di `calculatePenaltySc5()` |
| **SC6** | Pemerataan penugasan ke section **Inbound** antar agent (termasuk data histori) | 8.0 | Menggunakan standar deviasi jumlah penugasan inbound seluruh agent. Dihitung di `calculatePenaltySc6()` |

---

## Formula Fitness

Nilai fitness dihitung dengan formula berikut:

```
fitness = 1 / (1 + total_penalty)
```

di mana:

```
total_penalty = penalti_SC1 + penalti_SC2 + penalti_SC3 + penalti_SC4 + penalti_SC5 + penalti_SC6
```

Semakin kecil total penalti, semakin mendekati **1** nilai fitness-nya (solusi optimal).

---

## Ringkasan Bobot Penalti

```
SC1 (off request tinggi)  : 100.0  ← prioritas tertinggi
SC2 (off request rendah)  :  20.0
SC3 (max hari kerja)      :  30.0
SC4 (pemerataan kerja)    :  10.0
SC5 (istirahat min 11 jam):   5.0
SC6 (pemerataan inbound)  :   8.0
```

---

## Referensi

- Implementasi: `app/Services/GeneticAlgorithmScheduler.php`
- Konstanta batas hari kerja: `MAX_HARI_KERJA = 5`
- Minimum istirahat antar shift: `11 jam`
