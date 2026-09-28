// Arabic translation sources. Build: node scripts/write-locale.cjs ar resources/lang-src/ar.cjs
const base = require('./ar-base.cjs');
const extra = require('./ar-extra.cjs');
module.exports = {
    groups: { ...base, lms: require('./ar-lms.cjs'), ...extra },
    json: require('./ar-json.cjs'),
};
