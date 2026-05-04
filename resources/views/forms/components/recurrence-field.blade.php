<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div class="space-y-4">
        @foreach ($getChildComponents() as $component)
            {{ $component }}
        @endforeach
    </div>
</x-dynamic-component>
