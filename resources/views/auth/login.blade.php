<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion — Track Coach</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, sans-serif; margin: 0; min-height: 100vh; display: grid; place-items: center; background: #0f172a; color: #e2e8f0; }
        .card { background: #1e293b; padding: 2rem; border-radius: 12px; width: 100%; max-width: 380px; box-shadow: 0 25px 50px -12px rgba(0,0,0,.5); }
        h1 { margin: 0 0 1.5rem; font-size: 1.25rem; font-weight: 600; }
        label { display: block; font-size: 0.875rem; margin-bottom: 0.35rem; color: #94a3b8; }
        input { width: 100%; padding: 0.65rem 0.75rem; border-radius: 8px; border: 1px solid #334155; background: #0f172a; color: #f8fafc; margin-bottom: 1rem; }
        input:focus { outline: 2px solid #3b82f6; border-color: transparent; }
        button { width: 100%; padding: 0.75rem; border: 0; border-radius: 8px; background: #2563eb; color: white; font-weight: 600; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .error { color: #f87171; font-size: 0.875rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
<div class="card">
    <p style="margin:0 0 1rem;"><a href="{{ route('home') }}" style="color:#94a3b8;font-size:0.875rem;text-decoration:none;">← Retour à l'accueil</a></p>
    <h1>Track Coach</h1>
    @if (session('success'))
        <p style="color:#4ade80;font-size:0.875rem;margin-bottom:1rem;">{{ session('success') }}</p>
    @endif
    @if ($errors->any())
        <p class="error">{{ $errors->first() }}</p>
    @endif
    <form method="post" action="{{ route('login') }}">
        @csrf
        <label for="email">E-mail</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">

        <label for="password">Mot de passe</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">

        <button type="submit">Se connecter</button>
    </form>
</div>
</body>
</html>
