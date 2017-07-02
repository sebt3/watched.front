#/bin/sh

for lang in fr-FR;do

	file=public/langs/${lang}.json
	back="${file}.back"
	[ ! -e $file ] && >$file
	cp $file $back
	{
		if [ $(wc -l < $back) -gt 4 ];then
			cat $back|sed '/}/d'
			X=1
		else
			X=0
			echo '{'
		fi
		{
		grep -r '_(' templates |sed 's/.*{{ _('"'"'//;s/'"'"') }}.*//'|grep -v '}}'
		grep -r '$_(' classes|sed 's/.*\$\_(//;s/).*//;s/^'"'"'//;s/'"'"'$//;s/^"//;s/"$//;s/"/\\\\"/g'
		grep -r 'wd.lang.tr(' public/js|sed 's/.*wd.lang.tr(//;s/).*//;s/^'"'"'//;s/'"'"'$//;s/^"//;s/"$//'
		}|sort -u|while read line;do
			if ! grep -Fq "\"$line\":" $back;then
				[ $X -ne 0 ] && echo -n ","
				echo "	\"$line\": \"${line}\""
			fi
			X=1
		done
		echo '}'
	} > "$file"
done

