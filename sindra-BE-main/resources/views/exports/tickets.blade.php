<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Code</th>
            <th>Judul</th>
            <th>Status</th>
            <th>Priority</th>
            <th>Category</th>
            <th>Reporter</th>
            <th>Created</th>
            <th>Deadline</th>
            <th>Resolved At</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($tickets as $t)
        <tr>
            <td>{{ $t->code }}</td>
            <td>{{ $t->title }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ $t->priority }}</td>
            <td>{{ optional($t->category)->name }}</td>
            <td>{{ optional($t->reporter)->name }}</td>
            <td>{{ $t->created_at }}</td>
            <td>{{ $t->deadline_at }}</td>
            <td>{{ $t->resolved_at }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
