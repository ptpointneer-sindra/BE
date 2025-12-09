<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Status Ticket Berubah</title>
</head>
<body>
    <h2>Status Tiket Telah Berubah</h2>
    <p><strong>Judul:</strong> {{ $ticket->title }}</p>
    <p><strong>Deskripsi:</strong> {{ $ticket->description }}</p>

    <p>
        Status tiket berubah dari 
        <strong>{{ ucfirst($oldStatus) }}</strong> 
        menjadi 
        <strong>{{ ucfirst($newStatus) }}</strong>.
    </p>

    <p>Silakan login ke sistem untuk melihat detailnya.</p>

    <br>
    <p>Terima kasih,</p>
    <p><em>Tim Helpdesk</em></p>
</body>
</html>
