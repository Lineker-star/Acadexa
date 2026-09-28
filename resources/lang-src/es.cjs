// Spanish translation sources. Build: node scripts/write-locale.cjs es resources/lang-src/es.cjs
const base = require('./es-base.cjs');
const extra = require('./es-extra.cjs');
module.exports = {
    groups: { ...base, lms: require('./es-lms.cjs'), ...extra },
    json: require('./es-json.cjs'),
};
