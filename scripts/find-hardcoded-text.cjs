// Lists user-visible text written directly in Blade templates (not wrapped in __()).
// Usage: node scripts/find-hardcoded-text.cjs [--json]
// Also used by tests/Feature/TranslationCompletenessTest.php, which fails if anything is found.
const fs = require('fs');
const path = require('path');

const BASE = path.join(__dirname, '..');
const ROOT = path.join(BASE, 'resources', 'views');
// Text that does not depend on the language (brand names, units, acronyms).
const ALLOW = [/^ACADE?X+A\.?$/i, /^ZTF(-UI)?$/, /^FCFA$/, /^PDF$/, /^CSV$/, /^2FA$/, /^OK$/, /^ACADE$/, /^XA$/,
    /^(www\.)?[a-z0-9-]+(\.[a-z0-9-]+)+$/i, // domain names
    /^\/[a-z0-9\/-]*$/i,                   // URL paths
];
const ATTRS = ['placeholder', 'title', 'alt', 'aria-label', 'data-confirm', 'label'];
const SKIP = ['resources/views/pwa/', 'resources/views/vendor/', 'resources/views/sitemap.blade.php'];

function walk(dir) {
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap(e =>
        e.isDirectory() ? walk(path.join(dir, e.name)) : (e.name.endsWith('.blade.php') ? [path.join(dir, e.name)] : []));
}

const blank = m => m.replace(/[^\n]/g, ' ');

function strip(src) {
    return src
        .replace(/\{\{--[\s\S]*?--\}\}/g, blank)
        .replace(/@php[\s\S]*?@endphp/g, blank)
        .replace(/<style[\s\S]*?<\/style>/gi, blank)
        .replace(/<script\b[\s\S]*?<\/script>/gi, blank)
        .replace(/\{!![\s\S]*?!!\}/g, blank)
        .replace(/\{\{[\s\S]*?\}\}/g, blank)
        // @directive(...) with up to 5 levels of nested parentheses
        .replace(/@[a-zA-Z]+\s*(\((?:[^()]|\((?:[^()]|\((?:[^()]|\((?:[^()]|\([^()]*\))*\))*\))*\))*\))?/g, blank)
        .replace(/<\?php[\s\S]*?\?>/g, blank)
        // Blade components: <x-icon ... />
        .replace(/<x-[\s\S]*?\/?>/g, blank);
}

function lineOf(src, index) {
    return src.slice(0, index).split('\n').length;
}

function meaningful(text) {
    const t = text.replace(/&[a-z#0-9]+;/gi, ' ').replace(/\s+/g, ' ').trim();
    if (!t || !/\p{L}{2,}/u.test(t)) return null;
    if (ALLOW.some(re => re.test(t))) return null;
    return t;
}

function scan() {
    const results = [];
    for (const file of walk(ROOT)) {
        const rel = path.relative(BASE, file).split(path.sep).join('/');
        if (SKIP.some(s => rel.startsWith(s))) continue;
        const raw = fs.readFileSync(file, 'utf8');
        const src = strip(raw);

        let m;
        const textRe = />([^<>]+)</g;
        while ((m = textRe.exec(src))) {
            const t = meaningful(m[1]);
            if (t) results.push({ file: rel, line: lineOf(src, m.index), text: t });
        }
        const attrRe = new RegExp(`\\s(${ATTRS.join('|')})="([^"]*)"`, 'g');
        while ((m = attrRe.exec(src))) {
            const t = meaningful(m[2]);
            if (t) results.push({ file: rel, line: lineOf(src, m.index), text: `[${m[1]}] ${t}` });
        }
        const confirmRe = /confirm\('([^']+)'\)/g;
        while ((m = confirmRe.exec(raw))) {
            results.push({ file: rel, line: lineOf(raw, m.index), text: `[confirm] ${m[1]}` });
        }
    }
    return results;
}

const results = scan();
if (process.argv.includes('--json')) {
    process.stdout.write(JSON.stringify(results, null, 1));
} else if (process.argv.includes('--list')) {
    results.forEach(r => console.log(`${r.file}:${r.line}  ${r.text}`));
} else {
    const byFile = {};
    results.forEach(r => (byFile[r.file] ||= []).push(r));
    for (const [f, rows] of Object.entries(byFile)) console.log(String(rows.length).padStart(4), f);
    console.log('TOTAL', results.length, 'in', Object.keys(byFile).length, 'files');
}
