<?php

namespace Database\Seeders;

use App\Models\CmsPage;
use Illuminate\Database\Seeder;

class CmsPageSeeder extends Seeder
{
    /**
     * System pages in every supported language.
     * Also used by the 2026_09_28 migration to add missing languages to existing installations.
     */
    public const PAGES = [
        'about' => [
            'en' => ['title' => 'About ACADEXA', 'content' => '<h2>About ZTF University Institute</h2>
<p>ZTF University Institute (ZTF-UI), located in Koumé – Bertoua, East Region, Cameroon, is an institution of higher learning dedicated to excellence in education, research and community development.</p>
<h3>Our mission</h3>
<p>To provide accessible, high-quality education that empowers students to become leaders, innovators and contributors to Africa\'s sustainable development.</p>
<h3>About ACADEXA</h3>
<p>ACADEXA is the official learning platform of ZTF-UI. It extends our teaching beyond the campus: students, professionals and lifelong learners can follow our courses, earn certificates and develop their skills, online or offline.</p>
<h3>Why choose ACADEXA?</h3>
<ul><li>Instructors from ZTF-UI and industry</li><li>Courses in 6 languages</li><li>Verifiable certificates</li><li>Flexible, self-paced learning</li><li>Available on every device, even without a connection</li></ul>
<p>Website: <a href="https://www.ztfuniversity.com">www.ztfuniversity.com</a></p>'],
            'fr' => ['title' => 'À propos d\'ACADEXA', 'content' => '<h2>À propos de l\'Institut Universitaire ZTF</h2>
<p>L\'Institut Universitaire ZTF (IU-ZTF), situé à Koumé – Bertoua, dans la Région de l\'Est du Cameroun, est un établissement d\'enseignement supérieur engagé pour l\'excellence dans l\'éducation, la recherche et le développement communautaire.</p>
<h3>Notre mission</h3>
<p>Offrir une éducation accessible et de qualité qui permet aux étudiants de devenir des leaders, des innovateurs et des acteurs du développement durable de l\'Afrique.</p>
<h3>À propos d\'ACADEXA</h3>
<p>ACADEXA est la plateforme d\'apprentissage officielle de l\'IU-ZTF. Elle prolonge notre enseignement au-delà du campus : étudiants, professionnels et apprenants de tous âges peuvent suivre nos cours, obtenir des certificats et développer leurs compétences, en ligne comme hors ligne.</p>
<h3>Pourquoi choisir ACADEXA ?</h3>
<ul><li>Des formateurs de l\'IU-ZTF et du monde professionnel</li><li>Des cours en 6 langues</li><li>Des certificats vérifiables</li><li>Un apprentissage flexible, à votre rythme</li><li>Disponible sur tous les appareils, même sans connexion</li></ul>
<p>Site web : <a href="https://www.ztfuniversity.com">www.ztfuniversity.com</a></p>'],
            'es' => ['title' => 'Acerca de ACADEXA', 'content' => '<h2>Acerca del Instituto Universitario ZTF</h2>
<p>El Instituto Universitario ZTF (IU-ZTF), situado en Koumé – Bertoua, Región del Este de Camerún, es una institución de educación superior comprometida con la excelencia en la enseñanza, la investigación y el desarrollo comunitario.</p>
<h3>Nuestra misión</h3>
<p>Ofrecer una educación accesible y de calidad que permita a los estudiantes convertirse en líderes, innovadores y actores del desarrollo sostenible de África.</p>
<h3>Acerca de ACADEXA</h3>
<p>ACADEXA es la plataforma de aprendizaje oficial del IU-ZTF. Extiende nuestra enseñanza más allá del campus: estudiantes, profesionales y personas de todas las edades pueden seguir nuestros cursos, obtener certificados y desarrollar sus competencias, en línea o sin conexión.</p>
<h3>¿Por qué elegir ACADEXA?</h3>
<ul><li>Instructores del IU-ZTF y del mundo profesional</li><li>Cursos en 6 idiomas</li><li>Certificados verificables</li><li>Aprendizaje flexible, a tu ritmo</li><li>Disponible en todos los dispositivos, incluso sin conexión</li></ul>
<p>Sitio web: <a href="https://www.ztfuniversity.com">www.ztfuniversity.com</a></p>'],
            'pt' => ['title' => 'Sobre a ACADEXA', 'content' => '<h2>Sobre o Instituto Universitário ZTF</h2>
<p>O Instituto Universitário ZTF (IU-ZTF), localizado em Koumé – Bertoua, Região Leste dos Camarões, é uma instituição de ensino superior dedicada à excelência no ensino, na pesquisa e no desenvolvimento comunitário.</p>
<h3>Nossa missão</h3>
<p>Oferecer uma educação acessível e de qualidade que permita aos estudantes tornarem-se líderes, inovadores e agentes do desenvolvimento sustentável da África.</p>
<h3>Sobre a ACADEXA</h3>
<p>A ACADEXA é a plataforma de aprendizagem oficial do IU-ZTF. Ela leva o nosso ensino além do campus: estudantes, profissionais e pessoas de todas as idades podem seguir os nossos cursos, obter certificados e desenvolver competências, online ou offline.</p>
<h3>Por que escolher a ACADEXA?</h3>
<ul><li>Instrutores do IU-ZTF e do mercado</li><li>Cursos em 6 idiomas</li><li>Certificados verificáveis</li><li>Aprendizagem flexível, no seu ritmo</li><li>Disponível em todos os dispositivos, mesmo sem conexão</li></ul>
<p>Site: <a href="https://www.ztfuniversity.com">www.ztfuniversity.com</a></p>'],
            'zh' => ['title' => '关于 ACADEXA', 'content' => '<h2>关于 ZTF 大学学院</h2>
<p>ZTF 大学学院（ZTF-UI）位于喀麦隆东部大区贝尔图阿的库梅，是一所致力于卓越教学、科研和社区发展的高等教育机构。</p>
<h3>我们的使命</h3>
<p>提供普及而优质的教育，使学生成为推动非洲可持续发展的领导者、创新者和贡献者。</p>
<h3>关于 ACADEXA</h3>
<p>ACADEXA 是 ZTF-UI 的官方学习平台，将我们的教学延伸到校园之外：学生、职场人士和终身学习者都可以在线或离线学习我们的课程、获得证书并提升技能。</p>
<h3>为什么选择 ACADEXA？</h3>
<ul><li>来自 ZTF-UI 和行业的讲师</li><li>6 种语言的课程</li><li>可验证的证书</li><li>灵活、按自己节奏学习</li><li>适用于所有设备，即使没有网络</li></ul>
<p>网站：<a href="https://www.ztfuniversity.com">www.ztfuniversity.com</a></p>'],
            'ar' => ['title' => 'عن أكاديكسا', 'content' => '<h2>عن معهد ZTF الجامعي</h2>
<p>معهد ZTF الجامعي (ZTF-UI)، الواقع في كومي – بيرتوا بالمنطقة الشرقية في الكاميرون، مؤسسة للتعليم العالي ملتزمة بالتميز في التعليم والبحث وتنمية المجتمع.</p>
<h3>رسالتنا</h3>
<p>تقديم تعليم متاح وعالي الجودة يمكّن الطلاب من أن يصبحوا قادة ومبتكرين ومساهمين في التنمية المستدامة لأفريقيا.</p>
<h3>عن أكاديكسا</h3>
<p>أكاديكسا هي منصة التعلم الرسمية لمعهد ZTF الجامعي، وهي تمد تعليمنا إلى ما وراء الحرم الجامعي: يمكن للطلاب والمهنيين والمتعلمين مدى الحياة متابعة دوراتنا والحصول على الشهادات وتطوير مهاراتهم، عبر الإنترنت أو دون اتصال.</p>
<h3>لماذا تختار أكاديكسا؟</h3>
<ul><li>مدربون من معهد ZTF الجامعي ومن عالم المهن</li><li>دورات بست لغات</li><li>شهادات قابلة للتحقق</li><li>تعلم مرن وفق وتيرتك</li><li>متاحة على جميع الأجهزة، حتى دون اتصال</li></ul>
<p>الموقع الإلكتروني: <a href="https://www.ztfuniversity.com">www.ztfuniversity.com</a></p>'],
        ],

        'privacy' => [
            'en' => ['title' => 'Privacy Policy', 'content' => '<h2>Privacy Policy</h2>
<h3>1. Information we collect</h3>
<p>We collect the information you provide when you register, enroll in courses or contact us: your name, e-mail address, country and learning activity (progress, quiz results, submitted assignments).</p>
<h3>2. How we use it</h3>
<p>We use this information to provide and improve our services, send you notifications about your courses and issue certificates when you complete a course.</p>
<h3>3. Offline mode</h3>
<p>Courses you download for offline use are stored only on your device and are deleted when you log out.</p>
<h3>4. Data security</h3>
<p>We apply technical and organisational measures to protect your personal data against unauthorised access, alteration, disclosure or destruction.</p>
<h3>5. Cookies</h3>
<p>We use session cookies to keep you signed in and to remember your language. They are necessary for the platform to work.</p>
<h3>6. Contact</h3>
<p>For any privacy question: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'fr' => ['title' => 'Politique de confidentialité', 'content' => '<h2>Politique de confidentialité</h2>
<h3>1. Informations collectées</h3>
<p>Nous collectons les informations que vous fournissez lors de votre inscription, de vos inscriptions aux cours ou lorsque vous nous contactez : nom, adresse e-mail, pays et données d\'apprentissage (progression, résultats aux quiz, devoirs remis).</p>
<h3>2. Utilisation</h3>
<p>Ces informations servent à fournir et améliorer nos services, à vous envoyer des notifications sur vos cours et à délivrer vos certificats lorsque vous terminez un cours.</p>
<h3>3. Mode hors ligne</h3>
<p>Les cours téléchargés pour le hors-ligne sont stockés uniquement sur votre appareil et supprimés à la déconnexion.</p>
<h3>4. Sécurité des données</h3>
<p>Nous appliquons des mesures techniques et organisationnelles pour protéger vos données contre tout accès, modification, divulgation ou destruction non autorisés.</p>
<h3>5. Cookies</h3>
<p>Nous utilisons des cookies de session pour maintenir votre connexion et mémoriser votre langue. Ils sont indispensables au fonctionnement de la plateforme.</p>
<h3>6. Contact</h3>
<p>Pour toute question relative à vos données : <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'es' => ['title' => 'Política de privacidad', 'content' => '<h2>Política de privacidad</h2>
<h3>1. Información que recopilamos</h3>
<p>Recopilamos la información que proporcionas al registrarte, inscribirte en cursos o contactarnos: nombre, correo electrónico, país y datos de aprendizaje (progreso, resultados de cuestionarios, tareas entregadas).</p>
<h3>2. Uso de la información</h3>
<p>Usamos esta información para prestar y mejorar nuestros servicios, enviarte notificaciones sobre tus cursos y emitir certificados cuando completas un curso.</p>
<h3>3. Modo sin conexión</h3>
<p>Los cursos descargados para usar sin conexión se guardan solo en tu dispositivo y se eliminan al cerrar sesión.</p>
<h3>4. Seguridad de los datos</h3>
<p>Aplicamos medidas técnicas y organizativas para proteger tus datos personales contra el acceso, la modificación, la divulgación o la destrucción no autorizados.</p>
<h3>5. Cookies</h3>
<p>Usamos cookies de sesión para mantener tu sesión iniciada y recordar tu idioma. Son necesarias para el funcionamiento de la plataforma.</p>
<h3>6. Contacto</h3>
<p>Para cualquier pregunta sobre privacidad: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'pt' => ['title' => 'Política de privacidade', 'content' => '<h2>Política de privacidade</h2>
<h3>1. Informações que coletamos</h3>
<p>Coletamos as informações que você fornece ao se registrar, inscrever-se em cursos ou entrar em contato: nome, e-mail, país e dados de aprendizagem (progresso, resultados de questionários, trabalhos enviados).</p>
<h3>2. Como usamos</h3>
<p>Usamos essas informações para prestar e melhorar os nossos serviços, enviar notificações sobre os seus cursos e emitir certificados quando você conclui um curso.</p>
<h3>3. Modo offline</h3>
<p>Os cursos baixados para uso offline ficam armazenados apenas no seu dispositivo e são apagados quando você sai da conta.</p>
<h3>4. Segurança dos dados</h3>
<p>Aplicamos medidas técnicas e organizacionais para proteger os seus dados pessoais contra acesso, alteração, divulgação ou destruição não autorizados.</p>
<h3>5. Cookies</h3>
<p>Usamos cookies de sessão para manter você conectado e lembrar o seu idioma. Eles são necessários para o funcionamento da plataforma.</p>
<h3>6. Contato</h3>
<p>Para qualquer questão de privacidade: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'zh' => ['title' => '隐私政策', 'content' => '<h2>隐私政策</h2>
<h3>1. 我们收集的信息</h3>
<p>我们收集您在注册、报名课程或联系我们时提供的信息：姓名、电子邮箱、国家以及学习数据（进度、测验成绩、提交的作业）。</p>
<h3>2. 信息的使用</h3>
<p>我们使用这些信息来提供和改进服务、向您发送课程通知，并在您完成课程时颁发证书。</p>
<h3>3. 离线模式</h3>
<p>为离线学习而下载的课程仅保存在您的设备上，并在您退出登录时删除。</p>
<h3>4. 数据安全</h3>
<p>我们采取技术和组织措施，保护您的个人数据免遭未经授权的访问、修改、披露或销毁。</p>
<h3>5. Cookie</h3>
<p>我们使用会话 Cookie 来保持您的登录状态并记住您的语言。它们是平台正常运行所必需的。</p>
<h3>6. 联系我们</h3>
<p>如有任何隐私问题：<a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'ar' => ['title' => 'سياسة الخصوصية', 'content' => '<h2>سياسة الخصوصية</h2>
<h3>1. المعلومات التي نجمعها</h3>
<p>نجمع المعلومات التي تقدمها عند التسجيل أو الالتحاق بالدورات أو التواصل معنا: الاسم والبريد الإلكتروني والبلد وبيانات التعلم (التقدم ونتائج الاختبارات والواجبات المسلّمة).</p>
<h3>2. كيفية استخدامها</h3>
<p>نستخدم هذه المعلومات لتقديم خدماتنا وتحسينها، وإرسال إشعارات حول دوراتك، وإصدار الشهادات عند إتمامك لدورة.</p>
<h3>3. الوضع دون اتصال</h3>
<p>تُخزَّن الدورات التي تنزّلها للاستخدام دون اتصال على جهازك فقط، وتُحذف عند تسجيل الخروج.</p>
<h3>4. أمن البيانات</h3>
<p>نطبق تدابير تقنية وتنظيمية لحماية بياناتك الشخصية من الوصول أو التعديل أو الإفصاح أو الإتلاف غير المصرح به.</p>
<h3>5. ملفات تعريف الارتباط</h3>
<p>نستخدم ملفات تعريف ارتباط الجلسة لإبقائك مسجلاً للدخول وتذكّر لغتك، وهي ضرورية لعمل المنصة.</p>
<h3>6. التواصل</h3>
<p>لأي سؤال يتعلق بالخصوصية: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
        ],

        'terms' => [
            'en' => ['title' => 'Terms of Service', 'content' => '<h2>Terms of Service</h2>
<h3>1. Acceptance</h3>
<p>By using ACADEXA, you agree to these terms and to the applicable laws and regulations.</p>
<h3>2. Accounts</h3>
<p>You are responsible for keeping your login details confidential and must tell us immediately about any unauthorised use of your account.</p>
<h3>3. Course content</h3>
<p>Course materials are provided for educational purposes. You may not reproduce, distribute or create derivative works without written permission from their authors and ZTF University Institute. Content downloaded for offline use remains protected.</p>
<h3>4. Certificates</h3>
<p>Certificates are issued when a course is successfully completed. They attest to the completion of that course and are not formal academic degrees unless stated otherwise.</p>
<h3>5. Acceptable use</h3>
<p>You agree not to use the platform for unlawful purposes or in any way that could damage, disable or impair it.</p>
<h3>6. Contact</h3>
<p>Questions about these terms: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'fr' => ['title' => 'Conditions d\'utilisation', 'content' => '<h2>Conditions d\'utilisation</h2>
<h3>1. Acceptation</h3>
<p>En utilisant ACADEXA, vous acceptez les présentes conditions ainsi que les lois et règlements applicables.</p>
<h3>2. Comptes</h3>
<p>Vous êtes responsable de la confidentialité de vos identifiants et devez nous signaler immédiatement toute utilisation non autorisée de votre compte.</p>
<h3>3. Contenus des cours</h3>
<p>Les supports de cours sont fournis à des fins pédagogiques. Toute reproduction, diffusion ou création d\'œuvre dérivée est interdite sans l\'autorisation écrite de leurs auteurs et de l\'Institut Universitaire ZTF. Les contenus téléchargés pour le hors-ligne restent protégés.</p>
<h3>4. Certificats</h3>
<p>Les certificats sont délivrés à la réussite d\'un cours. Ils attestent de l\'achèvement de ce cours et ne constituent pas un diplôme académique, sauf mention contraire.</p>
<h3>5. Utilisation acceptable</h3>
<p>Vous vous engagez à ne pas utiliser la plateforme à des fins illicites ni de manière à l\'endommager ou à en perturber le fonctionnement.</p>
<h3>6. Contact</h3>
<p>Questions sur ces conditions : <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'es' => ['title' => 'Términos del servicio', 'content' => '<h2>Términos del servicio</h2>
<h3>1. Aceptación</h3>
<p>Al usar ACADEXA, aceptas estos términos y las leyes y normas aplicables.</p>
<h3>2. Cuentas</h3>
<p>Eres responsable de mantener la confidencialidad de tus credenciales y debes informarnos de inmediato de cualquier uso no autorizado de tu cuenta.</p>
<h3>3. Contenido de los cursos</h3>
<p>Los materiales de los cursos se ofrecen con fines educativos. No puedes reproducirlos, distribuirlos ni crear obras derivadas sin permiso escrito de sus autores y del Instituto Universitario ZTF. El contenido descargado para usar sin conexión sigue protegido.</p>
<h3>4. Certificados</h3>
<p>Los certificados se emiten al completar un curso con éxito. Acreditan la finalización de ese curso y no son títulos académicos oficiales, salvo que se indique lo contrario.</p>
<h3>5. Uso aceptable</h3>
<p>Te comprometes a no usar la plataforma con fines ilícitos ni de forma que pueda dañarla o impedir su funcionamiento.</p>
<h3>6. Contacto</h3>
<p>Preguntas sobre estos términos: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'pt' => ['title' => 'Termos de serviço', 'content' => '<h2>Termos de serviço</h2>
<h3>1. Aceitação</h3>
<p>Ao usar a ACADEXA, você aceita estes termos e as leis e regulamentos aplicáveis.</p>
<h3>2. Contas</h3>
<p>Você é responsável por manter a confidencialidade das suas credenciais e deve nos informar imediatamente sobre qualquer uso não autorizado da sua conta.</p>
<h3>3. Conteúdo dos cursos</h3>
<p>Os materiais dos cursos são fornecidos para fins educativos. Não é permitido reproduzi-los, distribuí-los ou criar obras derivadas sem autorização escrita dos autores e do Instituto Universitário ZTF. O conteúdo baixado para uso offline continua protegido.</p>
<h3>4. Certificados</h3>
<p>Os certificados são emitidos após a conclusão bem-sucedida de um curso. Eles atestam a conclusão desse curso e não são diplomas acadêmicos formais, salvo indicação em contrário.</p>
<h3>5. Uso aceitável</h3>
<p>Você concorda em não usar a plataforma para fins ilícitos nem de forma que possa danificá-la ou prejudicar o seu funcionamento.</p>
<h3>6. Contato</h3>
<p>Dúvidas sobre estes termos: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'zh' => ['title' => '服务条款', 'content' => '<h2>服务条款</h2>
<h3>1. 接受条款</h3>
<p>使用 ACADEXA 即表示您同意本条款以及适用的法律法规。</p>
<h3>2. 账户</h3>
<p>您有责任对登录信息保密，如发现账户被未经授权使用，须立即通知我们。</p>
<h3>3. 课程内容</h3>
<p>课程资料仅供教育用途。未经作者和 ZTF 大学学院书面许可，不得复制、传播或创作衍生作品。为离线学习而下载的内容同样受到保护。</p>
<h3>4. 证书</h3>
<p>成功完成课程后将颁发证书。证书证明您已完成该课程，除非另有说明，并不等同于正式学位。</p>
<h3>5. 合理使用</h3>
<p>您同意不将平台用于任何非法目的，也不以任何可能损害平台或妨碍其运行的方式使用平台。</p>
<h3>6. 联系我们</h3>
<p>关于本条款的问题：<a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
            'ar' => ['title' => 'شروط الخدمة', 'content' => '<h2>شروط الخدمة</h2>
<h3>1. القبول</h3>
<p>باستخدامك أكاديكسا، فإنك توافق على هذه الشروط وعلى القوانين واللوائح المعمول بها.</p>
<h3>2. الحسابات</h3>
<p>أنت مسؤول عن الحفاظ على سرية بيانات دخولك، ويجب عليك إبلاغنا فورًا بأي استخدام غير مصرح به لحسابك.</p>
<h3>3. محتوى الدورات</h3>
<p>تُقدَّم مواد الدورات لأغراض تعليمية. لا يجوز نسخها أو توزيعها أو إنشاء أعمال مشتقة منها دون إذن كتابي من مؤلفيها ومن معهد ZTF الجامعي. يظل المحتوى الذي يتم تنزيله للاستخدام دون اتصال محميًا.</p>
<h3>4. الشهادات</h3>
<p>تُمنح الشهادات عند إتمام الدورة بنجاح، وهي تثبت إتمام تلك الدورة ولا تُعد شهادة أكاديمية رسمية ما لم يُذكر خلاف ذلك.</p>
<h3>5. الاستخدام المقبول</h3>
<p>توافق على عدم استخدام المنصة لأغراض غير قانونية أو بأي طريقة قد تضر بها أو تعطل عملها.</p>
<h3>6. التواصل</h3>
<p>للأسئلة حول هذه الشروط: <a href="mailto:info@ztfuniversity.com">info@ztfuniversity.com</a></p>'],
        ],
    ];

    public function run(): void
    {
        foreach (self::PAGES as $slug => $translations) {
            $page = CmsPage::create(['slug' => $slug, 'is_active' => true]);
            foreach ($translations as $locale => $t) {
                $page->translations()->create(['locale' => $locale, 'title' => $t['title'], 'content' => $t['content']]);
            }
        }
    }
}
