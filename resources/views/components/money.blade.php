@props(['value' => 0])
<span {{ $attributes }}>Rp{{ number_format($value, 0, ',', '.') }}</span>
