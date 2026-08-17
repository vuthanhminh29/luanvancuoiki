@once
@push('styles')
<style>
.report-page{background:#f4f7fb;color:#111827;margin:-24px -24px 0;min-height:100vh;padding:22px 24px 70px}
.report-inner{max-width:1500px;margin:0 auto}
.report-head{align-items:flex-start;display:flex;gap:16px;justify-content:space-between;margin-bottom:16px}
.report-kicker{color:#0f766e;font-size:13px;font-weight:900;margin-bottom:6px;text-transform:uppercase}
.report-title{color:#111827;font-size:28px;font-weight:900;line-height:1.18;margin:0}
.report-subtitle{color:#667085;font-size:14px;line-height:1.5;margin:8px 0 0;max-width:700px}
.report-actions{display:flex;flex-wrap:wrap;gap:9px;justify-content:flex-end}
.report-btn{align-items:center;background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;display:inline-flex;font-size:13px;font-weight:900;gap:8px;justify-content:center;min-height:38px;padding:0 13px;text-decoration:none;white-space:nowrap}
.report-btn:hover{filter:brightness(.98);color:#111827;text-decoration:none}
.report-btn.active{background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8}
.report-btn.primary,.report-btn.primary:hover{background:#0f766e;border-color:#0f766e;color:#fff}
.report-card{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);overflow:hidden}
.report-grid{display:grid;gap:12px;grid-template-columns:repeat(4,minmax(0,1fr));margin-bottom:14px}
.report-grid .report-card{min-height:112px;padding:15px}
.report-metric-top{align-items:flex-start;display:flex;gap:12px;justify-content:space-between;margin-bottom:12px}
.report-metric-label{color:#667085;font-size:12px;font-weight:900;line-height:1.35;margin:0;text-transform:uppercase}
.report-metric-icon{align-items:center;border-radius:8px;display:inline-flex;flex:0 0 36px;height:36px;justify-content:center;width:36px}
.report-grid .report-card:nth-child(1) .report-metric-icon{background:#ecfeff;color:#0e7490}
.report-grid .report-card:nth-child(2) .report-metric-icon{background:#eff6ff;color:#1d4ed8}
.report-grid .report-card:nth-child(3) .report-metric-icon{background:#ecfdf3;color:#067647}
.report-grid .report-card:nth-child(4) .report-metric-icon{background:#fff7ed;color:#c2410c}
.report-metric-value{color:#101828;font-size:26px;font-weight:900;line-height:1;margin-bottom:8px}
.report-metric-note{color:#667085;font-size:13px;line-height:1.45;margin:0}
.report-toolbar{background:#fff;border:1px solid #e4e7ec;border-radius:8px;box-shadow:0 8px 24px rgba(16,24,40,.04);margin-bottom:14px;overflow:hidden}
.report-filter-main{align-items:center;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}
.report-period{align-items:center;display:flex;flex-wrap:wrap;gap:9px}
.report-period span{color:#667085;font-size:12px;font-weight:900;text-transform:uppercase}
.report-period span i{color:#0f766e;margin-right:6px}
.report-period strong{color:#111827;font-size:14px;font-weight:900}
.report-data-note{color:#667085;font-size:12px;font-weight:800;margin:0;text-align:right}
.report-date-form{align-items:end;display:grid;gap:10px;grid-template-columns:minmax(160px,1fr) minmax(160px,1fr) auto auto;padding:14px}
.report-date-form label{color:#667085;display:grid;font-size:11px;font-weight:900;gap:6px;margin:0;text-transform:uppercase}
.report-date-form input[type="date"]{background:#fff;border:1px solid #d0d5dd;border-radius:7px;color:#111827;font-size:13px;font-weight:800;min-height:38px;padding:8px 10px;width:100%}
.report-date-form input[type="date"]:focus{border-color:#0f766e;box-shadow:0 0 0 3px rgba(15,118,110,.12);outline:none}
.report-shortcuts{display:flex;flex-wrap:wrap;gap:8px;grid-column:1/-1}
.report-shortcuts .report-btn{min-height:34px;padding:0 11px}
.report-section{margin-bottom:16px}
.report-section-head{align-items:center;background:#fbfcfd;border-bottom:1px solid #eef2f6;display:flex;gap:12px;justify-content:space-between;padding:13px 14px}
.report-section-title{color:#111827;font-size:16px;font-weight:900;margin:0}
.report-section-note{color:#667085;font-size:12px;font-weight:800;margin:0;text-align:right}
.report-section>.report-bars,.report-section>.report-chart,.report-section>.report-empty,.report-section>.report-table-shell{margin:14px}
.report-table-shell{border:1px solid #e4e7ec;border-radius:8px;max-height:560px;overflow:auto}
.report-table{margin:0;min-width:820px;table-layout:fixed;width:100%}
.report-table th{background:#f8fafc;border-top:0;color:#667085;font-size:12px;font-weight:900;position:sticky;text-transform:uppercase;top:0;white-space:nowrap;z-index:1}
.report-table td{border-color:#eef2f7;color:#344054;font-size:13px;vertical-align:middle}
.report-table tbody tr:hover td{background:#fafafa}
.report-table code{color:#0f766e;font-size:12px}
.report-pill{border-radius:999px;display:inline-flex;font-size:12px;font-weight:900;line-height:1;padding:7px 10px;white-space:nowrap}
.report-pill.warning{background:#fff7ed;color:#b45309}.report-pill.primary{background:#eff6ff;color:#1d4ed8}.report-pill.info{background:#ecfeff;color:#0e7490}.report-pill.success{background:#ecfdf3;color:#067647}.report-pill.secondary{background:#f2f4f7;color:#475467}.report-pill.danger{background:#fff1f2;color:#be123c}.report-pill.dark{background:#f1f5f9;color:#0f172a}
.report-rank{color:#98a2b3;font-weight:900;width:46px}
.report-main-cell{min-width:240px;width:38%}
.report-main-cell strong{color:#101828;display:block;font-weight:900;line-height:1.35}
.report-meta{color:#667085;display:flex;flex-wrap:wrap;font-size:12px;gap:4px 10px;line-height:1.45;margin-top:4px}
.report-meta span+span::before{color:#cbd5e1;content:"/";margin-right:10px}
.report-chart{height:340px;position:relative}
.report-bars{display:grid;gap:12px}
.report-bar-row{display:grid;gap:12px;grid-template-columns:minmax(0,1fr) 112px}
.report-bar-row strong{color:#111827;font-size:13px;font-weight:900}
.report-bar-track{background:#e4e7ec;border-radius:999px;height:8px;margin-top:7px;overflow:hidden}
.report-bar-track span{background:#0f766e;border-radius:inherit;display:block;height:100%;min-width:4px}
.report-empty{align-items:center;color:#667085;display:flex;font-size:14px;justify-content:center;min-height:130px;text-align:center}
.report-two-col{display:grid;gap:14px;grid-template-columns:360px minmax(0,1fr)}
@media(max-width:1200px){.report-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.report-two-col{grid-template-columns:1fr}}
@media(max-width:768px){.report-page{margin:-24px -12px 0;padding:16px 12px}.report-head{display:block}.report-actions{justify-content:flex-start;margin-top:14px}.report-grid{grid-template-columns:1fr}.report-chart{height:300px}.report-filter-main,.report-section-head{align-items:flex-start;flex-direction:column}.report-data-note,.report-section-note{text-align:left}.report-date-form{grid-template-columns:1fr}.report-table{min-width:760px}}
</style>
@endpush
@endonce
