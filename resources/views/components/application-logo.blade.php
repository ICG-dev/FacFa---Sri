<svg viewBox="0 0 256 256" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
  <defs>
    <linearGradient id="logoBgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#0a1220" />
      <stop offset="50%" stop-color="#0f1b2d" />
      <stop offset="100%" stop-color="#1a3a6c" />
    </linearGradient>
    <linearGradient id="logoDocGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#ffffff" />
      <stop offset="100%" stop-color="#e8eef8" />
    </linearGradient>
    <linearGradient id="logoFoldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#cbd5e1" />
      <stop offset="100%" stop-color="#94a3b8" />
    </linearGradient>
    <linearGradient id="logoGoldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#fde047" />
      <stop offset="45%" stop-color="#f59e0b" />
      <stop offset="100%" stop-color="#d97706" />
    </linearGradient>
    <filter id="logoShadow" x="-10%" y="-10%" width="130%" height="130%">
      <feDropShadow dx="0" dy="10" stdDeviation="8" flood-color="#000000" flood-opacity="0.35" />
    </filter>
    <filter id="logoSealShadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="6" stdDeviation="6" flood-color="#f59e0b" flood-opacity="0.35" />
    </filter>
  </defs>

  <!-- Squircle Base -->
  <rect x="14" y="14" width="228" height="228" rx="54" fill="url(#logoBgGrad)" />
  <rect x="14" y="14" width="228" height="228" rx="54" fill="none" stroke="rgba(255, 255, 255, 0.15)" stroke-width="2" />

  <!-- Ambient Glow -->
  <circle cx="174" cy="172" r="50" fill="#f59e0b" opacity="0.15" />
  <circle cx="90" cy="74" r="40" fill="#38bdf8" opacity="0.12" />

  <!-- Factura Body -->
  <g filter="url(#logoShadow)">
    <path d="M 62 46 H 142 L 174 78 V 186 A 14 14 0 0 1 160 200 H 62 A 14 14 0 0 1 48 186 V 60 A 14 14 0 0 1 62 46 Z" fill="url(#logoDocGrad)" />
    <path d="M 142 46 V 70 A 8 8 0 0 0 150 78 H 174 Z" fill="url(#logoFoldGrad)" />
    <path d="M 142 46 L 174 78" stroke="rgba(0, 0, 0, 0.08)" stroke-width="1.5" />

    <!-- SRI Barcode lines -->
    <g transform="translate(64, 62)">
      <rect x="0" y="0" width="4" height="18" rx="2" fill="#1a3a6c" />
      <rect x="7" y="0" width="2.5" height="18" rx="1.2" fill="#1a3a6c" />
      <rect x="13" y="0" width="6" height="18" rx="2" fill="#1a3a6c" />
      <rect x="22" y="0" width="2.5" height="18" rx="1.2" fill="#1a3a6c" />
      <rect x="28" y="0" width="4.5" height="18" rx="2" fill="#1a3a6c" />
      <rect x="36" y="0" width="2" height="18" rx="1" fill="#1a3a6c" />
      <rect x="41" y="0" width="5.5" height="18" rx="2" fill="#1a3a6c" />
      <rect x="50" y="0" width="3" height="18" rx="1.5" fill="#1a3a6c" />
      <rect x="56" y="0" width="4.5" height="18" rx="2" fill="#1a3a6c" />
    </g>

    <rect x="64" y="94" width="88" height="6.5" rx="3.2" fill="#94a3b8" />
    <rect x="64" y="108" width="66" height="5.5" rx="2.7" fill="#cbd5e1" />
    <rect x="64" y="121" width="76" height="5.5" rx="2.7" fill="#cbd5e1" />
    <line x1="64" y1="139" x2="132" y2="139" stroke="#94a3b8" stroke-width="1.5" stroke-dasharray="3 3" opacity="0.6" />
    <rect x="64" y="148" width="38" height="7" rx="3.5" fill="#1a3a6c" />
    <rect x="110" y="148" width="22" height="7" rx="3.5" fill="#2563eb" />
  </g>

  <!-- Golden SRI Seal -->
  <g filter="url(#logoSealShadow)">
    <circle cx="174" cy="172" r="39" fill="url(#logoGoldGrad)" />
    <circle cx="174" cy="172" r="32" fill="#0f1b2d" />
    <circle cx="174" cy="172" r="27" fill="none" stroke="url(#logoGoldGrad)" stroke-width="2" stroke-dasharray="6 3" />
    <path d="M 158 172 L 169 183 L 192 160" fill="none" stroke="url(#logoGoldGrad)" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round" />
  </g>

  <!-- Cryptographic Sparks -->
  <path d="M 204 122 Q 204 132 214 132 Q 204 132 204 142 Q 204 132 194 132 Q 204 132 204 122 Z" fill="#fde047" opacity="0.95" />
  <path d="M 52 40 Q 52 46 58 46 Q 52 46 52 52 Q 52 46 46 46 Q 52 46 52 40 Z" fill="#38bdf8" opacity="0.8" />
</svg>