<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: auto;
        }
        h1 {
            color: #333333;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #dddddd;
        }
        .label {
            font-weight: bold;
            color: #555555;
        }
        .value {
            color: #333333;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #777777;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Permintaan Kontak Baru</h1>
        <p>Permintaan kontak baru telah dikirimkan:</p>
        <table>
            <tr>
                <td class="label">Nama Depan:</td>
                <td class="value">{{ $contact->firstname }}</td>
            </tr>
            <tr>
                <td class="label">Nama Belakang:</td>
                <td class="value">{{ $contact->lastname }}</td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td class="value">{{ $contact->email }}</td>
            </tr>
            <tr>
                <td class="label">Telepon:</td>
                <td class="value">{{ $contact->phone }}</td>
            </tr>
            <tr>
                <td class="label">Perusahaan:</td>
                <td class="value">{{ $contact->company }}</td>
            </tr>
            <tr>
                <td class="label">Judul:</td>
                <td class="value">{{ $contact->title }}</td>
            </tr>
            <tr>
                <td class="label">Pesan:</td>
                <td class="value">{{ $contact->message }}</td>
            </tr>
        </table>
        <div class="footer">
            <p>Email ini dikirim dari formulir kontak di situs web Anda.</p>
        </div>
    </div>
</body>
</html>
