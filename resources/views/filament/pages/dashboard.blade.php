<x-filament-panels::page>
    <style>
        @media (max-width: 640px) {
            .dashboard-stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: .4rem !important;
            }

            .dashboard-stats-grid > div {
                min-width: 0;
                padding: .5rem .6rem !important;
            }

            .dashboard-session-bar {
                gap: .5rem !important;
            }

            .dashboard-session-bar button {
                margin-left: 0 !important;
            }

            .active-bots-heading {
                align-items: flex-start !important;
                flex-wrap: wrap !important;
                gap: .5rem !important;
            }

            .active-bots-heading-controls {
                width: 100% !important;
                justify-content: flex-start !important;
                flex-wrap: wrap !important;
            }

            .active-bot-header {
                grid-template-columns: minmax(0, 1fr) auto !important;
                gap: .5rem !important;
                padding: .65rem .7rem !important;
            }

            .active-bot-price {
                grid-column: 1 / -1;
                grid-row: 2;
                min-width: 0;
            }

            .active-bot-pnl {
                grid-column: 2;
                grid-row: 1;
                min-width: 90px !important;
            }

            .active-bot-details {
                grid-template-columns: 1fr !important;
                gap: .75rem !important;
            }

            .active-bot-details > div + div {
                border-top: 1px solid #1f2937;
                padding-top: .7rem;
            }

            .active-bot-actions {
                gap: .35rem !important;
            }

            .active-bot-actions button,
            .active-bot-actions a {
                min-height: 2.25rem;
            }

            .dca-ladder-panel {
                overflow-x: auto;
            }

            .dca-detail-lane {
                min-width: 480px;
            }
        }

        @media (max-width: 400px) {
            .dashboard-stats-grid {
                grid-template-columns: 1fr !important;
            }

            .active-bot-header {
                padding: .6rem !important;
            }
        }
    </style>

    @if (count($this->getHeaderWidgets()) > 0)
        <x-filament-widgets::widgets
            :columns="$this->getHeaderWidgetsColumns()"
            :data="$this->getWidgetData()"
            :widgets="$this->getHeaderWidgets()"
        />
    @endif

    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="$this->getWidgetData()"
        :widgets="$this->getWidgets()"
    />

    @if (count($this->getFooterWidgets()) > 0)
        <x-filament-widgets::widgets
            :columns="$this->getFooterWidgetsColumns()"
            :data="$this->getWidgetData()"
            :widgets="$this->getFooterWidgets()"
        />
    @endif
</x-filament-panels::page>
