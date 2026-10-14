#!/usr/bin/env node
// Menyalin isi resources/views/portal/partials/*.html ke setiap halaman portal
// (resources/views/portal/*.blade.php dan resources/views/errors/404.blade.php).
// Halaman menandai area dengan komentar, mis.:
//   <!-- @header --> ... <!-- /@header -->
// Penanda halaman aktif dibaca dari <body data-page="..." data-group="...">.
// Akhir baris setiap halaman (CRLF atau LF) dipertahankan, jadi halaman yang
// isinya sudah sama tidak dilaporkan maupun ditulis ulang.
//
// Pemakaian:  node scripts/sync-partials.mjs           (menulis perubahan)
//             node scripts/sync-partials.mjs --check   (hanya melaporkan)
import { readFileSync, writeFileSync, readdirSync } from "node:fs";
import { join, dirname, relative } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const views = join(root, "resources", "views");
const portal = join(views, "portal");
const check = process.argv.includes("--check");

const partials = Object.fromEntries(
  ["icons", "header", "footer"].map((name) => [name, readFileSync(join(portal, "partials", `${name}.html`), "utf8").replace(/\r\n/g, "\n").trim()])
);

const pages = [
  ...readdirSync(portal)
    .filter((f) => f.endsWith(".blade.php"))
    .map((f) => join(portal, f)),
  join(views, "errors", "404.blade.php"),
];
const changed = [];
const broken = [];

for (const path of pages) {
  const original = readFileSync(path, "utf8");
  const eol = original.includes("\r\n") ? "\r\n" : "\n";
  const src = original.replace(/\r\n/g, "\n");

  // Halaman tanpa penanda sengaja tidak memakai partial; penanda yang tidak
  // berpasangan atau ganda membuat area itu tidak tersinkron, jadi dilaporkan.
  const markerErrors = Object.keys(partials).flatMap((name) => {
    const open = src.split(`<!-- @${name} -->`).length - 1;
    const close = src.split(`<!-- /@${name} -->`).length - 1;
    return open === close && open <= 1 ? [] : [`@${name}: ${open} pembuka, ${close} penutup`];
  });
  if (markerErrors.length) {
    broken.push(`${relative(root, path)} (${markerErrors.join("; ")})`);
    continue;
  }

  const page = src.match(/<body[^>]*\sdata-page="([^"]+)"/)?.[1] ?? "";
  const group = src.match(/<body[^>]*\sdata-group="([^"]+)"/)?.[1] ?? "";

  let out = src;
  for (const [name, html] of Object.entries(partials)) {
    let body = html;
    if (name === "header") {
      if (page) body = body.replaceAll(`data-nav="${page}"`, `data-nav="${page}" aria-current="page"`);
      if (group) body = body.replace(`data-group="${group}"`, `data-group="${group}" data-current`);
    }
    const re = new RegExp(`(<!-- @${name} -->)[\\s\\S]*?(<!-- /@${name} -->)`, "g");
    // Fungsi pengganti: tanda $ di isi partial tidak dibaca sebagai pola
    out = out.replace(re, (_, buka, tutup) => `${buka}\n${body}\n${tutup}`);
  }
  if (out !== src) {
    changed.push(relative(root, path));
    if (!check) writeFileSync(path, out.replace(/\n/g, eol));
  }
}

const verb = check ? "akan diperbarui" : "diperbarui";
console.log(`partials: ${changed.length} dari ${pages.length} halaman ${verb}`);
for (const file of changed) console.log(`  ${file}`);
if (broken.length) {
  console.error(`penanda rusak di ${broken.length} halaman (dilewati):`);
  for (const file of broken) console.error(`  ${file}`);
  process.exitCode = 1;
}
