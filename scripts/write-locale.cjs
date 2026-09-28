// Writes a language's translation files from a data module.
// Usage: node scripts/write-locale.cjs <locale> <data-module.cjs>
// The module exports { groups: { auth: {...}, lms: {...}, ... }, json: { "English": "Translation" } }.
const fs = require('fs');
const path = require('path');

const [locale, dataFile] = process.argv.slice(2);
const data = require(path.resolve(dataFile));
const LANG = path.join(__dirname, '..', 'resources', 'lang');

const str = s => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";

function phpValue(value, indent) {
    if (Array.isArray(value)) return '[' + value.map(str).join(', ') + ']';
    if (value && typeof value === 'object') {
        const pad = '    '.repeat(indent + 1);
        const lines = Object.entries(value).map(([k, v]) => `${pad}'${k}' => ${phpValue(v, indent + 1)},`);
        return '[\n' + lines.join('\n') + '\n' + '    '.repeat(indent) + ']';
    }
    return str(value);
}

fs.mkdirSync(path.join(LANG, locale), { recursive: true });
for (const [group, entries] of Object.entries(data.groups || {})) {
    const body = `<?php\n\n// Generated from the translation sources — keep keys identical to resources/lang/en/${group}.php.\nreturn ${phpValue(entries, 0)};\n`;
    fs.writeFileSync(path.join(LANG, locale, `${group}.php`), body);
}
if (data.json) {
    // Keep exactly the strings the application uses (resources/lang/en.json), in the same order.
    const english = JSON.parse(fs.readFileSync(path.join(LANG, 'en.json'), 'utf8'));
    const missing = Object.keys(english).filter(k => !(k in data.json));
    if (missing.length) {
        console.error(`${locale}: ${missing.length} JSON string(s) missing:\n` + missing.map(k => '  ' + JSON.stringify(k)).join('\n'));
        process.exitCode = 1;
    }
    const out = {};
    for (const key of Object.keys(english)) if (key in data.json) out[key] = data.json[key];
    data.json = out;
    fs.writeFileSync(path.join(LANG, `${locale}.json`), JSON.stringify(out, null, 4) + '\n');
}
console.log(`${locale}: ${Object.keys(data.groups || {}).length} group files, ${Object.keys(data.json || {}).length} JSON strings`);
