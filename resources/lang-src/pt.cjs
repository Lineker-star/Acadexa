// Portuguese translation sources. Build: node scripts/write-locale.cjs pt resources/lang-src/pt.cjs
const base = require('./pt-base.cjs');
const extra = require('./pt-extra.cjs');
module.exports = {
    groups: { ...base, lms: require('./pt-lms.cjs'), ...extra },
    json: require('./pt-json.cjs'),
};
