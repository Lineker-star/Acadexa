// Collects every translatable string used in the project.
//  - "JSON" strings: __('Some English text') -> resources/lang/en.json (key = English text)
//  - group keys:     __('lms.some_key')      -> must exist in resources/lang/{locale}/lms.php
// Usage:
//   node scripts/collect-translations.cjs            writes resources/lang/en.json, prints a summary
//   node scripts/collect-translations.cjs --check    exits 1 when a string is missing in any locale
const fs = require('fs');
const path = require('path');

const BASE = path.join(__dirname, '..');
const LANG = path.join(BASE, 'resources', 'lang');
const LOCALES = ['en', 'fr', 'es', 'pt', 'zh', 'ar'];
const SCAN = ['resources/views', 'app', 'routes'];
const GROUP_KEY = /^[a-z_]+\.[a-z0-9_]+(\.[a-z0-9_]+)*$/;

// Strings used by Laravel's own templates/notifications (mail, pagination, auth e-mails).
const FRAMEWORK = [
    'Hello!', 'Regards,', 'Whoops!', 'All rights reserved.', 'Showing', 'to', 'of', 'results',
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\ninto your web browser:",
    'Reset Password Notification', 'Reset Password', 'This password reset link will expire in :count minutes.',
    'If you did not request a password reset, no further action is required.',
    'You are receiving this email because we received a password reset request for your account.',
    'Verify Email Address', 'Please click the button below to verify your email address.',
    'If you did not create an account, no further action is required.',
];

function walk(dir) {
    if (!fs.existsSync(dir)) return [];
    return fs.readdirSync(dir, { withFileTypes: true }).flatMap(e =>
        e.isDirectory() ? walk(path.join(dir, e.name)) : [path.join(dir, e.name)]);
}

function unescapePhp(s, quote) {
    return quote === "'" ? s.replace(/\\(['\\])/g, '$1') : s.replace(/\\(["\\$])/g, '$1').replace(/\\n/g, '\n');
}

function collect() {
    const json = new Set(FRAMEWORK);
    const groups = new Set();
    const re = /(?:__|trans_choice|@lang|trans|Lang::get)\(\s*(['"])((?:(?!\1)[^\\]|\\.)*)\1/g;
    for (const dir of SCAN) {
        for (const file of walk(path.join(BASE, dir))) {
            if (!file.endsWith('.php')) continue;
            const src = fs.readFileSync(file, 'utf8');
            let m;
            while ((m = re.exec(src))) {
                const key = unescapePhp(m[2], m[1]);
                if (!key.trim() || /^[a-z_]+\.$/.test(key)) continue;
                if (GROUP_KEY.test(key)) { if (!key.endsWith('_')) groups.add(key); }
                else json.add(key);
            }
        }
    }
    return { json: [...json].sort(), groups: [...groups].sort() };
}

function groupValue(locale, key) {
    const [group, ...rest] = key.split('.');
    const file = path.join(LANG, locale, `${group}.php`);
    if (!fs.existsSync(file)) return undefined;
    // Cheap check without PHP: look for the last key segment defined in the file.
    const src = fs.readFileSync(file, 'utf8');
    const last = rest[rest.length - 1];
    return new RegExp(`['"]${last}['"]\\s*=>`).test(src) ? true : undefined;
}

const { json, groups } = collect();

if (process.argv.includes('--check')) {
    let missing = 0;
    for (const locale of LOCALES) {
        const file = path.join(LANG, `${locale}.json`);
        const data = fs.existsSync(file) ? JSON.parse(fs.readFileSync(file, 'utf8')) : {};
        const miss = json.filter(k => !(k in data));
        const gmiss = groups.filter(k => !groupValue(locale, k));
        if (miss.length || gmiss.length) {
            missing += miss.length + gmiss.length;
            console.log(`[${locale}] ${miss.length} JSON, ${gmiss.length} group keys missing`);
            miss.slice(0, 15).forEach(k => console.log('   json:', JSON.stringify(k)));
            gmiss.slice(0, 15).forEach(k => console.log('   group:', k));
        }
    }
    console.log(missing ? `MISSING: ${missing}` : 'All strings are translated in every language.');
    process.exit(missing ? 1 : 0);
}

const en = {};
json.forEach(k => { en[k] = k; });
fs.writeFileSync(path.join(LANG, 'en.json'), JSON.stringify(en, null, 4) + '\n');
console.log(`en.json: ${json.length} strings; group keys used: ${groups.length}`);
