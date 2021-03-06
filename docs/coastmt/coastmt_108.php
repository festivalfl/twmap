<?php

$row = 1;
if (($handle = fopen("coastmt_108.csv", "r")) !== FALSE) {
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
        $num = 10; // count($data);
        //echo "<p> $num fields in line $row: <br /></p>\n";
        $row++;
        for ($c=0; $c < $num; $c++) {
            //echo $data[$c] . "<br />\n";
			$lines[$row][$c]=trim($data[$c]);
        }
    }
    fclose($handle);
}
?>
<head>
<title>海岸山脈 108 基石 暨 12 名峰(共 110 座)(KaKu)</title>
<style>

#newspaper-b
{
	font-family: "Lucida Sans Unicode", "Lucida Grande", Sans-Serif;
	font-size: 16px;
	margin: 45px;
	width: 90%;
	text-align: left;
	border-collapse: collapse;
	border: 1px solid #69c;
}
#newspaper-b th
{
	padding: 12px;
	font-weight: normal;
	color: #039;
}
#newspaper-b tbody
{
	background: #e8edff;
}
#newspaper-b td
{
	padding: 14px;
	color: #669;
	border-top: 1px dashed #fff;
}
#newspaper-b tbody tr:hover td
{
	color: #339;
	background: #d0dafd;
}
#newspaper-b tbody tr:hover th
{
	color: #339;
	background: lightgreen;

}
</style>
</head>
<h2>海岸山脈 108 基石暨 12 名峰</h2>
由 2011 年完成的郭水明(Kaku)先生提供，蚯蚓整理(2016.11)。
12 峰源自莊錦豐(小花)兄依照地表突起度(prominence)選擇的的台灣中級山/郊山獨立峰列表。
<table id=newspaper-b>

<?php
foreach($lines as $row){
	echo "<tr>";
	if (strstr($row[9], '12峰'))
		$td = '<th>'; 
	else
		$td = '<td>';
	 for ($c=0; $c < count($row); $c++) {

			echo $td. $row[$c];
        }
	
	echo "</tr>";
}
?>
</table>
