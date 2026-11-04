# Informasi & Sumber Dataset Klasifikasi Sampah

Dokumen ini menjelaskan spesifikasi, sumber data, struktur folder, pembagian data (*train-test split*), serta teknik *preprocessing* dan augmentasi pada dataset yang digunakan untuk melatih model AI pada **Smart Waste Management System**.

---

## 1. Ringkasan Dataset

- **Lokasi Folder:** `dataset/`
- **Total Gambar:** **450 citra** (`7.42 MB`, `0` file rusak)
- **Format Citra:** `.jpg` (RGB)
- **Jumlah Kelas:** **3 kategori seimbang (1:1:1)**

| Sub-Folder Dataset | Label Kelas pada Model & Database | Jumlah Gambar | Data Training (80%) | Data Testing (20%) |
| :--- | :--- | :---: | :---: | :---: |
| `dataset/organic/` | `Organic Waste` | 150 | 120 | 30 |
| `dataset/paper/` | `Paper Waste` | 150 | 120 | 30 |
| `dataset/plastic/` | `Plastic Waste` | 150 | 120 | 30 |
| **Total** | **3 Kelas** | **450** | **360** | **90** |

---

## 2. Sumber Data & Komposisi Citra

Dataset disusun secara terkurasi dari repositori publik klasifikasi sampah berlisensi terbuka agar mencakup variasi pencahayaan dan bentuk objek dunia nyata:

1. **TrashNet Dataset (Gary Thung & Mindy Yang, Stanford University)**
   - Menyumbangkan sampel citra kemasan plastik (`plastic*_trashnet.jpg`) serta kertas/kardus (`cardboard*_trashnet.jpg`, `paper*_trashnet.jpg`).
2. **Garbage Classification Dataset (Kaggle / Public Waste Benchmark)**
   - Menyumbangkan citra limbah organik biologis (`biological*.jpg` seperti sisa makanan, sayuran, kulit buah, dan dedaunan) serta variasi tambahan objek kertas dan plastik (`paper_object_*_new.jpg`, `plastic_object_*_new.jpg`).

Seluruh 450 file gambar telah diverifikasi integritasnya menggunakan `PIL.Image.verify()` sehingga bebas dari file korup atau berformat tidak valid.

---

## 3. Preprocessing & Image Augmentation

Pada pipeline pelatihan `ml-service/train_model.py` dan `ml-service/training_notebook.ipynb`, setiap citra melalui tahapan berikut:

1. **Resize & Color Space:** Seluruh gambar dikonversi ke kanal warna `RGB` dengan ukuran input standar `224 x 224` piksel.
2. **Stratified Train-Test Split (80:20):** Pembagian dilakukan menggunakan `train_test_split(..., test_size=0.20, stratify=labels, random_state=42)` untuk menjaga proporsi kelas tetap seimbang di data latih (360 citra) dan data uji (90 citra).
3. **Normalisasi MobileNetV2:** Piksel dinormalisasi ke rentang `[-1, 1]` menggunakan `tensorflow.keras.applications.mobilenet_v2.preprocess_input`.
4. **Data Augmentation (Khusus Data Training):**
   - `RandomFlip("horizontal")`
   - `RandomRotation(0.15)`
   - `RandomZoom(0.15)`
   - `RandomContrast(0.10)`

---

## 4. Ringkasan Hasil Pelatihan & Evaluasi

Model menggunakan **Transfer Learning MobileNetV2** (*pre-trained* `imagenet`) dengan dua tahap pelatihan (*head training* diikuti *fine-tuning* lapisan atas) dan dievaluasi pada 90 citra data uji:

- **Test Accuracy:** **94.44%** (melampaui batas minimal `80%`)
- **Macro Avg Precision:** `0.9497` (`94.97%`)
- **Macro Avg Recall:** `0.9444` (`94.44%`)
- **Macro Avg F1-Score:** `0.9442` (`94.42%`)

Bukti evaluasi lengkap tersimpan di dalam folder `docs/ml_evaluation/`:
- `docs/ml_evaluation/classification_report.txt`
- `docs/ml_evaluation/confusion_matrix.png`
- `docs/ml_evaluation/training_history.png`
