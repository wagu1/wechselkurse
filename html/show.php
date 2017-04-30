<?php
  /*
   * Anzeige der Wechselkurse
   * mit Auswahl des Tages 
   * und Grafik für eine Währung
   * 
   * Datenbank: wechselkurse
   * Tabelle:   kurse (id*, datum, waehrung, kurs)
   *
   * April 2017
   */

include_once("./db.php");

$db = new DB("wechselkurse", "username","password");

$anzahl = 7;
if (isset($_GET["dauer"])) $anzahl = $_GET["dauer"]; 
$anzahl1 = $anzahl*4;  // pro Tag 4 Zeilen

$datum = "";
$filter = "";
if (isset($_GET["datum"])) {
	$datum  = $_GET["datum"]; 
	$filter = " WHERE datum='$datum'";
	$anzahl = 0;
}

$waehrung = "USD";
if (isset($_GET["waehrung"])) $waehrung = $_GET["waehrung"]; 
    
$server = "https://gussmann-berlin.de/wechselkurs";


$max = 0;
$min = 0;
$avg = 0;


function calculateMinMaxAvg($waehrung) {
    global $min;
    global $max;
    global $avg;
    global $db;
    $sql = "SELECT min(kurs) as MIN1, max(kurs) as MAX1, avg(kurs) as AVG1 from kurse WHERE waehrung='$waehrung' ORDER BY datum DESC";
    $result = $db->get_Query($sql);
    if ($result != null) {
        $zeile = $result[0];
        $min = $zeile["MIN1"];
        $max = $zeile["MAX1"];
        $avg = $zeile["AVG1"];
    }
}

function getMax() {
    global $max;
    return $max;
}

function getMin() {
    global $min;
    return $min;
}

function getAvg() {
    global $avg;
    return $avg;
}



?>


<!DOCTYPE html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="kurse.css">

<style> /* set the CSS */
</style>

</head>

<body>

<script src="https://d3js.org/d3.v3.min.js"></script> 
<script src="chart.js"></script>


<h2>Wechselkurse der europäischen Zentralbank</h2>

<div style="float:left; width:350px;">
<h3> Wechselkurse für <?php echo "$datum"; ?></h3>

  Datum: 
  <?php echo $db->getQueryAsHTMLDropDownBox("SELECT DISTINCT(datum) from kurse ORDER BY datum desc", "$server/show.php?datum=", "", "datum")   ?>
  
<br />
<br />

<?php 
  $sql = "SELECT datum, waehrung, kurs FROM $table $filter ORDER BY datum DESC LIMIT $anzahl1"; 
  echo $db->getQueryAsTEXTTable($sql,"%-12s %-5s %10f")  
?>

</div>



<div style="float:left;margin-left:20px; width:350px;">
<h3> Kursentwicklung für</h3>

Währung: 
  <?php 
    $sqlW = "SELECT DISTINCT(waehrung) from kurse ORDER BY waehrung";
    echo $db->getQueryAsHTMLDropDownBox($sqlW, "$server/show.php?waehrung=", "JPY", "waehrung");   
  ?>

<br />
<br />

<?php
    $sqlW = "SELECT * from kurse WHERE waehrung='$waehrung' ORDER BY datum DESC";
    echo $db->getQueryAsTEXTTable($sqlW,"%-5s %-12s %-5s %10f");  
?>
</div>

<br />




<!--*****************************************************
    * Beginn Ausgabe der Kursentwicklung als Diagramm
    *****************************************************  -->    
<div style="float:left;margin-left:20px; padding-top:80px;">
<div id="graphCurrency">
<script>
    <?php 
        $sqlW = "SELECT datum as zeit, kurs as value from kurse WHERE waehrung='$waehrung' ORDER BY datum DESC";
        $json_data = $db->getJSON_Data($sqlW);
        echo "data=".$json_data.";" 
    ?>
    showDiagram1Line("Währung","","Zeit", "", 600, 300, data, 0, "<?php echo $waehrung; ?><br>=1 Euro", "#graphCurrency");
</script>

<?php
    calculateMinMaxAvg($waehrung);
    $min1 = getMin();
    $max1 = getMax();
    $avg1 = getAvg();
    $median = ($max1+$min1)/2;
    $dmin = ($avg1-$min1)/$avg1*100;
    $dmax = ($max1-$avg1)/$avg1*100;
?>

<pre>
  min  ..... <?php echo sprintf("%01.4f",$min1)."<br>"  ?>
  max  ..... <?php echo sprintf("%01.4f",$max1)."<br>"  ?>
  avg  ..... <?php echo sprintf("%01.4f",$avg1)."<br>"  ?>
  median ... <?php echo sprintf("%01.4f",$median)."<br>" ?>
  dmin ..... <?php echo sprintf("%01.2f ",$dmin)."%" ?>  
  dmax ..... <?php echo sprintf("%01.2f ",$dmax)."%" ?>
</pre>
</div>

</div>
</div>

</body>
</html>
