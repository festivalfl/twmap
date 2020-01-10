<?php

require_once("proj_lib.php");
require_once("kml_lib.php");
setlocale(LC_ALL,"zh_TW.UTF-8");

// $Id: garmin.inc.php 361 2013-10-18 15:03:00Z happyman $
/*
$gkmz = new garminKMZ(3.25,3.25,"/home/map/out/000003/307000x2677000-12x6.tag.png");
echo "doit";
$gkmz->doit();
echo "done";
$gkmz->makekml();
 */

class garminKMZ {
	var $cutx; // km: 
	var $cuty; // km
	var $startx; // 298000
	var $starty; // 2672000
	var $shiftx; // 20
	var $shifty; // 20
	var $fname;
	var $kml;
	var $fromGPS; // content convert from gpx
	var $ph; // 澎湖
	var $debug = 0;
	function __construct($cutx,$cuty,$fname,$ph=0) {
		if (preg_match("/(\d+)x(\d+)-(\d+)x(\d+)/",basename($fname),$r)){
			if (file_exists($fname)) {
				$this->cutx = $cutx;
				$this->cuty = $cuty;
				$this->startx = $r[1];
				$this->starty = $r[2];
				$this->shiftx = $r[3];
				$this->shifty = $r[4];
				$this->fname = $fname;
				$this->ph = $ph;
				//echo "ok";
				return true;
			}
		}
		return false;
	}
	function setDebug($flag){
		$this->debug = $flag;
	}
	/**
	 * get_image_bounds: 使用於 show.php
	 *  取得圖形的 bounds 
	 * @access public
	 * @return void
	 */
	function get_image_bounds() {
		$WN = array($this->startx, $this->starty);
		$ES = array($this->startx+ $this->shiftx*1000, $this->starty- $this->shifty*1000);
		$EN = array($this->startx+ $this->shiftx*1000, $this->starty);
		$WS = array($this->startx, $this->starty- $this->shifty*1000);

		$r = $this->transcoord($WN,$ES, $EN, $WS);
		return $r;
	}
	/**
	 * makeOrux 
	 * 產生 oruxmap 格式的附檔 => buggy
	 * @param mixed $title 
	 * @access public
	 * @return void
	 */
	function makeOrux($title,$reload_flag=0) {
		$dir=dirname($this->fname);
		$zipname = str_replace(".png",".oruxmap.zip",$this->fname);
		if (file_exists($zipname))
			unlink($zipname);

		$zipname = str_replace(".png",".oruxmap.7z",$this->fname);
		if ($reload_flag == 0 && file_exists($zipname))
			return ture;
		mkdir("$dir/oruxmap");
		$r = $this->get_image_bounds();
		list ($r['pix_x'],$r['pix_y']) = getimagesize($this->fname);
		$oruxparam = sprintf("-i=%s -d=\"WGS 1984Global Definition\"  -p=\"LATITUDE/LONGITUDE\" -r=\"geo,0,0,%f,%f,N;geo,%d,%d,%f,%f,N,geo,%d,0,%f,%f,N,geo,0,%d,%f,%f,N\" -sqlite -z=50,25,12.5,6.5  -n=\"%s\" -o=\"%s\"",$this->fname,$r['W'],$r['N'],$r['pix_x'],$r['pix_y'],$r['E'],$r['S'], $r['pix_x'], $r['E1'],$r['N1'], $r['pix_y'],$r['W1'],$r['S1'], $title,"$dir/oruxmap");
		$oruxparam = sprintf("-i=%s -r=\"geo,0,0,%f,%f,N;geo,%d,%d,%f,%f,N,geo,%d,0,%f,%f,N,geo,0,%d,%f,%f,N\" -sqlite -z=50,25,12.5,6.5  -n=\"%s\" -o=\"%s\"",$this->fname,$r['W'],$r['N'],$r['pix_x'],$r['pix_y'],$r['E'],$r['S'], $r['pix_x'], $r['E1'],$r['N1'], $r['pix_y'],$r['W1'],$r['S1'], $title,"$dir/oruxmap");

		/* wwwrun 不一定要有 shell, 但一定要有 java home
		 * #!/bin/sh
		 * export LC_ALL="zh_TW.UTF-8"
		 * export JAVA_BINDIR=/usr/lib64/jvm/jre/bin
		 * export JAVA_HOME=/usr/lib64/jvm/jre
		 * export JAVA_ROOT=/usr/lib64/jvm/jre
		 * java -Xmx1024m -jar OruxMapsDesktop.jar "$@"
		 */
		$cmd = sprintf("cd /opt/OruxMapsDesktop;./OruxMapsDesktop.sh %s",$oruxparam);
		// echo $cmd;
		exec($cmd, $output, $ret);
		if ($this->debug)
			error_log("run $cmd\n".print_r($output,true)."\nret=".$ret);
		if ($ret != 0 ) {
			error_log("fail: $cmd");
			return false;
		}

		$output=array();
		$cmd = sprintf("cd $dir/oruxmap; export LC_ALL=zh_TW.UTF-8; 7z a %s $title/* ;rm -r $dir/oruxmap", $zipname);
		exec($cmd, $output,$ret);
		if ($this->debug)
			error_log("run $cmd\n".print_r($output,true)."\nret=".$ret);
		//echo "<head><meta charset=\"utf-8\" />";
		//echo $cmd;
		//print_r($output);
		if ($ret == 0 ) {
			return true;
		}
		error_log("fail $cmd");
	}
	function makekml() {
		$cutx = $this->cutx;
		$cuty = $this->cuty;
		$startx = $this->startx;
		$starty = $this->starty;
		$shiftx = $this->shiftx;
		$shifty = $this->shifty;

		//	echo "y=0 to ". ceil($shifty/$cuty) . "\n";
		//	echo "x=0 to ". ceil($shiftx/$cutx) . "\n";
		$index =0;
		for($y=0; $y < ceil($shifty/$cuty)*$cuty; $y+=$cuty) {
			for($x=0;$x < ceil($shiftx/$cutx)*$cutx; $x+=$cutx) {
				$left = $startx + $x * 1000;
				if ($x+$cutx > $shiftx) $right = $startx + $shiftx*1000;
				else
					$right = $left + $cutx * 1000;
				$top = $starty - $y * 1000;
				if ($y+$cuty > $shifty) $buttom = $starty - $shifty*1000;
				else $buttom = $top - $cuty * 1000;

				$img[$index] = array("left"=>$left, 
					"right"=>$right,
					"top" => $top, "buttom"=> $buttom,
					"x" => $x, "y"=> $y);
				$WN = array( $left, $top );
				$ES = array( $right, $buttom );
				//$img[$index]['name'] = sprintf("%dx%d-%dx%d.tag_%02d",$startx,$starty,$shiftx,$shifty,$index);
				// 取出 prefix
				$img[$index]['name'] = sprintf("%s.tag_%02d",str_replace(".tag.png", "", basename($this->fname)), $index);
				$img[$index]['href'] = "files/".$img[$index]['name'].".jpg";
				$img[$index]['bounds'] = $this->transcoord($WN,$ES,array( $right, $top), array($left, $buttom));
				$index++;
			}
		}
		$this->kml = $this->kml_head();
		foreach($img as $p) {
			$this->kml .= $this->kml_folder($p);
		}
		// 加上 gps 來的資料
		if (!empty($this->fromGPS)) 
			$this->kml .= $this->fromGPS . "\n";

		$this->kml .= $this->kml_foot();
		// echo $this->kml;
		if ($this->debug)
			error_log($this->kml);

	}
	function doit() {
		if ($this->debug)
			error_log("makekml\n");
		$this->makekml();
		if ($this->debug)
			error_log("im_cropimage\n");
		$this->im_cropimage();
		if ($this->debug)
			error_log("makekmz\n");
		$this->makekmz();
		if ($this->debug)
			error_log("doit finished\n");
	}
	/**
	 * transcoord 
	 * 四座標
	 * @param mixed $p1 
	 * @param mixed $p2 
	 * @param mixed $p3 
	 * @param mixed $p4 
	 * @access public
	 * @return void
	 */
	function transcoord($p1,$p2,$p3,$p4) {
		$r = array();
		if ($this->ph == 1 ) {
			list ($r['W'],$r['N']) = ph_proj_67toge2($p1);
			list ($r['E'],$r['S']) = ph_proj_67toge2($p2);
			list ($r['E1'],$r['N1']) = ph_proj_67toge2($p3);
			list ($r['W1'],$r['S1']) = ph_proj_67toge2($p4);
		} else {
			list ($r['W'],$r['N']) = proj_67toge2($p1);
			list ($r['E'],$r['S']) = proj_67toge2($p2);
			list ($r['E1'],$r['N1']) = proj_67toge2($p3);
			list ($r['W1'],$r['S1']) = proj_67toge2($p4);
		}
		return $r;
	}
	function im_cropimage() {
		$fname = $this->fname;
		if (!file_exists($fname)) return false;
		list($w, $h) = getimagesize($this->fname);
		$crop = sprintf("%dx%d",ceil($this->cutx * $w / $this->shiftx) ,ceil($this->cuty * $h / $this->shifty));
		$dir = dirname($fname);
		$newname = str_replace(".tag.png", ".tag_%02d.jpg", trim($fname));
		$cmd = sprintf("cd %s; mkdir -p files; convert -crop %s +repage %s 'files/%s';",$dir,$crop,trim(basename($fname)),basename($newname));
		exec($cmd,$output,$ret);
		if ($this->debug) {
			error_log("run $cmd\nret=".$ret);
		}
		return $ret;
	}
	function makekmz() {
		$dir = dirname($this->fname);
		$zipname = str_replace("png","kmz",$this->fname);
		file_put_contents("$dir/doc.kml",$this->kml);
		$output=array();
		/*
		if ($this->debug) {
			$cmd="ls -lRa $dir";
			exec($cmd,$output,$ret);
			error_log("run $cmd\n".print_r($output,true)."\nret=".$ret);
		}
		*/
		$output=array();
		$cmd = sprintf("cd %s; zip %s doc.kml files/*.jpg; rm -r files doc.kml " ,$dir,basename($zipname));
		exec($cmd, $output, $ret);
		if ($this->debug)
			error_log("run $cmd\n".print_r($output,true)."\nret=".$ret);
		//error_log($cmd);
	}
	function kml_head() {
		$str = '<?xml version="1.0" encoding="utf-8"?>
			<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
			<Document>';
		$str .= sprintf("\n<name>地圖產生器 %dx%d-%dx%d</name>",
			$this->startx,$this->starty,$this->shiftx,$this->shifty);
		return $str;

	}
	function kml_foot() {
		return "</Document>\n</kml>\n";
	}
	function kml_folder($p) {
		return sprintf(
			'<Folder>
			<name>%s</name>
			<GroundOverlay>
			<color>80ffffff</color>
			<Icon>
			<href>%s</href>
			</Icon>
			<LatLonBox>
			<north>%f</north>
			<south>%f</south>
			<east>%f</east>
			<west>%f</west>
			</LatLonBox>
			</GroundOverlay>
			</Folder>', $p['name'],$p['href'],
			$p['bounds']['N'],
			$p['bounds']['S'],
			$p['bounds']['E'],
			$p['bounds']['W']);

	}
	/**
	 * addgps 
	 * 利用 gpsbabel 的轉換,把內文加入 kml 中
	 * @param mixed $type 
	 * @param mixed $file 
	 * @access public
	 * @return void
	 */
	function addgps($type, $file) {
		$cmd = sprintf("gpsbabel -i %s -f '%s' -o kml,points=0,line_width=2 -F -",$type, $file);
		exec($cmd, $out, $ret);
		if ($this->debug)
			error_log("run $cmd\n".print_r($output,true)."\nret=".$ret);
		// skip head
		// <?xml version="1.0" encoding="UTF-8"
		// <kml xmlns="http://www.opengis.net/kml/2.2"
		//         xmlns:gx="http://www.google.com/kml/ext/2.2">
		//           <Document>
		//               <name>GPS device</name>
		//                   <Snippet>Created Thu Dec 15 00:27:28 2011</Snippet>
		//                   <!-- Normal track style -->
		//
		$start = 0;
		for($i=0;$i<count($out);$i++){
			if ($start == 0 && stristr($out[$i],"<Snippet")) {
				$start = 1; continue;
			}
			if ($start == 1 &&  stristr($out[$i],"</Document")) {
				break;
			}
			if ($start == 1) 
				$this->fromGPS .= $out[$i];

		}
	}
}
