// "learn" group — course content protection (blur, capture warning). Merged into learn.cjs.
const text = {
    en: ['Fullscreen', 'Content hidden while the window is not active. Come back to the page to continue.', 'Screen captures of the courses are not allowed.'],
    fr: ['Plein écran', 'Contenu masqué tant que la fenêtre n’est pas active. Revenez sur la page pour continuer.', 'Les captures d’écran des cours sont interdites.'],
    es: ['Pantalla completa', 'Contenido oculto mientras la ventana no está activa. Vuelve a la página para continuar.', 'Las capturas de pantalla de los cursos no están permitidas.'],
    pt: ['Ecrã inteiro', 'Conteúdo oculto enquanto a janela não está ativa. Volte à página para continuar.', 'As capturas de ecrã dos cursos não são permitidas.'],
    zh: ['全屏', '窗口未处于活动状态时内容已隐藏。回到页面即可继续。', '禁止对课程进行截屏。'],
    ar: ['ملء الشاشة', 'المحتوى مخفي ما دامت النافذة غير نشطة. عُد إلى الصفحة للمتابعة.', 'لا يُسمح بتصوير شاشة الدورات.'],
};
module.exports = Object.fromEntries(Object.entries(text).map(([locale, [fullscreen, hidden, blocked]]) => [locale, {
    fullscreen,
    js: { fullscreen, content_hidden: hidden, capture_blocked: blocked },
}]));
