<x-mail::message>
# Halo {{ $contactUs->firstname }},

Terima kasih telah menghubungi kami. Berikut tanggapan kami untuk pertanyaan Anda:

<x-mail::panel>
{!! $contactUs->reply_message !!}
</x-mail::panel>

Pesan asli Anda:
<x-mail::panel>
**Subjek:** {{ $contactUs->title }}

{{ $contactUs->message }}
</x-mail::panel>

Jika Anda memiliki pertanyaan lebih lanjut, jangan ragu untuk menghubungi kami lagi.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
