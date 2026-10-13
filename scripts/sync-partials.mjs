#!/usr/bin/env node
// Menyalin isi resources/views/portal/partials/*.html ke setiap halaman portal
// (resources/views/portal/*.blade.php dan resources/views/errors/404.blade.php).
// Halaman menandai area dengan komentar, mis.:
//   <!-- @header --> ... <!-- /@header -->
// Penanda halaman aktif dibaca dari <body data-page="..." data-group="...">.
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
  ["icons", "header", "footer"].map((name) => [name, readFileSync(join(portal, "partials", `${name}.html`), "utf8").trim()])
);

const pages = [
  ...readdirSync(portal)
    .filter((f) => f.endsWith(".blade.php"))
    .map((f) => join(portal, f)),
  join(views, "errors", "404.blade.php"),
];
const changed = [];

for (const path of pages) {
  const src = readFileSync(path, "utf8");
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
    out = out.replace(re, `$1\n${body}\n$2`);
  }
  if (out !== src) {
    changed.push(relative(root, path));
    if (!check) writeFileSync(path, out);
  }
}

const verb = check ? "akan diperbarui" : "diperbarui";
console.log(`partials: ${changed.length} dari ${pages.length} halaman ${verb}`);
for (const file of changed) console.log(`  ${file}`);
