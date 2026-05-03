(: INSERTION — إضافة عضو جديد :)
insert node
  <membre id="M011" categorieRef="C2">
    <nom>Zerrouk</nom>
    <prenom>Lyna</prenom>
    <email>l.zerrouk@club.dz</email>
  </membre>
into doc("club.xml")//membres
,
(: MODIFICATION — تغيير coefficient CO2 :)
replace value of node
  doc("club.xml")//concours[@id="CO2"]/@coefficient
with "2.0"
,
(: SUPPRESSION — حذف مشارك M003 من CO1 :)
delete node
  doc("club.xml")//concours[@id="CO1"]//participant[@membreRef="M003"]