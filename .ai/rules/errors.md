---
paths:
  - 'app/**'
  - 'resources/views/pages/**'
---

# Error Handling & Notification

## Pisahkan user-facing message dari logger entry
Setiap exception WAJIB menghasilkan dua output berbeda: 1) pesan humanized untuk UI (alert/toast/addError), singkat & actionable; 2) entry `Log::` teknis terstruktur untuk debugging. Jangan pernah tampilkan `$e->getMessage()` langsung ke UI.

## Logger: context array terstruktur, bahasa Inggris teknis
Selalu `Log::error()`/`Log::warning()` dengan context array (jangan interpolasi variabel ke string). Sertakan bila relevan: `exception` ($e->getMessage()), `trace` (hanya level error, bukan info), `user_id` (auth()->id()), `team_id`, `request_id`/`correlation_id`, `input` (payload relevan). Pesan log singkat deskriptif di awal, detail di context.

## Business error: custom exception + userMessage(), log sebagai info
Kondisi bisnis yang memang bisa terjadi (bukan bug) WAJIB dijelaskan lengkap: apa + kenapa + langkah user. Implementasikan sebagai custom exception per kondisi yang mengimplementasikan `HasUserFacingMessage::userMessage()` (string via file lang, mis. `__('errors.client_has_active_tokens')`), contoh `ClientStillHasActiveTokensException extends \DomainException`. Di catch: `Log::info('Business rule prevented action', [...])` lalu `$this->addError('form', $e->userMessage())`.

## Unexpected error: pesan generik + reference ID yang sama dengan log
Detail teknis tidak actionable buat user dan berisiko info disclosure (OWASP ASVS). Pesan generik WAJIB disertai reference ID (`Str::uuid()`): `"Terjadi kesalahan pada sistem (kode referensi: xxx). ... sertakan kode referensi ini saat menghubungi admin."` Catat ID yang sama di `Log::error('Unexpected error: '.$ref, ['reference_id' => $ref, 'exception' => ..., 'trace' => ..., ...])` supaya admin tinggal grep.

## Validasi field tetap spesifik per-field
Aturan humanize/generik berlaku untuk unexpected error saja; validation error HARUS tetap spesifik per field, bukan disamaratakan.

## Larangan: redact field sensitif
JANGAN log mentah: password, `client_secret` Passport, token OAuth, nomor identitas — mask/redact (`***`) sebelum masuk context. JANGAN gabungkan `trace` dengan level `info`.

## Pola wajib di Livewire (API/job/listener menyesuaikan channel)
```php
try {
    // aksi yang berisiko gagal
} catch (HasUserFacingMessage $e) {
    Log::info('Business rule prevented action', ['exception' => get_class($e), 'user_id' => auth()->id(), 'team_id' => $team->id ?? null]);
    $this->addError('form', $e->userMessage());
    return;
} catch (\Throwable $e) {
    $referenceId = (string) \Illuminate\Support\Str::uuid();
    Log::error('Unexpected error: '.$referenceId, ['reference_id' => $referenceId, 'exception' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'user_id' => auth()->id(), 'team_id' => $team->id ?? null]);
    $this->addError('form', __('errors.generic_action_failed', ['ref' => $referenceId]));
    return;
}
```
Di luar Livewire pola sama, notifikasi lewat channel sesuai (JSON terstruktur untuk API, notification/mail untuk job async); logger tetap wajib context lengkap.
