# 地圖產生器的 hillshading 圖層產生方式

## 1. download Taiwan's 20M DEM 
Thanks to Rex. we have ready-to-use tiff. [1][2]

```
unzip dem_20m-wgs840-lzw.tiff.zip
ln -s dem_20m-wgs840-lzw.tiff raw.tiff
```
## 2. Wrap the image into better resolution (gdal > 1.7) 
note: use bilinear resampling  [5][7]

```
gdalwarp  -wt Float32 -ot Float32  -co BIGTIFF=YES -co TILED=YES -co COMPRESS=LZW -co PREDICTOR=2 -t_srs "+proj=merc +ellps=sphere +R=6378137 +a=6378137 +units=m" -r bilinear -tr 10 10 raw.tiff wraped.tif
```
## 3. create hillshade tiff
### 3.1 grayscale
ref  [6][9]
```
echo doing hillshade by gdaldem
gdaldem hillshade -z 2 wraped.tif hillshade.tif
echo make transparent background
gdaldem color-relief hillshade.tif -alpha shade.rmap hillshade-overlay.tif
```

cat shade.rmap
```
## shade.rmap contains
0 0 0 0 255
128 0 0 0 0
129 255 255 255 0
255 255 255 255 192
```
### 3.2 color relief
create a slope tif and color tif, ref [10]
```
echo create slope tiff
gdaldem slope wraped.tif slope.tif 
gdaldem color-relief slope.tif slope.rmap hillshade-slope.tif

cat slope.rmap
0 255 255 255
90 0 0 0

cat color.rmap
1 0 0 255
2 46 154 88
1000 251 255 128
2000 224 108 31
2500 200 55 55
3000 163 20 227
3200 215 15 242
3500 204 14 93
4000 215 244 244

gdaldem color-relief wraped.tif color.rmap color.tif
```

## 4. serve tif with mapnik 
ref: [9]
this is my xml for reference.

```xml
<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE Map[]>
<Map srs="+proj=merc +a=6378137 +b=6378137 +lat_ts=0.0 +lon_0=0.0 +x_0=0.0 +y_0=0.0 +k=1.0 +units=m +nadgrids=@null +wktext +no_defs +over" maximum-extent="-20037508.34,-20037508.34,20037508.34,20037508.34">

<Parameters>
  <Parameter name="bounds">119.52029999999999,21.2484,122.80520000000001,25.8296</Parameter>
  <Parameter name="center">121.0968,23.9586,9</Parameter>
  <Parameter name="format">png</Parameter>
  <Parameter name="minzoom">10</Parameter>
  <Parameter name="maxzoom">18</Parameter>
  <Parameter name="scale">1</Parameter>
  <Parameter name="metatile">1</Parameter>
  <Parameter name="id"><![CDATA[[hillshading]]></Parameter>
  <Parameter name="name"><![CDATA[tw20m_hillshading]]></Parameter>
  <Parameter name="description"><![CDATA[Taiwan 20M DEM tiff]]></Parameter>
</Parameters>
<Style name="raster">
        <Rule>
                <RasterSymbolizer>
                </RasterSymbolizer>
        </Rule>
</Style>

<Layer name="dem-taiwan" status="on">
        <StyleName>raster</StyleName>
        <Datasource>
                <Parameter name="type">gdal</Parameter>
                <Parameter name="file">hillshade-overlay.tif</Parameter>
                <Parameter name="format">tiff</Parameter>
        </Datasource>
</Layer>
</Map>
```

## 5. serve it 
use tilestache to serve mapnik layer, for example: [11]
```
                        "hillshading-slope":
                        {
                                "provider": {"name": "mapnik", "mapfile": "/var/www/etc/hillshading-slope.xml"},
                                "projection": "spherical mercator",
                                "extension": "png",
                                "cache lifespan": 86400,
                                "maximum cache age": 86400
                        },


                        "color":
                        {
                                "provider": {"name": "mapnik", "mapfile": "/var/www/etc/colorrelief.xml"},
                                "projection": "spherical mercator",
                                "extension": "png",
                                "cache lifespan": 86400,
                                "maximum cache age": 86400
                        },
                        "colorrelief":
                        {
                                  "provider": { "name": "Sandwich",
                                        "stack": [
                                        {"src": "color", "opacity": 0.7 },
                                        {"src": "hillshading-slope", "mode": "multiply", "opacity": 0.9}
                                        ]
                                },
                                "cache lifespan": 86400,
                                "maximum cache age": 86399

                        }


```
## 6. reference
 * 1: http://blog.nutsfactory.net/2016/09/14/taiwan-moi-20m-dtm/
 * 2: https://drive.google.com/drive/folders/0B7mj_CQDLqFCUzdRazk5TFRNWDg
 * 3: http://blog.mastermaps.com/2012/06/creating-color-relief-and-slope-shading.html
 * 4: http://blog.mastermaps.com/2012/07/terrain-mapping-with-mapnik.html
 * 5: http://wiki.openstreetmap.org/wiki/Mapnik/Hillshading_using_Mapnik,_GDAL_and_SRMT_data
 * 6: https://github.com/der-stefan/OpenTopoMap/blob/master/mapnik/HOWTO_DEM
 * 7: http://www.gdal.org/gdalwarp.html
 * 8: http://wiki.openstreetmap.org/wiki/Shaded_relief_maps_using_mapnik
 * 9: http://www.gdal.org/gdaldem.html
 * 10: http://blog.mastermaps.com/2012/06/creating-color-relief-and-slope-shading.html
 * 11: http://tilestache.org/doc/TileStache.Sandwich.html

