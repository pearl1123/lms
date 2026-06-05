/* DEV-only — injected when ?debug_certificate=1 (non-production) */
.cert-debug .cert-header-left,
.cert-debug .cert-header-right {
    outline: 0.3mm dashed rgba(37, 99, 235, 0.45);
    outline-offset: 1mm;
}
.cert-debug .cert-hero-inner {
    outline: 0.3mm dashed rgba(16, 185, 129, 0.45);
    outline-offset: 1mm;
}
.cert-debug .meta-grid {
    outline: 0.3mm dashed rgba(245, 158, 11, 0.45);
}
.cert-debug .cert-footer-left,
.cert-debug .cert-footer-right {
    outline: 0.3mm dashed rgba(139, 92, 246, 0.45);
    outline-offset: 1mm;
}
.cert-debug .brand-logo-cell {
    outline: 0.3mm solid rgba(37, 99, 235, 0.6);
}
.cert-debug .qr-cell {
    outline: 0.3mm solid rgba(37, 99, 235, 0.6);
}
.cert-debug .accent-track::after {
    content: 'TOP ACCENT';
    position: absolute;
    top: 5mm;
    left: 22mm;
    font-size: 5pt;
    color: #2563eb;
    letter-spacing: 0.1em;
}
.cert-debug .cert-hero-inner::before {
    content: 'HERO ZONE';
    display: block;
    font-size: 5pt;
    color: #10b981;
    letter-spacing: 0.12em;
    text-align: left;
    margin-bottom: 2mm;
}
.cert-debug .meta-grid::before {
    content: 'METADATA ROW';
    display: block;
    font-size: 5pt;
    color: #f59e0b;
    letter-spacing: 0.12em;
    margin-bottom: 2mm;
}
