@props(['label', 'name', 'type' => 'text', 'value' => null, 'required' => false, 'hint' => null, 'options' => null, 'placeholder' => null])
@php $id = 'f_'.str_replace(['[', ']', '.'], '_', $name); $dot = str_replace(['[', ']'], ['.', ''], $name); @endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="form-label">{{ $label }} @if ($required)<span class="text-rose-500">*</span>@endif</label>
    @if ($options !== null)
        <select id="{{ $id }}" name="{{ $name }}" class="form-input" @if ($required) required @endif {{ $attributes->except('class') }}>
            <option value="">— Pilih —</option>
            @foreach ($options as $k => $v)
                <option value="{{ $k }}" @selected((string) old($dot, $value) === (string) $k)>{{ $v }}</option>
            @endforeach
        </select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $id }}" name="{{ $name }}" rows="3" class="form-input" placeholder="{{ $placeholder }}" @if ($required) required @endif {{ $attributes->except('class') }}>{{ old($dot, $value) }}</textarea>
    @else
        <input id="{{ $id }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($dot, $value) }}" class="form-input" placeholder="{{ $placeholder }}" @if ($required) required @endif {{ $attributes->except('class') }}>
    @endif
    @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($dot)<p class="form-error">{{ $message }}</p>@enderror
</div>
