<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Abonnement bientôt expiré</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; color: #333; }
    .container { max-width: 520px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; }
    .header { background: #1a1a2e; padding: 24px 32px; }
    .header h1 { color: #fff; margin: 0; font-size: 20px; }
    .body { padding: 32px; }
    .body p { line-height: 1.6; margin: 0 0 16px; }
    .alert { background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; padding: 16px; margin: 20px 0; }
    .alert strong { color: #856404; }
    .btn { display: inline-block; background: #4f46e5; color: #fff; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 8px; }
    .footer { padding: 20px 32px; border-top: 1px solid #eee; font-size: 12px; color: #888; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>CouturePro</h1>
    </div>

    <div class="body">
      <p>Bonjour,</p>

      <div class="alert">
        <strong>
          @if ($jours === 1)
            ⚠️ Votre abonnement {{ $niveau_label }} expire demain ({{ $expire_at }}).
          @else
            ⚠️ Votre abonnement {{ $niveau_label }} expire dans {{ $jours }} jours ({{ $expire_at }}).
          @endif
        </strong>
      </div>

      <p>
        Pour continuer à utiliser toutes les fonctionnalités de CouturePro sans interruption,
        renouvelez votre abonnement dès maintenant.
      </p>

      <a href="{{ $app_url }}/parametres" class="btn">Renouveler mon abonnement</a>

      <p style="margin-top: 24px; font-size: 13px; color: #666;">
        Si vous avez des questions, contactez notre support via l'application.
      </p>
    </div>

    <div class="footer">
      Vous recevez cet email car vous êtes abonné à CouturePro.
    </div>
  </div>
</body>
</html>
