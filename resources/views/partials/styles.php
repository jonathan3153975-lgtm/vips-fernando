<style>
    :root {
        --bg: #f4f7fb;
        --panel: #ffffff;
        --brand: #0d47a1;
        --border: #d8e2ec;
        --text: #16324a;
        --muted: #65798d;
        --danger: #b3261e;
        --ok: #1b7f4b;
    }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
    a { color: var(--brand); }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 24px;
        background: var(--panel);
        border-bottom: 1px solid var(--border);
    }
    form { margin: 0; }
    button {
        border: 0;
        border-radius: 12px;
        padding: 10px 14px;
        background: var(--brand);
        color: #fff;
        cursor: pointer;
    }
    button.secondary { background: #e7eef8; color: var(--brand); }
    button.danger { background: var(--danger); }

    .shell { display: flex; align-items: stretch; min-height: calc(100vh - 78px); }
    aside {
        width: 232px;
        flex: 0 0 232px;
        background: var(--panel);
        border-right: 1px solid var(--border);
        padding: 20px 12px;
    }
    aside .group { margin-bottom: 18px; }
    aside .group-label {
        font-size: 11px;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--muted);
        padding: 0 10px 8px;
    }
    aside a.item {
        display: block;
        padding: 9px 10px;
        border-radius: 10px;
        text-decoration: none;
        color: var(--text);
        font-size: 14px;
    }
    aside a.item:hover { background: #eef3fb; }
    aside a.item.active { background: var(--brand); color: #fff; }

    main { flex: 1; max-width: 1120px; margin: 0 auto; padding: 24px; }
    .card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 20px;
        margin-bottom: 16px;
    }
    .eyebrow { color: var(--muted); font-size: 14px; }
    h1 { font-size: 22px; margin: 4px 0 12px; }
    h2 { font-size: 17px; margin: 0 0 10px; }

    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid var(--border); vertical-align: middle; }
    th { font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: var(--muted); }
    tr:last-child td { border-bottom: 0; }

    .badge {
        display: inline-block;
        padding: 3px 9px;
        border-radius: 999px;
        font-size: 12px;
        background: #e7eef8;
        color: var(--brand);
    }
    .badge.on { background: #e2f3ea; color: var(--ok); }
    .badge.off { background: #fbe6e5; color: var(--danger); }
    .muted { color: var(--muted); font-size: 13px; }
    .notice { margin: 0 0 16px; font-size: 14px; }
    .notice.ok { border-left: 4px solid var(--ok); }
    .notice.bad { border-left: 4px solid var(--danger); }
    .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
    .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    input[type="text"], input[type="email"], input[type="password"], select {
        padding: 9px 10px;
        border: 1px solid var(--border);
        border-radius: 10px;
        font: inherit;
        min-width: 180px;
    }
    label { display: block; font-size: 13px; color: var(--muted); margin-bottom: 4px; }
    .field { margin-bottom: 12px; }
</style>
