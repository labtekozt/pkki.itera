<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your Email - PKKI ITERA</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            max-width: 500px;
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: #B82132;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        h1 {
            color: #333;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        .btn {
            background: #B82132;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #a01d2a;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">P</div>
        <h1>Verifikasi Email Anda</h1>
        
        @if (session('message'))
            <div class="success">
                {{ session('message') }}
            </div>
        @endif
        
        <p>
            Terima kasih telah mendaftar di <strong>PKKI ITERA</strong>. 
            Untuk melengkapi registrasi Anda, silakan klik tombol di bawah ini 
            untuk memverifikasi alamat email Anda.
        </p>
        
        <p>
            Jika Anda tidak menerima email verifikasi, silakan klik tombol 
            "Kirim Ulang" di bawah ini.
        </p>
        
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn">
                📧 Kirim Ulang Email Verifikasi
            </button>
        </form>
        
        <p style="margin-top: 2rem; font-size: 0.9rem; color: #888;">
            Setelah email terverifikasi, Anda dapat mengakses panel admin di: 
            <br>
            <a href="/admin" style="color: #B82132;">hki.proyekai.com/admin</a>
        </p>
    </div>
</body>
</html>
