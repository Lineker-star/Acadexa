<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CmsPage;

class CmsPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug'  => 'about',
                'en'    => [
                    'title'   => 'About ACADEXA',
                    'content' => "<h2>About ZTF University Institute</h2>
<p>ZTF University Institute (ZTF-UI), located in Koumé – Bertoua, East Region, Cameroon, is a leading institution of higher learning dedicated to excellence in education, research, and community development.</p>
<h3>Our Mission</h3>
<p>To provide accessible, high-quality education that empowers students to become leaders, innovators, and contributors to Africa's sustainable development.</p>
<h3>About ACADEXA</h3>
<p>ACADEXA is ZTF-UI's official Learning Management System — a comprehensive online platform designed to extend our educational reach beyond campus walls. Students, professionals, and lifelong learners from across the continent and the world can access our courses, earn certificates, and grow their skills.</p>
<h3>Why Choose ACADEXA?</h3>
<ul>
<li>World-class instructors from ZTF-UI and beyond</li>
<li>Courses available in 6 languages</li>
<li>Industry-recognized certificates</li>
<li>Flexible, self-paced learning</li>
<li>Available on any device</li>
</ul>
<p>Visit us at: <a href='https://www.ztfuniversity.com'>www.ztfuniversity.com</a></p>",
                ],
                'fr'    => [
                    'title'   => 'À propos d\'ACADEXA',
                    'content' => "<h2>À propos de l'Institut Universitaire ZTF</h2>
<p>L'Institut Universitaire ZTF (IU-ZTF), situé à Koumé – Bertoua, dans la Région de l'Est, Cameroun, est un établissement d'enseignement supérieur dédié à l'excellence dans l'éducation, la recherche et le développement communautaire.</p>
<h3>Notre Mission</h3>
<p>Offrir une éducation accessible et de haute qualité qui permet aux étudiants de devenir des leaders, des innovateurs et des contributeurs au développement durable de l'Afrique.</p>
<h3>À propos d'ACADEXA</h3>
<p>ACADEXA est le Système de Gestion d'Apprentissage officiel de l'IU-ZTF — une plateforme en ligne complète conçue pour étendre notre portée éducative au-delà des murs du campus.</p>",
                ],
            ],
            [
                'slug'  => 'privacy',
                'en'    => [
                    'title'   => 'Privacy Policy',
                    'content' => "<h2>Privacy Policy</h2>
<p><em>Last updated: January 1, 2025</em></p>
<h3>1. Information We Collect</h3>
<p>We collect information you provide when you register, enroll in courses, or contact us. This includes your name, email address, country, and learning activity data.</p>
<h3>2. How We Use Your Information</h3>
<p>We use your information to provide and improve our services, send you important notifications about your courses, and issue certificates upon course completion.</p>
<h3>3. Data Security</h3>
<p>We implement appropriate technical and organizational measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>
<h3>4. Cookies</h3>
<p>We use session cookies to maintain your login state and remember your language preference. These are essential for the platform to function correctly.</p>
<h3>5. Contact Us</h3>
<p>For any privacy concerns, contact us at: <a href='mailto:info@ztfuniversity.com'>info@ztfuniversity.com</a></p>",
                ],
            ],
            [
                'slug'  => 'terms',
                'en'    => [
                    'title'   => 'Terms of Service',
                    'content' => "<h2>Terms of Service</h2>
<p><em>Last updated: January 1, 2025</em></p>
<h3>1. Acceptance of Terms</h3>
<p>By accessing ACADEXA, you agree to be bound by these Terms of Service and all applicable laws and regulations.</p>
<h3>2. User Accounts</h3>
<p>You are responsible for maintaining the confidentiality of your account credentials. You agree to notify us immediately of any unauthorized use of your account.</p>
<h3>3. Course Content</h3>
<p>Course materials are provided for educational purposes. You may not reproduce, distribute, or create derivative works without explicit written permission from the content creators and ZTF University Institute.</p>
<h3>4. Certificates</h3>
<p>Certificates are issued upon successful completion of courses. ACADEXA certificates represent completion of the respective course and should not be misrepresented as formal academic credentials unless specified.</p>
<h3>5. Acceptable Use</h3>
<p>You agree not to use the platform for any unlawful purpose or in any way that could damage, disable, or impair the platform.</p>
<h3>6. Contact</h3>
<p>Questions about the Terms of Service may be sent to us at: <a href='mailto:info@ztfuniversity.com'>info@ztfuniversity.com</a></p>",
                ],
            ],
        ];

        foreach ($pages as $page) {
            $enData = $page['en'];
            $frData = $page['fr'] ?? null;

            $cmsPage = CmsPage::create([
                'slug'  => $page['slug'],
                
            ]);

            $cmsPage->translations()->create([
                'locale'  => 'en',
                'title'   => $enData['title'],
                'content' => $enData['content'],
            ]);

            if ($frData) {
                $cmsPage->translations()->create([
                    'locale'  => 'fr',
                    'title'   => $frData['title'],
                    'content' => $frData['content'],
                ]);
            }
        }
    }
}
