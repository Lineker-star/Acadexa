// "learn" group — new course wizard and presentation video. Merged into learn.cjs.
module.exports = {
    en: {
        wizard_help: 'Three short steps. The course is only created at the end; you will then add modules and lessons.',
        wizard_step_info: 'Information',
        wizard_step_category: 'Category and picture',
        wizard_step_video: 'Presentation video',
        thumbnail_generated_help: 'Without a picture, a cover is generated from the title.',
        intro_video: 'Presentation video',
        intro_video_help: 'Optional. Paste the link of a YouTube video (e.g. https://youtu.be/…): it plays on the course page, inside the platform.',
        create_course: 'Create the course',
    },
    fr: {
        wizard_help: 'Trois étapes rapides. Le cours n’est créé qu’à la fin ; vous ajouterez ensuite les modules et les leçons.',
        wizard_step_info: 'Informations',
        wizard_step_category: 'Catégorie et image',
        wizard_step_video: 'Vidéo de présentation',
        thumbnail_generated_help: 'Sans image, une couverture est générée à partir du titre.',
        intro_video: 'Vidéo de présentation',
        intro_video_help: 'Facultatif. Collez le lien d’une vidéo YouTube (ex. https://youtu.be/…) : elle est lue sur la page du cours, dans la plateforme.',
        create_course: 'Créer le cours',
    },
    es: {
        wizard_help: 'Tres pasos rápidos. El curso solo se crea al final; después añadirás los módulos y las lecciones.',
        wizard_step_info: 'Información',
        wizard_step_category: 'Categoría e imagen',
        wizard_step_video: 'Vídeo de presentación',
        thumbnail_generated_help: 'Sin imagen, se genera una portada a partir del título.',
        intro_video: 'Vídeo de presentación',
        intro_video_help: 'Opcional. Pega el enlace de un vídeo de YouTube (p. ej. https://youtu.be/…): se reproduce en la página del curso, dentro de la plataforma.',
        create_course: 'Crear el curso',
    },
    pt: {
        wizard_help: 'Três passos rápidos. O curso só é criado no fim; depois vai adicionar os módulos e as aulas.',
        wizard_step_info: 'Informações',
        wizard_step_category: 'Categoria e imagem',
        wizard_step_video: 'Vídeo de apresentação',
        thumbnail_generated_help: 'Sem imagem, é gerada uma capa a partir do título.',
        intro_video: 'Vídeo de apresentação',
        intro_video_help: 'Opcional. Cole a ligação de um vídeo do YouTube (ex. https://youtu.be/…): é reproduzido na página do curso, dentro da plataforma.',
        create_course: 'Criar o curso',
    },
    zh: {
        wizard_help: '三个简单步骤。课程在最后一步才会创建，之后再添加模块和课时。',
        wizard_step_info: '基本信息',
        wizard_step_category: '分类与图片',
        wizard_step_video: '介绍视频',
        thumbnail_generated_help: '未上传图片时，将根据标题自动生成封面。',
        intro_video: '介绍视频',
        intro_video_help: '可选。粘贴 YouTube 视频链接（如 https://youtu.be/…），视频将在平台内的课程页面播放。',
        create_course: '创建课程',
    },
    ar: {
        wizard_help: 'ثلاث خطوات سريعة. لا تُنشأ الدورة إلا في النهاية، ثم تضيف الوحدات والدروس.',
        wizard_step_info: 'المعلومات',
        wizard_step_category: 'الفئة والصورة',
        wizard_step_video: 'فيديو التعريف',
        thumbnail_generated_help: 'بدون صورة، يتم إنشاء غلاف انطلاقًا من العنوان.',
        intro_video: 'فيديو التعريف',
        intro_video_help: 'اختياري. الصق رابط فيديو على YouTube (مثل https://youtu.be/…): يُعرض في صفحة الدورة داخل المنصة.',
        create_course: 'إنشاء الدورة',
    },
};

// Presentation video upload and direct publication by an admin.
const extra = {
    "en": {
        "intro_source_youtube": "YouTube link",
        "intro_source_upload": "Upload a video (:max MB max.)",
        "intro_remove": "Remove this video",
        "intro_replace": "Drop another video here to replace it",
        "intro_video_optional": "Optional: a short video presenting the course, shown on its page. Paste a YouTube link or upload a file.",
        "not_submitted_help": "This course has not been submitted by its instructor (status: :status). As an administrator you can publish it directly.",
        "publish_missing": "Still missing for a complete course:",
        "publish_anyway_confirm": "Some elements are missing. Publish anyway?",
        "publish_now": "Publish now",
        "publish_confirm": "Publish this course? It will be visible on the courses page.",
        "admin": "admin",
        "preview_unpublished": "Preview — this course is not published (status: :status). Visitors cannot see it.",
        "js": {
            "video_ready": "Video uploaded",
            "upload_in_progress": "Please wait until the video upload is finished."
        }
    },
    "fr": {
        "intro_source_youtube": "Lien YouTube",
        "intro_source_upload": "Envoyer une vidéo (:max Mo max.)",
        "intro_remove": "Supprimer cette vidéo",
        "intro_replace": "Déposez une autre vidéo ici pour la remplacer",
        "intro_video_optional": "Facultatif : une courte vidéo qui présente le cours, affichée sur sa page. Collez un lien YouTube ou envoyez un fichier.",
        "not_submitted_help": "Ce cours n’a pas été soumis par son formateur (statut : :status). En tant qu’administrateur, vous pouvez le publier directement.",
        "publish_missing": "Il manque encore pour un cours complet :",
        "publish_anyway_confirm": "Des éléments manquent. Publier quand même ?",
        "publish_now": "Publier maintenant",
        "publish_confirm": "Publier ce cours ? Il sera visible sur la page des cours.",
        "admin": "admin",
        "preview_unpublished": "Aperçu — ce cours n’est pas publié (statut : :status). Les visiteurs ne le voient pas.",
        "js": {
            "video_ready": "Vidéo envoyée",
            "upload_in_progress": "Patientez jusqu’à la fin de l’envoi de la vidéo."
        }
    },
    "es": {
        "intro_source_youtube": "Enlace de YouTube",
        "intro_source_upload": "Subir un vídeo (máx. :max MB)",
        "intro_remove": "Eliminar este vídeo",
        "intro_replace": "Suelta otro vídeo aquí para reemplazarlo",
        "intro_video_optional": "Opcional: un vídeo corto que presenta el curso, mostrado en su página. Pega un enlace de YouTube o sube un archivo.",
        "not_submitted_help": "Su instructor no ha enviado este curso (estado: :status). Como administrador puedes publicarlo directamente.",
        "publish_missing": "Aún falta para un curso completo:",
        "publish_anyway_confirm": "Faltan elementos. ¿Publicar de todos modos?",
        "publish_now": "Publicar ahora",
        "publish_confirm": "¿Publicar este curso? Será visible en la página de cursos.",
        "admin": "admin",
        "preview_unpublished": "Vista previa: este curso no está publicado (estado: :status). Los visitantes no lo ven.",
        "js": {
            "video_ready": "Vídeo subido",
            "upload_in_progress": "Espera a que termine la subida del vídeo."
        }
    },
    "pt": {
        "intro_source_youtube": "Ligação do YouTube",
        "intro_source_upload": "Enviar um vídeo (máx. :max MB)",
        "intro_remove": "Remover este vídeo",
        "intro_replace": "Largue outro vídeo aqui para o substituir",
        "intro_video_optional": "Opcional: um vídeo curto que apresenta o curso, mostrado na sua página. Cole uma ligação do YouTube ou envie um ficheiro.",
        "not_submitted_help": "Este curso não foi submetido pelo formador (estado: :status). Como administrador, pode publicá-lo diretamente.",
        "publish_missing": "Ainda falta para um curso completo:",
        "publish_anyway_confirm": "Faltam elementos. Publicar mesmo assim?",
        "publish_now": "Publicar agora",
        "publish_confirm": "Publicar este curso? Ficará visível na página dos cursos.",
        "admin": "admin",
        "preview_unpublished": "Pré-visualização — este curso não está publicado (estado: :status). Os visitantes não o veem.",
        "js": {
            "video_ready": "Vídeo enviado",
            "upload_in_progress": "Aguarde até o envio do vídeo terminar."
        }
    },
    "zh": {
        "intro_source_youtube": "YouTube 链接",
        "intro_source_upload": "上传视频（最大 :max MB）",
        "intro_remove": "删除此视频",
        "intro_replace": "将另一个视频拖到此处以替换",
        "intro_video_optional": "可选：一段介绍课程的短视频，显示在课程页面上。粘贴 YouTube 链接或上传文件。",
        "not_submitted_help": "讲师尚未提交此课程（状态：:status）。作为管理员，你可以直接发布。",
        "publish_missing": "完整课程仍缺少：",
        "publish_anyway_confirm": "仍有内容缺失。仍要发布吗？",
        "publish_now": "立即发布",
        "publish_confirm": "发布此课程？它将显示在课程页面上。",
        "admin": "管理员",
        "preview_unpublished": "预览 — 此课程尚未发布（状态：:status），访客无法看到。",
        "js": {
            "video_ready": "视频已上传",
            "upload_in_progress": "请等待视频上传完成。"
        }
    },
    "ar": {
        "intro_source_youtube": "رابط YouTube",
        "intro_source_upload": "رفع فيديو (بحد أقصى :max ميغابايت)",
        "intro_remove": "حذف هذا الفيديو",
        "intro_replace": "أفلت فيديو آخر هنا لاستبداله",
        "intro_video_optional": "اختياري: فيديو قصير يعرّف بالدورة ويظهر في صفحتها. الصق رابط YouTube أو ارفع ملفًا.",
        "not_submitted_help": "لم يُرسل المدرّب هذه الدورة للمراجعة (الحالة: :status). بصفتك مشرفًا يمكنك نشرها مباشرة.",
        "publish_missing": "ما زال ينقص لدورة كاملة:",
        "publish_anyway_confirm": "هناك عناصر ناقصة. هل تريد النشر مع ذلك؟",
        "publish_now": "النشر الآن",
        "publish_confirm": "نشر هذه الدورة؟ ستظهر في صفحة الدورات.",
        "admin": "مشرف",
        "preview_unpublished": "معاينة — هذه الدورة غير منشورة (الحالة: :status). لا يراها الزوار.",
        "js": {
            "video_ready": "تم رفع الفيديو",
            "upload_in_progress": "انتظر حتى ينتهي رفع الفيديو."
        }
    }
};
for (const locale of Object.keys(module.exports)) {
    const { js, ...rest } = extra[locale];
    Object.assign(module.exports[locale], rest, { js });
}
