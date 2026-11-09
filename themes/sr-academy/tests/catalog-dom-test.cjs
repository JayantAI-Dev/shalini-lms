const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {JSDOM} = require(process.env.SR_LMS_JSDOM_PATH || 'jsdom');
const html = '<div class="lms-shell">' + fs.readFileSync(path.resolve(__dirname, '../templates/page-course.php'), 'utf8').replace(/<\?php[\s\S]*?\?>/g, '') + '</div>';
const source = fs.readFileSync(path.resolve(__dirname, '../assets/js/catalog.js'), 'utf8');
let assertions = 0;
for (const slug of ['speakathon', 'power-dressing', 'dining-etiquette']) {
  for (const modules of [[], [{title: 'Empty module', lessons: []}], [{title: 'One lesson', lessons: [{id:'one',title:'Edited lesson',summary:'Edited summary'}]}]]) {
    const dom = new JSDOM(html, {url: 'http://lms.local/course/?course=' + slug, runScripts: 'outside-only'});
    const {window} = dom;
    const course = {title: 'Administrator edited <title>', description: 'Edited description', price: 100, modules, includes: []};
    window.SHALINI_COURSES = {[slug]: course};
    window.SR_THEME = {assetBase: 'http://lms.local/theme/', home: 'http://lms.local/', asset: p => 'http://lms.local/theme/' + p, url: p => 'http://lms.local/' + p};
    assert.doesNotThrow(() => window.eval(source), slug + ' renders a catalog with ' + modules.length + ' modules');
    assert.equal(window.document.getElementById('lms-detail-title').textContent, course.title, slug + ' preserves administrator title');
    assert.equal(window.document.querySelectorAll('.lms-lesson-item').length, modules.flatMap(m => m.lessons).length);
    assertions += 3;
    dom.window.close();
  }
}
console.log(`PASS: ${assertions} course catalog DOM assertions`);
