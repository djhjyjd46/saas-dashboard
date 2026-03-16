<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Days+One&family=Manrope:wght@200;300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --gold-gradient: linear-gradient(180deg, #F1D38C 0%, #AC9658 100%);
            --bg-main: #2B2B2B;
            --bg-card: #474747;
            --bg-table-wrapper: #434141;
            --font-header: 'Days One', sans-serif;
            --font-main: 'Manrope', sans-serif;
        }

        .gradient-text {
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
            font-size: 19px;
            font-weight: 400;
            font-family: var(--font-header);
        }

        body {
            background-color: var(--bg-main);
            color: #FFFFFF;
            font-family: var(--font-main);
            font-weight: 400;
            font-size: 24px;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }

        .font-header {
            font-family: var(--font-header) !important;
            font-size: 19px !important;
            font-weight: 400 !important;
        }

        .font-main {
            font-family: var(--font-main) !important;
            font-size: 24px !important;
            font-weight: 400 !important;
        }

        .gold-text {
            background: var(--gold-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: inline-block;
        }

        .gold-bg {
            background: var(--gold-gradient);
        }

        .gold-border {
            border-image: var(--gold-gradient) 1;
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-main);
        }

        ::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #666;
        }

        /* Transition for slide panel */
        .slide-over-enter {
            transform: translateX(100%);
        }

        .slide-over-enter-active {
            transform: translateX(0);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Gold Dashboard Table Styles - Pure CSS Nesting */
        .gold-dashboard-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            color: #FFFFFF;
            font-size: 14px;
            background: var(--bg-card);
            font-weight: 400;
            font-size: 16px;
            overflow-y: auto;

            & :is(th, td) {
                padding: 1rem 1.5rem;
                border-right: 1px solid rgba(255, 255, 255, 0.05);
                color: inherit;

                &:not(:first-child) {
                    text-align: center;
                }
            }

            & thead {
                & th {
                    font-family: var(--font-main);
                    background: var(--bg-main);
                    padding: 10px 20px;
                    font-size: 18px;
                    font-weight: 500;
                }
            }

            & tbody {
                & tr {
                    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
                    transition: background 0.3s ease;
                    cursor: pointer;

                    & td {}
                }
            }
        }

        .sidebar-active {
            background: rgba(255, 255, 255, 0.05) !important;
            border-radius: 0.75rem;
        }

        .status-circle {
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 9999px;
            border: 2px solid rgba(241, 211, 140, 0.3);
            transition: all 0.3s ease;
        }

        .sidebar-active .status-circle {
            background: var(--gold-gradient) !important;
            border-color: transparent !important;
        }
    </style>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'gold': '#F1D38C',
                        'gray': '#474747',
                        'card': '#2B2B2B',
                    }
                }
            }
        }
    </script>
    @livewireStyles
</head>

<body class="flex h-screen overflow-hidden bg-card px-5 py-10">
    {{ $slot }}
    @livewireScripts
</body>

</html>
