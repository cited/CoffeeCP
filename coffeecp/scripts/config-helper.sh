#!/bin/bash

FLAG_RELOAD=0
for user in $(ls /home); do
	if [ -f "/home/${user}/reload.txt" ]; then
		USER=$(cut -f1 -d' ' /home/${user}/reload.txt)
		DOMAIN=$(cut -f2 -d' ' /home/${user}/reload.txt)
		php /usr/coffeecp/paths/index.php ${USER} ${DOMAIN}

		FLAG_RELOAD=1
		rm -f /home/${user}/reload.txt
	fi

	if [ -f "/home/${user}/mapping.txt" ]; then
		USER=$(cut -f1 -d' ' /home/${user}/mapping.txt)
		DOMAIN=$(cut -f2 -d' ' /home/${user}/mapping.txt)
		TYPE=$(cut -f3 -d' ' /home/${user}/mapping.txt)
		php /usr/coffeecp/paths/mapping.php ${USER} ${DOMAIN} ${TYPE}

		FLAG_RELOAD=1
		rm -f /home/${user}/mapping.txt
	fi
done

if [ ${FLAG_RELOAD} = 1 ]; then
	/usr/sbin/httpd -k graceful
fi
