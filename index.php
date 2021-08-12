<html !doctype>
<head>
  <title>NPZ Locator</title>
  <link rel="stylesheet" href="https://unpkg.com/mvp.css">
</head>
<body>
  <header>
  <h1>Nashville Promise Zone Address Locator</h1>
</header>
<main>
  <section>
   <form action="index.php" method="post">
      <h3>Street Address Lookup</h3>
      <input type="text" name="street" placeholder="Street Address">
      <input type="text" style="display:inline" name="city" value="Nashville" size="9" readonly>
      <input type="text" style="display:inline" name="state" value="TN" size="2" readonly>
      <input style="cursor:pointer;" type="submit" name="street_submit" value="Submit" />
   </form>

  <form id="bulk_form" style="max-width:50%;"action="index.php" method="post" enctype="multipart/form-data">
    <h3>Bulk Address Lookup</h3>
    Must be file-type .csv, .txt, .dat, .xls, or .xlsx with the following format:</br>
    <code>Unique ID, Street Address, City, State, Zip</code>

    <input type="file" id="file" name="bulk_file"/>
    <input style="display:inline; cursor:pointer;" id="bulk_submit" type="submit" name="bulk_submit" value="Submit"/>
    <p style="font-size:10pt;">Returns a .csv spreadsheet, the rightmost columns <code>npz</code> and <code>subzone</code> indicate if the address is in the promise zone and if so, which subzone it is in.</p>
  </form>
  <script>
  document.getElementById("bulk_form").addEventListener('submit', function( evt ) {
      var file = document.getElementById('file').files[0];

      if(file && file.size < 10485760) { // 10 MB (this size is in bytes)
          //Submit form
      } else {
          //Prevent default and display error
          evt.preventDefault();
          document.getElementById("php_text").innerHTML="Document is too large";
      }
  }, false);
  </script>

</section>

<section id="landing_display">
  <aside style="width:75%;">
  <img src="tract_images/AllSubzones.png"></img>
  </aside>
</section>

<section id="php_text">
<?php
// Census tracts in each subzone in the Nashville Promise Zone
$npz=["z1"=>
["019300",
"012600",
"011800",
"011900",
"019200"],
"z2"=>
["016400",
"016300",
"016200",
"016100"],
"z3"=>
["016000",
"014800",
"015900",
"015802",
"015803",
"015804",
"019600"],
"z4"=>
["017200",
"017300",
"017401",
"017402",
"017500",
"980200",
"018901",
"019006",
"019005"],
"z5"=>
["013500",
"013601",
"013602",
"013700",
"013800",
"013900",
"014200",
"014300",
"014400",
"019400",
"012702",
"012701"],
"z6"=>
["018101",
"013202",
"013300"]
];

$all_tracts = array_merge(...array_values($npz));

function getSingleTract($street, $city, $state, $tract_list, $subzones)
{
  echo "<script>document.getElementById('landing_display').style.display='none'</script>";
  if (gettype($street)!="string"||$street=="")
  {
    echo "Please enter a street address";
  }
  else
  {
    $doc=fopen("https://geocoding.geo.census.gov/geocoder/geographies/address?street=".urlencode($street).
    "&city=".urlencode($city).
    "&state=".urlencode($state).
    "&benchmark=Public_AR_Current&vintage=Census2010_Current&format=json&layers=Census%20Tracts", "r");
    $contents=stream_get_contents($doc);
    $j = json_decode($contents, true);
    fclose($doc);
    $match=$j['result']['addressMatches'];

    if (count($match)==0)
    {
      echo "Could not find address: ".$street.", ".$city." ".$state."</br>";
    }
    else
    {
      $tract=$match[0]['geographies']['Census Tracts'][0]['TRACT'];
      if (in_array($tract, $tract_list))
      {
        echo "<section><aside style='text-align:center;width:50%'><b>".$street."</b> is in the Nashville Promise Zone!</br>";
        for ($i=0;$i<=5;$i++)
        {
          if (in_array($tract, array_values($subzones)[$i]))
          {
            $sub=$i+1;
          }
        }
        echo "Subzone: ".$sub."</br>";
        echo "Census Tract (2010): ".floatval(substr($tract, 0,4).".".substr($tract, 4));
        echo "<br><img src='tract_images/Subzone".$sub."_Tract".$tract.".png'</img></aside>";
      }
      else
      {
        echo "<p><b>".$street."</b> is not in the Nashville Promise Zone</p>";
      }
    }
  }
}

function getBulkTract($bulkFile, $tract_list, $subzones)
{
  echo "<script>document.getElementById('landing_display').style.display='none'</script>";
  if ($bulkFile['name']=='')
  {
    echo "Please select a file to upload";
  }
  else
  {
      echo "<span id='loading'>Creating link for download<span id='dots'>...</span> (larger files may take a few minutes)</span>";
      echo "<script>var el = document.getElementById('dots'),
        i = 0,
        load = setInterval(function() {
          i = ++i % 4;
          el.innerHTML = Array(i + 1).join('.');}, 600);</script>";

      $url="https://geocoding.geo.census.gov/geocoder/geographies/addressbatch";
      $ch=curl_init();
      $data=["vintage"=>"Census2010_Current", "benchmark"=>"Public_AR_Current", "layers"=>"Census%20Tracts"];

      $f=new CurlFile($bulkFile['tmp_name'], mime_content_type($bulkFile['tmp_name']), $bulkFile['name']);

      $data['addressFile']=$f;

      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

      $result=curl_exec($ch);
      curl_close($ch);

      if (substr($result, 0, 3)=="<p>")
      {
        echo "<form>".$result."</form>";
        echo "<script>document.getElementById('loading').style.display='none';clearInterval(load);</script>";
      }
      else
      {
        $process=fopen("processing.csv", "w");
        fwrite($process, $result);
        fclose($process);

        $rows=array_map('str_getcsv', file('processing.csv'));

        foreach ($rows as &$cells)
        {
          if ($cells[2]=="Match"&&$cells[8]=="47"&&$cells[9]=="037"&&in_array($cells[10], $tract_list))
          {
            $cells[12]="true";
            for ($i=0;$i<=5;$i++)
            {
              if (in_array($cells[10], array_values($subzones)[$i]))
              {
                $cells[13]=$i+1;
              }
            }
          }
        }
        unset($cells);

        array_unshift($rows, ['id', 'address', 'match', 'exact', 'match_address', 'coords', 'line', 'side', 'state', 'county', 'tract', 'block', 'npz', 'subzone']);
        $out_csv=fopen("npzexport.csv", 'w');

        foreach ($rows as $r)
        {
          fputcsv($out_csv, $r);
          fwrite($out_csv, "\n");
        }
        fclose($out_csv);
        echo "<a id='download' href='npzexport.csv' style='display:block;color:#000;padding-bottom:20px;'>Download Here!</a>";
        echo "<script>document.getElementById('loading').style.display='none';clearInterval(load);</script>";
        echo "<table><br>";
        $f = fopen("npzexport.csv", "r");
        while (($line = fgetcsv($f)) !== false)
        {
          echo "<tr>";
          foreach ($line as $cell)
          {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
          }
          echo "</tr>\n";
        }
      fclose($f);
      echo "\n</table>";
      }
  }
}


if($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['street_submit']))
{
  getSingleTract($_POST['street'],$_POST['city'],$_POST['state'],$all_tracts,$npz);
}
elseif($_SERVER['REQUEST_METHOD'] == "POST" and isset($_POST['bulk_submit']))
{
  if($_FILES['file']['size'] > 10485760)
  {
    echo "File is too large";
  }
  else
  {
    getBulkTract($_FILES['bulk_file'], $all_tracts, $npz);
  }
}

 ?>
</section>
</main>
</body>
</html>
