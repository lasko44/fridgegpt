<!-- resources/views/errors/419.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Oops - 419 Error</title>
    <style>
        body { background: #f3f4f6; color: #1e293b; font-family: 'Inter', sans-serif; }
        .modal {
            background: #fff;
            box-shadow: 0 4px 24px rgba(37,99,235,0.08);
            padding: 2.5rem 2rem;
            border-radius: 16px;
            max-width: 420px;
            margin: 10% auto;
            text-align: center;
        }
        .btn {
            background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(37,99,235,0.12);
            transition: background 0.2s;
        }
        .btn:hover {
            background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 100%);
        }
    </style>
</head>
<body>
<div class="modal">
    <h2 style="color: #dc2626;">Oops</h2>
    <p>Something went wrong. Try reloading the page.</p>
</div>
</body>
</html>