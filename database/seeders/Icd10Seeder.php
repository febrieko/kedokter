<?php

namespace Database\Seeders;

use App\Models\Icd10Code;
use Illuminate\Database\Seeder;

class Icd10Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $codes = [
            // Infeksi & Parasit
            ['code' => 'A09', 'name_en' => 'Infectious gastroenteritis and colitis', 'name_id' => 'Gastroenteritis dan kolitis infeksius (Diare akut)', 'category' => 'Penyakit Infeksi', 'is_common' => true],
            ['code' => 'A01.0', 'name_en' => 'Typhoid fever', 'name_id' => 'Demam tifoid (Tifus abdominalis)', 'category' => 'Penyakit Infeksi', 'is_common' => true],
            ['code' => 'A90', 'name_en' => 'Dengue fever', 'name_id' => 'Demam berdarah dengue (Dengue fever)', 'category' => 'Penyakit Infeksi', 'is_common' => true],
            ['code' => 'A15.0', 'name_en' => 'Tuberculosis of lung', 'name_id' => 'Tuberkulosis paru (TBC)', 'category' => 'Penyakit Infeksi', 'is_common' => true],
            ['code' => 'B01.9', 'name_en' => 'Varicella without complication', 'name_id' => 'Varicella (Cacar air)', 'category' => 'Penyakit Infeksi', 'is_common' => true],
            ['code' => 'B02.9', 'name_en' => 'Zoster without complication', 'name_id' => 'Herpes zoster', 'category' => 'Penyakit Infeksi', 'is_common' => false],
            ['code' => 'B35.4', 'name_en' => 'Tinea corporis', 'name_id' => 'Tinea korporis (Kurap badan)', 'category' => 'Penyakit Infeksi', 'is_common' => true],
            ['code' => 'B35.3', 'name_en' => 'Tinea pedis', 'name_id' => 'Tinea pedis (Kutu air)', 'category' => 'Penyakit Infeksi', 'is_common' => true],
            ['code' => 'B86', 'name_en' => 'Scabies', 'name_id' => 'Skabies (Gudik / Kudis)', 'category' => 'Penyakit Infeksi', 'is_common' => true],

            // Saluran Pernapasan
            ['code' => 'J00', 'name_en' => 'Acute nasopharyngitis [common cold]', 'name_id' => 'Nasofaringitis akut (Common cold / Batuk pilek)', 'category' => 'Saluran Pernapasan', 'is_common' => true],
            ['code' => 'J02.9', 'name_en' => 'Acute pharyngitis, unspecified', 'name_id' => 'Faringitis akut (Radang tenggorokan)', 'category' => 'Saluran Pernapasan', 'is_common' => true],
            ['code' => 'J03.9', 'name_en' => 'Acute tonsillitis, unspecified', 'name_id' => 'Tonsilitis akut (Radang amandel)', 'category' => 'Saluran Pernapasan', 'is_common' => true],
            ['code' => 'J06.9', 'name_en' => 'Acute upper respiratory infection, unspecified', 'name_id' => 'Infeksi saluran pernapasan atas akut (ISPA)', 'category' => 'Saluran Pernapasan', 'is_common' => true],
            ['code' => 'J20.9', 'name_en' => 'Acute bronchitis, unspecified', 'name_id' => 'Bronkitis akut', 'category' => 'Saluran Pernapasan', 'is_common' => true],
            ['code' => 'J45.9', 'name_en' => 'Asthma, unspecified', 'name_id' => 'Asma bronkial', 'category' => 'Saluran Pernapasan', 'is_common' => true],
            ['code' => 'J01.9', 'name_en' => 'Acute sinusitis, unspecified', 'name_id' => 'Sinusitis akut', 'category' => 'Saluran Pernapasan', 'is_common' => false],
            ['code' => 'J18.9', 'name_en' => 'Pneumonia, unspecified', 'name_id' => 'Pneumonia', 'category' => 'Saluran Pernapasan', 'is_common' => true],

            // Kardiovaskular & Sirkulasi
            ['code' => 'I10', 'name_en' => 'Essential (primary) hypertension', 'name_id' => 'Hipertensi esensial primer (Tekanan darah tinggi)', 'category' => 'Penyakit Sirkulasi', 'is_common' => true],
            ['code' => 'I11.9', 'name_en' => 'Hypertensive heart disease without heart failure', 'name_id' => 'Penyakit jantung hipertensi', 'category' => 'Penyakit Sirkulasi', 'is_common' => true],
            ['code' => 'I20.9', 'name_en' => 'Angina pectoris, unspecified', 'name_id' => 'Angina pektoris (Nyeri dada jantung)', 'category' => 'Penyakit Sirkulasi', 'is_common' => false],
            ['code' => 'I50.9', 'name_en' => 'Heart failure, unspecified', 'name_id' => 'Gagal jantung kongestif', 'category' => 'Penyakit Sirkulasi', 'is_common' => false],
            ['code' => 'I84.9', 'name_en' => 'Haemorrhoids without complication', 'name_id' => 'Hemoroid (Wasir / Ambeien)', 'category' => 'Penyakit Sirkulasi', 'is_common' => true],
            ['code' => 'I95.9', 'name_en' => 'Hypotension, unspecified', 'name_id' => 'Hipotensi (Tekanan darah rendah)', 'category' => 'Penyakit Sirkulasi', 'is_common' => true],

            // Pencernaan & Lambung
            ['code' => 'K29.7', 'name_en' => 'Gastritis, unspecified', 'name_id' => 'Gastritis (Sakit maag)', 'category' => 'Penyakit Pencernaan', 'is_common' => true],
            ['code' => 'K30', 'name_en' => 'Dyspepsia', 'name_id' => 'Dispepsia fungsional (Kembung, mual & perih lambung)', 'category' => 'Penyakit Pencernaan', 'is_common' => true],
            ['code' => 'K21.9', 'name_en' => 'Gastro-oesophageal reflux disease without oesophagitis', 'name_id' => 'GERD (Asam lambung naik)', 'category' => 'Penyakit Pencernaan', 'is_common' => true],
            ['code' => 'K59.0', 'name_en' => 'Constipation', 'name_id' => 'Konstipasi (Sembelit)', 'category' => 'Penyakit Pencernaan', 'is_common' => true],
            ['code' => 'K35.8', 'name_en' => 'Acute appendicitis, other and unspecified', 'name_id' => 'Apendisitis akut (Radang usus buntu)', 'category' => 'Penyakit Pencernaan', 'is_common' => false],

            // Endokrin & Metabolik
            ['code' => 'E11.9', 'name_en' => 'Type 2 diabetes mellitus without complications', 'name_id' => 'Diabetes mellitus tipe 2 (Kencing manis)', 'category' => 'Endokrin & Metabolik', 'is_common' => true],
            ['code' => 'E10.9', 'name_en' => 'Type 1 diabetes mellitus without complications', 'name_id' => 'Diabetes mellitus tipe 1', 'category' => 'Endokrin & Metabolik', 'is_common' => false],
            ['code' => 'E78.0', 'name_en' => 'Pure hypercholesterolaemia', 'name_id' => 'Hiperkolesterolemia (Kolesterol darah tinggi)', 'category' => 'Endokrin & Metabolik', 'is_common' => true],
            ['code' => 'E78.5', 'name_en' => 'Hyperlipidaemia, unspecified', 'name_id' => 'Hiperlipidemia (Trigliserida tinggi)', 'category' => 'Endokrin & Metabolik', 'is_common' => true],
            ['code' => 'E79.0', 'name_en' => 'Hyperuricaemia without signs of inflammatory arthritis', 'name_id' => 'Hiperurisemia (Kadar asam urat darah tinggi)', 'category' => 'Endokrin & Metabolik', 'is_common' => true],
            ['code' => 'M10.9', 'name_en' => 'Gout, unspecified', 'name_id' => 'Gout artritis (Nyeri sendi asam urat)', 'category' => 'Endokrin & Metabolik', 'is_common' => true],
            ['code' => 'E66.9', 'name_en' => 'Obesity, unspecified', 'name_id' => 'Obesitas', 'category' => 'Endokrin & Metabolik', 'is_common' => true],

            // Muskuloskeletal & Sendi
            ['code' => 'M79.1', 'name_en' => 'Myalgia', 'name_id' => 'Mialgia (Nyeri otot / Pegal linu)', 'category' => 'Penyakit Otot & Sendi', 'is_common' => true],
            ['code' => 'M54.5', 'name_en' => 'Low back pain', 'name_id' => 'Low back pain (Nyeri punggung / Pinggang bawah)', 'category' => 'Penyakit Otot & Sendi', 'is_common' => true],
            ['code' => 'M13.9', 'name_en' => 'Arthritis, unspecified', 'name_id' => 'Artritis (Radang sendi non-spesifik)', 'category' => 'Penyakit Otot & Sendi', 'is_common' => true],
            ['code' => 'M15.9', 'name_en' => 'Polyarthrosis, unspecified', 'name_id' => 'Osteoartritis (Pengapuran sendi)', 'category' => 'Penyakit Otot & Sendi', 'is_common' => true],
            ['code' => 'M54.2', 'name_en' => 'Cervicalgia', 'name_id' => 'Servikalgia (Nyeri leher / Kaku kuduk)', 'category' => 'Penyakit Otot & Sendi', 'is_common' => true],

            // Saraf & Sakit Kepala
            ['code' => 'R51', 'name_en' => 'Headache', 'name_id' => 'Cephalgia (Sakit kepala)', 'category' => 'Penyakit Saraf', 'is_common' => true],
            ['code' => 'G44.2', 'name_en' => 'Tension-type headache', 'name_id' => 'Tension headache (Sakit kepala tegang)', 'category' => 'Penyakit Saraf', 'is_common' => true],
            ['code' => 'G43.9', 'name_en' => 'Migraine, unspecified', 'name_id' => 'Migrain (Sakit kepala sebelah)', 'category' => 'Penyakit Saraf', 'is_common' => true],
            ['code' => 'R42', 'name_en' => 'Dizziness and giddiness', 'name_id' => 'Vertigo (Pusing berputar / Gangguan keseimbangan)', 'category' => 'Penyakit Saraf', 'is_common' => true],
            ['code' => 'G51.0', 'name_en' => "Bell's palsy", 'name_id' => "Bell's palsy (Kelumpuhan otot wajah)", 'category' => 'Penyakit Saraf', 'is_common' => false],

            // Kulit & Alergi
            ['code' => 'L20.9', 'name_en' => 'Atopic dermatitis, unspecified', 'name_id' => 'Dermatitis atopik (Eksim alergi)', 'category' => 'Penyakit Kulit', 'is_common' => true],
            ['code' => 'L23.9', 'name_en' => 'Allergic contact dermatitis, unspecified cause', 'name_id' => 'Dermatitis kontak alergi (DKA)', 'category' => 'Penyakit Kulit', 'is_common' => true],
            ['code' => 'L24.9', 'name_en' => 'Irritant contact dermatitis, unspecified cause', 'name_id' => 'Dermatitis kontak iritan (DKI)', 'category' => 'Penyakit Kulit', 'is_common' => true],
            ['code' => 'L50.9', 'name_en' => 'Urticaria, unspecified', 'name_id' => 'Urtikaria (Biduran / Kaligata)', 'category' => 'Penyakit Kulit', 'is_common' => true],
            ['code' => 'L70.0', 'name_en' => 'Acne vulgaris', 'name_id' => 'Akne vulgaris (Jerawat)', 'category' => 'Penyakit Kulit', 'is_common' => true],
            ['code' => 'L02.9', 'name_en' => 'Cutaneous abscess, furuncle and carbuncle, unspecified', 'name_id' => 'Bisul / Furunkel / Karbunkel', 'category' => 'Penyakit Kulit', 'is_common' => true],

            // Mata & THT
            ['code' => 'H10.9', 'name_en' => 'Conjunctivitis, unspecified', 'name_id' => 'Konjungtivitis (Sakit mata merah / Belekan)', 'category' => 'Mata & THT', 'is_common' => true],
            ['code' => 'H00.0', 'name_en' => 'Hordeolum and other deep inflammation of eyelid', 'name_id' => 'Hordeolum (Bintitan mata)', 'category' => 'Mata & THT', 'is_common' => true],
            ['code' => 'H60.9', 'name_en' => 'Otitis externa, unspecified', 'name_id' => 'Otitis eksterna (Radang liang telinga)', 'category' => 'Mata & THT', 'is_common' => true],
            ['code' => 'H65.9', 'name_en' => 'Nonsuppurative otitis media, unspecified', 'name_id' => 'Otitis media (Infeksi telinga tengah)', 'category' => 'Mata & THT', 'is_common' => true],
            ['code' => 'H61.2', 'name_en' => 'Impacted cerumen', 'name_id' => 'Serumen obturans (Kotoran telinga menyumbat)', 'category' => 'Mata & THT', 'is_common' => true],
            ['code' => 'R04.0', 'name_en' => 'Epistaxis', 'name_id' => 'Epistaksis (Mimisan)', 'category' => 'Mata & THT', 'is_common' => true],

            // Gigi & Mulut
            ['code' => 'K02.9', 'name_en' => 'Dental caries, unspecified', 'name_id' => 'Karies gigi (Gigi berlubang)', 'category' => 'Gigi & Mulut', 'is_common' => true],
            ['code' => 'K04.0', 'name_en' => 'Pulpitis', 'name_id' => 'Pulpitis (Radang pulpa gigi sakit berdenyut)', 'category' => 'Gigi & Mulut', 'is_common' => true],
            ['code' => 'K05.3', 'name_en' => 'Chronic periodontitis', 'name_id' => 'Periodontitis kronis (Radang gusi)', 'category' => 'Gigi & Mulut', 'is_common' => true],
            ['code' => 'K12.0', 'name_en' => 'Recurrent aphthous stomatitis', 'name_id' => 'Stomatitis aftosa (Sariawan mulut)', 'category' => 'Gigi & Mulut', 'is_common' => true],

            // Saluran Kemih & Kandungan
            ['code' => 'N39.0', 'name_en' => 'Urinary tract infection, site not specified', 'name_id' => 'Infeksi saluran kemih (ISK / Anyang-anyangan)', 'category' => 'Urogenital', 'is_common' => true],
            ['code' => 'N20.0', 'name_en' => 'Calculus of kidney', 'name_id' => 'Batu ginjal (Nefrolitiasis)', 'category' => 'Urogenital', 'is_common' => false],
            ['code' => 'N40', 'name_en' => 'Hyperplasia of prostate', 'name_id' => 'Hiperplasia prostat (BPH)', 'category' => 'Urogenital', 'is_common' => false],
            ['code' => 'N94.6', 'name_en' => 'Dysmenorrhoea, unspecified', 'name_id' => 'Dismenorea (Nyeri haid)', 'category' => 'Urogenital', 'is_common' => true],

            // Umum & Gejala Klinis
            ['code' => 'R50.9', 'name_en' => 'Fever, unspecified', 'name_id' => 'Demam (Penyebab belum diketahui)', 'category' => 'Gejala Umum', 'is_common' => true],
            ['code' => 'R53', 'name_en' => 'Malaise and fatigue', 'name_id' => 'Malaise dan kelelahan (Badan lemas)', 'category' => 'Gejala Umum', 'is_common' => true],
            ['code' => 'D50.9', 'name_en' => 'Iron deficiency anaemia, unspecified', 'name_id' => 'Anemia defisiensi besi (Kurang darah)', 'category' => 'Hematologi', 'is_common' => true],
            ['code' => 'Z00.0', 'name_en' => 'General medical examination', 'name_id' => 'Pemeriksaan kesehatan umum (Medical check-up / Kontrol rutin)', 'category' => 'Pemeriksaan Umum', 'is_common' => true],
            ['code' => 'T14.0', 'name_en' => 'Superficial injury of unspecified body region', 'name_id' => 'Luka lecet / Vulnus excoriatum', 'category' => 'Trauma & Cedera', 'is_common' => true],
            ['code' => 'T14.1', 'name_en' => 'Open wound of unspecified body region', 'name_id' => 'Luka robek / Vulnus laceratum', 'category' => 'Trauma & Cedera', 'is_common' => true],
        ];

        foreach ($codes as $item) {
            Icd10Code::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
