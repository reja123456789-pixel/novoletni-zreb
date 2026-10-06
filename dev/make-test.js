// Iz glavne igre ustvari testno verzijo brez MP3-ja (ena sama HTML datoteka, deluje z dvoklikom).
// Zagon: node dev/make-test.js

const fs = require('fs');
const path = require('path');

const src = path.join(__dirname, '..', 'htdocs', 'igra', 'index.html');
const out = path.join(__dirname, '..', 'minigame', 'glava-hero-test.html');

let html = fs.readFileSync(src, 'utf8');
const flag = 'const TEST_VERSION = false;';
if (!html.includes(flag)) throw new Error('Ne najdem zastavice TEST_VERSION v htdocs/igra/index.html');
html = html.replace(flag, 'const TEST_VERSION = true;');
html = html.replace('<title>Glava Hero</title>', '<title>Glava Hero (test)</title>');

fs.writeFileSync(out, html);
console.log('Ustvarjeno:', path.relative(process.cwd(), out));
