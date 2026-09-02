---
paths:
  - 'resources/views/pages/**'
---

# Pages

## Trim Passport token ids (CHAR(80) padding)
Passport oauth_access_tokens.id is a CHAR(80) column, so PostgreSQL pads stored ids with spaces (values read back are length 80). Never pass Eloquent `$token->id` straight into Livewire param matching or wire:click comparisons against the id re-read from the DB; always `trim($token->getKey())` when building arrays/params. DB equality still matches in where clauses (bpchar ignores trailing spaces).

## Template layout urutan halaman (index/form/detail)
Urutan wajib: 1) breadcrumbs (flux:breadcrumbs), 2) page-header flex justify-between berisi heading+subheading di kiri & action buttons (Tambah, Kembali, Export, dll. flux:button) di kanan, 3) card-stat opsional (angka statistik saja, flux:card grid), 4) card-filter opsional (flux:card berisi select/input + flux:button Terapkan + Reset, wire:model.live), 5) content (flux:table/card/list). Jangan tukar urutan; filter selalu di atas content, stat di atas filter. Gunakan flux & tailwind sesuai docs fluxui.dev.
