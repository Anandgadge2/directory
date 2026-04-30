@props(['guardId', 'name'])

<a href="#" class="guard-name-link" data-guard-id="{{ $guardId }}">
    {{ \App\Helpers\FormatHelper::formatName($name) }}
</a>

