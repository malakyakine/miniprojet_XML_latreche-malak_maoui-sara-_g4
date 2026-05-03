(: Q1 — Liste des membres :)
<membres>{
  for $m in db:open("club")//membre
  let $cat := doc("club.xml")//categorie[@id = $m/@categorieRef]
  return
    <membre id="{$m/@id}">
      <nomComplet>{$m/prenom/text()} {$m/nom/text()}</nomComplet>
      <email>{$m/email/text()}</email>
      <categorie>{$cat/@libelle/string()}</categorie>
    </membre>
}</membres>
,
(: Q2 — Liste des concours :)
<concours_liste>{
  for $c in doc("club.xml")//concours[@id]
  let $cat := doc("club.xml")//categorie[@id = $c/@categorieRef]
  order by $c/@date ascending
  return
    <concours id="{$c/@id}">
      <titre>{$c/titre/text()}</titre>
      <date>{$c/@date/string()}</date>
      <coefficient>{$c/@coefficient/string()}</coefficient>
      <categorie>{$cat/@libelle/string()}</categorie>
    </concours>
}</concours_liste>
,
(: Q3 — Calcul des scores :)
<resultats>{
  for $c in doc("club.xml")//concours[@id]
  for $p in $c//participant
  let $m     := doc("club.xml")//membre[@id = $p/@membreRef]
  let $comp  := xs:integer($p/complexite)
  let $temps := xs:integer($p/tempsExecution)
  let $coef  := xs:decimal($c/@coefficient)
  let $score := round(($comp + $temps) * $coef * 100) div 100
  return
    <resultat>
      <concours>{$c/titre/text()}</concours>
      <participant>{$m/prenom/text()} {$m/nom/text()}</participant>
      <complexite>{$comp}</complexite>
      <tempsExecution>{$temps}</tempsExecution>
      <score>{$score}</score>
    </resultat>
}</resultats>
,
(: Q4 — Vainqueur de chaque concours :)
<vainqueurs>{
  for $c in doc("club.xml")//concours[@id]
  let $coef := xs:decimal($c/@coefficient)
  let $scores := for $p in $c//participant
                 return ($p/complexite + $p/tempsExecution) * $coef
  let $maxScore := max($scores)
  return
    <concours id="{$c/@id}" titre="{$c/titre}">{
      for $p in $c//participant
      let $m     := doc("club.xml")//membre[@id = $p/@membreRef]
      let $score := ($p/complexite + $p/tempsExecution) * $coef
      where $score = $maxScore
      return
        <vainqueur>
          <nom>{$m/nom/text()}</nom>
          <prenom>{$m/prenom/text()}</prenom>
          <score>{$score}</score>
        </vainqueur>
    }</concours>
}</vainqueurs>
,
(: Q5 — Membres d'une catégorie :)
let $categorie := "Intelligence Artificielle"
return
<membres_categorie categorie="{$categorie}">{
  let $catId := doc("club.xml")//categorie[@libelle = $categorie]/@id
  for $m in doc("club.xml")//membre[@categorieRef = $catId]
  order by $m/nom ascending, $m/prenom ascending
  return
    <membre id="{$m/@id}">
      <nom>{$m/nom/text()}</nom>
      <prenom>{$m/prenom/text()}</prenom>
      <email>{$m/email/text()}</email>
    </membre>
}</membres_categorie>