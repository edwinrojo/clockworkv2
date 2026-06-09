<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Reset password — {{ config('app.name') }}</title>
        <style>
            :root {
                color-scheme: light;
                --brand: #70002a;
                --text: #1f1218;
                --muted: #6b5d63;
                --card: #ffffff;
                --border: #eadde4;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                font-family:
                    'Instrument Sans',
                    ui-sans-serif,
                    system-ui,
                    sans-serif;
                background: linear-gradient(180deg, #f9eef2 0%, #ffffff 55%);
                color: var(--text);
                display: grid;
                place-items: center;
                padding: 24px;
            }

            .card {
                width: 100%;
                max-width: 420px;
                background: var(--card);
                border: 1px solid var(--border);
                border-radius: 20px;
                padding: 32px 28px;
                box-shadow:
                    0 1px 2px rgb(112 0 42 / 0.04),
                    0 8px 24px -4px rgb(0 0 0 / 0.08);
                text-align: center;
            }

            .logo {
                width: 56px;
                height: 56px;
                margin: 0 auto 20px;
                border-radius: 16px;
                background: linear-gradient(135deg, #70002a 0%, #a00045 100%);
                display: grid;
                place-items: center;
                color: #fff;
                font-weight: 700;
                font-size: 22px;
            }

            h1 {
                margin: 0 0 8px;
                font-size: 1.35rem;
                line-height: 1.3;
            }

            p {
                margin: 0;
                color: var(--muted);
                font-size: 0.95rem;
                line-height: 1.55;
            }

            .actions {
                margin-top: 28px;
                display: grid;
                gap: 12px;
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                min-height: 52px;
                padding: 0 20px;
                border-radius: 16px;
                font-size: 1rem;
                font-weight: 600;
                text-decoration: none;
                border: none;
                cursor: pointer;
            }

            .btn-primary {
                background: linear-gradient(135deg, #70002a 0%, #a00045 100%);
                color: #fff;
                box-shadow: 0 8px 24px -4px rgb(112 0 42 / 0.35);
            }

            .hint {
                margin-top: 20px;
                font-size: 0.82rem;
            }
        </style>
    </head>
    <body>
        <main class="card">
            <div class="logo" aria-hidden="true">C</div>
            <h1>Open Clockwork to reset your password</h1>
            <p>
                We are opening the mobile app so you can choose a new password.
                If nothing happens, tap the button below.
            </p>

            <div class="actions">
                <a class="btn btn-primary" id="open-app" href="{{ $deepLink }}">
                    Open Clockwork app
                </a>
            </div>

            <p class="hint">
                Install the Clockwork app on this phone first, then return to
                this page and tap the button again.
            </p>
        </main>

        <script>
            (function () {
                var deepLink = @json($deepLink);
                var openApp = document.getElementById('open-app');

                function openDeepLink() {
                    window.location.href = deepLink;
                }

                openApp.addEventListener('click', function (event) {
                    event.preventDefault();
                    openDeepLink();
                });

                window.setTimeout(openDeepLink, 300);
            })();
        </script>
    </body>
</html>
