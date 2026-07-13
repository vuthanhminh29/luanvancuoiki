@once
@push('styles')
<style>
.pa-page { background: #f4f7fb; min-height: calc(100vh - 72px); padding: 24px; }
.pa-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
.pa-kicker { color: #0f766e; font-size: 13px; font-weight: 800; letter-spacing: .04em; margin-bottom: 6px; text-transform: uppercase; }
.pa-title { color: #101828; font-size: 26px; font-weight: 900; line-height: 1.2; margin: 0; }
.pa-subtitle { color: #667085; font-size: 14px; margin: 8px 0 0; }
.pa-actions { display: flex; flex-wrap: wrap; gap: 10px; justify-content: flex-end; }
.pa-btn { align-items: center; background: #fff; border: 1px solid #d0d5dd; border-radius: 8px; color: #344054; display: inline-flex; font-size: 14px; font-weight: 800; gap: 8px; min-height: 40px; padding: 0 14px; text-decoration: none; cursor: pointer; }
.pa-btn.primary, .pa-submit { background: #0f766e; border-color: #0f766e; color: #fff; }
.pa-btn.danger { background: #fff1f2; border-color: #fecdd3; color: #be123c; }
.pa-card { background: #fff; border: 1px solid #e4e7ec; border-radius: 8px; box-shadow: 0 8px 24px rgba(16,24,40,.04); padding: 18px; }
.pa-grid { display: grid; gap: 16px; grid-template-columns: minmax(0, 1.5fr) minmax(320px, .8fr); }
.pa-section { margin-bottom: 16px; }
.pa-section-title { color: #101828; font-size: 17px; font-weight: 900; margin: 0 0 16px; }
.pa-form-grid { display: grid; gap: 14px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.pa-field { margin-bottom: 14px; }
.pa-label { color: #344054; display: block; font-size: 13px; font-weight: 800; margin-bottom: 7px; }
.pa-input, .pa-select, .pa-textarea, .pa-file { background: #fff; border: 1px solid #d0d5dd; border-radius: 8px; color: #101828; font-size: 14px; min-height: 42px; padding: 9px 12px; width: 100%; }
.pa-textarea { min-height: 130px; resize: vertical; }
.pa-input:focus, .pa-select:focus, .pa-textarea:focus { border-color: #0f766e; box-shadow: 0 0 0 3px rgba(15,118,110,.12); outline: none; }
.pa-error { color: #be123c; display: block; font-size: 13px; margin-top: 5px; }
.pa-hint { color: #667085; font-size: 12px; margin-top: 6px; }
.pa-table { margin: 0; width: 100%; }
.pa-table th { border-top: 0; color: #667085; font-size: 12px; font-weight: 900; text-transform: uppercase; white-space: nowrap; }
.pa-table td { color: #344054; font-size: 14px; vertical-align: middle; }
.pa-table code { color: #0f766e; font-size: 12px; }
.pa-thumb { background: #f8fafc; border: 1px solid #e4e7ec; border-radius: 8px; height: 58px; object-fit: cover; width: 58px; }
.pa-preview { background: #f8fafc; border: 1px solid #e4e7ec; border-radius: 8px; margin-top: 10px; max-height: 220px; max-width: 100%; object-fit: contain; padding: 6px; }
.pa-pill { border-radius: 999px; display: inline-flex; font-size: 12px; font-weight: 900; line-height: 1; padding: 7px 10px; white-space: nowrap; }
.pa-pill.success { background: #ecfdf3; color: #067647; }
.pa-pill.warning { background: #fff7ed; color: #b45309; }
.pa-pill.secondary { background: #f2f4f7; color: #475467; }
.pa-pill.danger { background: #fff1f2; color: #be123c; }
.pa-pill.dark { background: #f1f5f9; color: #0f172a; }
.pa-color-dot { border: 1px solid #d0d5dd; border-radius: 50%; display: inline-block; height: 18px; margin-left: 6px; vertical-align: middle; width: 18px; }
.pa-empty { align-items: center; color: #667085; display: flex; justify-content: center; min-height: 130px; text-align: center; }
.pa-pagination { display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; list-style: none; margin: 18px 0 0; padding: 0; }
.pa-pagination a, .pa-pagination span { background: #fff; border: 1px solid #d0d5dd; border-radius: 8px; color: #344054; display: inline-flex; font-weight: 800; min-width: 38px; padding: 8px 12px; text-decoration: none; }
.pa-pagination .active span { background: #0f766e; border-color: #0f766e; color: #fff; }
.pa-variant-table { min-width: 920px; }
.pa-inline-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
@media (max-width: 1100px) { .pa-grid { grid-template-columns: 1fr; } }
@media (max-width: 768px) { .pa-page { padding: 16px; } .pa-head { display: block; } .pa-actions { justify-content: flex-start; margin-top: 14px; } .pa-form-grid { grid-template-columns: 1fr; } }
</style>
@endpush
@endonce
