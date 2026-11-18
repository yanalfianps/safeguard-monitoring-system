<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\FindingSource;
use App\Models\IssueDocumentation;
use App\Models\IssueFollowUp;
use App\Models\IssueFollowUpLog;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IssueFollowUpSeeder extends Seeder
{
    /**
     * Seed 150 data realistis untuk modul Issue & Follow Up (Temuan & Tindak Lanjut).
     */
    public function run(): void
    {
        if (app()->environment('production') || !filter_var(env('ALLOW_DESTRUCTIVE_SEEDING', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->command?->warn('IssueFollowUpSeeder diblokir agar tidak menghapus/menimpa data temuan operasional. Set ALLOW_DESTRUCTIVE_SEEDING=true di non-produksi jika sengaja ingin mengisi data sampel.');
            return;
        }

        $now = Carbon::now('Asia/Jakarta');

        // 1. Bersihkan data lama modul Issue & Follow Up agar tepat berisi 150 data realistis
        IssueDocumentation::query()->delete();
        IssueFollowUpLog::query()->delete();
        ActivityLog::where('module', 'Issue & Follow Up')->delete();
        IssueFollowUp::query()->delete();

        // 2. Pastikan 6 Sumber Temuan default terdaftar pada tabel master finding_sources
        $sources = [
            'Audit GIMS',
            'Audit EBTKE',
            'ADB Mission',
            'Audit PMC',
            'GRM',
            'Hasil Observasi',
        ];

        foreach ($sources as $srcName) {
            FindingSource::firstOrCreate(
                ['name' => $srcName],
                ['description' => "Sumber temuan standar {$srcName}", 'is_active' => true]
            );
        }

        // 3. Katalog Topik Realistis untuk 4 Fungsi Safeguard:
        //    - Health & Safety ('H'): 38 data
        //    - Security ('S_SECURITY'): 38 data
        //    - Environment ('E'): 37 data
        //    - Social ('S_SOCIAL'): 37 data
        //    Total = 150 data
        $catalog = [
            'H' => [
                [
                    'title' => 'Penggunaan APD helm dan pelindung pendengaran belum lengkap di area bising turbin',
                    'desc' => 'Ditemukan pekerja kontraktor pemeliharaan memasuki area bising rumah turbin tanpa mengenakan ear muff standar.',
                    'action' => 'Dilakukan penghentian sementara (stop work), pembagian ear muff standar, dan safety briefing ulang di lokasi.',
                ],
                [
                    'title' => 'Isolasi kabel power pada perancah (scaffolding) terkelupas di area kerja las',
                    'desc' => 'Kabel ekstensi mesin gerinda yang melintasi pipa scaffolding mengalami pengelupasan lapisan isolasi luar.',
                    'action' => 'Kabel segera dilepas dari panel distribusi, diganti dengan kabel berpelindung conduit, dan diberi label inspeksi K3.',
                ],
                [
                    'title' => 'Masa kalibrasi detektor gas H2S fixed di area wellpad melewati tanggal berlaku',
                    'desc' => 'Stiker bukti kalibrasi sensor detektor gas H2S tetap (fixed detector) menunjukkan masa berlaku telah habis 5 hari lalu.',
                    'action' => 'Tim instrumen melakukan bump test dan kalibrasi ulang menggunakan gas standar serta memperbarui label sertifikasi.',
                ],
                [
                    'title' => 'Pekerjaan pengelasan pipa penyangga belum dilengkapi dokumen Permit to Work (PTW) panas',
                    'desc' => 'Aktivitas pengelasan bracket penyangga pipa dilakukan sebelum lembar Hot Work Permit ditandatangani oleh pengawas K3.',
                    'action' => 'Pekerjaan dihentikan hingga verifikasi gas test, JSA, dan tanda tangan otorisasi Hot Work Permit selesai dilengkapi.',
                ],
                [
                    'title' => 'Baut pagar pengaman (handrail) lantai kerja rig pengeboran longgar',
                    'desc' => 'Dua titik sambungan pipa handrail pada tangga akses lantai rig goyang dan berpotensi membahayakan pekerja.',
                    'action' => 'Dilakukan pengencangan baut pengikat dan penambahan pin pengunci pengaman pada struktur handrail.',
                ],
                [
                    'title' => 'Tabung APAR Dry Chemical Powder terhalang tumpukan material pipa di gudang mekanik',
                    'desc' => 'Jalur akses menuju titik gantung APAR nomor 04 terhalang palet kayu dan potongan pipa besi.',
                    'action' => 'Material dipindahkan ke rak penyimpanan khusus dan area di depan APAR diberi marka kotak merah bebas hambatan.',
                ],
                [
                    'title' => 'Scaffolding pemeliharaan valve uap belum dipasang green tag kelayakan inspeksi',
                    'desc' => 'Struktur perancah setinggi 4 meter di area separator telah berdiri namun belum memiliki label pemeriksaan Scaffolder bersertifikat.',
                    'action' => 'Inspektor scaffolding melakukan pemeriksaan kelayakan struktur dan memasang Green Tag resmi sebelum digunakan.',
                ],
                [
                    'title' => 'Kotak P3K dinding di ruang kontrol membutuhkan pengisian ulang kasa steril dan antiseptik',
                    'desc' => 'Hasil inspeksi bulanan menunjukkan stok perban steril dan cairan antiseptik pada kotak P3K tipe B kurang dari standar.',
                    'action' => 'Tim medis mengisi ulang seluruh item P3K sesuai daftar Permenaker dan memperbarui kartu kendali isi kotak.',
                ],
                [
                    'title' => 'Lantai kerja di sekitar pompa injeksi brine licin akibat kondensasi uap',
                    'desc' => 'Permukaan pelat bordes di jalur inspeksi pompa injeksi basah dan licin sehingga berisiko menyebabkan terpeleset.',
                    'action' => 'Pemasangan lapisan anti-slip (grating) tambahan serta rambu peringatan lantai licin di titik akses.',
                ],
                [
                    'title' => 'Pelindung mata gerinda (safety guard) dilepas pada perkakas portabel kontraktor',
                    'desc' => 'Satu unit hand grinder ditemukan tanpa kap pelindung mata pisau saat pemeriksaan pre-use peralatan kerja.',
                    'action' => 'Unit gerinda ditarik dari area kerja dan dipasang kembali safety guard standar sebelum diizinkan beroperasi.',
                ],
                [
                    'title' => 'Rambu titik kumpul darurat (Muster Point) pudar akibat paparan cuaca',
                    'desc' => 'Papan penunjuk arah evakuasi dan titik kumpul di sisi timur wellpad mengalami pemudaran warna reflektif.',
                    'action' => 'Penggantian plat rambu evakuasi baru dengan bahan stiker reflektif berkualitas tinggi.',
                ],
                [
                    'title' => 'Penerapan gembok Lockout Tagout (LOTO) belum terpasang pada panel pompa yang sedang diperbaiki',
                    'desc' => 'Teknisi melakukan pengecekan motor pompa transfer tanpa memasang padlock LOTO pribadi pada breaker panel MCC.',
                    'action' => 'Pemasangan padlock LOTO dan label Danger Do Not Operate secara langsung bersama teknisi listrik.',
                ],
                [
                    'title' => 'Selang tabung oksigen dan asetilena pada troli las belum dilengkapi flashback arrestor',
                    'desc' => 'Unit pemotong api (cutting torch) di bengkel fabrikasi belum memiliki katup penahan arus balik api.',
                    'action' => 'Pemasangan flashback arrestor ganda pada sisi regulator maupun sisi torch sebelum pekerjaan dilanjutkan.',
                ],
                [
                    'title' => 'Windsock (penunjuk arah angin) di area menara pendingin robek',
                    'desc' => 'Kain kerucut penunjuk arah angin darurat H2S di dekat cooling tower robek sehingga kurang jelas terlihat dari jauh.',
                    'action' => 'Penggantian kain windsock oranye terang baru pada tiang pantau utama.',
                ],
                [
                    'title' => 'Kabel grounding pada tangki bahan bakar sementara belum terhubung sempurna',
                    'desc' => 'Jepitan klem kabel pentanahan (grounding clamp) pada skid tank solar longgar dan berkarat.',
                    'action' => 'Pembersihan titik kontak logam, penggantian klem kuningan baru, dan pengukuran nilai resistansi grounding.',
                ],
                [
                    'title' => 'Tangga portabel aluminium tidak memiliki karet anti-selip pada kaki tumpuan',
                    'desc' => 'Satu unit tangga lipat yang digunakan tim elektrikal kehilangan sepatu karet pengaman di bagian bawah.',
                    'action' => 'Pemasangan karet kaki anti-selip baru dan pemberian stiker layak pakai.',
                ],
                [
                    'title' => 'Pekerja ketinggian belum mengaitkan double lanyard secara bergantian saat berpindah titik',
                    'desc' => 'Observasi keselamatan menemukan pekerja di atas struktur pipe rack sempat melepas kedua hook full body harness.',
                    'action' => 'Coaching langsung mengenai prinsip 100% tie-off dan penyegaran prosedur bekerja di ketinggian.',
                ],
                [
                    'title' => 'Tekanan indikator manometer pada 2 tabung APAR CO2 turun di bawah zona hijau',
                    'desc' => 'Pemeriksaan rutin menemukan dua tabung pemadam api ringan mengalami penurunan tekanan internal.',
                    'action' => 'Tabung diganti dengan unit cadangan siap pakai dan tabung lama dikirim untuk pengisian serta uji tekan ulang.',
                ],
                [
                    'title' => 'Ventilasi blower tambahan belum terpasang pada persiapan kerja ruang terbatas (confined space)',
                    'desc' => 'Area manhole kondensor yang akan dibersihkan belum dilengkapi flexible duct blower penghisap udara.',
                    'action' => 'Pemasangan axial fan blower dan pengujian kadar oksigen serta gas beracun sebelum izin masuk diterbitkan.',
                ],
            ],

            'S_SECURITY' => [
                [
                    'title' => 'Akses gerbang samping pos security wellpad tidak terkunci saat pergantian shift',
                    'desc' => 'Pintu akses pejalan kaki di samping portal utama ditemukan dalam keadaan slot gembok terbuka tanpa penjagaan.',
                    'action' => 'Penguncian langsung dengan gembok standar dan penertiban SOP serah terima kunci antar regu jaga.',
                ],
                [
                    'title' => 'Kamera CCTV titik pantau sudut barat switchyard mati tidak menampilkan gambar',
                    'desc' => 'Layar monitor di ruang pos komando keamanan menunjukkan kehilangan sinyal (no video) pada kamera jalur perimeter barat.',
                    'action' => 'Penggantian adaptor daya PoE dan penyambungan ulang konektor kabel serat optik hingga kamera kembali aktif.',
                ],
                [
                    'title' => 'Pengunjung vendor pengiriman logistik masuk area proyek tanpa mengenakan visitor badge',
                    'desc' => 'Sopir kargo ekspedisi terlihat berjalan di area gudang tanpa tanda pengenal tamu yang ditukar di pos utama.',
                    'action' => 'Petugas patroli mendampingi tamu kembali ke pos utama untuk registrasi buku tamu dan pemakaian kartu Visitor.',
                ],
                [
                    'title' => 'Lampu sorot penerangan pos jaga perimeter belakang padam 2 titik',
                    'desc' => 'Pencahayaan malam hari di sepanjang jalur pagar pengaman sektor selatan kurang terang karena lampu LED putus.',
                    'action' => 'Tim elektrikal mengganti 2 unit lampu sorot LED 100W sehingga visibilitas patroli malam kembali optimal.',
                ],
                [
                    'title' => 'Jalur patroli pagar perimeter terhalang semak belukar tinggi',
                    'desc' => 'Rumput liar dan semak tumbuh menutup jalur inspeksi jalan kaki petugas keamanan di sepanjang pagar kawat berduri.',
                    'action' => 'Pembersihan dan pemangkasan vegetasi sepanjang 150 meter jalur perimeter oleh tim housekeeping.',
                ],
                [
                    'title' => 'Kawat berduri (concertina wire) di atas pagar pengaman area brine pond renggang',
                    'desc' => 'Gulungan kawat pengaman di bagian atas pagar segmen timur kendur akibat tiang penyangga miring.',
                    'action' => 'Perbaikan tiang penyangga besi Y dan penarikan ulang gulungan kawat berduri hingga rapat.',
                ],
                [
                    'title' => 'Buku mutasi pencatatan keluar-masuk kendaraan alat berat belum terisi lengkap',
                    'desc' => 'Terdapat 3 kendaraan operasional yang keluar area wellpad tanpa pencatatan nomor polisi dan jam keluar di logbook.',
                    'action' => 'Evaluasi disiplin administrasi personel jaga dan penerapan pengecekan silang oleh Komandan Regu setiap 4 jam.',
                ],
                [
                    'title' => 'Palang portal otomatis (barrier gate) gerbang utama kantor PMU lambat menutup',
                    'desc' => 'Motor penggerak palang pintu pemeriksaan kendaraan mengalami penurunan respons mekanis.',
                    'action' => 'Pelumasan gear box dan kalibrasi sensor batas bukaan palang pintu oleh teknisi pemeliharaan.',
                ],
                [
                    'title' => 'Rambu peringatan Area Terbatas Objek Vital (Restricted Area) miring di jalur pipa',
                    'desc' => 'Tiang plang larangan masuk tanpa izin di dekat jalur pipa uap miring akibat tanah gembur pasca hujan.',
                    'action' => 'Pengecoran ulang pondasi tiang rambu dan pembersihan permukaan tulisan peringatan.',
                ],
                [
                    'title' => 'Cermin pemeriksaan kolong kendaraan (under-vehicle mirror) di pos utama retak',
                    'desc' => 'Kaca cembung alat bantu inspeksi bagian bawah mobil tamu retak dan lampu senternya redup.',
                    'action' => 'Pengadaan dan penyerahan 2 unit under-vehicle inspection mirror baru dilengkapi lampu LED.',
                ],
                [
                    'title' => 'Baterai cadangan radio komunikasi HT regu patroli malam cepat habis',
                    'desc' => 'Dua unit handy talky petugas patroli mengalami penurunan daya simpan baterai saat digunakan lebih dari 3 jam.',
                    'action' => 'Penggantian baterai lithium baru pada unit HT dan penyediaan dok pengisi daya cadangan di pos jaga.',
                ],
                [
                    'title' => 'Pintu ruang panel server telekomunikasi ditemukan tidak terkunci rapat',
                    'desc' => 'Door closer otomatis pada ruang perangkat jaringan tidak menutup sempurna sehingga kunci magnetik tidak mengait.',
                    'action' => 'Penyetelan ulang engsel door closer dan pengujian akses kartu RFID personel berwenang.',
                ],
                [
                    'title' => 'Kendaraan proyek parkir di luar garis marka dan menghalangi jalur keluar darurat',
                    'desc' => 'Satu unit mobil double cabin parkir tepat di depan akses pintu gerbang evakuasi darurat halaman kantor.',
                    'action' => 'Penertiban langsung oleh petugas security dan pemasangan kerucut lalu lintas (traffic cone) di zona larangan parkir.',
                ],
                [
                    'title' => 'Pos pantau keamanan sementara di Wellpad 7 belum dilengkapi daftar nomor telepon darurat terbaru',
                    'desc' => 'Lembar daftar kontak darurat di dinding pos masih menggunakan nomor lama yang belum diperbarui.',
                    'action' => 'Pencetakan dan pemasangan poster nomor darurat terintegrasi (Medis, Damkar, Koordinator HSSE, Aparat).',
                ],
                [
                    'title' => 'Segel plastik pengaman gudang penyimpanan suku cadang instrumen belum diperbarui pasca stok opname',
                    'desc' => 'Pintu gudang material hanya digembok biasa tanpa pemasangan nomor segel registrasi harian.',
                    'action' => 'Pemasangan segel bernomor seri dan pencatatan nomor segel pada berita acara penguncian gudang.',
                ],
                [
                    'title' => 'Metal detector genggam (handheld detector) di pos pemeriksaan tamu kehabisan baterai',
                    'desc' => 'Alat pemindai logam tidak berbunyi saat diuji coba sebelum jam kunjungan tamu pagi.',
                    'action' => 'Penggantian baterai isi ulang 9V dan pengecekan fungsi harian setiap apel pagi.',
                ],
                [
                    'title' => 'Kunci cadangan rumah sumur (wellhead) belum tersimpan di dalam lemari kunci bersegel',
                    'desc' => 'Gantungan kunci cadangan masih diletakkan di laci meja pos jaga dan belum dimasukkan ke key box khusus.',
                    'action' => 'Penataan seluruh anak kunci di dalam Key Management Box terkunci beserta buku kendali peminjaman kunci.',
                ],
                [
                    'title' => 'Tanda pengenal (ID Card) personel kontraktor baru belum mencantumkan masa berlaku induksi keamanan',
                    'desc' => 'Empat pekerja subkontraktor mengenakan kartu identitas sementara yang belum distempel validasi HSSE & Security.',
                    'action' => 'Verifikasi daftar hadir safety induction dan penerbitan ID Card resmi berstempel masa aktif.',
                ],
                [
                    'title' => 'Titik cek (checkpoint) patroli digital QR Code di sudut timur workshop terkelupas',
                    'desc' => 'Stiker penanda titik kunjung patroli keliling rusak terkena air hujan sehingga sulit dipindai.',
                    'action' => 'Pemasangan pelat akrilik pelindung baru untuk QR Code checkpoint patroli.',
                ],
            ],

            'E' => [
                [
                    'title' => 'Ceceran oli pelumas pada lantai secondary containment genset darurat',
                    'desc' => 'Ditemukan rembesan oli sekitar 0,5 liter dari paking filter oli genset di dalam bak penampung sekunder.',
                    'action' => 'Pembersihan menggunakan oil absorbent pad, penggantian seal filter oli, dan penyimpanan majun bekas ke TPS Limbah B3.',
                ],
                [
                    'title' => 'Pemilahan tempat sampah organik, anorganik, dan B3 di area workshop belum rapi',
                    'desc' => 'Ditemukan kaleng bekas cat semprot dan sarung tangan terkontaminasi oli tercampur di dalam tong sampah domestik.',
                    'action' => 'Pemilahan ulang isi tempat sampah, penambahan label warna jelas, dan briefing singkat kebersihan lingkungan.',
                ],
                [
                    'title' => 'Volume air pada kolam penampungan air lindi / settling pond mendekati batas kapasitas',
                    'desc' => 'Tingginya curah hujan menyebabkan level muka air di kolam pengendapan naik hingga 85% dari batas tanggul.',
                    'action' => 'Pengoperasian pompa sirkulasi menuju sistem injeksi kembali (reinjection) dan pengerukan endapan lumpur.',
                ],
                [
                    'title' => 'Bundwall tangki penyimpanan solar tergenang air hujan dan belum dikuras melalui oil trap',
                    'desc' => 'Bak penampung tangki BBM solar terisi air hujan karena katup pembuangan menuju oil water separator tertutup.',
                    'action' => 'Pemeriksaan ketiadaan lapisan minyak, pembukaan valve terukur melewati oil catcher, dan penutupan kembali valve.',
                ],
                [
                    'title' => 'Emisi gas buang cerobong genset cadangan tampak berasap pekat saat uji pemanasan',
                    'desc' => 'Saat running test mingguan, asap buangan mesin diesel tampak lebih hitam dari kondisi normal selama 10 menit awal.',
                    'action' => 'Pembersihan injektor bahan bakar, penggantian filter udara mesin, dan pengujian opasitas asap hingga normal.',
                ],
                [
                    'title' => 'Kemasan drum oli bekas di TPS Limbah B3 belum seluruhnya ditempeli simbol dan label B3',
                    'desc' => 'Tiga buah drum penampung pelumas bekas baru masuk ke TPS namun belum dilengkapi stiker tanggal pengemasan dan kode limbah.',
                    'action' => 'Penempelan stiker simbol limbah B3 Mudah Menyala beserta label identitas dan pencatatan pada logbook neraca limbah.',
                ],
                [
                    'title' => 'Saluran drainase air hujan di sisi utara wellpad mengalami pendangkalan sedimen tanah',
                    'desc' => 'Aliran air larian permukaan melambat akibat tumpukan pasir dan kerikil yang terbawa dari lereng.',
                    'action' => 'Pembersihan sedimen saluran sepanjang 80 meter dan pemasangan karung pasir penahan erosi (silt fence).',
                ],
                [
                    'title' => 'Isi perlengkapan Spill Kit di area gudang bahan kimia kurang lengkap',
                    'desc' => 'Kotak kuning Oil & Chemical Spill Kit kekurangan 5 lembar absorbent pad dan 1 pasang sarung tangan nitril.',
                    'action' => 'Pengisian ulang kelengkapan Spill Kit sesuai daftar inventaris standar dan penyegelan kotak.',
                ],
                [
                    'title' => 'Lembar Data Keselamatan Bahan (MSDS/LDKB) belum terpasang pada rak penyimpanan bahan kimia',
                    'desc' => 'Area penyimpanan bahan kimia pengolahan air belum dilengkapi map dokumen MSDS berbahasa Indonesia yang mudah diakses.',
                    'action' => 'Pencetakan dan pemasangan dokumen MSDS terlaminasi pada dinding rak penyimpanan bahan kimia.',
                ],
                [
                    'title' => 'Tetesan air brine pada sambungan flensa jalur pipa reinjeksi memerlukan pengencangan',
                    'desc' => 'Terlihat rembesan tipis air panas bumi (brine) yang membentuk kerak silika pada sambungan baut flensa.',
                    'action' => 'Penggantian gasket tahan panas dan pengencangan baut flensa dengan kunci torsi terukur.',
                ],
                [
                    'title' => 'Lahan miring bekas galian konstruksi di dekat Pad Dieng 2 memerlukan penanaman rumput penutup (cover crop)',
                    'desc' => 'Permukaan tanah terbuka di lereng tanggul berpotensi tergerus air hujan apabila tidak segera ditanami vegetasi.',
                    'action' => 'Pemasangan jaring biomat dan penanaman rumput vetiver penahan erosi tanah.',
                ],
                [
                    'title' => 'Palet penampung tumpahan (spill pallet) untuk jerigen bahan kimia di laboratorium retak',
                    'desc' => 'Satu unit spill pallet plastik berkapasitas 4 jerigen mengalami retak halus di bagian sudut bawah.',
                    'action' => 'Penggantian dengan unit secondary spill pallet HDPE baru yang kedap cairan.',
                ],
                [
                    'title' => 'Pencatatan logbook neraca keluar-masuk Limbah B3 minggu berjalan belum diperbarui',
                    'desc' => 'Terdapat penambahan limbah lampu TL bekas dan baterai bekas yang belum diinput ke lembar neraca mingguan.',
                    'action' => 'Penimbangan berat limbah dan pembaruan buku administrasi neraca Limbah B3 secara akurat.',
                ],
                [
                    'title' => 'Saringan perangkap minyak (oil catcher) di area pencucian kendaraan tertutup lumpur',
                    'desc' => 'Bak kontrol pertama unit oil water separator dipenuhi endapan tanah dari pencucian ban truk proyek.',
                    'action' => 'Pengurasan lumpur secara berkala dan pembersihan sekat pemisah minyak.',
                ],
                [
                    'title' => 'Kran air bersih di fasilitas tempat cuci tangan pekerja menetes terus-menerus',
                    'desc' => 'Karet katup kran wastafel di dekat area kantin aus sehingga menyebabkan pemborosan air bersih.',
                    'action' => 'Penggantian kran air baru hemat air dan pengecekan jaringan pipa sanitasi.',
                ],
                [
                    'title' => 'Tumpukan kayu bekas kemasan (pallet kayu) di area laydown belum tertata sesuai prinsip 5R',
                    'desc' => 'Potongan kayu bekas peti peralatan diletakkan berserakan di sudut lapangan terbuka.',
                    'action' => 'Penataan kayu berdasarkan ukuran di area khusus dan pembersihan paku yang menonjol.',
                ],
                [
                    'title' => 'Lapisan geomembran di tepi atas kolam brine pond sedikit terangkat akibat angin kencang',
                    'desc' => 'Lipatan pengunci lembaran HDPE geomembran di sisi barat daya kolam memerlukan pemberat tambahan.',
                    'action' => 'Perbaikan jangkar pengunci tanah (anchor trench) dan penambahan pemberat geobag.',
                ],
                [
                    'title' => 'Bak penampung tetesan oli (drip pan) belum diletakkan di bawah mesin kompresor portabel',
                    'desc' => 'Unit kompresor udara diesel yang ditempatkan di tanah terbuka belum diberi alas terpal kedap maupun drip tray.',
                    'action' => 'Pemasangan drip pan logam di bawah mesin kompresor untuk mencegah kontaminasi tanah.',
                ],
                [
                    'title' => 'Papan informasi titik penaatan pemantauan kualitas udara ambien perlu dicat ulang',
                    'desc' => 'Plang koordinat titik pantau udara ambien (UA-02) di batas tapak proyek mulai berkarat.',
                    'action' => 'Pengecatan ulang tiang dan pencetakan pelat titik koordinat pemantauan lingkungan.',
                ],
            ],

            'S_SOCIAL' => [
                [
                    'title' => 'Keluhan debu jalan akibat lalu lalang kendaraan proyek di jalur pemukiman warga',
                    'desc' => 'Warga desa sekitar menyampaikan aspirasi mengenai peningkatan debu jalan saat cuaca cerah dan angin kencang.',
                    'action' => 'Penambahan frekuensi penyiraman jalan menggunakan water truck menjadi 3 kali sehari dan pembatasan kecepatan 20 km/jam.',
                ],
                [
                    'title' => 'Gangguan kebisingan pengoperasian alat berat saat jam istirahat siang warga',
                    'desc' => 'Laporan masyarakat melalui kanal GRM terkait suara bising manuver truk material pada pukul 12.00-13.00 WIB.',
                    'action' => 'Pengaturan jadwal operasional angkutan material agar berhenti selama jam istirahat warga dan ibadah.',
                ],
                [
                    'title' => 'Pipa saluran air bersih warga di bahu jalan tergeser akibat manuver kendaraan proyek',
                    'desc' => 'Sambungan pipa HDPE distribusi air warga mengalami kebocoran karena tertekan ban kendaraan di tikungan sempit.',
                    'action' => 'Tim teknis bersama warga langsung menyambung kembali pipa, menanam pipa lebih dalam dengan pelindung selubung besi.',
                ],
                [
                    'title' => 'Permintaan transparansi informasi kuota penyerapan tenaga kerja lokal desa penyangga',
                    'desc' => 'Perangkat desa mengusulkan agar pengumuman lowongan kerja subkontraktor ditempel resmi di papan informasi balai desa.',
                    'action' => 'Koordinasi bersama Humas dan kontraktor untuk mempublikasikan setiap kebutuhan tenaga kerja melalui forum resmi desa.',
                ],
                [
                    'title' => 'Rambu peringatan hati-hati keluar-masuk kendaraan proyek di simpang jalan desa tertutup dahan pohon',
                    'desc' => 'Rambu peringatan lalu lintas di persimpangan akses utama tertutup ranting pohon sehingga kurang terlihat pengendara warga.',
                    'action' => 'Pemangkasan ranting penghalang dan penambahan lampu kedip kuning (warning light) tenaga surya.',
                ],
                [
                    'title' => 'Ceceran tanah dari roda truk proyek mengotori jalan beraspal desa sepanjang 50 meter',
                    'desc' => 'Kendaraan yang keluar dari area galian wellpad saat kondisi hujan membawa lumpur ke jalan umum.',
                    'action' => 'Pembersihan jalan aspal segera oleh regu kebersihan dan pengaktifan wajib fasilitas cuci ban (wheel wash bay) di gerbang.',
                ],
                [
                    'title' => 'Aspirasi petani terkait pengamanan akses jalan setapak menuju lahan pertanian di dekat jalur pipa',
                    'desc' => 'Petani sayur membutuhkan jembatan penyeberangan kecil (crossing bridge) di atas jalur konstruksi pipa uap.',
                    'action' => 'Pembangunan jembatan penyeberangan pejalan kaki dilengkapi pegangan tangan (handrail) yang aman bagi petani.',
                ],
                [
                    'title' => 'Kotak pengaduan masyarakat (GRM Box) di kantor desa membutuhkan perbaikan kunci dan penambahan formulir',
                    'desc' => 'Stok lembar formulir pengaduan di dalam kotak GRM habis dan engsel penutup kotak mulai berkarat.',
                    'action' => 'Perbaikan kotak GRM, pengisian 50 lembar formulir baru, serta pencantuman nomor WhatsApp Hotline Pengaduan.',
                ],
                [
                    'title' => 'Keluhan getaran saat mobilisasi trailer pengangkut peralatan berat melewati jembatan desa',
                    'desc' => 'Warga di sekitar jembatan mengkhawatirkan dampak getaran saat konvoi muatan berat melintas pada malam hari.',
                    'action' => 'Inspeksi struktur jembatan bersama Dinas PUPR, pemasangan pelat baja perata beban, dan pengawalan kecepatan rendah.',
                ],
                [
                    'title' => 'Permohonan sosialisasi batas zona aman aktivitas pertanian di sekitar pagar sumur panas bumi',
                    'desc' => 'Sebagian petani baru belum mengetahui jarak aman pengolahan lahan dari pagar perimeter wellpad.',
                    'action' => 'Sosialisasi tatap muka bersama kelompok tani dan pemasangan patok batas ruang milik proyek yang jelas.',
                ],
                [
                    'title' => 'Saluran irigasi kebun kentang warga tersumbat limpasan kerikil dari bahu jalan akses proyek',
                    'desc' => 'Hujan deras membawa material agregat bahu jalan masuk ke parit pengairan kebun warga.',
                    'action' => 'Normalisasi parit irigasi secara gotong royong dan pembuatan tanggul penahan (curb) di tepi jalan akses.',
                ],
                [
                    'title' => 'Petugas pengatur lalu lintas (flagman) belum disiagakan di titik penyempitan jalan saat jam sekolah',
                    'desc' => 'Arus lalu lintas pagi hari di depan sekolah dasar desa padat bersamaan dengan jam berangkat kerja proyek.',
                    'action' => 'Penempatan 2 personel flagman berseragam reflektif setiap pukul 06.30-08.00 WIB dan 12.30-14.00 WIB.',
                ],
                [
                    'title' => 'Usulan pelibatan UMKM katering lokal dalam penyediaan konsumsi kegiatan pelatihan HSSE',
                    'desc' => 'Kelompok usaha ibu-ibu PKK desa mengajukan kemitraan penyediaan kudapan sehat untuk rapat dan pelatihan proyek.',
                    'action' => 'Verifikasi standar higienitas pangan dan pendaftaran 4 kelompok UMKM desa sebagai mitra resmi katering.',
                ],
                [
                    'title' => 'Lampu penerangan jalan umum (PJU) bantuan proyek di tikungan desa padam 1 titik',
                    'desc' => 'Warga melaporkan satu unit lampu PJU tenaga surya di jalur tanjakan desa tidak menyala saat malam hari.',
                    'action' => 'Pembersihan permukaan panel surya dan penggantian kontroler baterai lampu PJU hingga kembali menyala.',
                ],
                [
                    'title' => 'Parkir truk tunggu material di bahu jalan sempit mengurangi ruang pandang pengendara warga',
                    'desc' => 'Dua unit dump truck menunggu giliran bongkar muat di bahu jalan desa di luar area kantong parkir.',
                    'action' => 'Pengalihan seluruh antrean truk ke dalam area holding yard internal proyek dan peneguran sopir.',
                ],
                [
                    'title' => 'Permintaan jadwal pemberitahuan dini kepada warga sebelum uji coba pelepasan uap (venting)',
                    'desc' => 'Masyarakat meminta agar informasi kegiatan uji uap yang berpotensi menimbulkan suara gemuruh disampaikan minimal H-2.',
                    'action' => 'Penyusunan protokol surat pemberitahuan resmi ke pemerintah desa dan pengumuman melalui grup komunikasi warga.',
                ],
                [
                    'title' => 'Papan informasi proyek dan kontak layanan masyarakat di simpang jalan utama pudar',
                    'desc' => 'Tulisan nomor telepon petugas hubungan masyarakat (Community Relations) pada papan informasi kurang terbaca.',
                    'action' => 'Penggantian banner papan informasi proyek dengan desain baru yang jelas dan tahan cuaca.',
                ],
                [
                    'title' => 'Evaluasi ketepatan waktu pembayaran upah pekerja harian lokal oleh subkontraktor sipil',
                    'desc' => 'Tim safeguard sosial menerima laporan keterlambatan administrasi slip gaji 2 hari pada subkontraktor pekerjaan drainase.',
                    'action' => 'Pemanggilan manajemen subkontraktor, pelunasan langsung hak pekerja, dan pengawasan daftar gaji bulanan.',
                ],
                [
                    'title' => 'Perbaikan permukaan jalan desa yang berlubang pada rute lintasan kendaraan operasional',
                    'desc' => 'Terdapat 4 titik lubang jalan di ruas akses menuju Pad Dieng 2 yang berisiko bagi pengendara sepeda motor warga.',
                    'action' => 'Pelaksanaan penambalan jalan (patching) menggunakan aspal hotmix dan pemadatan bahu jalan.',
                ],
            ],
        ];

        $locationsByDivision = [
            'H' => [
                'Pad Dieng 2',
                'Wellpad 7',
                'Area Power Plant Unit 1',
                'Workshop Mekanik & Rig',
                'Ruang Turbin & Generator',
                'Area Separator & Demister',
                'Menara Pendingin (Cooling Tower)',
                'Gudang Peralatan K3',
            ],
            'S_SECURITY' => [
                'Gerbang Utama (Main Gate)',
                'Switchyard 150 kV',
                'Pos Keamanan Pad Dieng 2',
                'Perimeter Barat Wellpad 7',
                'Area Kantor PMU',
                'Gudang Material Utama',
                'Jalur Pipa Uap Sektor 3',
                'Pos Pantau Brine Pond',
            ],
            'E' => [
                'Brine Pond (Thermal Pond)',
                'TPS Limbah B3 Terpadu',
                'Area Secondary Containment Genset',
                'Bundwall Tangki Solar',
                'Settling Pond Wellpad 7',
                'Workshop Pencucian Alat Berat',
                'Area Lereng Pad Dieng 2',
                'Laboratorium Kualitas Air',
            ],
            'S_SOCIAL' => [
                'Desa Karangtengah (Sekitar Proyek)',
                'Jalur Akses Pemukiman Warga',
                'Desa Kepakisan',
                'Simpang Jalan Utama Proyek',
                'Area Pertanian Sekitar Jalur Pipa',
                'Balai Desa Sikunang',
                'Jembatan Akses Desa Dieng Kulon',
                'Posko Layanan Masyarakat PMU',
            ],
        ];

        $units = [
            'PMU',
            'Dieng Unit 1',
            'Dieng Unit 2',
            'Utility',
            'JOTRE',
            'BISMO',
        ];

        $picsByDivision = [
            'H' => ['Budi Santoso', 'Hendra Wijaya', 'Rizal K3', 'Dedi Kurniawan'],
            'S_SECURITY' => ['Agus Priyanto', 'Komandan Regu Joko', 'Wahyu Pratama', 'Bambang Security'],
            'E' => ['Rina Dewi', 'Lestari Handayani', 'Fajar Lingkungan', 'Dr. Arif Nugroho'],
            'S_SOCIAL' => ['Siti Nurhaliza', 'Anwar Humas', 'Dewi Comdev', 'Lukman Safeguard'],
        ];

        $priorities = ['Rendah', 'Sedang', 'Tinggi', 'Kritis'];

        // 4. Susun tepat 150 item secara bergiliran (interleaved) antar 4 fungsi safeguard:
        //    Target per fungsi: H = 38, S_SECURITY = 38, E = 37, S_SOCIAL = 37 -> Total = 150
        $quotas = [
            'H' => 38,
            'S_SECURITY' => 38,
            'E' => 37,
            'S_SOCIAL' => 37,
        ];

        $counts = [
            'H' => 0,
            'S_SECURITY' => 0,
            'E' => 0,
            'S_SOCIAL' => 0,
        ];

        $itemsToCreate = [];
        $round = 0;

        while (count($itemsToCreate) < 150) {
            foreach (['H', 'S_SECURITY', 'E', 'S_SOCIAL'] as $divCode) {
                if ($counts[$divCode] >= $quotas[$divCode]) {
                    continue;
                }

                $pool = $catalog[$divCode];
                $tpl = $pool[$counts[$divCode] % count($pool)];
                $variantNum = intdiv($counts[$divCode], count($pool)) + 1;

                $locs = $locationsByDivision[$divCode];
                $location = $locs[($counts[$divCode] + $round) % count($locs)];

                // Untuk variasi siklus ke-2 agar setiap judul kontekstual dengan lokasinya
                $title = $variantNum === 1
                    ? $tpl['title']
                    : $tpl['title'] . ' (' . $location . ')';

                $itemsToCreate[] = [
                    'hsse_division' => $divCode,
                    'title' => $title,
                    'description' => $tpl['desc'] . ' — Tindak lanjut: ' . $tpl['action'],
                    'action_plan' => $tpl['action'],
                    'location' => $location,
                    'div_seq' => $counts[$divCode],
                ];

                $counts[$divCode]++;
            }
            $round++;
        }

        // 5. Sebar tanggal dalam rentang ~7,5 bulan terakhir (225 hari lalu hingga hari ini)
        //    dan atur rasio 60% Closed (90 data) serta 40% Open (60 data).
        for ($i = 0; $i < 150; $i++) {
            $row = $itemsToCreate[$i];
            $divCode = $row['hsse_division'];

            // Index 0 = paling baru (hari ini / kemarin), index 149 = ~225 hari lalu
            $daysAgo = (int) floor(($i * 224) / 149);
            $reportedDate = $now->copy()->subDays($daysAgo);

            // Distribusi 60% Closed (3 dari setiap 5 data = Closed, 2 = Open)
            // Untuk temuan yang baru dilaporkan dalam 3 hari terakhir, buat sebagian besar Open agar realistis
            if ($daysAgo <= 2) {
                $isClosed = ($i % 3 === 0);
            } else {
                $isClosed = (($i % 5) < 3); // 0, 1, 2 => Closed (60%); 3, 4 => Open (40%)
            }

            $status = $isClosed ? 'Closed' : 'Open';

            // Tanggal Closed: wajib terisi untuk status Closed (antara 1 s/d 10 hari setelah reported_date, tidak melebihi hari ini)
            $closedDate = null;
            $closedBy = null;
            $pics = $picsByDivision[$divCode];
            $pic = $pics[$i % count($pics)];

            if ($isClosed) {
                $daysToClose = min(max(1, ($i % 9) + 1), max(0, $daysAgo));
                $closedDate = $reportedDate->copy()->addDays($daysToClose)->toDateString();
                $closedBy = $pic;
            }

            $dueDate = $reportedDate->copy()->addDays(7 + ($i % 7))->toDateString();

            // Pilih Sumber Temuan realistis sesuai konteks fungsi
            if ($divCode === 'S_SOCIAL' && ($i % 3 === 0)) {
                $source = 'GRM';
            } else {
                $source = $sources[($i + $row['div_seq']) % count($sources)];
            }

            $unit = $units[$i % count($units)];
            $priority = $priorities[$i % count($priorities)];
            $seqCode = str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $issueCode = 'IFU-' . $reportedDate->format('Ymd') . '-' . $seqCode;

            $createdAt = $reportedDate->copy()->setTime(8 + ($i % 8), ($i * 7) % 60);
            $updatedAt = $isClosed
                ? Carbon::parse($closedDate)->setTime(15, 30)
                : $createdAt->copy()->addHours(2);

            $issue = IssueFollowUp::create([
                'issue_code' => $issueCode,
                'hsse_division' => $divCode,
                'title' => $row['title'],
                'category' => $source,
                'unit' => $unit,
                'reported_date' => $reportedDate->toDateString(),
                'due_date' => $dueDate,
                'location' => $row['location'],
                'description' => $row['description'],
                'action_plan' => $row['action_plan'],
                'pic' => $pic,
                'priority' => $priority,
                'status' => $status,
                'closed_date' => $closedDate,
                'closed_by' => $closedBy,
                'notes' => $isClosed
                    ? "Tindak lanjut telah diselesaikan dan diverifikasi: {$row['action_plan']}"
                    : "Menunggu penyelesaian lapangan: {$row['action_plan']}",
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);

            // Buat log kronologis awal
            IssueFollowUpLog::create([
                'issue_follow_up_id' => $issue->id,
                'user_id' => null,
                'user_name' => $pic,
                'action_type' => 'CREATED',
                'old_status' => null,
                'new_status' => 'Open',
                'note' => "Temuan dilaporkan dari sumber {$source} di lokasi {$row['location']}.",
                'followed_up_at' => $createdAt,
            ]);

            if ($isClosed) {
                IssueFollowUpLog::create([
                    'issue_follow_up_id' => $issue->id,
                    'user_id' => null,
                    'user_name' => $pic,
                    'action_type' => 'CLOSED',
                    'old_status' => 'Open',
                    'new_status' => 'Closed',
                    'note' => "Perbaikan selesai diverifikasi: {$row['action_plan']}",
                    'followed_up_at' => $updatedAt,
                ]);
            }
        }
    }
}
