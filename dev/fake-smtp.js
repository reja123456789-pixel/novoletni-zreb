// Lažni SMTP strežnik za lokalno testiranje: sprejme vse maile in jih shrani v dev/mails/*.eml
// Zagon: node dev/fake-smtp.js

const net = require('net');
const fs = require('fs');
const path = require('path');

const PORT = 2525;
const OUT = path.join(__dirname, 'mails');
fs.mkdirSync(OUT, { recursive: true });

net.createServer(sock => {
  let buf = '', inData = false, msg = '', rcpt = '';
  const say = s => sock.write(s + '\r\n');
  say('220 fake-smtp ready');

  sock.on('data', chunk => {
    buf += chunk.toString('utf8');
    let i;
    while ((i = buf.indexOf('\r\n')) >= 0) {
      const line = buf.slice(0, i);
      buf = buf.slice(i + 2);
      if (inData) {
        if (line === '.') {
          inData = false;
          const file = path.join(OUT, `${Date.now()}-${rcpt.replace(/[^a-z0-9@.]/gi, '_')}.eml`);
          fs.writeFileSync(file, msg);
          console.log(`📧 mail za ${rcpt} → ${path.basename(file)}`);
          msg = '';
          say('250 OK');
        } else {
          msg += line + '\r\n';
        }
        continue;
      }
      const cmd = line.slice(0, 4).toUpperCase();
      if (cmd === 'EHLO' || cmd === 'HELO') say('250 fake-smtp');
      else if (cmd === 'MAIL') say('250 OK');
      else if (cmd === 'RCPT') { rcpt = (line.match(/<(.*)>/) || [, ''])[1]; say('250 OK'); }
      else if (cmd === 'DATA') { inData = true; say('354 Go ahead'); }
      else if (cmd === 'QUIT') { say('221 Bye'); sock.end(); }
      else say('250 OK');
    }
  });
  sock.on('error', () => {});
}).listen(PORT, () => console.log(`Lažni SMTP posluša na portu ${PORT}, maili gredo v dev/mails/`));
