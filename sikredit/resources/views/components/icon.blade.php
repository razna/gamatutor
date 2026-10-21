@props(['name'])
@php
$paths = [
    'home' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10',
    'inbox' => 'M4 4h16v12h-5l-2 3h-2l-2-3H4zM4 16v4h16v-4',
    'wallet' => 'M3 7h18v12H3zM3 7l3-3h12l3 3M16 13h2',
    'alert' => 'M12 9v4m0 4h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z',
    'chart' => 'M4 20V10M10 20V4M16 20v-8M22 20H2',
    'chat' => 'M21 12a8 8 0 01-11.6 7.1L3 21l1.9-6.4A8 8 0 1121 12z',
    'user' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
    'bell' => 'M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0a3 3 0 01-6 0',
    'cog' => 'M12 15a3 3 0 100-6 3 3 0 000 6zM19.4 15a1.7 1.7 0 00.3 1.8l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-2.9 1.2V21a2 2 0 11-4 0v-.1a1.7 1.7 0 00-2.9-1.2l-.1.1a2 2 0 11-2.8-2.8l.1-.1A1.7 1.7 0 003 15H3a2 2 0 110-4h.1a1.7 1.7 0 001.2-2.9l-.1-.1a2 2 0 112.8-2.8l.1.1A1.7 1.7 0 009 4.6V3a2 2 0 114 0v.1a1.7 1.7 0 002.9 1.2l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 001.2 2.9H21a2 2 0 110 4h-.1a1.7 1.7 0 00-1.5 1z',
    'doc' => 'M7 3h7l5 5v13H7zM14 3v5h5',
    'check' => 'M5 13l4 4L19 7',
    'clock' => 'M12 6v6l4 2M12 22a10 10 0 110-20 10 10 0 010 20z',
    'print' => 'M6 9V3h12v6M6 18H4v-7h16v7h-2M8 14h8v7H8z',
    'download' => 'M12 3v12m0 0l-4-4m4 4l4-4M4 21h16',
    'plus' => 'M12 5v14M5 12h14',
    'shield' => 'M12 3l8 4v5c0 5-3.5 8.5-8 9-4.5-.5-8-4-8-9V7z',
    'bolt' => 'M13 2L3 14h8l-1 8 10-12h-8z',
    'calc' => 'M5 3h14v18H5zM8 7h8M8 12h2M14 12h2M8 16h2M14 16h2',
];
@endphp
<svg {{ $attributes }} fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $paths[$name] ?? $paths['doc'] }}"/></svg>
