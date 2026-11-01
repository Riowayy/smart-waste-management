@props(['label', 'name', 'type' => 'text', 'autocomplete' => null, 'required' => true])
@php($fieldId = $attributes->get('id', $name))
<div>
    <label for="{{ $fieldId }}" class="mb-1.5 block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input
        id="{{ $fieldId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name) }}"
        @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @required($required)
        @if($errors->has($name)) aria-invalid="true" aria-describedby="{{ $fieldId }}-error" @endif
        {{ $attributes->except('id')->merge(['class' => 'field-control w-full']) }}
    >
    @error($name)
        <p id="{{ $fieldId }}-error" class="mt-1.5 text-sm text-red-700" role="alert">{{ $message }}</p>
    @enderror
</div>
