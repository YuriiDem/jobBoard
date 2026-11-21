@props(['rank', 'name', 'logo', 'count', 'maximum'])
<tr>
    <td class="font-mono tabular-nums">{{ $rank }}</td>
    <th scope="row"><span class="flex items-center gap-2"><x-board.company-logo :name="$name" :src="$logo" :size="32" /> {{ $name }}</span></th>
    <td class="text-right font-mono tabular-nums">{{ number_format($count) }}</td>
    <td><progress aria-label="{{ $name }} jobs relative to the leader" class="progress progress-primary w-full" value="{{ $count }}" max="{{ max(1, $maximum) }}"></progress></td>
</tr>
