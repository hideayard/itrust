<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>TradingView Market Dashboard</title>
    <script type="text/javascript" src="https://s3.tradingview.com/tv.js"></script>
    <style>
        :root {
            --bg-primary: #0a0e17;
            --bg-secondary: #121826;
            --bg-widget: #1a1f2e;
            --bg-sidebar: #141927;
            --border-color: #2a3245;
            --border-light: #343d54;
            --text-primary: #e2e8f0;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --accent-blue: #3b82f6;
            --accent-blue-dark: #2563eb;
            --accent-green: #10b981;
            --accent-red: #ef4444;
            --accent-purple: #8b5cf6;
            --accent-yellow: #f59e0b;
            --accent-orange: #f97316;
            --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.25);
            --shadow-lg: 0 10px 30px rgba(0, 0, 0, 0.35);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', Roboto, sans-serif;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            overflow-y: auto;
        }



        .container {
            width: 100%;
            min-height: 100vh;
            height: 100vh;
            padding: 20px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Header */
        .dashboard-header {
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
            padding: 18px 30px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            box-shadow: var(--shadow-md);
            backdrop-filter: blur(10px);
            flex-shrink: 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-left h1 {
            font-size: clamp(1.4rem, 3vw, 2.1rem);
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .header-left p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            line-height: 1.5;
            max-width: 600px;
        }

        .controls {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            align-items: flex-end;
        }

        .control-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            min-width: 150px;
        }

        .control-group label {
            font-size: 0.85rem;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        select {
            background: var(--bg-widget);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 10px 16px;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            cursor: pointer;
            transition: var(--transition);
            height: 44px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%2394a3b8' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
        }

        select:hover {
            border-color: var(--accent-blue);
            background-color: var(--bg-widget);
        }

        select:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }

        button {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-blue-dark));
            border: none;
            color: white;
            padding: 10px 24px;
            border-radius: var(--radius-sm);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            height: 44px;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4);
        }

        /* Main Layout */
        .dashboard-layout {
            display: grid;
            grid-template-columns: minmax(220px, 18%) 1fr minmax(220px, 18%);
            gap: 20px;
            height: auto;
            flex: 1;
            min-height: 0;
        }

        /* Sidebars - Fixed Height */
        .sidebar {
            display: flex;
            flex-direction: column;
            min-height: 0;
            top: 0;
            height: auto;
            max-height: calc(100vh - 140px);
            overflow: hidden;
        }

        .sidebar-left {
            grid-column: 1;
        }

        .sidebar-right {
            grid-column: 3;
        }

        .widget {
            background: var(--bg-widget);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            min-height: 0;
        }

        .widget:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--border-light);
            transform: translateY(-2px);
        }

        .widget-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border-light);
            background: linear-gradient(135deg, var(--bg-widget), var(--bg-secondary));
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .widget-header h2 {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .widget-badge {
            background: var(--accent-green);
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .widget-badge.today {
            background: var(--accent-purple);
        }

        .widget-content {
            flex: 1;
            min-height: 0;
            overflow: hidden;
            position: relative;
        }

        .tradingview-widget-container {
            width: 100%;
            height: 100%;
            position: relative;
        }

        .tradingview-widget-container__widget {
            width: 100%;
            height: 100%;
        }

        /* Main Content - Scrollable */
        .main-content-scrollable {
            grid-column: 2;
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            overflow: hidden;
        }

        .main-content-wrapper {
            display: flex;
            flex-direction: column;
            gap: 20px;
            height: 100%;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Chart Section */
        .chart-section {
            background: var(--bg-widget);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: var(--shadow-md);
            min-height: clamp(320px, 60vh, 700px);
            flex-shrink: 0;
        }

        .chart-header {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--bg-secondary);
            flex-shrink: 0;
        }

        .chart-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .chart-title h2 {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin: 0;
        }

        .chart-meta {
            display: flex;
            gap: 16px;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        .exchange {
            color: var(--accent-blue);
            font-weight: 600;
        }

        .spread {
            color: var(--accent-green);
            font-weight: 600;
        }

        .chart-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 500;
            background: rgba(59, 130, 246, 0.1);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .live-dot {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: var(--accent-green);
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
            box-shadow: 0 0 10px var(--accent-green);
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }

            50% {
                transform: scale(1.1);
                opacity: 0.8;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .refresh-btn,
        .fullscreen-btn {
            background: transparent;
            border: 1px solid var(--border-color);
            padding: 8px 16px;
            font-size: 0.9rem;
            height: 36px;
            min-width: 100px;
        }

        .refresh-btn:hover {
            background: var(--bg-widget);
            border-color: var(--accent-blue);
            transform: translateY(-2px);
        }

        .fullscreen-btn:hover {
            background: var(--bg-widget);
            border-color: var(--accent-purple);
            transform: translateY(-2px);
        }

        .chart-container {
            flex: 1;
            min-height: clamp(300px, 55vh, 650px);
            position: relative;
            background: var(--bg-widget);
        }

        #chart {
            width: 100%;
            height: 100%;
            position: absolute;
            top: 0;
            left: 0;
        }

        /* Lower Dashboard */
        .lower-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .widget.full-width {
            grid-column: 1 / -1;
        }

        /* Watchlist Controls */
        .watchlist-controls {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .market-status {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            color: var(--text-secondary);
            background: rgba(16, 185, 129, 0.1);
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .market-open {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: var(--accent-green);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .watchlist-tabs {
            display: flex;
            gap: 8px;
        }

        .tab-btn {
            background: transparent;
            border: 1px solid var(--border-color);
            padding: 6px 12px;
            font-size: 0.85rem;
            height: 32px;
            min-width: auto;
        }

        .tab-btn.active {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
        }

        .timeframe-display {
            background: linear-gradient(135deg, var(--accent-blue), var(--accent-purple));
            color: white;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
        }

        /* Analysis Widget */
        .analysis-placeholder {
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            height: 100%;
        }

        .analysis-item {
            background: linear-gradient(145deg, rgba(59, 130, 246, 0.08), rgba(139, 92, 246, 0.08));
            border-radius: var(--radius-md);
            padding: 20px;
            border: 1px solid var(--border-light);
            transition: var(--transition);
        }

        .analysis-item:hover {
            transform: translateY(-2px);
            border-color: var(--accent-blue);
            box-shadow: var(--shadow-sm);
        }

        .analysis-item h3 {
            font-size: 1.1rem;
            color: var(--accent-blue);
            margin-bottom: 18px;
            font-weight: 700;
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 10px;
        }

        .indicator-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .indicator-row:last-child {
            margin-bottom: 0;
        }

        .indicator-row span:first-child {
            color: var(--text-secondary);
        }

        /* Sentiment Bar */
        .sentiment-bar {
            height: 6px;
            background: var(--border-color);
            border-radius: 3px;
            margin: 15px 0;
            overflow: hidden;
        }

        .sentiment-fill {
            height: 100%;
            border-radius: 3px;
        }

        .sentiment-fill.bullish {
            background: linear-gradient(90deg, var(--accent-green), #34d399);
        }

        .sentiment-fill.bearish {
            background: linear-gradient(90deg, var(--accent-red), #f87171);
        }

        .sentiment-labels {
            display: flex;
            justify-content: space-between;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        /* Detailed Analysis */
        .detailed-analysis {
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            height: 100%;
        }

        .analysis-card {
            background: linear-gradient(145deg, rgba(59, 130, 246, 0.05), rgba(139, 92, 246, 0.05));
            border-radius: var(--radius-md);
            padding: 16px;
            border: 1px solid var(--border-light);
        }

        .analysis-card h4 {
            color: var(--accent-blue);
            margin-bottom: 10px;
            font-size: 1rem;
            font-weight: 600;
        }

        .analysis-card p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .analysis-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .tag.bullish {
            background: rgba(16, 185, 129, 0.2);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .tag.neutral {
            background: rgba(148, 163, 184, 0.2);
            color: var(--text-secondary);
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .tag.positive {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .tag.good-rr {
            background: rgba(245, 158, 11, 0.2);
            color: var(--accent-yellow);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .tag.medium-risk {
            background: rgba(249, 115, 22, 0.2);
            color: var(--accent-orange);
            border: 1px solid rgba(249, 115, 22, 0.3);
        }

        /* Analysis Status Colors */
        .bullish {
            color: var(--accent-green);
            font-weight: 600;
        }

        .bearish {
            color: var(--accent-red);
            font-weight: 600;
        }

        .neutral {
            color: var(--text-secondary);
            font-weight: 600;
        }

        .positive {
            color: var(--accent-green);
            font-weight: 600;
        }

        .negative {
            color: var(--accent-red);
            font-weight: 600;
        }

        .support {
            color: var(--accent-green);
            font-weight: 600;
        }

        .resistance {
            color: var(--accent-red);
            font-weight: 600;
        }

        .pivot {
            color: var(--accent-yellow);
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 1400px) {
            .dashboard-layout {
                grid-template-columns: 18% 1fr 18%;
            }

            .lower-dashboard {
                grid-template-columns: 1fr;
            }

            .analysis-placeholder {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 1200px) {
            .dashboard-layout {
                grid-template-columns: 1fr;
                grid-template-rows: auto;
                height: auto;
            }

            .sidebar {
                height: 450px;
                /* Slightly smaller height for mobile */
                position: static;
            }

            .sidebar-left {
                grid-row: 2;
                grid-column: 1;
            }

            .sidebar-right {
                grid-row: 3;
                grid-column: 1;
            }

            .main-content-scrollable {
                grid-row: 1;
                grid-column: 1;
                height: auto;
            }

            .main-content-wrapper {
                overflow-y: visible;
                padding-right: 0;
            }

            /* Fix for the scroll issue */
            .container {
                height: auto;
                min-height: 100vh;
                overflow: visible;
                padding: 15px;
                /* Reduce padding on mobile */
            }

            /* Compact header for mobile */
            .dashboard-header {
                padding: 15px 20px;
                margin-bottom: 15px;
                flex-direction: row;
                flex-wrap: wrap;
                gap: 15px;
            }

            .header-left {
                flex: 1;
                min-width: 100%;
                text-align: center;
                margin-bottom: 10px;
            }

            .header-left h1 {
                font-size: 1.6rem;
                margin-bottom: 4px;
            }

            .header-left p {
                font-size: 0.85rem;
                max-width: 100%;
            }

            /* Compact controls layout */
            .controls {
                flex: 1;
                display: flex;
                flex-direction: row;
                justify-content: center;
                gap: 12px;
                flex-wrap: wrap;
            }

            .control-group {
                flex: 1;
                min-width: 140px;
                max-width: 180px;
            }

            .control-group label {
                font-size: 0.8rem;
                margin-bottom: 2px;
            }

            select {
                height: 38px;
                padding: 8px 14px;
                font-size: 0.9rem;
                padding-right: 35px;
            }

            button {
                height: 38px;
                padding: 8px 18px;
                font-size: 0.9rem;
                min-width: 140px;
            }

            /* Optional: Make controls single line if enough width */
            @media (min-width: 768px) and (max-width: 1200px) {
                .controls {
                    flex-wrap: nowrap;
                }

                .control-group {
                    min-width: 120px;
                }

                .dashboard-header {
                    flex-wrap: nowrap;
                }

                .header-left {
                    min-width: auto;
                    text-align: left;
                    flex: 2;
                    margin-bottom: 0;
                }

                .controls {
                    flex: 3;
                }
            }

            /* Adjust chart height for mobile */
            .chart-section {
                min-height: 400px;
            }

            .chart-container {
                min-height: 350px;
            }

            /* Adjust chart header */
            .chart-header {
                padding: 14px 20px;
            }

            .chart-title h2 {
                font-size: 1.2rem;
            }

            .chart-meta {
                font-size: 0.8rem;
                flex-wrap: wrap;
            }

            /* Compact chart controls */
            .chart-controls {
                gap: 10px;
            }

            .status-indicator {
                font-size: 0.8rem;
                padding: 5px 10px;
            }

            .refresh-btn,
            .fullscreen-btn {
                padding: 6px 12px;
                font-size: 0.85rem;
                height: 34px;
                min-width: 90px;
            }

            /* Reduce widget padding */
            .widget-header {
                padding: 14px 18px;
            }

            .widget-header h2 {
                font-size: 1rem;
            }

            /* Add spacing between stacked sections */
            .sidebar-left,
            .sidebar-right {
                margin-bottom: 15px;
            }

            /* Adjust analysis items */
            .analysis-placeholder {
                padding: 15px;
                gap: 15px;
            }

            .analysis-item {
                padding: 15px;
            }

            .analysis-item h3 {
                font-size: 1rem;
                margin-bottom: 12px;
            }

            .indicator-row {
                font-size: 0.9rem;
            }

            /* Adjust detailed analysis */
            .detailed-analysis {
                padding: 15px;
                gap: 12px;
            }

            .analysis-card {
                padding: 14px;
            }

            .analysis-card h4 {
                font-size: 0.95rem;
            }

            .analysis-card p {
                font-size: 0.85rem;
            }
        }

        /* For very small screens between 768px and 1200px */
        @media (max-width: 992px) and (min-width: 768px) {
            .dashboard-header {
                padding: 12px 18px;
            }

            .control-group {
                min-width: 110px;
            }

            select,
            button {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 768px) {

            /* Container adjustments for mobile */
            .container {
                padding: 8px;
            }

            /* Ultra-compact header */
            .dashboard-header {
                flex-direction: column;
                gap: 8px;
                padding: 8px 12px;
                max-height: 180px;
                min-height: 140px;
                overflow: hidden;
                justify-content: space-between;
            }

            /* Compact header left section */
            .header-left {
                text-align: center;
                flex-shrink: 1;
                min-height: 0;
                margin-bottom: 2px;
                width: 100%;
            }

            .header-left h1 {
                font-size: 1.2rem;
                margin-bottom: 2px;
                line-height: 1.2;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            /* Very compact paragraph */
            .header-left p {
                font-size: 0.7rem;
                line-height: 1.2;
                max-height: 24px;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 1;
                -webkit-box-orient: vertical;
                margin: 0 auto;
                width: 95%;
            }

            /* Single line controls - dynamic widths */
            .controls {
                width: 100%;
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                gap: 6px;
                flex-shrink: 0;
                align-items: flex-end;
                /* Align items to bottom */
                justify-content: space-between;
                margin-top: 2px;
                min-width: 0;
            }

            /* Control groups - dynamic sizing */
            .control-group {
                flex: 1 1 0px;
                min-width: 0;
                max-width: 100%;
                margin: 0;
                display: flex;
                flex-direction: column;
                justify-content: flex-end;
                /* Push content to bottom */
            }

            .control-group label {
                font-size: 0.65rem;
                margin-bottom: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                display: block;
                line-height: 1;
                width: 100%;
            }

            /* Form elements - dynamic widths with consistent height */
            select {
                height: 30px;
                /* Fixed height */
                min-height: 30px;
                max-height: 30px;
                padding: 5px 8px;
                font-size: 0.75rem;
                padding-right: 28px;
                width: 100%;
                min-width: 0;
                border-radius: 4px;
                flex-shrink: 1;
                overflow: hidden;
                text-overflow: ellipsis;
                line-height: 1.2;
                /* Ensure text aligns properly */
            }

            button {
                height: 30px !important;
                /* Fixed height with !important */
                min-height: 30px !important;
                max-height: 30px !important;
                padding: 5px 8px;
                font-size: 0.75rem;
                flex: 1 1 0px;
                min-width: 0;
                max-width: 100%;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                border-radius: 4px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 4px;
                line-height: 1 !important;
                /* Reset line height */
                box-sizing: border-box !important;
                /* Ensure padding included in height */
            }

            /* Ensure button content doesn't affect height */
            button span,
            button i,
            button svg {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100%;
                line-height: 1;
                margin: 0;
                padding: 0;
            }

            /* Even more compact for small screens */
            @media (max-width: 576px) {
                .dashboard-header {
                    max-height: 160px;
                    min-height: 130px;
                    padding: 6px 10px;
                }

                .header-left h1 {
                    font-size: 1.1rem;
                }

                .header-left p {
                    font-size: 0.65rem;
                    max-height: 20px;
                }

                /* Adjust gap for tighter layout */
                .controls {
                    gap: 4px;
                }

                .control-group label {
                    font-size: 0.6rem;
                }

                select {
                    height: 28px !important;
                    min-height: 28px !important;
                    max-height: 28px !important;
                    padding: 4px 6px;
                    font-size: 0.7rem;
                    padding-right: 24px;
                }

                button {
                    height: 28px !important;
                    min-height: 28px !important;
                    max-height: 28px !important;
                    padding: 4px 6px;
                    font-size: 0.7rem;
                    line-height: 1 !important;
                }

                /* Handle text overflow better on very small screens */
                select option {
                    font-size: 0.8rem;
                }
            }

            /* Extra small screens - maintain layout but reduce sizes */
            @media (max-width: 400px) {
                .dashboard-header {
                    max-height: 150px;
                    min-height: 120px;
                    padding: 4px 8px;
                }

                .header-left h1 {
                    font-size: 1rem;
                }

                .header-left p {
                    font-size: 0.6rem;
                }

                .controls {
                    gap: 3px;
                }

                .control-group label {
                    font-size: 0.55rem;
                    margin-bottom: 1px;
                }

                select {
                    height: 26px !important;
                    min-height: 26px !important;
                    max-height: 26px !important;
                    padding: 3px 4px;
                    font-size: 0.65rem;
                    padding-right: 22px;
                }

                button {
                    height: 26px !important;
                    min-height: 26px !important;
                    max-height: 26px !important;
                    padding: 3px 4px;
                    font-size: 0.65rem;
                    line-height: 1 !important;
                }

                /* Hide button text on very small, show only icon/short text */
                button span:not(.icon) {
                    display: inline;
                    max-width: 40px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    line-height: 1;
                }
            }

            /* Ultra small screens - minimal but functional */
            @media (max-width: 320px) {
                .header-left p {
                    display: none;
                }

                .control-group label {
                    display: none;
                }

                .controls {
                    align-items: center;
                }

                select {
                    height: 24px !important;
                    min-height: 24px !important;
                    max-height: 24px !important;
                    font-size: 0.6rem;
                    padding: 2px 4px;
                }

                button {
                    height: 24px !important;
                    min-height: 24px !important;
                    max-height: 24px !important;
                    font-size: 0.6rem;
                    padding: 2px 4px;
                    line-height: 1 !important;
                }
            }

            /* Ensure select dropdowns are readable and aligned */
            select {
                background-size: 12px;
                background-position: right 6px center;
                line-height: 1.2 !important;
                display: flex;
                align-items: center;
            }

            @media (max-width: 576px) {
                select {
                    background-size: 10px;
                    background-position: right 4px center;
                }
            }

            /* Override any existing button height styles */
            button:not(.refresh-btn):not(.fullscreen-btn):not(.tab-btn) {
                height: 30px !important;
                min-height: 30px !important;
                max-height: 30px !important;
            }

            /* Chart adjustments */
            .chart-header {
                flex-direction: column;
                text-align: center;
                gap: 8px;
                padding: 8px 12px;
            }

            .chart-title h2 {
                font-size: 1rem;
            }

            .chart-meta {
                font-size: 0.7rem;
                gap: 6px;
            }

            .chart-controls {
                gap: 6px;
            }

            .refresh-btn,
            .fullscreen-btn {
                height: 28px !important;
                font-size: 0.75rem;
                line-height: 1 !important;
            }

            /* Adjust sidebar heights */
            .sidebar {
                height: 350px;
            }
        }

        /* Override all button heights for consistency */
        @media (max-width: 1200px) {
            .controls button:not(.refresh-btn):not(.fullscreen-btn):not(.tab-btn) {
                height: 30px !important;
                min-height: 30px !important;
                max-height: 30px !important;
                line-height: 1 !important;
            }

            .controls {
                flex-wrap: nowrap !important;
            }

            .control-group {
                min-width: 0 !important;
                max-width: none !important;
            }
        }

        /* Ensure vertical alignment matches */
        @media (max-width: 768px) {

            select,
            button {
                vertical-align: middle;
                align-items: center;
            }
        }

        /* Scrollbar Styling */
        ::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--border-light);
        }

        /* Widget Specific Styles */
        .tradingview-widget-copyright {
            display: none !important;
        }

        /* Fullscreen Mode */
        .chart-section.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            border-radius: 0;
            border: none;
            margin: 0;
        }

        .chart-section.fullscreen .chart-container {
            min-height: calc(100vh - 80px);
        }

        /* Loading Styles */
        .chart-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-left">
                <h1>📈 Market Analysis Dashboard</h1>
                <p>
                    Advanced real-time charts, market data, economic calendar, and news
                    in one unified view
                </p>
            </div>
            <div class="controls">
                <div class="control-group">
                    <label for="pair">Currency Pair</label>
                    <select id="pair">
                        <option value="EURJPY" selected>EUR/JPY</option>
                        <option value="USDJPY">USD/JPY</option>
                        <option value="EURUSD">EUR/USD</option>
                        <option value="GBPUSD">GBP/USD</option>
                        <option value="XAUUSD">XAU/USD</option>
                        <option value="XAGUSD">XAG/USD</option>
                        <option value="BTCUSD">BTC/USD</option>
                    </select>
                </div>
                <div class="control-group">
                    <label for="tf">Timeframe</label>
                    <select id="tf">
                        <option value="15">15min</option>
                        <option value="30">30min</option>
                        <option value="60">1H</option>
                        <option value="240" selected>4H</option>
                        <option value="D">Daily</option>
                        <option value="W">Weekly</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>&nbsp;</label>
                    <button onclick="loadChart()">Load Chart</button>
                </div>
            </div>
        </header>

        <main class="dashboard-layout">
            <!-- Left Sidebar - Financial News -->
            <aside class="sidebar sidebar-left">
                <section class="widget">
                    <div class="widget-header">
                        <h2>📰 Financial News</h2>
                        <span class="widget-badge">Live</span>
                    </div>
                    <div class="widget-content">
                        <!-- TradingView Widget BEGIN -->
                        <div class="tradingview-widget-container">
                            <div class="tradingview-widget-container__widget"></div>
                            <div class="tradingview-widget-copyright">
                                <a href="https://www.tradingview.com/news/top-providers/tradingview/"
                                    rel="noopener nofollow"
                                    target="_blank"><span class="blue-text">Top stories</span></a>
                                <span class="trademark"> by TradingView</span>
                            </div>
                            <script type="text/javascript"
                                src="https://s3.tradingview.com/external-embedding/embed-widget-timeline.js"
                                async>
                                {
                                    "displayMode": "compact",
                                    "feedMode": "market",
                                    "colorTheme": "dark",
                                    "isTransparent": false,
                                    "locale": "en",
                                    "market": "forex",
                                    "width": "100%",
                                    "height": "100%"
                                }
                            </script>
                        </div>
                        <!-- TradingView Widget END -->
                    </div>
                </section>
            </aside>

            <!-- Main Content Area - Scrollable -->
            <div class="main-content-scrollable">
                <div class="main-content-wrapper">
                    <!-- Chart Section -->
                    <section class="chart-section">
                        <div class="chart-header">
                            <div class="chart-title">
                                <h2 id="current-pair">EURJPY - 4H Chart</h2>
                                <div class="chart-meta">
                                    <span class="exchange">FXCM</span>
                                    <span class="spread">Spread: 0.8 pips</span>
                                </div>
                            </div>
                            <div class="chart-controls">
                                <div class="status-indicator">
                                    <span class="live-dot"></span> Live Market Data
                                </div>
                                <button class="refresh-btn" onclick="loadChart()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M23 4v6h-6M1 20v-6h6M3.51 9a9 9 0 0114.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0020.49 15" />
                                    </svg>
                                    Refresh
                                </button>
                                <button class="fullscreen-btn" onclick="toggleFullscreen()">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" />
                                    </svg>
                                    Fullscreen
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <div id="chart"></div>
                        </div>
                    </section>

                    <!-- Lower Dashboard Grid -->
                    <div class="lower-dashboard">
                        <section class="widget full-width">
                            <div class="widget-header">
                                <h2>📊 Market Data Watchlist</h2>
                            </div>
                            <div class="widget-content">
                                <div class="watchlist-container" id="watchlist-all">
                                    <tv-market-summary
                                        symbol-sectors='[{"sectionName":"Currency","symbols":["OANDA:EURJPY","OANDA:EURUSD","OANDA:USDJPY","OANDA:GBPUSD","OANDA:GBPJPY"]},{"sectionName":"Crypto","symbols":["BINANCEUS:BTCUSDT","BINANCEUS:ETHUSDT","BINANCEUS:XRPUSDT","BINANCEUS:SOLUSDT","OKX:HYPEUSDT","BINANCE:BNBUSDT","CRYPTOCAP:TOTAL3","OKX:XAUTUSDT"]},{"sectionName":"Stocks","symbols":["SPREADEX:SPX","NASDAQ:TSLA","NASDAQ:NVDA","NASDAQ:GOOGL","FXOPEN:DXY","IDX:BBCA","IDX:COMPOSITE","IDX:ANTM","IDX:BBRI"]},{"sectionName":"Commodity","symbols":["CMCMARKETS:GOLD","CMCMARKETS:SILVER","TVC:USOIL"]}]'
                                        show-time-range layout-mode="grid" item-size="compact" mode="custom"></tv-market-summary>
                                </div>
                                <script type="module"
                                    src="https://widgets.tradingview-widget.com/w/en/tv-market-summary.js"></script>
                            </div>
                        </section>

                        <section class="widget">
                            <div class="widget-header">
                                <h2>📈 Market Analysis</h2>
                                <div class="timeframe-display" id="current-tf">4H</div>
                            </div>
                            <div class="widget-content">
                                <div class="analysis-placeholder">
                                    <div class="analysis-item">
                                        <h3>Technical Indicators</h3>
                                        <div class="indicator-row">
                                            <span>RSI:</span>
                                            <span class="neutral">54.2</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>MACD:</span>
                                            <span class="bullish">Bullish</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>Volume:</span>
                                            <span class="positive">↑ 12%</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>MA(50):</span>
                                            <span class="bullish">Above</span>
                                        </div>
                                    </div>
                                    <div class="analysis-item">
                                        <h3>Support & Resistance</h3>
                                        <div class="indicator-row">
                                            <span>Support:</span>
                                            <span class="support">158.50</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>Resistance:</span>
                                            <span class="resistance">160.20</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>Pivot:</span>
                                            <span class="pivot">159.35</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>Range:</span>
                                            <span>170 pips</span>
                                        </div>
                                    </div>
                                    <div class="analysis-item">
                                        <h3>Market Sentiment</h3>
                                        <div class="sentiment-bar">
                                            <div class="sentiment-fill bullish" style="width: 62%"></div>
                                        </div>
                                        <div class="sentiment-labels">
                                            <span>Bullish: <strong class="bullish">62%</strong></span>
                                            <span>Bearish: <strong class="bearish">38%</strong></span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>Volatility:</span>
                                            <span class="neutral">Medium</span>
                                        </div>
                                        <div class="indicator-row">
                                            <span>Trend:</span>
                                            <span class="bullish">Uptrend</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Additional Analysis Section -->
                        <section class="widget">
                            <div class="widget-header">
                                <h2>🔍 Detailed Analysis</h2>
                            </div>
                            <div class="widget-content">
                                <div class="detailed-analysis">
                                    <div class="analysis-card">
                                        <h4>Price Action</h4>
                                        <p>Currently trading above 50-day moving average. Bullish engulfing pattern detected on 4H timeframe.</p>
                                        <div class="analysis-tags">
                                            <span class="tag bullish">Bullish Pattern</span>
                                            <span class="tag neutral">Consolidation</span>
                                        </div>
                                    </div>
                                    <div class="analysis-card">
                                        <h4>Volume Analysis</h4>
                                        <p>Volume increasing on up moves, decreasing on down moves. Supports bullish bias.</p>
                                        <div class="analysis-tags">
                                            <span class="tag positive">Volume Confirmation</span>
                                        </div>
                                    </div>
                                    <div class="analysis-card">
                                        <h4>Risk Levels</h4>
                                        <p>Stop loss: 158.00, Take profit: 160.50. Risk/Reward ratio: 1:2.5</p>
                                        <div class="analysis-tags">
                                            <span class="tag good-rr">Good R:R</span>
                                            <span class="tag medium-risk">Medium Risk</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Economic Calendar -->
            <aside class="sidebar sidebar-right">
                <section class="widget">
                    <div class="widget-header">
                        <h2>📅 Economic Calendar</h2>
                        <span class="widget-badge today">Today</span>
                    </div>
                    <div class="widget-content">
                        <!-- TradingView Widget BEGIN -->
                        <div class="tradingview-widget-container">
                            <div class="tradingview-widget-container__widget"></div>
                            <div class="tradingview-widget-copyright">
                                <a href="https://www.tradingview.com/economic-calendar/"
                                    rel="noopener nofollow"
                                    target="_blank"><span class="blue-text">Economic Calendar</span></a>
                                <span class="trademark"> by TradingView</span>
                            </div>
                            <script type="text/javascript"
                                src="https://s3.tradingview.com/external-embedding/embed-widget-events.js"
                                async>
                                {
                                    "colorTheme": "dark",
                                    "isTransparent": false,
                                    "locale": "en",
                                    "countryFilter": "ar,au,br,ca,cn,fr,de,in,id,it,jp,kr,mx,ru,sa,za,tr,gb,us,eu",
                                    "importanceFilter": "-1,0,1",
                                    "width": "100%",
                                    "height": "100%"
                                }
                            </script>
                        </div>
                        <!-- TradingView Widget END -->
                    </div>
                </section>
            </aside>
        </main>
    </div>

    <script>
        // TradingView Chart Configuration
        let currentChart = null;
        let isChartLoading = false;

        function loadChart() {
            if (isChartLoading) return;

            isChartLoading = true;
            const symbol = document.getElementById("pair").value;
            const tf = document.getElementById("tf").value;
            const timeframeLabel = getTimeframeLabel(tf);

            // Update current pair display
            document.getElementById("current-pair").textContent = `${symbol} - ${timeframeLabel} Chart`;
            document.getElementById("current-tf").textContent = timeframeLabel;

            // Show loading state
            const chartContainer = document.getElementById("chart");
            chartContainer.innerHTML = `
        <div class="chart-loading">
            <div class="loading-spinner"></div>
            <p>Loading ${symbol} chart...</p>
        </div>
    `;

            // Clear previous chart after a short delay
            setTimeout(() => {
                chartContainer.innerHTML = "";

                // Load new chart
                currentChart = new TradingView.widget({
                    container_id: "chart",
                    width: "100%",
                    height: "100%",
                    symbol: symbol,
                    interval: tf,
                    timezone: "Etc/UTC",
                    theme: "dark",
                    style: "1",
                    locale: "en",
                    enable_publishing: false,
                    withdateranges: true,
                    hide_side_toolbar: false,
                    allow_symbol_change: true,
                    save_image: true,
                    details: true,
                    hotlist: true,
                    calendar: false,
                    studies: [
                        "RSI@tv-basicstudies",
                        "MACD@tv-basicstudies",
                        "Volume@tv-basicstudies",
                        "MovingAverage@tv-basicstudies",
                        "BollingerBands@tv-basicstudies"
                    ],
                    show_popup_button: true,
                    popup_width: "1000",
                    popup_height: "650",
                    toolbar_bg: "#1a1f2e",
                    indicator_width: 1,
                    disabled_features: ["use_localstorage_for_settings"],
                    enabled_features: ["study_templates", "side_toolbar_in_fullscreen"],
                    overrides: {
                        "paneProperties.background": "#1a1f2e",
                        "paneProperties.vertGridProperties.color": "#2a3245",
                        "paneProperties.horzGridProperties.color": "#2a3245",
                        "volumePaneSize": "medium"
                    },
                    studies_overrides: {
                        "volume.volume.color.0": "#ef4444",
                        "volume.volume.color.1": "#10b981",
                        "volume.volume.transparency": 70
                    }
                });

                isChartLoading = false;

                // Update analysis with dynamic data
                updateAnalysis(symbol, timeframeLabel);

                // Update header with market status
                updateMarketStatus(symbol);
            }, 100);
        }

        function getTimeframeLabel(value) {
            const labels = {
                1: "1min",
                5: "5min",
                15: "15min",
                30: "30min",
                60: "1H",
                120: "2H",
                240: "4H",
                D: "Daily",
                W: "Weekly",
                M: "Monthly"
            };
            return labels[value] || value;
        }

        function updateAnalysis(symbol, timeframe) {
            // Simulate dynamic analysis data
            const analysisData = {
                EURJPY: {
                    rsi: Math.random() * 30 + 35,
                    macd: Math.random() > 0.5 ? "Bullish" : "Bearish",
                    volume: Math.random() * 20 + 5,
                    support: (158 + Math.random() * 2).toFixed(2),
                    resistance: (160 + Math.random() * 2).toFixed(2),
                    bullish: Math.floor(Math.random() * 30 + 50)
                },
                EURUSD: {
                    rsi: Math.random() * 30 + 40,
                    macd: Math.random() > 0.5 ? "Bullish" : "Bearish",
                    volume: Math.random() * 25 + 5,
                    support: (1.08 + Math.random() * 0.01).toFixed(4),
                    resistance: (1.10 + Math.random() * 0.01).toFixed(4),
                    bullish: Math.floor(Math.random() * 30 + 45)
                },
                GBPUSD: {
                    rsi: Math.random() * 30 + 35,
                    macd: Math.random() > 0.5 ? "Bullish" : "Bearish",
                    volume: Math.random() * 15 + 5,
                    support: (1.26 + Math.random() * 0.01).toFixed(4),
                    resistance: (1.28 + Math.random() * 0.01).toFixed(4),
                    bullish: Math.floor(Math.random() * 30 + 40)
                },
                USDJPY: {
                    rsi: Math.random() * 30 + 40,
                    macd: Math.random() > 0.5 ? "Bullish" : "Bearish",
                    volume: Math.random() * 20 + 5,
                    support: (147 + Math.random() * 1).toFixed(2),
                    resistance: (149 + Math.random() * 1).toFixed(2),
                    bullish: Math.floor(Math.random() * 30 + 55)
                },
                BTCUSD: {
                    rsi: Math.random() * 30 + 45,
                    macd: Math.random() > 0.5 ? "Bullish" : "Bearish",
                    volume: Math.random() * 40 + 10,
                    support: (60000 + Math.random() * 5000).toFixed(0),
                    resistance: (65000 + Math.random() * 5000).toFixed(0),
                    bullish: Math.floor(Math.random() * 30 + 60)
                }
            };

            const data = analysisData[symbol] || analysisData.EURJPY;
            const bearish = 100 - data.bullish;
            const pivot = ((parseFloat(data.support) + parseFloat(data.resistance)) / 2).toFixed(2);
            const volumeChange = data.volume.toFixed(1);
            const rsiStatus = data.rsi > 70 ? "overbought" : data.rsi < 30 ? "oversold" : "neutral";

            const analysisContainer = document.querySelector('.analysis-placeholder');
            if (analysisContainer) {
                analysisContainer.innerHTML = `
            <div class="analysis-item">
                <h3>Technical Indicators (${timeframe})</h3>
                <p>RSI: <span class="${rsiStatus}">${data.rsi.toFixed(1)} (${rsiStatus})</span></p>
                <p>MACD: <span class="${data.macd.toLowerCase()}">${data.macd}</span></p>
                <p>Volume: <span class="positive">↑ ${volumeChange}%</span></p>
            </div>
            <div class="analysis-item">
                <h3>Support & Resistance</h3>
                <p>Support: <span class="support">${data.support}</span></p>
                <p>Resistance: <span class="resistance">${data.resistance}</span></p>
                <p>Pivot: <span class="pivot">${pivot}</span></p>
            </div>
            <div class="analysis-item">
                <h3>Market Sentiment</h3>
                <p>Bullish: <span class="bullish">${data.bullish}%</span></p>
                <p>Bearish: <span class="bearish">${bearish}%</span></p>
                <p>Trend: <span class="${data.rsi > 50 ? 'bullish' : 'bearish'}">
                    ${data.rsi > 50 ? 'Uptrend' : 'Downtrend'}
                </span></p>
            </div>
        `;
            }
        }

        function updateMarketStatus(symbol) {
            const marketOpen = document.querySelector('.market-open');
            const marketStatus = document.querySelector('.market-status span:last-child');

            // Simulate market hours check
            const now = new Date();
            const hour = now.getUTCHours();
            const isOpen = (hour >= 0 && hour < 21); // Forex market hours

            if (marketOpen) {
                marketOpen.style.backgroundColor = isOpen ? 'var(--accent-green)' : 'var(--accent-red)';
            }

            if (marketStatus) {
                marketStatus.textContent = isOpen ? 'Markets Open' : 'Markets Closed';
            }
        }

        // Initialize chart on page load
        document.addEventListener('DOMContentLoaded', () => {
            // Add loading styles
            const style = document.createElement('style');
            style.textContent = `
        .chart-loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: var(--text-secondary);
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-color);
            border-top-color: var(--accent-blue);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    `;
            document.head.appendChild(style);

            // Load initial chart
            loadChart();

            // Add event listeners
            document.getElementById('pair').addEventListener('change', loadChart);
            document.getElementById('tf').addEventListener('change', loadChart);

            // Add click handler to refresh button
            document.querySelector('.refresh-btn').addEventListener('click', loadChart);

            // Auto-refresh every 2 minutes
            setInterval(() => {
                if (document.visibilityState === 'visible' && !isChartLoading) {
                    loadChart();
                }
            }, 120000);

            // Update market status every minute
            setInterval(() => {
                const symbol = document.getElementById("pair").value;
                updateMarketStatus(symbol);
            }, 60000);
        });

        // Handle window resize
        window.addEventListener('resize', () => {
            // TradingView charts handle their own resizing
            if (currentChart && typeof currentChart.onResize === 'function') {
                setTimeout(() => currentChart.onResize(), 100);
            }
        });
    </script>
</body>

</html>