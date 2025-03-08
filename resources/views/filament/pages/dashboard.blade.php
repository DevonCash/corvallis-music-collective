<x-filament-panels::page>
    <x-filament-widgets::widgets
        :widgets="$this->getHeaderWidgets()"
        :columns="$this->getHeaderWidgetsColumns()"
        :data="$this->getWidgetsData()"
    />

    <x-filament-widgets::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getWidgetsColumns()"
        :data="$this->getWidgetsData()"
    />

    <x-filament-widgets::widgets
        :widgets="$this->getFooterWidgets()"
        :columns="$this->getFooterWidgetsColumns()"
        :data="$this->getWidgetsData()"
    />
</x-filament-panels::page> 