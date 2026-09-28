// Writes one translation group for every language from a single source module.
// Usage: node scripts/write-group.cjs <group> <source.cjs>
// The module exports { en: {...}, fr: {...}, es: {...}, ... } with identical keys.
const fs = require('fs');
const path = require('path');

const [group, dataFile] = process.argv.slice(2);
const data = require(path.resolve(dataFile));
const LANG = path.join(__dirname, '..', 'resources', 'lang');

const str = s => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
function phpValue(value, indent) {
    if (value && typeof value === 'object') {
        const pad = '    '.repeat(indent + 1);
        const lines = Object.entries(value).map(([k, v]) => `${pad}'${k}' => ${phpValue(v, indent + 1)},`);
        return '[\n' + lines.join('\n') + '\n' + '    '.repeat(indent) + ']';
    }
    return str(value);
}
const keysOf = (obj, prefix = '') => Object.entries(obj).flatMap(([k, v]) =>
    v && typeof v === 'object' ? keysOf(v, `${prefix}${k}.`) : [`${prefix}${k}`]);

const reference = keysOf(data.en);
let failed = false;
for (const [locale, entries] of Object.entries(data)) {
    const keys = keysOf(entries);
    const missing = reference.filter(k => !keys.includes(k));
    const extra = keys.filter(k => !reference.includes(k));
    if (missing.length || extra.length) {
        console.error(`${locale}: missing [${missing.join(', ')}] extra [${extra.join(', ')}]`);
        failed = true;
        continue;
    }
    fs.mkdirSync(path.join(LANG, locale), { recursive: true });
    const body = `<?php\n\n// Generated from resources/lang-src/${path.basename(dataFile)} — edit the source, then run:\n// node scripts/write-group.cjs ${group} resources/lang-src/${path.basename(dataFile)}\nreturn ${phpValue(entries, 0)};\n`;
    fs.writeFileSync(path.join(LANG, locale, `${group}.php`), body);
    console.log(`${locale}: ${keys.length} keys`);
}
process.exitCode = failed ? 1 : 0;
