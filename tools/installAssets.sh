#/bin/sh

mkdir -p public/vendor/css/skins public/vendor/js public/vendor/fonts

#cp vendor/components/jquery/jquery.min.js public/vendor/js/

#cp vendor/twbs/bootstrap/dist/js/bootstrap.min.js public/vendor/js/
cp vendor/twbs/bootstrap/dist/fonts/* public/vendor/fonts/
cp vendor/twbs/bootstrap/dist/css/bootstrap.min.css public/vendor/css/

cp vendor/almasaeed2010/adminlte/dist/css/AdminLTE.min.css public/vendor/css/
cp vendor/almasaeed2010/adminlte/dist/css/skins/skin-blue.css public/vendor/css/skins/
#cp vendor/almasaeed2010/adminlte/dist/js/app.min.js public/vendor/js/adminlte.min.js

cp vendor/fortawesome/font-awesome/css/font-awesome.min.css public/vendor/css/
cp vendor/fortawesome/font-awesome/fonts/* public/vendor/fonts/

cp vendor/driftyco/ionicons/css/ionicons.min.css public/vendor/css/
cp vendor/driftyco/ionicons/fonts/* public/vendor/fonts/

cp vendor/sebt3/d3-bootstrap/vendor/d3.v4.min.js public/vendor/js/
cp vendor/sebt3/d3-bootstrap/dist/d3-bootstrap-all.min.js public/vendor/js/
