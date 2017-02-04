#/bin/sh

for lang in fr-FR;do

	file=langs/${lang}.json
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
		grep '_(' templates/*/*|sed 's/.*{{ _('"'"'//;s/'"'"') }}.*//'|grep -v '}}'|sort -u|while read line;do
			[ $X -ne 0 ] && echo -n ","
			if ! grep -q "\"$line\":" $back;then
				echo "	\"$line\": \"${line}\""
			fi
			X=1
		done
		echo '}'
	} > "$file"
done

