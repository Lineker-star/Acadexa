<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Default categories: SVG icon (Bootstrap Icons name) and name in every supported language.
     * Also used by the 2026_09_28 migration to convert existing emoji icons and fill missing translations.
     */
    public const CATEGORIES = [
        ['slug' => 'technology-programming', 'icon' => 'bi-laptop', 'names' => [
            'en' => 'Technology & Programming', 'fr' => 'Technologie et programmation', 'es' => 'Tecnología y programación',
            'pt' => 'Tecnologia e programação', 'zh' => '技术与编程', 'ar' => 'التكنولوجيا والبرمجة'],
            'children' => [
                ['slug' => 'web-development', 'icon' => 'bi-globe2', 'names' => [
                    'en' => 'Web Development', 'fr' => 'Développement web', 'es' => 'Desarrollo web',
                    'pt' => 'Desenvolvimento web', 'zh' => '网页开发', 'ar' => 'تطوير الويب']],
                ['slug' => 'mobile-apps', 'icon' => 'bi-phone', 'names' => [
                    'en' => 'Mobile Apps', 'fr' => 'Applications mobiles', 'es' => 'Aplicaciones móviles',
                    'pt' => 'Aplicativos móveis', 'zh' => '移动应用', 'ar' => 'تطبيقات الجوال']],
                ['slug' => 'data-science', 'icon' => 'bi-bar-chart-line', 'names' => [
                    'en' => 'Data Science', 'fr' => 'Science des données', 'es' => 'Ciencia de datos',
                    'pt' => 'Ciência de dados', 'zh' => '数据科学', 'ar' => 'علم البيانات']],
                ['slug' => 'cybersecurity', 'icon' => 'bi-shield-lock', 'names' => [
                    'en' => 'Cybersecurity', 'fr' => 'Cybersécurité', 'es' => 'Ciberseguridad',
                    'pt' => 'Cibersegurança', 'zh' => '网络安全', 'ar' => 'الأمن السيبراني']],
            ]],
        ['slug' => 'business-entrepreneurship', 'icon' => 'bi-briefcase', 'names' => [
            'en' => 'Business & Entrepreneurship', 'fr' => 'Commerce et entrepreneuriat', 'es' => 'Negocios y emprendimiento',
            'pt' => 'Negócios e empreendedorismo', 'zh' => '商业与创业', 'ar' => 'الأعمال وريادة الأعمال'],
            'children' => [
                ['slug' => 'startup-innovation', 'icon' => 'bi-rocket-takeoff', 'names' => [
                    'en' => 'Startup & Innovation', 'fr' => 'Startup et innovation', 'es' => 'Startup e innovación',
                    'pt' => 'Startup e inovação', 'zh' => '初创与创新', 'ar' => 'الشركات الناشئة والابتكار']],
                ['slug' => 'finance-accounting', 'icon' => 'bi-cash-coin', 'names' => [
                    'en' => 'Finance & Accounting', 'fr' => 'Finance et comptabilité', 'es' => 'Finanzas y contabilidad',
                    'pt' => 'Finanças e contabilidade', 'zh' => '财务与会计', 'ar' => 'المالية والمحاسبة']],
                ['slug' => 'project-management', 'icon' => 'bi-kanban', 'names' => [
                    'en' => 'Project Management', 'fr' => 'Gestion de projet', 'es' => 'Gestión de proyectos',
                    'pt' => 'Gestão de projetos', 'zh' => '项目管理', 'ar' => 'إدارة المشاريع']],
            ]],
        ['slug' => 'arts-design', 'icon' => 'bi-palette', 'names' => [
            'en' => 'Arts & Design', 'fr' => 'Arts et design', 'es' => 'Arte y diseño',
            'pt' => 'Artes e design', 'zh' => '艺术与设计', 'ar' => 'الفنون والتصميم'],
            'children' => [
                ['slug' => 'graphic-design', 'icon' => 'bi-vector-pen', 'names' => [
                    'en' => 'Graphic Design', 'fr' => 'Design graphique', 'es' => 'Diseño gráfico',
                    'pt' => 'Design gráfico', 'zh' => '平面设计', 'ar' => 'التصميم الجرافيكي']],
                ['slug' => 'photography', 'icon' => 'bi-camera', 'names' => [
                    'en' => 'Photography', 'fr' => 'Photographie', 'es' => 'Fotografía',
                    'pt' => 'Fotografia', 'zh' => '摄影', 'ar' => 'التصوير الفوتوغرافي']],
            ]],
        ['slug' => 'languages', 'icon' => 'bi-translate', 'names' => [
            'en' => 'Languages', 'fr' => 'Langues', 'es' => 'Idiomas',
            'pt' => 'Idiomas', 'zh' => '语言', 'ar' => 'اللغات'],
            'children' => [
                ['slug' => 'english', 'icon' => 'bi-chat-left-text', 'names' => [
                    'en' => 'English', 'fr' => 'Anglais', 'es' => 'Inglés',
                    'pt' => 'Inglês', 'zh' => '英语', 'ar' => 'اللغة الإنجليزية']],
                ['slug' => 'french', 'icon' => 'bi-chat-right-text', 'names' => [
                    'en' => 'French', 'fr' => 'Français', 'es' => 'Francés',
                    'pt' => 'Francês', 'zh' => '法语', 'ar' => 'اللغة الفرنسية']],
            ]],
        ['slug' => 'health-wellness', 'icon' => 'bi-heart-pulse', 'names' => [
            'en' => 'Health & Wellness', 'fr' => 'Santé et bien-être', 'es' => 'Salud y bienestar',
            'pt' => 'Saúde e bem-estar', 'zh' => '健康与保健', 'ar' => 'الصحة والعافية']],
        ['slug' => 'agriculture-environment', 'icon' => 'bi-tree', 'names' => [
            'en' => 'Agriculture & Environment', 'fr' => 'Agriculture et environnement', 'es' => 'Agricultura y medio ambiente',
            'pt' => 'Agricultura e meio ambiente', 'zh' => '农业与环境', 'ar' => 'الزراعة والبيئة']],
        ['slug' => 'education-teaching', 'icon' => 'bi-book', 'names' => [
            'en' => 'Education & Teaching', 'fr' => 'Éducation et enseignement', 'es' => 'Educación y enseñanza',
            'pt' => 'Educação e ensino', 'zh' => '教育与教学', 'ar' => 'التعليم والتدريس']],
        ['slug' => 'personal-development', 'icon' => 'bi-stars', 'names' => [
            'en' => 'Personal Development', 'fr' => 'Développement personnel', 'es' => 'Desarrollo personal',
            'pt' => 'Desenvolvimento pessoal', 'zh' => '个人发展', 'ar' => 'التنمية الشخصية']],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $order => $data) {
            $parent = $this->createCategory($data, null, $order);
            foreach ($data['children'] ?? [] as $i => $childData) {
                $this->createCategory($childData, $parent->id, $i);
            }
        }
    }

    private function createCategory(array $data, ?int $parentId, int $order): Category
    {
        $category = Category::create([
            'name'      => $data['names']['en'],
            'slug'      => $data['slug'],
            'icon'      => $data['icon'],
            'parent_id' => $parentId,
            'is_active' => true,
            'order'     => $order,
        ]);

        foreach ($data['names'] as $locale => $name) {
            $category->translations()->create(['locale' => $locale, 'name' => $name]);
        }

        return $category;
    }
}
