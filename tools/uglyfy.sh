#/bin/sh

D=public/js
FILES="$D/watched.js $D/watched.bar.js $D/watched.donut.js $D/watched.tables.js $D/watched.gfx.js $D/watched.widget.js"

mkdir -p $D/dist
uglifyjs $FILES > $D/dist/watched.min.js
