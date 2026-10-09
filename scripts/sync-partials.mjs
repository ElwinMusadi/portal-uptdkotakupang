#!/usr/bin/env node
// Menyalin isi partials/*.html ke setiap halaman di root repo.
// Halaman menandai area dengan komentar, mis.:
//   <!-- @header --> ... <!-- /@header -->
// Penanda halaman aktif dibaca dari <body data-page="..." data-group="...">.
//
// Pemakaian:  node scripts/sync-partials.mjs
import { readFileSync, writeFileSync, readdirSync } from "node:fs";
import { join, dirname } from "node:path";
import { fileURLToPath } from "node:url";

const root = join(dirname(fileURLToPath(import.meta.url)), "..");
const partials = Object.fromEntries(
  ["icons", "header", "footer"].map((name) => [name, readFileSync(join(root, "partials", `${name}.html`), "utf8").trim()])
);

const pages = readdirSync(root).filter((f) => f.endsWith(".html"));
let changed = 0;

for (const file of pages) {
  const path = join(root, file);
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
    writeFileSync(path, out);
    changed++;
  }
}
console.log(`partials disinkronkan: ${changed} dari ${pages.length} halaman diperbarui`);
