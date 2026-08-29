<?php

namespace Database\Seeders;

use App\Models\AvailabilityRule;
use App\Models\ResearchClaim;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach ([
            ['Power Admin', 'power.admin@example.test', 'power_admin'], ['Admin', 'admin@example.test', 'admin'],
            ['Moderator', 'moderator@example.test', 'moderator'], ['Demo Patient', 'patient@example.test', 'patient'],
        ] as [$name, $email, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'role' => $role,
                'password' => 'ChangeMe!2026',
                'is_active' => true,
                'email_verified_at' => now(),
                'account_claimed_at' => now(),
            ]);
        }

        $services = [
            ['Breast Concern Consultation', 'breast-concern', 'Assessment of a new lump, breast pain, nipple change or other breast concern.', 45, true],
            ['Breast Cancer Consultation', 'breast-cancer', 'A specialist consultation to understand a diagnosis and discuss an individual care pathway.', 60, true],
            ['Breast Surgery Consultation', 'breast-surgery', 'Discuss surgical assessment, treatment options and what to expect before and after surgery.', 60, false],
            ['Second Opinion', 'second-opinion', 'An independent specialist review of an existing diagnosis, test result or proposed treatment plan.', 60, true],
            ['Follow-up Consultation', 'follow-up', 'Review recovery, results, ongoing treatment questions or survivorship needs.', 30, true],
            ['Breast Screening Guidance', 'screening', 'Personalised discussion of appropriate screening based on age, symptoms and risk.', 30, true],
        ];
        foreach ($services as $i => [$name,$slug,$summary,$duration,$online]) {
            Service::updateOrCreate(['slug' => $slug], compact('name', 'slug', 'summary') + ['description' => $summary, 'duration_minutes' => $duration, 'online_available' => $online, 'sort_order' => $i]);
        }

        foreach ([1 => ['09:00', '13:00'], 2 => ['14:00', '18:00'], 4 => ['09:00', '13:00']] as $weekday => [$start,$end]) {
            AvailabilityRule::updateOrCreate(['weekday' => $weekday, 'start_time' => $start], ['end_time' => $end, 'slot_minutes' => 45, 'buffer_minutes' => 15, 'consultation_method' => 'both', 'is_active' => true]);
        }

        ResearchClaim::firstOrCreate(['claim' => 'ORCID identifier: 0000-0003-3315-990X'], ['category' => 'Academic profile', 'source_title' => 'ORCID profile', 'source_url' => 'https://orcid.org/0000-0003-3315-990X', 'confidence' => 'high', 'status' => 'pending_review']);

        foreach ([
            ['Employment as Lecturer/Consultant General Surgeon in Surgery at Obafemi Awolowo University.', 'Career', 'ORCID record', 'https://orcid.org/0000-0003-3315-990X', 'career', ['year_label' => 'Current', 'institution' => 'Obafemi Awolowo University', 'position' => 'Lecturer / Consultant General Surgeon', 'location' => 'Ile-Ife, Osun, Nigeria', 'description' => 'Academic and consultant appointment in Surgery.', 'source_url' => 'https://orcid.org/0000-0003-3315-990X', 'sort_order' => 10]],
            ['Breast Surgical Oncology Fellow at the University of Toronto / Princess Margaret Cancer Centre.', 'Fellowship', 'Princess Margaret Global Cancer Program', 'https://www.uhn.ca/PrincessMargaret/Global_Cancer_Program', 'achievement', ['title' => 'Breast Surgical Oncology Fellowship', 'year_label' => '2021', 'organization' => 'University of Toronto / Princess Margaret Cancer Centre', 'description' => 'Institutional profile identifies Dr. Wuraola as a Breast Surgical Oncology Fellow.', 'category' => 'Fellowships', 'source_url' => 'https://www.uhn.ca/PrincessMargaret/Global_Cancer_Program']],
            ['Clinicopathologic Profile and Psychosocial Experiences of Nigerian Breast Cancer Survivors.', 'Publication', 'PubMed PMID 37769219', 'https://pubmed.ncbi.nlm.nih.gov/37769219/', 'publication', ['title' => 'Clinicopathologic Profile and Psychosocial Experiences of Nigerian Breast Cancer Survivors', 'authors' => 'Funmilola Olanike Wuraola et al.', 'journal' => 'JCO Global Oncology', 'published_at' => '2023-09-01', 'doi' => '10.1200/GO.23.00022', 'pmid' => '37769219', 'category' => 'Cancer Survivorship', 'external_url' => 'https://pubmed.ncbi.nlm.nih.gov/37769219/', 'publication_type' => 'journal_article']],
            ['Pilot Health Care Provider Education Program for BRCA1/2 Genetic Testing, Counseling, and Management in Nigeria.', 'Publication', 'PubMed PMID 41538750', 'https://pubmed.ncbi.nlm.nih.gov/41538750/', 'publication', ['title' => 'Pilot Health Care Provider Education Program for BRCA1/2 Genetic Testing, Counseling, and Management in Nigeria', 'authors' => 'Funmilola O. Wuraola et al.', 'journal' => 'JCO Global Oncology', 'published_at' => '2026-01-15', 'doi' => '10.1200/GO-25-00306', 'pmid' => '41538750', 'category' => 'Genetics', 'external_url' => 'https://pubmed.ncbi.nlm.nih.gov/41538750/', 'publication_type' => 'journal_article']],
            ['Barriers to Breast Reconstruction After Mastectomy for Breast Cancer Management in Nigeria: Perspectives of Health Care Professionals.', 'Publication', 'PubMed PMID 41985118', 'https://pubmed.ncbi.nlm.nih.gov/41985118/', 'publication', ['title' => 'Barriers to Breast Reconstruction After Mastectomy for Breast Cancer Management in Nigeria: Perspectives of Health Care Professionals', 'authors' => 'Funmilola Olanike Wuraola et al.', 'journal' => 'JCO Global Oncology', 'published_at' => '2026-04-15', 'doi' => '10.1200/GO-25-00636', 'pmid' => '41985118', 'category' => 'Breast Reconstruction', 'external_url' => 'https://pubmed.ncbi.nlm.nih.gov/41985118/', 'publication_type' => 'journal_article']],
            ['Prospects for Population-Based Breast Cancer Screening in Nigeria: An Integrative Literature Review.', 'Publication', 'PubMed PMID 41759050', 'https://pubmed.ncbi.nlm.nih.gov/41759050/', 'publication', ['title' => 'Prospects for Population-Based Breast Cancer Screening in Nigeria: An Integrative Literature Review', 'authors' => 'Korede M. Akindoju et al.', 'journal' => 'JCO Global Oncology', 'published_at' => '2026-02-27', 'doi' => '10.1200/GO-25-00027', 'pmid' => '41759050', 'category' => 'Screening', 'external_url' => 'https://pubmed.ncbi.nlm.nih.gov/41759050/', 'publication_type' => 'review']],
            ['Oncoplastic Breast-Conserving Surgery in African Women: A Systematic Review.', 'Publication', 'PubMed PMID 39052945', 'https://pubmed.ncbi.nlm.nih.gov/39052945/', 'publication', ['title' => 'Oncoplastic Breast-Conserving Surgery in African Women: A Systematic Review', 'authors' => 'Abdulhafiz Oladapo Adesunkanmi et al.', 'journal' => 'JCO Global Oncology', 'published_at' => '2024-07-01', 'doi' => '10.1200/GO.23.00460', 'pmid' => '39052945', 'category' => 'Breast Surgery', 'external_url' => 'https://pubmed.ncbi.nlm.nih.gov/39052945/', 'publication_type' => 'systematic_review']],
            ['The out-of-pocket cost of breast cancer care in Nigeria: A prospective analysis.', 'Publication', 'PubMed PMID 39522636', 'https://pubmed.ncbi.nlm.nih.gov/39522636/', 'publication', ['title' => 'The out-of-pocket cost of breast cancer care in Nigeria: A prospective analysis', 'authors' => 'Funmilola Olanike Wuraola et al.', 'journal' => 'Journal of Cancer Policy', 'published_at' => '2024-12-01', 'doi' => '10.1016/j.jcpo.2024.100518', 'pmid' => '39522636', 'category' => 'Health Economics', 'external_url' => 'https://pubmed.ncbi.nlm.nih.gov/39522636/', 'publication_type' => 'journal_article']],
        ] as [$claim,$category,$sourceTitle,$sourceUrl,$targetType,$payload]) {
            ResearchClaim::firstOrCreate(['claim' => $claim], ['category' => $category, 'source_title' => $sourceTitle, 'source_url' => $sourceUrl, 'confidence' => 'high', 'status' => 'pending_review', 'target_type' => $targetType, 'target_payload' => $payload]);
        }

        $this->call(CoreCmsPageSeeder::class);
    }
}
