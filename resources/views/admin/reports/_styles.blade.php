@once
@push('styles')
<style>
.report-page {
    background: #f4f7fb;
    min-height: calc(100vh - 72px);
    padding: 24px;
}

.report-head {
    align-items: flex-start;
    display: flex;
    gap: 16px;
    justify-content: space-between;
    margin-bottom: 18px;
}

.report-kicker {
    color: #0f766e;
    font-size: 13px;
    font-weight: 800;
    letter-spacing: .04em;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.report-title {
    color: #101828;
    font-size: 26px;
    font-weight: 800;
    line-height: 1.2;
    margin: 0;
}

.report-subtitle {
    color: #667085;
    font-size: 14px;
    margin: 8px 0 0;
}

.report-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
}

.report-btn {
    align-items: center;
    background: #fff;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    color: #344054;
    display: inline-flex;
    font-size: 14px;
    font-weight: 800;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
    text-decoration: none;
}

.report-btn.primary {
    background: #0f766e;
    border-color: #0f766e;
    color: #fff;
}

.report-card {
    background: #fff;
    border: 1px solid #e4e7ec;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(16, 24, 40, .04);
    padding: 18px;
}

.report-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    margin-bottom: 18px;
}

.report-metric-label {
    color: #667085;
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 10px;
}

.report-metric-value {
    color: #101828;
    font-size: 27px;
    font-weight: 900;
    line-height: 1;
    margin-bottom: 8px;
}

.report-metric-note {
    color: #667085;
    font-size: 13px;
    margin: 0;
}

.report-section {
    margin-bottom: 18px;
}

.report-section-head {
    align-items: center;
    border-bottom: 1px solid #eef2f6;
    display: flex;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 14px;
}

.report-section-title {
    color: #101828;
    font-size: 17px;
    font-weight: 900;
    margin: 0;
}

.report-table {
    margin: 0;
    width: 100%;
}

.report-table th {
    border-top: 0;
    color: #667085;
    font-size: 12px;
    font-weight: 900;
    text-transform: uppercase;
    white-space: nowrap;
}

.report-table td {
    color: #344054;
    font-size: 14px;
    vertical-align: middle;
}

.report-table code {
    color: #0f766e;
    font-size: 12px;
}

.report-pill {
    border-radius: 999px;
    display: inline-flex;
    font-size: 12px;
    font-weight: 900;
    line-height: 1;
    padding: 7px 10px;
    white-space: nowrap;
}

.report-pill.warning { background: #fff7ed; color: #b45309; }
.report-pill.primary { background: #eff6ff; color: #1d4ed8; }
.report-pill.info { background: #ecfeff; color: #0e7490; }
.report-pill.success { background: #ecfdf3; color: #067647; }
.report-pill.secondary { background: #f2f4f7; color: #475467; }
.report-pill.danger { background: #fff1f2; color: #be123c; }
.report-pill.dark { background: #f1f5f9; color: #0f172a; }

.report-chart {
    height: 430px;
    position: relative;
}

.report-bars {
    display: grid;
    gap: 14px;
}

.report-bar-row {
    display: grid;
    gap: 12px;
    grid-template-columns: minmax(0, 1fr) 110px;
}

.report-bar-track {
    background: #e4e7ec;
    border-radius: 999px;
    height: 8px;
    margin-top: 7px;
    overflow: hidden;
}

.report-bar-track span {
    background: #0f766e;
    border-radius: inherit;
    display: block;
    height: 100%;
    min-width: 4px;
}

.report-empty {
    align-items: center;
    color: #667085;
    display: flex;
    font-size: 14px;
    justify-content: center;
    min-height: 130px;
    text-align: center;
}

@media (max-width: 1200px) {
    .report-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    .report-page {
        padding: 16px;
    }

    .report-head {
        display: block;
    }

    .report-actions {
        justify-content: flex-start;
        margin-top: 14px;
    }

    .report-grid {
        grid-template-columns: 1fr;
    }

    .report-chart {
        height: 320px;
    }
}
</style>
@endpush
@endonce
