<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <title>Composer</title>
    <style>
        :root {
            --bg: #0b0f14;
            --panel: #10151d;
            --muted: #9aa4b2;
            --border: #283546;
            --textarea: #0e141b;
            --accent: #3b82f6;
            --accent-strong: #2563eb;
            --text: #e6edf3;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }

        .container {
            max-width: 980px;
            margin: 48px auto;
            padding: 0 18px;
        }

        .card {
            background: var(--panel);
            border: 1.5px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.35);
        }

        .title {
            margin: 0 0 16px;
            font-size: 14px;
            font-weight: 600;
            color: var(--muted);
            letter-spacing: .02em;
            text-transform: uppercase;
        }

        .hint {
            color: var(--muted);
            font-size: 12px;
            margin-bottom: 16px;
        }

        label {
            display: block;
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 6px;
        }

        .field {
            margin-bottom: 14px;
        }

        select,
        input[type="text"] {
            width: 100%;
            background: var(--textarea);
            border: 1.25px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            padding: 10px 12px;
            font-size: 14px;
        }

        button {
            background: var(--accent-strong);
            border: none;
            border-radius: 8px;
            color: #fff;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 18px;
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        pre {
            margin-top: 16px;
            background: var(--textarea);
            border: 1.25px solid var(--border);
            border-radius: 10px;
            padding: 14px;
            min-height: 200px;
            max-height: 60vh;
            overflow: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            line-height: 1.55;
        }

        .ok {
            color: #51cf66;
        }

        .err {
            color: #ff6b6b;
        }
    </style>
</head>

<body>
    <script>
        window.__DEVTOOLS_DT = @json(request()->query('dt', ''));
        (function() {
            const t = window.__DEVTOOLS_DT;
            if (!t) return;
            try {
                const u = new URL(window.location.href);
                if (u.searchParams.has('dt')) {
                    u.searchParams.delete('dt');
                    const q = u.searchParams.toString();
                    window.history.replaceState({}, '', u.pathname + (q ? '?' + q : '') + u.hash);
                }
            } catch (e) {}
        })();
    </script>
    <div class="container">
        <div class="card">
            <h1 class="title">Composer</h1>
            <p class="hint">Runs <code>composer install</code> or <code>composer update</code> in the project root.
                Optional flags: --no-dev, --optimize-autoloader, -o, --with-all-dependencies, -W, --prefer-dist,
                --dry-run</p>

            <form id="composer-form">
                <div class="field">
                    <label for="command">Command</label>
                    <select id="command" name="command">
                        <option value="install">install</option>
                        <option value="update">update</option>
                    </select>
                </div>
                <div class="field">
                    <label for="flags">Optional flags</label>
                    <input type="text" id="flags" name="flags" placeholder="e.g. --no-dev --optimize-autoloader"
                        autocomplete="off" spellcheck="false">
                </div>
                <button type="submit" id="run-btn">Run</button>
            </form>

            <pre id="output" aria-live="polite"></pre>
        </div>
    </div>

    <script>
        const form = document.getElementById('composer-form');
        const output = document.getElementById('output');
        const runBtn = document.getElementById('run-btn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            runBtn.disabled = true;
            output.className = '';
            output.textContent = 'Running… (this may take several minutes)\n';

            try {
                const response = await fetch(@json(url('/composer/run')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'X-Dev-Tools-Token': window.__DEVTOOLS_DT || ''
                    },
                    body: JSON.stringify({
                        command: document.getElementById('command').value,
                        flags: document.getElementById('flags').value.trim()
                    })
                });

                const data = await response.json();
                output.textContent = data.output || '(no output)';
                output.className = data.success ? 'ok' : 'err';

                if (response.status === 403) {
                    output.className = 'err';
                    output.textContent = data.message || 'Unlock required.';
                }
            } catch (err) {
                output.className = 'err';
                output.textContent = err.message || 'Request failed.';
            } finally {
                runBtn.disabled = false;
            }
        });
    </script>
</body>

</html>
