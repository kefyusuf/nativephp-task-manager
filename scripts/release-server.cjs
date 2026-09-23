/**
 * DERS 7 — Yerel release sunucusu (Node.js).
 *
 * php -S süreçleri bu makinede kısa sürede sonlandırıldığı için sabit
 * dosya sunumu Node'a devredildi. Proje "type": "module" olduğu için
 * CommonJS sözdizimi kullanmak adına .cjs uzantısındadır.
 * Kullanım: node scripts/release-server.cjs <releases-klasoru>
 */
const http = require('http');
const fs = require('fs');
const path = require('path');

const safeRoot = path.resolve(process.argv[2] || __dirname);
const mime = {
    '.yml': 'text/yaml',
    '.exe': 'application/octet-stream',
    '.blockmap': 'application/octet-stream',
};

http.createServer((req, res) => {
    const urlPath = decodeURIComponent(req.url.split('?')[0]);
    const rel = urlPath === '/' ? 'latest.yml' : urlPath;

    // Path-traversal koruması: çözümlenen yol her zaman root içinde kalmalı.
    const target = path.resolve(safeRoot, '.' + path.posix.normalize('/' + rel));
    if (target !== safeRoot && !target.startsWith(safeRoot + path.sep)) {
        res.writeHead(403);
        res.end('forbidden');
        return;
    }

    fs.readFile(target, (err, data) => {
        if (err) {
            res.writeHead(404);
            res.end('not found');
            return;
        }
        res.writeHead(200, {
            'Content-Type': mime[path.extname(target).toLowerCase()] || 'application/octet-stream',
            'Content-Length': data.length,
        });
        res.end(data);
    });
}).listen(8090, '127.0.0.1', () => {
    console.log('release server: http://127.0.0.1:8090');
});
