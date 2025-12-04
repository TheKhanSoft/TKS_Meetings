<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Keyword;

class KeywordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $keywords = [
            // Approvals & Placements
            'Program Approval',
            'Subject Approval',
            'Subject Placement',
            'Scheme of Studies Approval',
            'BOS Approval',
            'BOF Approval',
            'Board of Studies Approval',
            'Board of Studies Placement',
            'Board of Faculty Approval',
            'Board of Faculty Placement',
            'Academic Council Approval',
            'Syndicate Approval',
            'Senate Approval',
            
            // "Approval of ..." Variations
            'Approval of Program',
            'Approval of Subject',
            'Approval of Scheme of Studies',
            'Approval of Board of Studies',
            'Approval of Board of Faculty',
            'Approval of Academic Council',
            'Approval of BOS',
            'Approval of BOF',
            'Approval of AC',
            'Approval of Syndicate',
            'Approval of Senate',
            'Approval of Curriculum',
            'Approval of New Department',
            'Approval of Faculty Recruitment',
            'Approval of Research Grant',
            'Approval of Examination Policy',
            'Approval of Semester Rules',
            'Approval of Academic Calendar',
            'Approval of Budget',
            'Approval of Scholarship',
            'Approval of Admissions',
            'Approval of Fee Structure',
            'Approval of MOU',
            'Approval of Affiliation',
            'Approval of Result',
            'Approval of Degree',
            'Approval of Medal',
            'Approval of Appointment',
            'Approval of Promotion',
            'Approval of Leave',
            'Approval of Project',
            'Approval of Report',
            'Approval of Rules',
            'Approval of Regulations',
            'Approval of Statutes',
            'Approval of Policies',
            'Approval of Guidelines',
            'Approval of Accreditation',
            'Approval of NOC',
            'Approval of Audit',
            'Approval of Inquiry',
            'Approval of Financial Matters',


            // Placement Variations
            'Placement of Subject',
            'Placement of Program',
            'Placement of Scheme of Studies',
            'Placement of Agenda',

            // Ratification & Confirmation
            'Ratification of Decision',
            'Ratification of Action',
            'Ratification of Notification',
            'Confirmation of Proceedings',

            // Constitution & Reconstitution
            'Constitution of Board',
            'Reconstitution of Board',
            'Constitution of Committee',
            'Reconstitution of Committee',
            'Nomination of Members',

            // Awards & Degrees
            'Award of Degree',
            'Award of Gold Medal',
            'Award of PhD',
            'Award of MPhil',
            'List of Graduates',

            // Regulatory & Policy
            'HEC Policy',
            'HEC Guidelines',
            'PEC Accreditation',
            'NCEAC Accreditation',
            'Accreditation Status',
            'NOC Issuance',

            // Audit & Inquiry
            'Audit Report',
            'Internal Audit',
            'Inquiry Committee',
            'Inquiry Report',
            'Fact Finding Report',

            // Appointments & HR
            'Appointment of Dean',
            'Appointment of HOD',
            'Appointment of Examiners',
            'Appointment of Paper Setters',
            'Revision of Rates',
            'Revision of Pay',
            'Honorarium',

            // Minutes & Proceedings
            'Approval of Minutes',
            'Minutes Approval',
            'Confirmation of Minutes',
            'Confirmation of the Minutes',
            'Proceedings Confirmation',
            'Action Taken Report',
            'Implementation Status',
            'Minuts of the Extension Committee',
            'Minutes of the Board of Faculty',
            'Minutes of the Board of Studies',
            'Minutes of the Academic Council',
            'Minutes of the BOF',
            'Minutes of the BOS',
            'Minutes of the AC',
            'Minutes of the Senate',
            'Minutes of the Syndicate',
            'Minutes of the Committee',
            'Minutes of the Meeting',

            // Academic Matters
            'Curriculum Revision',
            'New Department',
            'New Program',
            'New Subject',
            'Faculty Recruitment',
            'Research Grant',
            'Student Affairs',
            'Examination Policy',
            'Quality Assurance',
            'Semester Rules',
            'Academic Calendar',
            'Course Exemption',
            'Credit Transfer',
            'Thesis Defense',
            'Extension Granted',
            'Extension Request',
            'Study Leave',
            'Sabbatical Leave',

            // Administrative & Financial
            'Budget Approval',
            'Financial Matters',
            'Infrastructure',
            'Scholarship',
            'Admissions',
            'Convocation',
            'Disciplinary Committee',
            'Fee Structure',
            'Procurement',
            'MOU Signing',
            'Affiliation',
        ];

        foreach ($keywords as $keyword) {
            Keyword::firstOrCreate(['name' => $keyword]);
        }
    }
}
