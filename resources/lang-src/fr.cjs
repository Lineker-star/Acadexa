// French translation sources (group files are edited directly in resources/lang/fr/).
// Build: node scripts/write-locale.cjs fr resources/lang-src/fr.cjs
module.exports = {
    groups: {},
    json: {
        ...require('./fr-json.json'),
        'Optional: leave empty to show the English version in this language.': 'Facultatif : laissez vide pour afficher la version anglaise dans cette langue.',
    },
};
