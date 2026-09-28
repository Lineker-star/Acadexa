// Chinese (Simplified) translation sources. Build: node scripts/write-locale.cjs zh resources/lang-src/zh.cjs
const base = require('./zh-base.cjs');
const extra = require('./zh-extra.cjs');
module.exports = {
    groups: { ...base, lms: require('./zh-lms.cjs'), ...extra },
    json: require('./zh-json.cjs'),
};
