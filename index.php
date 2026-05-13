<?php
// index.php — Club Info_Tech
// Application web sur une seule page (comme l'exemple de l'enseignante)

$xml_path = $_SERVER['DOCUMENT_ROOT'] . '/Club_InfoTech/club.xml';

// ── Fonction tri scores décroissant ──
function compare_scores_desc($a, $b) {
    if ($a['score'] === $b['score']) return 0;
    return ($a['score'] < $b['score']) ? 1 : -1;
}

$message = '';
$erreur  = '';

// ── Traitement formulaire Inscription ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'inscription') {
    $concoursId = trim($_POST['concours']    ?? '');
    $membreId   = trim($_POST['membre']      ?? '');
    $complexite = intval($_POST['complexite'] ?? 0);
    $temps      = intval($_POST['temps']      ?? 0);

    if (!$concoursId || !$membreId) {
        $erreur = "Veuillez sélectionner un concours et un membre.";
    } elseif ($complexite < 0 || $complexite > 100) {
        $erreur = "La complexité doit être entre 0 et 100.";
    } elseif ($temps <= 0) {
        $erreur = "Le temps d'exécution doit être positif.";
    } else {
        if (file_exists($xml_path)) {
            $xml = simplexml_load_file($xml_path);

            // Vérification catégorie
            $catMembre = ''; $catConcours = '';
            foreach ($xml->membres->membre as $m) {
                if ((string)$m['id'] === $membreId) { $catMembre = (string)$m['categorieRef']; break; }
            }

            $targetConcours = null;
            foreach ($xml->concours->concours as $c) {
                if ((string)$c['id'] === $concoursId) { $targetConcours = $c; $catConcours = (string)$c['categorieRef']; break; }
            }

            if ($catMembre !== $catConcours) {
                $erreur = "❌ Ce membre appartient à une catégorie différente de ce concours.";
            } else {
                // Vérifier doublon
                $dejaInscrit = false;
                foreach ($targetConcours->participants->participant as $p) {
                    if ((string)$p['membreRef'] === $membreId) { $dejaInscrit = true; break; }
                }
                if ($dejaInscrit) {
                    $erreur = "⚠️ Ce membre est déjà inscrit à ce concours.";
                } else {
                    $participant = $targetConcours->participants->addChild('participant');
                    $participant->addAttribute('membreRef', $membreId);
                    $participant->addChild('complexite',      $complexite);
                    $participant->addChild('tempsExecution',  $temps);

                    $dom = dom_import_simplexml($xml)->ownerDocument;
                    $dom->formatOutput = true;
                    $dom->save($xml_path);
                    $message = "✅ Inscription réussie et sauvegardée !";
                }
            }
        }
    }
}

// ── Chargement XML ──
$xml = file_exists($xml_path) ? simplexml_load_file($xml_path) : null;

// ── Paramètre vue concours ──
$viewConcours = $_GET['view_concours'] ?? '';
$resultats    = [];
$concoursInfo = null;

if ($viewConcours && $xml) {
    foreach ($xml->concours->concours as $c) {
        if ((string)$c['id'] === $viewConcours) {
            $concoursInfo = $c;
            $coef = (float)$c['coefficient'];
            foreach ($c->participants->participant as $p) {
                $ref = (string)$p['membreRef'];
                $nom = ''; $prenom = '';
                foreach ($xml->membres->membre as $m) {
    if ((string)$m['id'] === $ref) { $nom = (string)$m->nom; $prenom = (string)$m->prenom; break; }
}
                $score = round(((int)$p->complexite + (int)$p->tempsExecution) * $coef, 2);
                $resultats[] = ['nom' => $nom, 'prenom' => $prenom, 'comp' => (int)$p->complexite, 'temps' => (int)$p->tempsExecution, 'score' => $score];
            }
            usort($resultats, 'compare_scores_desc');
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Club Info_Tech - Gestion des Concours</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page-header">
  <h1>🏆 Club Info_Tech - Gestion des Concours</h1>
</div>

<div class="container">

  <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
  <?php endif; ?>
  <?php if ($erreur): ?>
    <div class="alert alert-error"><?= $erreur ?></div>
  <?php endif; ?>

  <!-- ══════════════════════════════════════
       SECTION 1 — Liste des concours
       ══════════════════════════════════════ -->
  <div class="section-card">
    <h2>📅 Liste des Concours Disponibles</h2>
    <?php if ($xml): ?>
    <table class="table">
      <thead>
        <tr>
          <th>Titre</th>
          <th>Date</th>
          <th>Catégorie</th>
          <th>Coefficient</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($xml->concours->concours as $c):
          $catId = (string)$c['categorieRef'];
          $catLib = '';
          foreach ($xml->categories->categorie as $cat) {
            if ((string)$cat['id'] === $catId) { $catLib = (string)$cat['libelle']; break; }
          }
        ?>
        <tr>
          <td><?= htmlspecialchars((string)$c->titre) ?></td>
          <td><?= htmlspecialchars((string)$c['date']) ?></td>
          <td><span class="badge-cat"><?= htmlspecialchars($catLib) ?></span></td>
          <td><?= htmlspecialchars((string)$c['coefficient']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════
       SECTION 2 — Résultats
       ══════════════════════════════════════ -->
  <div class="section-card" id="results-anchor">
    <h2>🥇 Résultats des Concours</h2>
    <form method="GET" action="index.php" class="inline-form">
      <select name="view_concours">
        <option value="">Sélectionnez...</option>
        <?php if ($xml): foreach ($xml->concours->concours as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $viewConcours === (string)$c['id'] ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$c->titre) ?>
        </option>
        <?php endforeach; endif; ?>
      </select>
      <button type="submit" class="btn-primary">Afficher résultats</button>
    </form>

    <?php if ($resultats && $concoursInfo): ?>
    <table class="table" style="margin-top:20px">
      <thead>
        <tr><th>Rang</th><th>Participant</th><th>Complexité</th><th>Temps (ms)</th><th>Score</th></tr>
      </thead>
      <tbody>
        <?php $maxScore = $resultats[0]['score']; ?>
        <?php foreach ($resultats as $i => $r): ?>
        <tr class="<?= $r['score'] === $maxScore ? 'winner' : '' ?>">
          <td><?= $i+1 ?> <?= $r['score'] === $maxScore ? '🏆' : '' ?></td>
          <td><?= htmlspecialchars($r['nom'].' '.$r['prenom']) ?></td>
          <td><?= $r['comp'] ?></td>
          <td><?= $r['temps'] ?></td>
          <td><strong><?= number_format($r['score'], 2) ?></strong></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

  <!-- ══════════════════════════════════════
       SECTION 3 — Nouvelle inscription
       ══════════════════════════════════════ -->
  <div class="section-card">
    <h2>📝 Nouvelle Inscription</h2>
    <form method="POST" action="index.php">
      <input type="hidden" name="action" value="inscription">

      <div class="form-row">
        <div class="form-group">
          <label>Concours:</label>
          <select name="concours" required>
            <option value="">Sélectionnez...</option>
            <?php if ($xml): foreach ($xml->concours->concours as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars((string)$c->titre) ?></option>
            <?php endforeach; endif; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Membre:</label>
          <select name="membre" required>
            <option value="">Sélectionnez...</option>
            <?php if ($xml): foreach ($xml->membres->membre as $m): ?>
            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['prenom'].' '.$m['nom']) ?> (<?= $m['id'] ?>)</option>
            <?php endforeach; endif; ?>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Complexité de l'algo (0-100):</label>
          <input type="number" name="complexite" min="0" max="100" placeholder="ex: 85" required>
        </div>
        <div class="form-group">
          <label>Temps exécution (ms):</label>
          <input type="number" name="temps" min="1" placeholder="ex: 120" required>
        </div>
      </div>

      <button type="submit" class="btn-inscrit">S'inscrire</button>
    </form>
  </div>

  <!-- ══════════════════════════════════════
       SECTION 4 — Membres par catégorie
       ══════════════════════════════════════ -->
  <div class="section-card">
    <h2>👥 Membres par Catégorie</h2>
    <?php if ($xml): foreach ($xml->categories->categorie as $cat):
      $catId  = (string)$cat['id'];
      $catLib = (string)$cat['libelle'];
      $membres = [];
      foreach ($xml->membres->membre as $m) {
        if ((string)$m['categorieRef'] === $catId)
          $membres[] = $m;
      }
      if (empty($membres)) continue;
    ?>
    <div class="cat-block">
      <h3><span class="badge-cat"><?= htmlspecialchars($catLib) ?></span></h3>
      <table class="table">
        <thead>
          <tr><th>ID</th><th>Nom</th><th>Prénom</th><th>Email</th></tr>
        </thead>
        <tbody>
          <?php foreach ($membres as $m): ?>
          <tr>
            <td><?= htmlspecialchars((string)$m['id']) ?></td>
            <td><?= htmlspecialchars((string)$m->nom) ?></td>
            <td><?= htmlspecialchars((string)$m->prenom) ?></td>
            <td><?= htmlspecialchars((string)$m->email) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endforeach; endif; ?>
  </div>

</div><!-- /container -->

<div class="page-footer">
  <p>© 2026 Club Info_Tech — Université de Skikda | Département Informatique</p>
</div>

</body>
</html>