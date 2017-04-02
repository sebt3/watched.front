#/bin/sh

mkdir -p "public/vendor/css/skins" "public/vendor/js" "public/vendor/fonts"

BS="vendor/twbs/bootstrap/dist"
LTE="vendor/almasaeed2010/adminlte/dist"
FA="vendor/fortawesome/font-awesome"
II="vendor/driftyco/ionicons"
DB="vendor/sebt3/d3-bootstrap"

CSS="$BS/css/bootstrap.min.css $LTE/css/AdminLTE.min.css $FA/css/font-awesome.min.css $II/css/ionicons.min.css"
FONTS="$BS/fonts $FA/fonts $II/fonts"
JS="$DB/vendor/d3.v4.min.js $DB/dist/d3-bootstrap-all.min.js"

for c in $CSS;do
	cp "$c" "public/vendor/css/"
done
for f in $FONTS;do
	cp $f/* "public/vendor/fonts/"
done
for j in $JS;do
	cp "$j" "public/vendor/js/"
done
cp "$LTE/css/skins/skin-blue.css" "public/vendor/css/skins/"

