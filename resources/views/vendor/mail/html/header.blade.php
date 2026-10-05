@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block; color: #ffffff;">
{!! $slot !!}
</a>
</td>
</tr>
