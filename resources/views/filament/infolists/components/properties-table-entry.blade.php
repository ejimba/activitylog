<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $rows = $getState();
    @endphp

    <style>
        .properties-table-wrapper {
            --border-color: rgb(229 231 235);
            --bg-header: rgb(249 250 251);
            --bg-body: white;
            --bg-hover: rgb(249 250 251);
            --text-header: rgb(17 24 39);
            --text-body: rgb(55 65 81);
            --text-field: rgb(17 24 39);
            --text-muted: rgb(156 163 175);
            --bg-code: rgb(249 250 251);
        }

        .dark .properties-table-wrapper {
            --border-color: rgb(55 65 81);
            --bg-header: rgba(31 41 55 / 0.5);
            --bg-body: rgb(17 24 39);
            --bg-hover: rgba(31 41 55 / 0.3);
            --text-header: rgb(243 244 246);
            --text-body: rgb(209 213 219);
            --text-field: rgb(243 244 246);
            --text-muted: rgb(107 114 128);
            --bg-code: rgb(31 41 55);
        }
    </style>

    @if ($rows && count($rows) > 0)
        <div class="properties-table-wrapper" style="overflow: hidden; border-radius: 0.75rem; border: 1px solid var(--border-color); box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05);">
            <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                <thead style="background-color: var(--bg-header);">
                    <tr>
                        <th style="padding: 0.875rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--text-header); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color);">
                            Field
                        </th>
                        <th style="padding: 0.875rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--text-header); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); border-left: 1px solid var(--border-color);">
                            Old Value
                        </th>
                        <th style="padding: 0.875rem 1.5rem; text-align: left; font-size: 0.75rem; font-weight: 600; color: var(--text-header); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--border-color); border-left: 1px solid var(--border-color);">
                            New Value
                        </th>
                    </tr>
                </thead>
                <tbody style="background-color: var(--bg-body);">
                    @foreach ($rows as $row)
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 150ms;" onmouseover="this.style.backgroundColor='var(--bg-hover)'" onmouseout="this.style.backgroundColor='transparent'">
                            <td style="padding: 1rem 1.5rem; font-weight: 500; color: var(--text-field); vertical-align: top; white-space: nowrap;">
                                {{ $row['field'] }}
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-body); vertical-align: top; border-left: 1px solid var(--border-color);">
                                @if (isset($row['old']) && $row['old'] !== '' && $row['old'] !== null)
                                    @if (is_array($row['old']))
                                        <pre style="font-size: 0.75rem; background-color: var(--bg-code); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-family: ui-monospace, monospace; border: 1px solid var(--border-color);">{{ json_encode($row['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span style="word-break: break-word;">{{ $row['old'] }}</span>
                                    @endif
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                            <td style="padding: 1rem 1.5rem; color: var(--text-body); vertical-align: top; border-left: 1px solid var(--border-color);">
                                @if (isset($row['new']) && $row['new'] !== '' && $row['new'] !== null)
                                    @if (is_array($row['new']))
                                        <pre style="font-size: 0.75rem; background-color: var(--bg-code); padding: 0.75rem; border-radius: 0.5rem; overflow-x: auto; font-family: ui-monospace, monospace; border: 1px solid var(--border-color);">{{ json_encode($row['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        <span style="word-break: break-word;">{{ $row['new'] }}</span>
                                    @endif
                                @else
                                    <span style="color: var(--text-muted);">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-dynamic-component>
