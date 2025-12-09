@component('mail::message')
# {{ $subjectText }}

{{ $messageText }}

---

**Ticket ID:** {{ $ticket->code }}  
**Judul:** {{ $ticket->title ?? 'Tidak ada judul' }}  
**Status:** {{ ucfirst($ticket->status) ?? '-' }}  
**Reporter:** {{ $ticket->reporter->name ?? '-' }}

@component('mail::button', ['url' => config('app.url') . '/tickets/' . $ticket->id])
Lihat Detail Tiket
@endcomponent

Terima kasih,  
{{ config('app.name') }}
@endcomponent
