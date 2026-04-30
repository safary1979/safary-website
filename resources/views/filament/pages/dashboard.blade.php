<x-filament-panels::page>
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
