<?php

namespace Database\Seeders;

use App\Models\Division;
use Illuminate\Database\Seeder;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        $divisions = [
            [
                'code' => 'DIV-IT',
                'name' => 'Divisi Teknologi & Sistem Informasi',
                'description' => 'Bertanggung jawab atas infrastruktur digital, pengelolaan sistem, dan teknologi organisasi.',
            ],
            [
                'code' => 'DIV-HUMAS',
                'name' => 'Divisi Hubungan Masyarakat & Media',
                'description' => 'Mengelola komunikasi eksternal, publikasi, media sosial, dan kemitraan.',
            ],
            [
                'code' => 'DIV-PSDA',
                'name' => 'Divisi Pengembangan Sumber Daya Anggota',
                'description' => 'Fokus pada kaderisasi, pelatihan internal, dan kesejahteraan anggota organisasi.',
            ],
            [
                'code' => 'DIV-LOG',
                'name' => 'Divisi Logistik & Operasional',
                'description' => 'Mengatur perlengkapan, inventaris, dan fasilitas teknis seluruh kegiatan.',
            ],
            [
                'code' => 'DIV-ACARA',
                'name' => 'Divisi Acara & Kreatif',
                'description' => 'Merancang konsep, rundown, dan eksekusi kreatif setiap kegiatan dan seremonial.',
            ],
        ];

        foreach ($divisions as $data) {
            Division::firstOrCreate(['code' => $data['code']], $data);
        }
    }
}
