<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WasteReport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrNew(['email' => 'admin@waste.com']);
        $admin->fill([
            'name' => 'Administrator Sistem',
            'password' => 'password',
        ]);
        $admin->role = 'admin';
        $admin->save();

        $user1 = User::firstOrNew(['email' => 'user@waste.com']);
        $user1->fill([
            'name' => 'Budi Santoso',
            'password' => 'password',
        ]);
        $user1->role = 'user';
        $user1->save();

        $user2 = User::firstOrNew(['email' => 'siti@waste.com']);
        $user2->fill([
            'name' => 'Siti Aminah',
            'password' => 'password',
        ]);
        $user2->role = 'user';
        $user2->save();

        Storage::disk('public')->makeDirectory('reports');

        $datasetMap = [
            'Organic Waste' => ['folder' => 'organic', 'target' => 'reports/sample_organic.jpg'],
            'Plastic Waste' => ['folder' => 'plastic', 'target' => 'reports/sample_plastic.jpg'],
            'Paper Waste' => ['folder' => 'paper', 'target' => 'reports/sample_paper.jpg'],
        ];

        foreach ($datasetMap as $config) {
            $sourceDir = base_path('../dataset/' . $config['folder']);
            if (File::isDirectory($sourceDir)) {
                $files = File::files($sourceDir);
                if (! empty($files)) {
                    Storage::disk('public')->put(
                        $config['target'],
                        File::get($files[0]->getRealPath())
                    );
                }
            }
        }

        if (WasteReport::count() > 0) {
            return;
        }

        $reportsData = [
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_plastic.jpg',
                'waste_type' => 'Plastic Waste',
                'confidence_score' => 0.9812,
                'latitude' => -6.20876340,
                'longitude' => 106.84559900,
                'address' => 'Jl. HR Rasuna Said Kav. C-22, Kuningan, Jakarta Selatan',
                'description' => 'Tumpukan botol plastik bekas minuman di dekat halte TransJakarta Kuningan.',
                'status' => 'Completed',
                'admin_notes' => 'Telah diangkut oleh tim kebersihan sektor Selatan pukul 09:30 WIB.',
                'days_ago' => 12,
            ],
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_organic.jpg',
                'waste_type' => 'Organic Waste',
                'confidence_score' => 0.9540,
                'latitude' => -6.18648600,
                'longitude' => 106.83409100,
                'address' => 'Pasar Cikini Ampiun, Menteng, Jakarta Pusat',
                'description' => 'Sisa sayuran dan buah-buahan yang meluber dari bak penampungan sementara.',
                'status' => 'Completed',
                'admin_notes' => 'Sudah dibersihkan dan dikirim ke fasilitas pengomposan.',
                'days_ago' => 11,
            ],
            [
                'user_id' => $user2->id,
                'photo_path' => 'reports/sample_paper.jpg',
                'waste_type' => 'Paper Waste',
                'confidence_score' => 0.9325,
                'latitude' => -6.17539240,
                'longitude' => 106.82715280,
                'address' => 'Jl. Medan Merdeka Selatan No. 8, Gambir, Jakarta Pusat',
                'description' => 'Kardus kemasan dan tumpukan kertas arsip berserakan di tepi trotoar.',
                'status' => 'Completed',
                'admin_notes' => 'Kardus telah dipilah untuk unit daur ulang kertas.',
                'days_ago' => 10,
            ],
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_plastic.jpg',
                'waste_type' => 'Plastic Waste',
                'confidence_score' => 0.9685,
                'latitude' => -6.22496000,
                'longitude' => 106.80969000,
                'address' => 'Jl. Jenderal Sudirman Kav. 52-53, Senayan, Jakarta Selatan',
                'description' => 'Kantong kresek dan gelas plastik sekali pakai menumpuk di taman pedestrian.',
                'status' => 'In Progress',
                'admin_notes' => 'Petugas lapangan sedang menuju titik koordinat.',
                'days_ago' => 8,
            ],
            [
                'user_id' => $user2->id,
                'photo_path' => 'reports/sample_organic.jpg',
                'waste_type' => 'Organic Waste',
                'confidence_score' => 0.9410,
                'latitude' => -6.19312500,
                'longitude' => 106.82298000,
                'address' => 'Jl. Kebon Kacang Raya, Tanah Abang, Jakarta Pusat',
                'description' => 'Sampah sisa makanan warung makan yang belum terangkut sejak kemarin sore.',
                'status' => 'In Progress',
                'admin_notes' => 'Dijadwalkan pengangkutan pada shift siang hari ini.',
                'days_ago' => 7,
            ],
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_paper.jpg',
                'waste_type' => 'Paper Waste',
                'confidence_score' => 0.9188,
                'latitude' => -6.21462000,
                'longitude' => 106.84513000,
                'address' => 'Jl. Dr. Saharjo No. 120, Tebet, Jakarta Selatan',
                'description' => 'Tumpukan kertas brosur dan karton basah di dekat jembatan penyeberangan.',
                'status' => 'Pending',
                'admin_notes' => null,
                'days_ago' => 6,
            ],
            [
                'user_id' => $user2->id,
                'photo_path' => 'reports/sample_plastic.jpg',
                'waste_type' => 'Plastic Waste',
                'confidence_score' => 0.9910,
                'latitude' => -6.16832900,
                'longitude' => 106.79051200,
                'address' => 'Jl. Kyai Tapa No. 1, Grogol Petamburan, Jakarta Barat',
                'description' => 'Botol kemasan air mineral dan bungkus makanan ringan menyumbat saluran air.',
                'status' => 'Completed',
                'admin_notes' => 'Saluran air sudah dibersihkan oleh tim PPSU.',
                'days_ago' => 5,
            ],
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_organic.jpg',
                'waste_type' => 'Organic Waste',
                'confidence_score' => 0.9715,
                'latitude' => -6.23827000,
                'longitude' => 106.85412000,
                'address' => 'Jl. Raya Kalibata No. 45, Pancoran, Jakarta Selatan',
                'description' => 'Tumpukan daun kering dan ranting pohon bercampur sampah dapur warga.',
                'status' => 'Pending',
                'admin_notes' => null,
                'days_ago' => 5,
            ],
            [
                'user_id' => $user2->id,
                'photo_path' => 'reports/sample_paper.jpg',
                'waste_type' => 'Paper Waste',
                'confidence_score' => 0.8950,
                'latitude' => -6.15921000,
                'longitude' => 106.88234000,
                'address' => 'Jl. Boulevard Barat Raya, Kelapa Gading, Jakarta Utara',
                'description' => 'Limbah kotak karton pengiriman paket menumpuk di pojok ruko.',
                'status' => 'In Progress',
                'admin_notes' => 'Armada truk pengangkut sedang dalam perjalanan.',
                'days_ago' => 4,
            ],
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_plastic.jpg',
                'waste_type' => 'Plastic Waste',
                'confidence_score' => 0.9460,
                'latitude' => -6.26149300,
                'longitude' => 106.81060000,
                'address' => 'Jl. Kemang Raya No. 18, Mampang Prapatan, Jakarta Selatan',
                'description' => 'Sampah kemasan plastik kopi dan sedotan di area bangku taman.',
                'status' => 'Rejected',
                'admin_notes' => 'Titik lokasi berada di dalam area properti privat gedung yang dikelola mandiri.',
                'days_ago' => 4,
            ],
            [
                'user_id' => $user2->id,
                'photo_path' => 'reports/sample_organic.jpg',
                'waste_type' => 'Organic Waste',
                'confidence_score' => 0.9620,
                'latitude' => -6.20194000,
                'longitude' => 106.78152000,
                'address' => 'Jl. Palmerah Barat No. 32, Kebayoran Lama, Jakarta Selatan',
                'description' => 'Limbah organik pasar pagi yang menimbulkan bau menyengat di pinggir jalan.',
                'status' => 'Completed',
                'admin_notes' => 'Pengangkutan selesai dan lokasi telah disemprot disinfektan.',
                'days_ago' => 3,
            ],
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_plastic.jpg',
                'waste_type' => 'Plastic Waste',
                'confidence_score' => 0.9780,
                'latitude' => -6.17941000,
                'longitude' => 106.87651000,
                'address' => 'Jl. Pramuka Raya No. 60, Matraman, Jakarta Timur',
                'description' => 'Karung berisi botol plastik bekas yang ditinggalkan di bahu jalan.',
                'status' => 'Pending',
                'admin_notes' => null,
                'days_ago' => 2,
            ],
            [
                'user_id' => $user2->id,
                'photo_path' => 'reports/sample_paper.jpg',
                'waste_type' => 'Paper Waste',
                'confidence_score' => 0.9495,
                'latitude' => -6.22972800,
                'longitude' => 106.79914500,
                'address' => 'Jl. Asia Afrika No. 8, Gelora, Tanah Abang, Jakarta Pusat',
                'description' => 'Sampah kertas bungkus makanan dan koran bekas acara akhir pekan.',
                'status' => 'Completed',
                'admin_notes' => 'Telah dibersihkan oleh tim sapu bersih kawasan Senayan.',
                'days_ago' => 2,
            ],
            [
                'user_id' => $user2->id,
                'photo_path' => 'reports/sample_organic.jpg',
                'waste_type' => 'Organic Waste',
                'confidence_score' => 0.8840,
                'latitude' => -6.24510000,
                'longitude' => 106.86890000,
                'address' => 'Jl. Dewi Sartika No. 114, Cawang, Kramat Jati, Jakarta Timur',
                'description' => 'Tumpukan kulit buah dan sampah organik di dekat pemberhentian angkutan umum.',
                'status' => 'Rejected',
                'admin_notes' => 'Laporan duplikat dengan laporan warga sebelumnya yang sudah ditangani.',
                'days_ago' => 1,
            ],
            [
                'user_id' => $user1->id,
                'photo_path' => 'reports/sample_plastic.jpg',
                'waste_type' => 'Plastic Waste',
                'confidence_score' => 0.9890,
                'latitude' => -6.19512000,
                'longitude' => 106.82311000,
                'address' => 'Bundaran HI, Menteng, Jakarta Pusat',
                'description' => 'Tumpukan botol plastik dan kemasan minuman di dekat akses masuk stasiun MRT.',
                'status' => 'Pending',
                'admin_notes' => null,
                'days_ago' => 0,
            ],
        ];

        foreach ($reportsData as $item) {
            $timestamp = Carbon::now()->subDays($item['days_ago']);
            unset($item['days_ago']);

            $report = new WasteReport($item);
            $report->created_at = $timestamp;
            $report->updated_at = $timestamp;
            $report->save();
        }
    }
}
