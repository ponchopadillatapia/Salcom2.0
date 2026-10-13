<style>
    .aa-kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
    .aa-kpi { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 16px 16px 16px 18px; position: relative; overflow: hidden; }
    .aa-kpi::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 3px; background: var(--purple); }
    .aa-kpi.pend::before { background: #d97706; }
    .aa-kpi.rev::before { background: #dc2626; }
    .aa-kpi .num { font-size: 26px; font-weight: 700; color: var(--gray-text); letter-spacing: -0.4px; font-variant-numeric: tabular-nums; }
    .aa-kpi .lbl { font-size: 11px; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .4px; margin-top: 4px; }
    .aa-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .aa-header h2 { font-size: 18px; font-weight: 700; color: var(--gray-text); margin: 0; }
    .aa-aviso { background: #f5f3ff; border: 1px solid #ddd6fe; border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; color: #4c1d95; font-size: 12.5px; line-height: 1.5; }
    .aa-card { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 20px; margin-bottom: 16px; }
    .aa-card h3 { font-size: 14px; font-weight: 700; color: var(--gray-text); margin: 0 0 14px; }
    .aa-filters { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .aa-filters input, .aa-filters select, .aa-input, .aa-select, .aa-textarea {
        border: 1.5px solid var(--border); border-radius: 8px; padding: 9px 12px;
        font-size: 13px; font-family: inherit; color: var(--gray-text); background: var(--white); outline: none;
    }
    .aa-filters input { width: 220px; }
    .aa-filters input[type="date"] { width: 150px; }
    .aa-input:focus, .aa-select:focus, .aa-textarea:focus, .aa-filters input:focus, .aa-filters select:focus {
        border-color: var(--purple); box-shadow: 0 0 0 3px rgba(107,63,160,.1);
    }
    .aa-btn { padding: 9px 16px; background: var(--purple); color: #fff; border: none; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .aa-btn:hover { background: var(--purple-dark); color: #fff; }
    .aa-btn-sec { background: var(--white); color: var(--gray-text); border: 1.5px solid var(--border); }
    .aa-btn-sec:hover { background: var(--gray-soft); color: var(--gray-text); }
    .aa-btn-warn { background: #fff7ed; color: #9a3412; border: 1px solid #fdba74; }
    .aa-btn-warn:hover { background: #ffedd5; color: #9a3412; }
    .aa-btn-ok { background: #ecfdf5; color: #166534; border: 1px solid #86efac; }
    .aa-btn-ok:hover { background: #dcfce7; color: #166534; }
    .aa-btn-danger { background: none; color: #b91c1c; border: none; padding: 0; font-size: 12px; font-weight: 600; cursor: pointer; font-family: inherit; }
    .aa-table-wrap { overflow-x: auto; background: var(--white); border: 1px solid var(--border-light); border-radius: 12px; }
    .aa-table { width: 100%; border-collapse: collapse; }
    .aa-table th { background: var(--gray-soft); font-size: 10px; font-weight: 700; color: var(--gray-muted); text-transform: uppercase; letter-spacing: .3px; padding: 10px 12px; text-align: left; white-space: nowrap; }
    .aa-table th.aa-num, .aa-table td.aa-num { text-align: right; font-variant-numeric: tabular-nums; }
    .aa-table td { padding: 12px; font-size: 13px; border-top: 1px solid var(--border-light); vertical-align: top; }
    .aa-table tr:hover td { background: var(--purple-subtle); }
    .aa-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
    .aa-b-activo { background: #dcfce7; color: #166534; }
    .aa-b-inactivo { background: #fee2e2; color: #991b1b; }
    .aa-b-ok { background: #dbeafe; color: #1e40af; }
    .aa-b-pend { background: #fef3c7; color: #92400e; }
    .aa-b-no { background: #f3f4f6; color: #4b5563; }
    .aa-b-rev { background: #fee2e2; color: #991b1b; }
    .aa-links { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    .aa-link { color: var(--purple); font-weight: 600; font-size: 12px; text-decoration: none; background: none; border: none; cursor: pointer; font-family: inherit; padding: 0; }
    .aa-empty { text-align: center; padding: 40px 16px; color: var(--gray-muted); font-size: 13px; }
    .aa-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .aa-field { display: flex; flex-direction: column; gap: 6px; }
    .aa-field.full { grid-column: 1 / -1; }
    .aa-field label { font-size: 12px; font-weight: 600; color: var(--gray-muted); }
    .aa-req { color: #b91c1c; }
    .aa-help { font-size: 11px; color: var(--gray-muted); line-height: 1.4; }
    .aa-dl { display: grid; grid-template-columns: 180px 1fr; gap: 8px 12px; font-size: 13px; }
    .aa-dl dt { color: var(--gray-muted); font-weight: 600; }
    .aa-dl dd { margin: 0; color: var(--gray-text); }
    .aa-actions { display: flex; gap: 8px; flex-wrap: wrap; }
    .aa-back { font-size: 12px; color: var(--purple); text-decoration: none; font-weight: 600; }
    .aa-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; }
    .aa-error ul { margin: 0; padding-left: 16px; color: #991b1b; font-size: 12px; }
    .aa-ok { background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 10px; padding: 12px 16px; margin-bottom: 16px; color: #065f46; font-size: 13px; font-weight: 600; }
    .aa-mini { font-size: 11px; color: var(--gray-muted); }
    .aa-lead { margin: 4px 0 0; font-size: 13px; color: var(--gray-muted); line-height: 1.45; }
    .aa-drop { position: relative; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 6px; border: 1.5px dashed #d4c4ea; background: #faf8fc; border-radius: 16px; padding: 34px 22px; cursor: pointer; }
    .aa-drop:hover, .aa-drop.is-over { border-color: var(--purple); background: var(--purple-subtle); }
    .aa-drop-kicker { font-size: 11px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: var(--purple); }
    .aa-drop-title { font-size: 16px; font-weight: 700; color: var(--gray-text); }
    .aa-drop-sub { max-width: 460px; font-size: 12.5px; color: var(--gray-muted); line-height: 1.45; }
    .aa-drop-status { min-height: 1.2em; font-size: 12.5px; font-weight: 600; }
    .aa-drop-status.is-ok { color: #166534; }
    .aa-drop-status.is-wait { color: var(--purple); }
    .aa-drop-status.is-error { color: #991b1b; }
    .aa-file { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0; }
    .aa-card[hidden] { display: none !important; }
    .aa-resumen { margin: -2px 0 16px; font-size: 14px; font-weight: 700; color: var(--purple); min-height: 1.2em; }
    .aa-tiles { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 16px; }
    .aa-tile { background: var(--white); border: 1px solid var(--border-light); border-radius: 14px; padding: 14px 16px; }
    .aa-tile.principal { background: #faf8fc; border-color: #e9d5ff; }
    .aa-tile .lbl { font-size: 11px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--gray-muted); }
    .aa-tile .val { margin-top: 6px; font-size: 18px; font-weight: 700; color: var(--gray-text); font-variant-numeric: tabular-nums; letter-spacing: -0.3px; }
    .aa-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 8px; }
    @media (max-width: 980px) {
        .aa-tiles { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 800px) {
        .aa-kpis, .aa-grid, .aa-dl, .aa-tiles { grid-template-columns: 1fr; }
        .aa-filters input, .aa-filters select { width: 100%; }
    }
</style>
